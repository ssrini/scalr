<?php
	class Scalr_Role_Behavior_RabbitMQ extends Scalr_Role_Behavior implements Scalr_Role_iBehavior
	{
		/** DBFarmRole settings **/
		const ROLE_DATA_STORAGE_ENGINE 	= 'rabbitmq.data_storage.engine';
		const ROLE_COOKIE_NAME  		= 'rabbitmq.cookieName';
		const ROLE_NODES_RATIO 			= 'rabbitmq.nodes_ratio';
		const ROLE_PASSWORD				= 'rabbitmq.password';
		
		const ROLE_CP_SERVER_ID  		= 'rabbitmq.cp.server_id';
		const ROLE_CP_REQUESTED  		= 'rabbitmq.cp.isrequested';
		const ROLE_CP_REQUEST_TIME  	= 'rabbitmq.cp.request_time';
		const ROLE_CP_ERROR_MSG  		= 'rabbitmq.cp.error_msg';
		const ROLE_CP_URL  				= 'rabbitmq.cp.url';
		
		// For EBS storage
		const ROLE_DATA_STORAGE_EBS_SIZE = 'rabbitmq.data_storage.ebs.size';
		
		/** DBServer settings **/
		const SERVER_NODE_TYPE = 'rabbitmq.node_type';
		
		const NODE_TYPE_RAM = 'ram';
		const NODE_TYPE_DISK = 'disk';
		
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function onFarmSave(DBFarm $dbFarm, DBFarmRole $dbFarmRole)
		{
			if (!$dbFarmRole->GetSetting(self::ROLE_COOKIE_NAME))
			{
				$cookie = substr(sha1(microtime(true).rand(0,100000)), 0, 20);
				$dbFarmRole->SetSetting(self::ROLE_COOKIE_NAME, $cookie);
			}
		}
		
		public function getSecurityRules()
		{
			return array(
				"tcp:5672:5672:0.0.0.0/0",
				"tcp:55672:55672:0.0.0.0/0"
			);
		}
		
		public function listDnsRecords(DBServer $dbServer)
		{
			$records = array();
			
			array_push($records, array(
				"name" 		=> "int-rabbitmq",
				"value"		=> $dbServer->localIp,
				"type"		=> "A",
				"ttl"		=> 90,
				"server_id"	=> $dbServer->serverId,
				"issystem"	=> '1'
			));
			
			array_push($records, array(
				"name" 		=> "ext-rabbitmq",
				"value"		=> $dbServer->remoteIp,
				"type"		=> "A",
				"ttl"		=> 90,
				"server_id"	=> $dbServer->serverId,
				"issystem"	=> '1'
			));
			
			$nodeType = $dbServer->GetProperty(self::SERVER_NODE_TYPE);
			
			array_push($records, array(
				"name" 		=> "int-rabbitmq-{$nodeType}",
				"value"		=> $dbServer->localIp,
				"type"		=> "A",
				"ttl"		=> 90,
				"server_id"	=> $dbServer->serverId,
				"issystem"	=> '1'
			));
			
			array_push($records, array(
				"name" 		=> "ext-rabbitmq-{$nodeType}",
				"value"		=> $dbServer->remoteIp,
				"type"		=> "A",
				"ttl"		=> 90,
				"server_id"	=> $dbServer->serverId,
				"issystem"	=> '1'
			));
		}
		
		public function handleMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			if (!$message->rabbitmq)
				return;
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostUp":
					
					if ($message->rabbitmq->volumeConfig)
						$this->setVolumeConfig($message->rabbitmq->volumeConfig, $dbServer->GetFarmRoleObject(), $dbServer);
					else
						throw new Exception("Received hostUp message from RabbitMQ server without volumeConfig");
					
					$dbServer->GetFarmRoleObject()->SetSetting(self::ROLE_PASSWORD, $message->rabbitmq->password);
						
					break;
			}
		}
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			$message = parent::extendMessage($message);
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostInitResponse":
					
					$message->rabbitmq = new stdClass();
					$message->rabbitmq->cookie = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_COOKIE_NAME);
					$message->rabbitmq->volumeConfig = $this->getVolumeConfig($dbServer->GetFarmRoleObject(), $dbServer);
					$message->rabbitmq->nodeType = $this->getNodeType($dbServer->GetFarmRoleObject(), $dbServer);
					$message->rabbitmq->password = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_PASSWORD);
					
					$dbServer->SetProperty(self::SERVER_NODE_TYPE, $message->rabbitmq->nodeType);
					
					break;
			}
			
			return $message;
		}
		
		private function getNodeType(DBFarmRole $dbFarmRole, DBServer $dbServer)
		{
			if ($dbServer->index == 1)
				return self::NODE_TYPE_DISK;
			
			$ramServers = 0;
			$diskServers = 0;
			foreach ($dbFarmRole->GetServersByFilter(array('status' => array(SERVER_STATUS::RUNNING, SERVER_STATUS::INIT))) as $server) {
				if ($server->GetProperty(self::SERVER_NODE_TYPE) == self::NODE_TYPE_DISK)
					$diskServers++;
				elseif($server->GetProperty(self::SERVER_NODE_TYPE) == self::NODE_TYPE_RAM)
					$ramServers++;
					
			}
			
			$totalServers = ($ramServers+$diskServers);
			
			$currentRatio = ($totalServers != 0) ? $diskServers / $totalServers * 100 : 0;
			$sRatio = (int)trim($dbFarmRole->GetSetting(self::ROLE_NODES_RATIO), "%");
			
			
			//$this->logger->error(new FarmLogMessage($dbFarmRole->FarmID, "Total: {$totalServers}, Disk: {$diskServers}, Ram: {$ramServers}, Ratio: {$currentRatio}, sRatio: {$sRatio}"));
			
			if ($currentRatio <= $sRatio)
				return self::NODE_TYPE_DISK;
			else
				return self::NODE_TYPE_RAM;
		}
		
		public function setVolumeConfig($volumeConfig, DBFarmRole $dbFarmRole, DBServer $dbServer)
		{
			try {
				$volume = Scalr_Storage_Volume::init()->loadByFarmRoleServer(
					$dbFarmRole->ID,
					$dbServer->index,
					$this->behavior
				);
				
				if ($volume->id != $volumeConfig->id)
					$volume->delete();
					
			} catch (Exception $e) {}
			
			try {
				$storageVolume = Scalr_Storage_Volume::init();
				try {
					$storageVolume->loadById($volumeConfig->id);
					$storageVolume->setConfig($volumeConfig);
					$storageVolume->save();
				} catch (Exception $e) {
					if (strpos($e->getMessage(), 'not found')) {
						$storageVolume->loadBy(array(
							'id'			=> $volumeConfig->id,
							'client_id'		=> $dbFarmRole->GetFarmObject()->ClientID,
							'env_id'		=> $dbFarmRole->GetFarmObject()->EnvID,
							'name'			=> sprintf("'%s' data volume", $this->behavior),
							'type'			=> $dbFarmRole->GetSetting(static::ROLE_DATA_STORAGE_ENGINE),
							'platform'		=> $dbFarmRole->Platform,
							'size'			=> $volumeConfig->size,
							'fstype'		=> $volumeConfig->fstype,
							'farm_roleid'   => $dbFarmRole->ID,
							'server_index'  => $dbServer->index,
							'purpose'		=> $this->behavior
						));
						$storageVolume->setConfig($volumeConfig);
						$storageVolume->save(true);
					} 
					else
						throw $e;
				}
			}
			catch(Exception $e) {
				$this->logger->error(new FarmLogMessage($dbFarmRole->FarmID, "Cannot save storage volume: {$e->getMessage()}"));
			}
		}
		
		public function getVolumeConfig(DBFarmRole $dbFarmRole, DBServer $dbServer)
		{
			try {
				$volume = Scalr_Storage_Volume::init()->loadByFarmRoleServer(
					$dbFarmRole->ID,
					$dbServer->index,
					$this->behavior
				);
							
				$volumeConfig = $volume->getConfig();
			} catch (Exception $e) {}
	
			if (!$volumeConfig)
			{
				$volumeConfig = new stdClass();
				$volumeConfig->type = $dbFarmRole->GetSetting(static::ROLE_DATA_STORAGE_ENGINE);
	
				if (in_array($dbFarmRole->GetSetting(static::ROLE_DATA_STORAGE_ENGINE), array(MYSQL_STORAGE_ENGINE::EBS, MYSQL_STORAGE_ENGINE::CSVOL))) {
					$volumeConfig->size = $dbFarmRole->GetSetting(static::ROLE_DATA_STORAGE_EBS_SIZE);
				}
				// For RackSpace
				//TODO:
				elseif ($dbFarmRole->GetSetting(static::ROLE_DATA_STORAGE_ENGINE) == MYSQL_STORAGE_ENGINE::EPH) {
					$volumeConfig->snap_backend = "cf://{$this->behavior}-data-bundle/scalr-{$dbFarmRole->GetFarmObject()->Hash}";
					$volumeConfig->vg = $this->behavior;
					$volumeConfig->disk = new stdClass();
					$volumeConfig->disk->type = 'loop';
					$volumeConfig->disk->size = '75%root';
				}
			}
			
			return $volumeConfig;
		}
	}