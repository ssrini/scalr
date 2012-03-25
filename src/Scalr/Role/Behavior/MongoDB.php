<?php
	class Scalr_Role_Behavior_MongoDB extends Scalr_Role_Behavior implements Scalr_Role_iBehavior
	{
		/** DBFarmRole settings **/
		const ROLE_DATA_STORAGE_ENGINE = 'mongodb.data_storage.engine';
		const ROLE_PASSWORD = 'mongodb.password';
		const ROLE_SHARDS_COUNT = 'mongodb.shards.count';
		const ROLE_REPLICAS_COUNT = 'mongodb.replicas.count';
		
		// For EBS storage
		const ROLE_DATA_STORAGE_EBS_SIZE = 'mongodb.data_storage.ebs.size';
		
		const ROLE_KEYFILE = 'mongodb.keyfile';
		
		const ROLE_CLUSTER_STATUS = 'mongodb.cluster.status';
		const ROLE_CLUSTER_IS_REMOVING_SHARD_INDEX = 'mongodb.cluster.shards.removal.index';
		
		//Backups & data bundles
		/** Data Bundle settings **/
		const DATA_BUNDLE_ENABLED = 'mongodb.data_bundle.enabled';
		const DATA_BUNDLE_EVERY   = 'mongodb.data_bundle.every';
		const DATA_BUNDLE_IS_RUNNING = 'mongodb.data_bundle.is_running';
		const DATA_BUNDLE_SERVER_ID = 'mongodb.data_bundle.server_id';
		const DATA_BUNDLE_LAST_TS	= 'mongodb.data_bundle.timestamp';
		const DATA_BUNDLE_TIMEFRAME_START_HH = 'mongodb.data_bundle.timeframe.start_hh';
		const DATA_BUNDLE_TIMEFRAME_END_HH = 'mongodb.data_bundle.timeframe.end_hh';
		const DATA_BUNDLE_TIMEFRAME_START_MM = 'mongodb.data_bundle.timeframe.start_mm';
		const DATA_BUNDLE_TIMEFRAME_END_MM = 'mongodb.data_bundle.timeframe.end_mm';
		
		/** DBServer settings **/
		const SERVER_IS_ROUTER = 'mongodb.is_router';
		const SERVER_IS_CFG_SERVER = 'mongodb.is_cfg_server';
		const SERVER_IS_PRIMARY = 'mongodb.is_primary';
		const SERVER_REPLICA_SET_INDEX = 'mongodb.replica_set_index';
		const SERVER_SHARD_INDEX = 'mongodb.shard_index';
		const SERVER_NODE_STATUS = 'mongodb.node_status';
		const SERVER_NODE_MSG = 'mongodb.node_msg';
		
		const STATUS_BUILDING = 'building';
		const STATUS_ACTIVE = 'active';
		const STATUS_SHUTTING_DOWN = 'shutting-down';
		const STATUS_TERMINATED = 'terminated';

		
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function onFarmSave(DBFarm $dbFarm, DBFarmRole $dbFarmRole)
		{
			if (!$dbFarmRole->GetSetting(self::ROLE_REPLICAS_COUNT)) {
				$dbFarmRole->SetSetting(self::ROLE_REPLICAS_COUNT, 1);
				$dbFarmRole->SetSetting(self::ROLE_SHARDS_COUNT, 1);
			}
		}
		
		public function makeUpscaleDecision(DBFarmRole $dbFarmRole) 
		{
			$status = $dbFarmRole->GetSetting(self::ROLE_CLUSTER_STATUS);
			if ($status == self::STATUS_SHUTTING_DOWN || $status == self::STATUS_TERMINATED)
				return Scalr_Scaling_Decision::NOOP;
			
			$pLaunchServers = $dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::PENDING_LAUNCH));
			if (count($pLaunchServers) > 0)
				return Scalr_Scaling_Decision::NOOP;
				
			$indexes = $this->getMongoClusterIndexes($dbFarmRole);
			if (!$indexes)
				return Scalr_Scaling_Decision::NOOP;
				
			if ($indexes['replicaSetIndex'] != 0 && $status != self::STATUS_ACTIVE)
				return Scalr_Scaling_Decision::NOOP;
				
			return Scalr_Scaling_Decision::UPSCALE;
		}
		
		public function getMongoClusterIndexes(DBFarmRole $dbFarmRole)
		{
			$shardsCount = (int)$dbFarmRole->GetSetting(self::ROLE_SHARDS_COUNT);
			$replicasCount = (int)$dbFarmRole->GetSetting(self::ROLE_REPLICAS_COUNT);
			
			$clusterMap = array();
			$servers = $dbFarmRole->GetServersByFilter(array('status' => array(SERVER_STATUS::PENDING_LAUNCH, SERVER_STATUS::PENDING, SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ($servers as $server) {
				if ($server->GetProperty(self::SERVER_SHARD_INDEX) !== false && $server->GetProperty(self::SERVER_SHARD_INDEX) !== null)
					$clusterMap[$server->GetProperty(self::SERVER_SHARD_INDEX)][$server->GetProperty(self::SERVER_REPLICA_SET_INDEX)] = $server->status;
			}
			
			for ($shardIndex = 0; $shardIndex < $shardsCount; $shardIndex++) {
				$isFirstReplicaInShardRunning = ($clusterMap[$shardIndex][0] == SERVER_STATUS::RUNNING);
				$nextShardIndex = ($shardIndex+1) < $shardsCount ? $shardIndex+1 : false;
				
				for ($replicaSetIndex = 0; $replicaSetIndex < $replicasCount; $replicaSetIndex++) {			
					$serverStatus = $clusterMap[$shardIndex][$replicaSetIndex];
					
					// Checking config server (0-0)
					if ($shardIndex == 0 && $replicaSetIndex == 0) {
						if (is_null($serverStatus))
							return array('shardIndex' => 0, 'replicaSetIndex' => 0);
							
						// Waiting for config server
						/*
						if ($serverStatus != SERVER_STATUS::RUNNING) {
							//var_dump('Waiting for config server');
							return false;
						}
						*/
					}
					
					if ($nextShardIndex && count($clusterMap[$nextShardIndex]) < count($clusterMap[$shardIndex]))
						continue 0;
					
					// if no running server at current position OR we're launching first server in shard
					if (is_null($serverStatus) && ($isFirstReplicaInShardRunning || $replicaSetIndex == 0)) {
						return array('shardIndex' => $shardIndex, 'replicaSetIndex' => $replicaSetIndex);
					}
				}
			}
				
			return false;
		}
		
		public function onFarmTerminated(DBFarmRole $dbFarmRole) 
		{
			$dbFarmRole->SetSetting(self::ROLE_CLUSTER_STATUS, "");
		}
		
		public function onBeforeInstanceLaunch(DBServer $dbServer)
		{
			$indexes = $this->getMongoClusterIndexes($dbServer->GetFarmRoleObject());
			
			Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($dbServer->farmId, sprintf("MongoDB Scaling: Launching server %s-%s for mongo cluster", $indexes['shardIndex'], $indexes['replicaSetIndex'])));
			
			$dbServer->SetProperty(self::SERVER_SHARD_INDEX, $indexes['shardIndex']);
			$dbServer->SetProperty(self::SERVER_REPLICA_SET_INDEX, $indexes['replicaSetIndex']);
			
			if (!$dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_CLUSTER_STATUS))
				$dbServer->GetFarmRoleObject()->SetSetting(self::ROLE_CLUSTER_STATUS, self::STATUS_BUILDING);
		}
		
		public function getSecurityRules()
		{
			return array(
				"tcp:27017:27020:0.0.0.0/0",
				"tcp:28018:28018:0.0.0.0/0"
			);
		}
		
		public function listDnsRecords(DBServer $dbServer)
		{
			$records = array();
			
			if ($dbServer->GetProperty(self::SERVER_IS_ROUTER)) {
				array_push($records, array(
					"name" 		=> "int-mongodb-router",
					"value"		=> $dbServer->localIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $dbServer->serverId,
					"issystem"	=> '1'
				));
				
				array_push($records, array(
					"name" 		=> "ext-mongodb-router",
					"value"		=> $dbServer->remoteIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $dbServer->serverId,
					"issystem"	=> '1'
				));
			}
			
			array_push($records, array(
				"name" 		=> "int-mongodb",
				"value"		=> $dbServer->localIp,
				"type"		=> "A",
				"ttl"		=> 90,
				"server_id"	=> $dbServer->serverId,
				"issystem"	=> '1'
			));
			
			array_push($records, array(
				"name" 		=> "ext-mongodb",
				"value"		=> $dbServer->remoteIp,
				"type"		=> "A",
				"ttl"		=> 90,
				"server_id"	=> $dbServer->serverId,
				"issystem"	=> '1'
			));
		}
		
		public function handleMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			try {
				$dbFarmRole = $dbServer->GetFarmRoleObject();
			} catch (Exception $e) {}
				
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_MongoDb_ClusterTerminateStatus":
					
					$this->log($dbFarmRole, "Shutting-down cluster. Progress {$message->progress}%");
	       					
       				//Check instances
       				$nodes = array();
       				foreach ($message->nodes as $node) {
       					$nodes[$node->shardIndex][$node->replicaSetIndex] = $node;
       				}
	       					
	       			foreach ($dbFarmRole->GetServersByFilter() as $server) {
	       				$shardIndex = $server->GetProperty(self::SERVER_SHARD_INDEX);
	       				$replicaSetIndex = $server->GetProperty(self::SERVER_REPLICA_SET_INDEX);
	       				$node = $nodes[$shardIndex][$replicaSetIndex];
	       						
	       				switch ($server->status) {
	       					case SERVER_STATUS::PENDING_LAUNCH:
	       					case SERVER_STATUS::PENDING:
	       					case SERVER_STATUS::INIT:
	       						Scalr::FireEvent($dbFarmRole->FarmID, new BeforeHostTerminateEvent($server, true));
	       					break;
	       								
	       					case SERVER_STATUS::RUNNING:
	       						if ($node->status == 'pending')
	       							continue;
	       						if ($node->status == 'terminating')
	       							continue;
	       						if ($node->status == 'terminated') {
	       							$this->log($dbFarmRole, "Node {$shardIndex}-{$replicaSetIndex} successfully terminated. Terminating instance.");
	       							Scalr::FireEvent($dbFarmRole->FarmID, new BeforeHostTerminateEvent($server, true));
	       						}
	       						else {
	       							$this->log($dbFarmRole, "Cannot shutdown {$shardIndex}-{$replicaSetIndex} node. Error: {$node->lastError}", "ERROR");
	       							$server->SetProperty(Scalr_Role_Behavior_MongoDB::SERVER_NODE_STATUS, 'failed');
	       						}
	       								
	       					break;
	       				}
	       			}
					
					break;
				
				case "Scalr_Messaging_Msg_MongoDb_ClusterTerminateResult":
					
					if ($message->status == 'ok') {
       					$dbFarmRole->SetSetting(self::ROLE_CLUSTER_STATUS, self::STATUS_TERMINATED);
       					$this->log($dbFarmRole, "Cluster successfully terminated", "INFO");
       				} else {
       					$this->log($dbFarmRole, "Unable to shutdown mongodb cluster. Received TerminateCluster failed message.", "ERROR");
       					$dbFarmRole->SetSetting(self::ROLE_CLUSTER_STATUS, self::STATUS_ACTIVE);
       				}
					
					break;
				
				case "Scalr_Messaging_Msg_MongoDb_RemoveShardStatus":
					
					$status = "Removing shard #{$message->shardIndex}. Progress: {$message->progress}%";
	       			$this->log($dbFarmRole, $status);
					
					break;
				
				case "Scalr_Messaging_Msg_MongoDb_RemoveShardResult":
					
					$rShard = $dbFarmRole->GetSetting(self::ROLE_CLUSTER_IS_REMOVING_SHARD_INDEX);
       				if (!$rShard || $rShard != $message->shardIndex)
       					return;
       					
       				if ($message->status == 'ok') {	       					
	       				$dbFarmRole->SetSetting(self::ROLE_CLUSTER_IS_REMOVING_SHARD_INDEX, null);
	       				$sCount = $dbFarmRole->GetSetting(self::ROLE_SHARDS_COUNT);
	       				$dbFarmRole->SetSetting(self::ROLE_SHARDS_COUNT, $sCount-1);
	       					
	       				// Terminate instances
	       				foreach ($dbFarmRole->GetServersByFilter(array('status' => array(SERVER_STATUS::RUNNING, SERVER_STATUS::INIT, SERVER_STATUS::PENDING, SERVER_STATUS::PENDING_LAUNCH))) as $server) {
	       					if ($server->GetProperty(self::SERVER_SHARD_INDEX) == $message->shardIndex) {
	       						Scalr::FireEvent($server->farmId, new BeforeHostTerminateEvent($server, false));
	       					}
	       				}
	       					
	       				$this->log($dbFarmRole, "Shard #{$message->shardIndex} successfully removed");
	       					
       				} else {
       					$this>log($dbFarmRole, $message->lastError, "ERROR");
       				}
					
					break;
				
				case "Scalr_Messaging_Msg_HostUp":
					
					if (!$message->mongodb)
						return;
					
					if ($message->mongodb->volumeConfig)
						$this->setVolumeConfig($message->mongodb->volumeConfig, $dbServer->GetFarmRoleObject(), $dbServer);
					else
						throw new Exception("Received hostUp message from MongoDB server without volumeConfig");
					
					if ($message->mongodb->snapshotConfig)
						$this->setSnapshotConfig($message->mongodb->snapshotConfig, $dbServer->GetFarmRoleObject(), $dbServer);
						
					$dbServer->GetFarmRoleObject()->SetSetting(self::ROLE_KEYFILE, $message->mongodb->keyfile);
					$dbServer->GetFarmRoleObject()->SetSetting(self::ROLE_PASSWORD, $message->mongodb->password);
					
					$dbServer->SetProperty(self::SERVER_IS_CFG_SERVER, $message->mongodb->configServer);
					$dbServer->SetProperty(self::SERVER_IS_ROUTER, $message->mongodb->router);
					
					if ($message->mongodb->configServer == 1) {
						$dbServer->GetFarmRoleObject()->SetSetting(self::ROLE_CLUSTER_STATUS, self::STATUS_ACTIVE);
					}
					
					break;
			}
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
										
				$this->db->Execute("INSERT INTO services_mongodb_snapshots_map SET
					farm_roleid = ?,
					shard_index = ?,
					snapshot_id = ? ON DUPLICATE KEY UPDATE snapshot_id = ?
				", array(
					$dbFarmRole->ID,
					$dbServer->GetProperty(self::SERVER_SHARD_INDEX),
					$snapshotConfig->id,
					$snapshotConfig->id,
				));
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
										
				$this->db->Execute("INSERT INTO services_mongodb_volumes_map SET
					farm_roleid = ?,
					replica_set_index = ?,
					shard_index = ?,
					volume_id = ? ON DUPLICATE KEY UPDATE volume_id = ?
				", array(
					$dbFarmRole->ID,
					$dbServer->GetProperty(self::SERVER_REPLICA_SET_INDEX),
					$dbServer->GetProperty(self::SERVER_SHARD_INDEX),
					$volumeConfig->id,
					$volumeConfig->id
				));
			}
			catch(Exception $e) {
				$this->logger->error(new FarmLogMessage($dbFarmRole->FarmID, "Cannot save storage volume: {$e->getMessage()}"));
			}
		}
		
		public function log(DBFarmRole $dbFarmRole, $message, $severity = 'INFO')
		{
			$this->db->Execute("INSERT INTO services_mongodb_cluster_log SET
				farm_roleid = ?,
				dtadded = NOW(),
				severity = ?,
				message = ?
			", array(
				$dbFarmRole->ID,
				$severity,
				$message
			));
		}
		
		private function getSnapshotIdByServer(DBServer $dbServer)
		{
			return $this->db->GetOne("SELECT snapshot_id FROM services_mongodb_snapshots_map WHERE farm_roleid = ? AND shard_index = ?", array(
				$dbServer->farmRoleId, $dbServer->GetProperty(self::SERVER_SHARD_INDEX)
			));
		}
		
		private function getVolumeIdByServer(DBServer $dbServer) 
		{
			return $this->db->GetOne("SELECT volume_id FROM services_mongodb_volumes_map WHERE farm_roleid = ? AND shard_index = ? AND replica_set_index = ?", array(
				$dbServer->farmRoleId, $dbServer->GetProperty(self::SERVER_SHARD_INDEX), $dbServer->GetProperty(self::SERVER_REPLICA_SET_INDEX)
			));
		}
		
		public function getVolumeConfig(DBFarmRole $dbFarmRole, DBServer $dbServer)
		{
			$volumeId  = $this->getVolumeIdByServer($dbServer);
			if ($volumeId)
			{
				try {
					$volume = Scalr_Storage_Volume::init()->loadById($volumeId);								
					$volumeConfig = $volume->getConfig();
				} catch (Exception $e) {}
			}
	
			if (!$volumeConfig)
			{
				$volumeConfig = new stdClass();
				$volumeConfig->type = $dbFarmRole->GetSetting(static::ROLE_DATA_STORAGE_ENGINE);
				//$volumeConfig->fstype = 'xfs';
	
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
		
		public function getSnapshotConfig(DBFarmRole $dbFarmRole, DBServer $dbServer) 
		{
			$snapshotId  = $this->getSnapshotIdByServer($dbServer);
			if ($snapshotId)
			{
				try {
					$snapshot = Scalr_Storage_Snapshot::init()->loadById($snapshotId);
								
					return $snapshot->getConfig();
				} catch (Exception $e) {}
			}
			
			return null;
		}
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			$message = parent::extendMessage($message);
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostInitResponse":
					
					$message->mongodb = new stdClass();
					$message->mongodb->keyfile = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_KEYFILE);
					$message->mongodb->volumeConfig = $this->getVolumeConfig($dbServer->GetFarmRoleObject(), $dbServer);
					$message->mongodb->snapshotConfig = $this->getSnapshotConfig($dbServer->GetFarmRoleObject(), $dbServer);
					$message->mongodb->password = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_PASSWORD);
					
					$message->mongodb->replicaSetIndex = $dbServer->GetProperty(self::SERVER_REPLICA_SET_INDEX);
					$message->mongodb->shardIndex = $dbServer->GetProperty(self::SERVER_SHARD_INDEX);
					
					$message->mongodb->shardsTotal = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_SHARDS_COUNT);
					$message->mongodb->replicasPerShard = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_REPLICAS_COUNT);
					
					break;

				case "Scalr_Messaging_Msg_HostDown":
				case "Scalr_Messaging_Msg_HostUp":
				case "Scalr_Messaging_Msg_HostInit":
				case "Scalr_Messaging_Msg_BeforeHostTerminate":
					
					$message->mongodb = new stdClass();
					
					$message->mongodb->replicaSetIndex = $dbServer->GetProperty(self::SERVER_REPLICA_SET_INDEX);
					$message->mongodb->shardIndex = $dbServer->GetProperty(self::SERVER_SHARD_INDEX);
					
					break;
			}
			
			return $message;
		}
	}