<?php
	class DBEventObserver extends EventObserver
	{
		/**
		 * Observer name
		 *
		 * @var unknown_type
		 */
		public $ObserverName = 'DB';
		
		/**
		 * Update database when 'mysqlBckComplete' event recieved from instance
		 *
		 * @param MysqlBackupCompleteEvent $event
		 */
		public function OnMysqlBackupComplete(MysqlBackupCompleteEvent $event)
		{
			try
			{
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
				$farm_roleid = $DBfarmRole->ID;
			}
			catch(Exception $e) {
				return;
			}
			
			if ($event->Operation == MYSQL_BACKUP_TYPE::DUMP)
			{
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LAST_BCP_TS, time());
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING, 0);
			}
			elseif ($event->Operation == MYSQL_BACKUP_TYPE::BUNDLE)
			{
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LAST_BUNDLE_TS, time());
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);

				if (!is_array($event->SnapshotInfo))
					$event->SnapshotInfo = array('snapshotId' => $event->SnapshotInfo);
				
				if ($event->SnapshotInfo['snapshotId'])
				{
					if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE) == MYSQL_STORAGE_ENGINE::EBS)
					{	
						$this->DB->Execute("INSERT INTO ebs_snaps_info SET snapid=?, comment=?, dtcreated=NOW(), region=?, ebs_array_snapid='0', is_autoebs_master_snap='1', farm_roleid=?",
						array(
							$event->SnapshotInfo['snapshotId'], 
							_('MySQL Master volume snapshot'), 
							$event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION), 
							$DBFarmRole->ID
						));
						
						// Scalarizr stuff
						$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID, $event->SnapshotInfo['snapshotId']);
						
						$snapshotConfig = new stdClass();
						$snapshotConfig->type = 'ebs';
						$snapshotConfig->id = $event->SnapshotInfo['snapshotId'];

						$event->SnapshotInfo['snapshotConfig'] = $snapshotConfig;
					}
				}
				
				if ($event->SnapshotInfo['logFile'])
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LOG_FILE, $event->SnapshotInfo['logFile']);
					
				if ($event->SnapshotInfo['logPos'])
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LOG_POS, $event->SnapshotInfo['logPos']);
				
				
				try {
					$storageSnapshot = Scalr_Storage_Snapshot::init();
					$storageSnapshot->loadBy(array(
						'id'			=> $event->SnapshotInfo['snapshotConfig']->id,
						'client_id'		=> $event->DBServer->clientId,
						'farm_id'		=> $event->DBServer->farmId,
						'farm_roleid'	=> $event->DBServer->farmRoleId,
						'env_id'		=> $event->DBServer->envId,
						'name'			=> sprintf(_("MySQL data bundle #%s"), $event->SnapshotInfo['snapshotConfig']->id),
						'type'			=> $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE),
						'platform'		=> $event->DBServer->platform,
						'description'	=> sprintf(_("MySQL data bundle created on Farm '%s' -> Role '%s'"), 
							$DBFarmRole->GetFarmObject()->Name, 
							$DBFarmRole->GetRoleObject()->name
						),
						'ismysql'		=> true,
						'service'		=> ROLE_BEHAVIORS::MYSQL
					));
					
					$storageSnapshot->setConfig($event->SnapshotInfo['snapshotConfig']);
					
					$storageSnapshot->save(true);
					
					$DBFarmRole->SetSetting(
						DBFarmRole::SETTING_MYSQL_SCALR_SNAPSHOT_ID, 
						$storageSnapshot->id
					);
				}
				catch(Exception $e) {
					$this->Logger->fatal("Cannot save storage snapshot: {$e->getMessage()}");
				} 
			}
		}
		
		/**
		 * Update database when 'mysqlBckFail' event recieved from instance
		 *
		 * @param MysqlBackupFailEvent $event
		 */
		public function OnMysqlBackupFail(MysqlBackupFailEvent $event)
		{
			try
			{
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
			}
			catch(Exception $e) {
				return;
			}
			
			if ($event->Operation == MYSQL_BACKUP_TYPE::DUMP)
			{
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING, 0);
			}
			elseif ($event->Operation == MYSQL_BACKUP_TYPE::BUNDLE)
			{
				$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);
			}
		}
		
		/**
		 * Update database when replication was broken on slave
		 *
		 * @param MySQLReplicationFailEvent $event
		 */
		public function OnMySQLReplicationFail(MySQLReplicationFailEvent $event)
		{
			$event->DBServer->SetProperty(SERVER_PROPERTIES::DB_MYSQL_REPLICATION_STATUS, 0);
		}
		
		/**
		 * Update database when replication was recovered on slave
		 *
		 * @param MySQLReplicationRecoveredEvent $event
		 */
		public function OnMySQLReplicationRecovered(MySQLReplicationRecoveredEvent $event)
		{
			$event->DBServer->SetProperty(SERVER_PROPERTIES::DB_MYSQL_REPLICATION_STATUS, 1);
		}
		
		/**
		 * Update database when 'newMysqlMaster' event recieved from instance
		 *
		 * @param NewMysqlMasterUpEvent $event
		 * @deprecated
		 */
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{			
			if ($event->OldMasterDBServer instanceof DBServer)
				$event->OldMasterDBServer->SetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER, 0);
									
			$event->DBServer->GetFarmRoleObject()->SetSetting(DBFarmRole::SETTING_MYSQL_LAST_BUNDLE_TS, time());
			
			$event->DBServer->SetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER, 1);
		}
		
		public function OnNewDbMsrMasterUp(NewDbMsrMasterUpEvent $event) 
		{
			$event->DBServer->GetFarmRoleObject()->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_LAST_TS, time());
		}
		
		/**
		 * Update database when 'hostInit' event recieved from instance
		 *
		 * @param HostInitEvent $event
		 * 
		 */
		public function OnHostInit(HostInitEvent $event)
		{			
			$event->DBServer->localIp = $event->InternalIP;
			$event->DBServer->remoteIp = $event->ExternalIP;
			$event->DBServer->status = SERVER_STATUS::INIT;
			$event->DBServer->Save();
			
			$this->DB->Execute("DELETE FROM server_operations WHERE server_id=?", array($event->DBServer->serverId));
			$event->DBServer->SetProperty(SERVER_PROPERTIES::SZR_IS_INIT_FAILED, false);
			
			try {
				$key = Scalr_Model::init(Scalr_Model::SSH_KEY)->loadGlobalByFarmId(
					$event->DBServer->farmId,
					$event->DBServer->GetFarmRoleObject()->CloudLocation
				);
				
				if ($key && !$key->getPublic())
				{
					$key->setPublic($event->PublicKey);
					$key->save();
				}
			}
			catch(Exception $e) { }
		}
			
		/**
		 * Update database when 'newAMI' event recieved from instance
		 *
		 * @param RebundleCompleteEvent $event
		 * 
		 */
		public function OnRebundleComplete(RebundleCompleteEvent $event)
		{
			try {
				$BundleTask = BundleTask::LoadById($event->BundleTaskID);
			}
			catch (Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Rebundle complete event without bundle task.");
				return;
			}
			
			if ($BundleTask->status == SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS)
				$BundleTask->SnapshotCreationComplete($event->SnapshotID, $event->MetaData);
				
			if ($event->DBServer && $event->DBServer->status == SERVER_STATUS::TEMPORARY) {
				PlatformFactory::NewPlatform($event->DBServer->platform)->TerminateServer($event->DBServer);
				//$event->DBServer->status = SERVER_STATUS::TERMINATED;
				//$event->DBServer->save();
			}
		}
		
		/**
		 * Called when scalr recived notify about rebundle failure from instance
		 *
		 * @param RebundleFailedEvent $event
		 */
		public function OnRebundleFailed(RebundleFailedEvent $event)
		{
			try {
				$BundleTask = BundleTask::LoadById($event->BundleTaskID);
			}
			catch (Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Rebundle complete event without bundle task.");
				return;
			}
			
			$msg = 'Received RebundleFailed event from server';
			if ($event->LastErrorMessage)
				$msg .= ". Reason: {$event->LastErrorMessage}";
				
			$BundleTask->SnapshotCreationFailed($msg);
			
			if ($event->DBServer && $event->DBServer->status == SERVER_STATUS::TEMPORARY) {
				PlatformFactory::NewPlatform($event->DBServer->platform)->TerminateServer($event->DBServer);
				$event->DBServer->status = SERVER_STATUS::TERMINATED;
				$event->DBServer->save();
			}
		}

		/**
		 * Farm launched
		 *
		 * @param FarmLaunchedEvent $event
		 */
		public function OnFarmLaunched(FarmLaunchedEvent $event)
		{
			$DBFarm = DBFarm::LoadByID($this->FarmID);
			
			// TODO: Refactoting -> Move to DBFarm class
			$this->DB->Execute("UPDATE farms SET status=?, dtlaunched=NOW() WHERE id=?",
				array(FARM_STATUS::RUNNING, $this->FarmID)
			);
		}
		
		/**
		 * Farm terminated
		 *
		 * @param FarmTerminatedEvent $event
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{
			$syncs = $this->DB->GetOne("SELECT COUNT(*) FROM bundle_tasks WHERE farm_id=? AND status NOT IN ('success','failed')",
				array($this->FarmID)
			);
			
			$dbFarm = DBFarm::LoadByID($this->FarmID);	
			$dbFarm->Status = ($syncs > 0 && $dbFarm->Status == FARM_STATUS::RUNNING) ? FARM_STATUS::SYNCHRONIZING : FARM_STATUS::TERMINATED;
			$dbFarm->TermOnSyncFail = $event->TermOnSyncFail;
			$dbFarm->save();			
									
			if ($dbFarm->Status == FARM_STATUS::SYNCHRONIZING)
				$servers = $dbFarm->GetServersByFilter(array(), array('status' => array(SERVER_STATUS::PENDING_TERMINATE, SERVER_STATUS::TERMINATED)));
			else
				$servers = $dbFarm->GetServersByFilter(array(), array());
			                    
		    if (count($servers) == 0)
		    	return;
		    
		    // TERMINATE RUNNING INSTANCES
            foreach ($servers as $dbServer)
            {                
                if ($this->DB->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND status NOT IN ('success','failed')", array($dbServer->serverId)))
                	continue;
                
            	if ($dbServer->status != SERVER_STATUS::PENDING_LAUNCH)
                {
	            	try {
	            		//Scalr::FireEvent($dbFarm->ID, new BeforeHostTerminateEvent($dbServer, false));
	            		if ($dbServer->status != SERVER_STATUS::PENDING_TERMINATE && $dbServer->status != SERVER_STATUS::TERMINATED)
						{ 
							$dbServer->status = SERVER_STATUS::PENDING_TERMINATE;
							
							if (defined("SCALR_SERVER_TZ"))
							{
								$tz = date_default_timezone_get();
								date_default_timezone_set(SCALR_SERVER_TZ);
							}
								
							$dbServer->dateShutdownScheduled = date("Y-m-d H:i:s");
							
							if ($tz)
								date_default_timezone_set($tz);
							
							$dbServer->Save();
						}
	    					
	    				if ($dbServer->status != SERVER_STATUS::TERMINATED)
	    				{
		    				$this->DB->Execute("UPDATE servers_history SET
								dtterminated	= NOW(),
								terminate_reason	= ?
								WHERE server_id = ?
							", array(
								sprintf("Farm was terminated"),
								$dbServer->serverId
							));
	    				}
	    			}
	    			catch (Exception $e) {
	    				$this->Logger->error($e->getMessage()); 
	    			}
                }
    			else {
    				$dbServer->status = SERVER_STATUS::TERMINATED;
    				$dbServer->Save();
    			}
            }
		}
		
		/**
		 * Called when instance configured and upped
		 *
		 * @param HostUpEvent $event
		 */
		public function OnHostUp(HostUpEvent $event)
		{
			$event->DBServer->status = SERVER_STATUS::RUNNING;
			
			if ($event->ReplUserPass)
				$event->DBServer->GetFarmRoleObject()->SetSetting(DBFarmRole::SETTING_MYSQL_STAT_PASSWORD, $event->ReplUserPass);
			
			if (defined("SCALR_SERVER_TZ"))
			{
				$tz = date_default_timezone_get();
				date_default_timezone_set(SCALR_SERVER_TZ);
			}
				
			$event->DBServer->SetProperty(SERVER_PROPERTIES::INITIALIZED_TIME, time());
			
			if ($tz)
				date_default_timezone_set($tz);
				
			$event->DBServer->Save();
			
			try
			{
				// If we need replace old instance to new one terminate old one.
				if ($event->DBServer->replaceServerID)
				{
					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($this->FarmID, "Host UP. Terminating old server: {$event->DBServer->replaceServerID})."));
					
					try {
						$oldDBServer = DBServer::LoadByID($event->DBServer->replaceServerID);
					}
					catch(Exception $e) {}

					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($this->FarmID, "OLD Server found: {$oldDBServer->serverId})."));
					
					if ($oldDBServer)
						Scalr::FireEvent($oldDBServer->farmId, new BeforeHostTerminateEvent($oldDBServer));
				}
			}
			catch (Exception $e)
			{
				$this->Logger->fatal($e->getMessage());
			}	
		}
		
		/**
		 * Called when reboot complete
		 *
		 * @param RebootCompleteEvent $event
		 */
		public function OnRebootComplete(RebootCompleteEvent $event)
		{
			$event->DBServer->SetProperty(SERVER_PROPERTIES::REBOOTING, 0);
			
			try {
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
		        
		        if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_BCP_SERVER_ID) == $event->DBServer->serverId)
		        	$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING, 0);
		        
		        if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_BUNDLE_SERVER_ID) == $event->DBServer->serverId)
		        	$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);
		        	
		        if ($DBFarmRole->GetSetting(Scalr_Db_Msr::DATA_BACKUP_SERVER_ID) == $event->DBServer->serverId)
		        	$DBFarmRole->SetSetting(Scalr_Db_Msr::DATA_BACKUP_IS_RUNNING, 0);
		        	
		        if ($DBFarmRole->GetSetting(Scalr_Db_Msr::DATA_BUNDLE_SERVER_ID) == $event->DBServer->serverId)
		        	$DBFarmRole->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_IS_RUNNING, 0);
	        }
	        catch(Exception $e){}
		}
		
		/**
		 * Called when instance receive reboot command
		 *
		 * @param RebootBeginEvent $event
		 */
		public function OnRebootBegin(RebootBeginEvent $event)
		{
			$event->DBServer->SetProperty(SERVER_PROPERTIES::REBOOTING, 1);
		}
		
		/**
		 * Called when instance going down
		 *
		 * @param HostDownEvent $event
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBServer->IsRebooting())
				return;
			
			$event->DBServer->status = SERVER_STATUS::TERMINATED;
			$event->DBServer->dateShutdownScheduled = date("Y-m-d H:i:s");
			
			$this->DB->Execute("UPDATE servers_history SET
				dtterminated_scalr	= NOW()
				WHERE server_id = ?
			", array(
				$event->DBServer->serverId
			));
			
	        try {
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
		        
		        if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_BCP_SERVER_ID) == $event->DBServer->serverId)
		        	$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING, 0);
		        
		        if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_BUNDLE_SERVER_ID) == $event->DBServer->serverId)
		        	$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING, 0);
		        	
		        if ($DBFarmRole->GetSetting(Scalr_Db_Msr::DATA_BACKUP_SERVER_ID) == $event->DBServer->serverId)
		        	$DBFarmRole->SetSetting(Scalr_Db_Msr::DATA_BACKUP_IS_RUNNING, 0);
		        	
		        if ($DBFarmRole->GetSetting(Scalr_Db_Msr::DATA_BUNDLE_SERVER_ID) == $event->DBServer->serverId)
		        	$DBFarmRole->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_IS_RUNNING, 0);
	        }
	        catch(Exception $e){}
			
	        if ($event->replacementDBServer)
	        {
	        	$event->replacementDBServer->replaceServerID = null;
	        	$event->replacementDBServer->Save();
	        }
			
	        //Check active bundle task:
	        $bundle_task_id = $this->DB->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND status IN (?,?)", array(
	        	$event->DBServer->serverId,
	        	SERVER_SNAPSHOT_CREATION_STATUS::PENDING,
	        	SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS
	        ));
	        if ($bundle_task_id)
	        {
	        	$BundleTask = BundleTask::LoadById($bundle_task_id);
	        	$BundleTask->SnapshotCreationFailed("Server was terminated before image was created.");
	        }
	        
			//
			//
			//
			//TODO:
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=?", array($this->FarmID));
						
			if ($farminfo['status'] == FARM_STATUS::SYNCHRONIZING)
			{
				$event->DBServer->SkipEBSObserver = true;
				
				$farm_servers_count = $this->DB->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id=? and server_id != ? and status != ?", 
					array($this->FarmID, $event->DBServer->serverId, SERVER_STATUS::TERMINATED)
				);
				
				if ($farm_servers_count == 0)
				{
					$this->DB->Execute("UPDATE farms SET status=? WHERE id=?", 
						array(FARM_STATUS::TERMINATED, $this->FarmID)
					);
				}
			}
			
			$event->DBServer->Save();
		}
		
		public function OnIPAddressChanged(IPAddressChangedEvent $event)
		{
			Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($this->FarmID, "IP changed for server {$event->DBServer->serverId}. New IP address: {$event->NewIPAddress}"));
			
			$event->DBServer->remoteIp = $event->NewIPAddress;
			$event->DBServer->Save();
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{
			if ($event->DBServer->status != SERVER_STATUS::PENDING_TERMINATE && $event->DBServer->status != SERVER_STATUS::TERMINATED)
			{ 
				$event->DBServer->status = SERVER_STATUS::PENDING_TERMINATE;
				$event->DBServer->dateShutdownScheduled = date("Y-m-d H:i:s");
				$event->DBServer->Save();
			}
			
			if ($event->ForceTerminate)
			{ 				
				Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($this->FarmID, "Terminating instance '{$event->DBServer->serverId}' (O)."));
                PlatformFactory::NewPlatform($event->DBServer->platform)->TerminateServer($event->DBServer);
			}
		}
	}
?>
