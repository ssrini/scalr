<?php 
	class Scalr_Role_Behavior
	{		
		const ROLE_DM_APPLICATION_ID = 'dm.application_id';
		const ROLE_DM_REMOTE_PATH = 'dm.remote_path';
		
		
		protected $behavior;
		
		/**
		 * @return Scalr_Role_Behavior
		 * @param unknown_type $name
		 */
		static public function loadByName($name)
		{
			switch ($name)
			{
				case ROLE_BEHAVIORS::CF_CLOUD_CONTROLLER:
					$obj = 'Scalr_Role_Behavior_CfCloudController';
					break;
					
				case ROLE_BEHAVIORS::RABBITMQ:
					$obj = 'Scalr_Role_Behavior_RabbitMQ';
					break;

				case ROLE_BEHAVIORS::CHEF:
					$obj = 'Scalr_Role_Behavior_Chef';
					break;
					
				case ROLE_BEHAVIORS::MONGODB:
					$obj = 'Scalr_Role_Behavior_MongoDB';
					break;

				case ROLE_BEHAVIORS::MYSQLPROXY:
					$obj = 'Scalr_Role_Behavior_MysqlProxy';
					break;
					
				case ROLE_BEHAVIORS::APACHE:
					$obj = 'Scalr_Role_Behavior_Apache';
					break;
					
				case ROLE_BEHAVIORS::NGINX:
					$obj = 'Scalr_Role_Behavior_Nginx';
					break;
					
					
				case ROLE_BEHAVIORS::POSTGRESQL:
					$obj = 'Scalr_Role_Behavior_Postgresql';
					break;
					
				case ROLE_BEHAVIORS::REDIS:
					$obj = 'Scalr_Role_Behavior_Redis';
					break;

				case ROLE_BEHAVIORS::HAPROXY:
					$obj = 'Scalr_Role_Behavior_HAProxy';
					break;
					
					
				default:
					$obj = 'Scalr_Role_Behavior';
					break;
			}
			
			return new $obj($name);
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param DBRole $dbRole
		 * @return Scalr_Role_Behavior
		 */
		static public function getListForRole(DBRole $role)
		{
			$list = array();
			foreach ($role->getBehaviors() as $behavior)
				$list[] = self::loadByName($behavior);
			
			return $list;
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param DBFarmRole $dbFarmRole
		 * @return Scalr_Role_Behavior
		 */
		static public function getListForFarmRole(DBFarmRole $farmRole)
		{			
			return self::getListForRole($farmRole->GetRoleObject());
		}
		
		public function __construct($behavior = ROLE_BEHAVIORS::BASE)
		{
			$this->behavior = $behavior;
			$this->logger = Logger::getLogger(__CLASS__);
			$this->db = Core::GetDBInstance();
		}
		
		/**
		 * Handle message from scalarizr
		 * 
		 * @param Scalr_Messaging_Msg $message
		 * @param DBServer $dbServer
		 */
		public function handleMessage(Scalr_Messaging_Msg $message, DBServer $dbServer) { }
		
		public function makeUpscaleDecision(DBFarmRole $dbFarmRole) 
		{
			return false;
		}
		
		public function getSecurityRules()
		{
			return array();
		}
		
		/**
		 * @return Scalr_Util_CryptoTool
		 */
		protected function getCrypto()
		{
			if (! $this->crypto) {
				$this->crypto = new Scalr_Util_CryptoTool(MCRYPT_TRIPLEDES, MCRYPT_MODE_CFB, 24, 8);
				$this->cryptoKey = @file_get_contents(dirname(__FILE__)."/../../../etc/.cryptokey");
			}

			return $this->crypto;
		}
		
		public function getDnsRecords(DBServer $dbServer)
		{
			return array();
		}
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostInitResponse":
					
					try {
						if ($dbServer->farmRoleId) {
							$appId = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_DM_APPLICATION_ID);
							if (!$message->deploy && $appId) {
					
								$application = Scalr_Dm_Application::init()->loadById($appId);
								$deploymentTask = Scalr_Dm_DeploymentTask::init();
								$deploymentTask->create(
									$dbServer->farmRoleId,
									$appId,
									$dbServer->serverId,
									Scalr_Dm_DeploymentTask::TYPE_AUTO,
									$dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_DM_REMOTE_PATH),
									$dbServer->envId,
									Scalr_Dm_DeploymentTask::STATUS_DEPLOYING
								);
								
								$msg = $deploymentTask->getDeployMessage();
								
								$message->deploy = $msg;
							}
						}
					} catch (Exception $e) {
						$this->logger->error(new FarmLogMessage($dbServer->farmId, "Cannot init deployment: {$e->getMessage()}"));
					}
					
					break;
			}
			
			return $message;
		}
		
		public function onFarmSave(DBFarm $dbFarm, DBFarmRole $dbFarmRole)
		{
			
		}
		
		public function onFarmTerminated(DBFarmRole $dbFarmRole) 
		{
			
		}
		
		public function onBeforeInstanceLaunch(DBServer $dbServer)
		{
			
		}
		
		public function setSnapshotConfig($snapshotConfig, DBFarmRole $dbFarmRole, DBServer $dbServer)
		{
			try {
				$storageSnapshot = Scalr_Storage_Snapshot::init();
				
				try {
					$storageSnapshot->loadById($snapshotConfig->id);
					$storageSnapshot->setConfig($snapshotConfig);
					$storageSnapshot->save();
				} catch (Exception $e) {
					if (strpos($e->getMessage(), 'not found')) {
						$storageSnapshot->loadBy(array(
							'id'			=> $snapshotConfig->id,
							'client_id'		=> $dbServer->clientId,
							'farm_id'		=> $dbServer->farmId,
							'farm_roleid'	=> $dbServer->farmRoleId,
							'env_id'		=> $dbServer->envId,
							'name'			=> sprintf(_("%s data bundle #%s"), $this->behavior, $snapshotConfig->id),
							'type'			=> $dbFarmRole->GetSetting(static::ROLE_DATA_STORAGE_ENGINE),
							'platform'		=> $dbServer->platform,
							'description'	=> sprintf(_("{$this->behavior} data bundle created on Farm '%s' -> Role '%s'"), 
								$dbFarmRole->GetFarmObject()->Name, 
								$dbFarmRole->GetRoleObject()->name
							),
							'service'		=> $this->behavior
						));
						$storageSnapshot->setConfig($snapshotConfig);
						$storageSnapshot->save(true);
					} 
					else
						throw $e;
				}
										
				$dbFarmRole->SetSetting(static::ROLE_SNAPSHOT_ID, $storageSnapshot->id);
			}
			catch(Exception $e) {
				$this->logger->error(new FarmLogMessage($dbFarmRole->FarmID, "Cannot save storage volume: {$e->getMessage()}"));
			}
		}
		
		public function setVolumeConfig($volumeConfig, DBFarmRole $dbFarmRole, DBServer $dbServer)
		{
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
							'purpose'		=> $this->behavior,
							'farm_roleid'	=> $dbFarmRole->ID,
							'server_index'	=> $dbServer->index
						));
						$storageVolume->setConfig($volumeConfig);
						$storageVolume->save(true);
					} 
					else
						throw $e;
				}
										
				$dbFarmRole->SetSetting(static::ROLE_VOLUME_ID, $storageVolume->id);
			}
			catch(Exception $e) {
				$this->logger->error(new FarmLogMessage($dbFarmRole->FarmID, "Cannot save storage volume: {$e->getMessage()}"));
			}
		}
		
		public function getSnapshotConfig(DBFarmRole $dbFarmRole, DBServer $dbServer)
		{
			$r = new ReflectionClass($this);
			if ($r->hasConstant("ROLE_SNAPSHOT_ID")) {
				if ($dbFarmRole->GetSetting(static::ROLE_SNAPSHOT_ID))
				{
					try {
						$snapshot = Scalr_Storage_Snapshot::init()->loadById(
							$dbFarmRole->GetSetting(static::ROLE_SNAPSHOT_ID)
						);
									
						return $snapshot->getConfig();
					} catch (Exception $e) {}
				}
			}

			return false;
		}
		
		public function getVolumeConfig(DBFarmRole $dbFarmRole, DBServer $dbServer)
		{
			$r = new ReflectionClass($this);
			if ($r->hasConstant("ROLE_VOLUME_ID")) {
				if ($dbFarmRole->GetSetting(static::ROLE_VOLUME_ID))
				{
					try {
						$volume = Scalr_Storage_Volume::init()->loadById(
							$dbFarmRole->GetSetting(static::ROLE_VOLUME_ID)
						);
									
						$volumeConfig = $volume->getConfig();
					} catch (Exception $e) {}
				}
		
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
				
			} else 
				return false;
		}
	}