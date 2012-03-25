<?php
	class MessagingEventObserver extends EventObserver 
	{
		public $ObserverName = 'Messaging';
		
		function __construct()
		{
			parent::__construct();
		}

		public function OnServiceConfigurationPresetChanged(ServiceConfigurationPresetChangedEvent $event)
		{
			$farmRolesPresetInfo = $this->DB->GetAll("SELECT * FROM farm_role_service_config_presets WHERE
				preset_id = ? AND behavior = ?
			", array($event->ServiceConfiguration->id, $event->ServiceConfiguration->roleBehavior));
			if (count($farmRolesPresetInfo) > 0)
			{
				$msg = new Scalr_Messaging_Msg_UpdateServiceConfiguration(
					$event->ServiceConfiguration->roleBehavior,
					$event->ResetToDefaults,
					1
				);
				
				foreach ($farmRolesPresetInfo as $farmRole)
				{
					try
					{
						$dbFarmRole = DBFarmRole::LoadByID($farmRole['farm_roleid']);
						
						foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $dbServer)
						{
							if ($dbServer->IsSupported("0.6"))
								$dbServer->SendMessage($msg);
						}
					}
					catch(Exception $e){}
				}
			}
		}
		
		public function OnRoleOptionChanged(RoleOptionChangedEvent $event) 
		{	
			switch($event->OptionName)
			{
				case 'nginx_https_vhost_template':
				case 'nginx_https_host_template':
					
					$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
					foreach ((array)$servers as $DBServer)
					{
						if ($DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::APACHE) || $DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::NGINX))
							$DBServer->SendMessage(new Scalr_Messaging_Msg_VhostReconfigure());
					}
					
					break;
			}
		}
		
		/**
		 * @deprecated
		 */
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{
			$this->sendNewMasterUpMessage($event->DBServer, $event->SnapURL);
		}
		
		public function OnNewDbMsrMasterUp(NewDbMsrMasterUpEvent $event) 
		{
			$this->sendNewDbMsrMasterUpMessage($event->DBServer);
		}
		
		private function sendNewDbMsrMasterUpMessage(DBServer $newMasterServer)
		{
			$dbFarmRole = $newMasterServer->GetFarmRoleObject();
			$servers = $dbFarmRole->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			
			if ($newMasterServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL))
				$dbType = Scalr_Db_Msr::DB_TYPE_POSTGRESQL;
			elseif ($newMasterServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS))
				$dbType = Scalr_Db_Msr::DB_TYPE_REDIS;
	
			$props = Scalr_Db_Msr_Info::init($dbFarmRole, $newMasterServer, $dbType)->getMessageProperties();
			
			foreach ($servers as $dbServer) {
				
				$msg = new Scalr_Messaging_Msg_DbMsr_NewMasterUp(
					$dbFarmRole->GetRoleObject()->getBehaviors(),
					$dbFarmRole->GetRoleObject()->name,
					$newMasterServer->localIp,
					$newMasterServer->remoteIp,
					$dbType
				);
				$msg->{$dbType} = new stdClass();
				$msg->{$dbType}->snapshotConfig = $props->snapshotConfig;
				
				$dbServer->SendMessage($msg);
			}
		}
		
		/**
		 * @deprecated
		 */
		private function sendNewMasterUpMessage($newMasterServer, $snapURL = "") {
			$dbFarmRole = $newMasterServer->GetFarmRoleObject();
			$servers = $dbFarmRole->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			
			foreach ($servers as $DBServer) {
				
				$msg = new Scalr_Messaging_Msg_Mysql_NewMasterUp(
					$dbFarmRole->GetRoleObject()->getBehaviors(),
					$dbFarmRole->GetRoleObject()->name,
					$newMasterServer->localIp,
					$newMasterServer->remoteIp,
					
					/** @deprecated */
					$snapURL
				);
				
				$msg->replPassword = $dbFarmRole->GetSetting(DbFarmRole::SETTING_MYSQL_REPL_PASSWORD);
				$msg->rootPassword = $dbFarmRole->GetSetting(DbFarmRole::SETTING_MYSQL_ROOT_PASSWORD);
				if ($newMasterServer->platform == SERVER_PLATFORMS::RACKSPACE || $newMasterServer->platform == SERVER_PLATFORMS::OPENSTACK) {
					$msg->logPos = $dbFarmRole->GetSetting(DbFarmRole::SETTING_MYSQL_LOG_POS);
					$msg->logFile = $dbFarmRole->GetSetting(DbFarmRole::SETTING_MYSQL_LOG_FILE);
					
					$snapshot = Scalr_Storage_Snapshot::init();
					
					try {
						$snapshot->loadById($dbFarmRole->GetSetting(DbFarmRole::SETTING_MYSQL_SCALR_SNAPSHOT_ID));
						$msg->snapshotConfig = $snapshot->getConfig();	
					} catch (Exception $e) {
						$this->Logger->error(new FarmLogMessage($event->DBServer->farmId, "Cannot get snaphotConfig for newMysqlMasterUp message: {$e->getMessage()}"));
					}
				}
				
				$DBServer->SendMessage($msg);
			}
		}
		
		public function OnHostInit(HostInitEvent $event)
		{
			$msg = new Scalr_Messaging_Msg_HostInitResponse(
				$event->DBServer->GetFarmObject()->GetSetting(DBFarm::SETTING_CRYPTO_KEY),
				$event->DBServer->index
			);
			
			$dbServer = $event->DBServer;
			$dbFarmRole = $dbServer->GetFarmRoleObject();
			
			if (!$event->DBServer->IsSupported("0.5")) {
				if ($event->DBServer->platform == SERVER_PLATFORMS::EC2) {
					$msg->awsAccountId = $event->DBServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID);
				}
			}
			
			if ($dbFarmRole) {
				foreach (Scalr_Role_Behavior::getListForFarmRole($dbFarmRole) as $behavior)
					$msg = $behavior->extendMessage($msg, $dbServer);
			}
			
			/**
			 * TODO: Move everything to Scalr_Db_Msr_* 
			 */
			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL))
			{
				$isMaster = (int)$dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER);

				$msg->mysql = (object)array(
					"replicationMaster" => $isMaster,
					"rootPassword" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD),
					"replPassword" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_REPL_PASSWORD),
					"statPassword" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_STAT_PASSWORD),
					"logFile" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LOG_FILE),
					"logPos" => $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LOG_POS)
				);
				
				if ($event->DBServer->IsSupported("0.7"))
				{
					if ($dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SCALR_VOLUME_ID) && $isMaster)
					{
						try {
							$volume = Scalr_Storage_Volume::init()->loadById(
								$dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SCALR_VOLUME_ID)
							);
							
							$msg->mysql->volumeConfig = $volume->getConfig();
						} catch (Exception $e) {
						
						}
					}
					
					/*** 
					 * For Rackspace we ALWAYS need snapsjot_config for mysql
					 * ***/
					if ($dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SCALR_SNAPSHOT_ID))
					{
						try {
							$snapshotConfig = Scalr_Storage_Snapshot::init()->loadById(
								$dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SCALR_SNAPSHOT_ID)
							);
							
							$msg->mysql->snapshotConfig = $snapshotConfig->getConfig();
						} catch (Exception $e) {
							$this->Logger->error(new FarmLogMessage($event->DBServer->farmId, "Cannot get snaphotConfig for hostInit message: {$e->getMessage()}"));
						}
					}
					
					if (!$msg->mysql->snapshotConfig && $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID))
					{
						$msg->mysql->snapshotConfig = new stdClass();
						$msg->mysql->snapshotConfig->type = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE);
						$msg->mysql->snapshotConfig->id = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID);
					}
					
					if ($isMaster && !$msg->mysql->volumeConfig)
					{
						$msg->mysql->volumeConfig = new stdClass();
						$msg->mysql->volumeConfig->type = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE);
						
						if (!$dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID))
						{
							if (in_array($dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE), array(MYSQL_STORAGE_ENGINE::EBS, MYSQL_STORAGE_ENGINE::CSVOL))) {
								$msg->mysql->volumeConfig->size = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_EBS_VOLUME_SIZE);
							}
							elseif ($dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE) == MYSQL_STORAGE_ENGINE::EPH) {
								$msg->mysql->volumeConfig->snap_backend = "cf://mysql-data-bundle/scalr-{$dbFarmRole->GetFarmObject()->Hash}";
								$msg->mysql->volumeConfig->vg = 'mysql';
								$msg->mysql->volumeConfig->disk = new stdClass();
								$msg->mysql->volumeConfig->disk->type = 'loop';
								$msg->mysql->volumeConfig->disk->size = '75%root';
							}
						}
						else {
							$msg->mysql->volumeConfig->id = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID);
						}
					}
				}
				else {
					
					if ($isMaster)
						$msg->mysql->volumeId = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID);
					
					$msg->mysql->snapshotId = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID);	
				}
			}			
			
			
			// Create ssh keypair for rackspace
			if ($event->DBServer->IsSupported("0.7"))
			{
				if ($event->DBServer->platform == SERVER_PLATFORMS::RACKSPACE || 
					$event->DBServer->platform == SERVER_PLATFORMS::NIMBULA || 
					$event->DBServer->platform == SERVER_PLATFORMS::OPENSTACK ||
					$event->DBServer->platform == SERVER_PLATFORMS::CLOUDSTACK)
				{
					$sshKey = Scalr_SshKey::init();
					
					if (!$sshKey->loadGlobalByFarmId(
						$event->DBServer->farmId, 
						$event->DBServer->GetFarmRoleObject()->CloudLocation
					)) {
						$key_name = "FARM-{$event->DBServer->farmId}";
						
						$sshKey->generateKeypair();
						
						$sshKey->farmId = $event->DBServer->farmId;
						$sshKey->clientId = $event->DBServer->clientId;
						$sshKey->envId = $event->DBServer->envId;
						$sshKey->type = Scalr_SshKey::TYPE_GLOBAL;
						$sshKey->platform = $event->DBServer->platform;
						$sshKey->cloudLocation = $event->DBServer->GetFarmRoleObject()->CloudLocation;
						$sshKey->cloudKeyName = $key_name;
						$sshKey->platform = $event->DBServer->platform;
						
						$sshKey->save();
					}
					
					$sshKeysMsg = new Scalr_Messaging_Msg_UpdateSshAuthorizedKeys(array($sshKey->getPublic()), array());
					$event->DBServer->SendMessage($sshKeysMsg);
				}
			}
			
			// Send broadcast HostInit
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ((array)$servers as $DBServer)
			{
				$hiMsg = new Scalr_Messaging_Msg_HostInit(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp
				);
				
				$hiMsg->serverIndex = $event->DBServer->index;
				
				$hiMsg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				if ($event->DBServer->farmRoleId != 0) {
					foreach (Scalr_Role_Behavior::getListForFarmRole($event->DBServer->GetFarmRoleObject()) as $behavior)
						$hiMsg = $behavior->extendMessage($hiMsg, $event->DBServer);
				}
				
				$DBServer->SendMessage($hiMsg, false, true);
			}
			
			// Send HostInitResponse to target server
			$event->DBServer->SendMessage($msg);
		}
		
		private function getScripts(Event $event, DBServer $eventServer, DBServer $targetServer) 
		{
			$retval = array();
			
			try {
				$scripts = Scalr_Scripting_Manager::getEventScriptList($event, $eventServer, $targetServer);
				if (count($scripts) > 0)
				{
					foreach ($scripts as $script)
					{						
						$itm = new stdClass();
						$itm->asynchronous = ($script['issync'] == 1) ? '0' : '1';
						$itm->timeout = $script['timeout'];
						$itm->name = $script['name'];
						$itm->body = $script['body'];
						
						$retval[] = $itm;
					}
				}
			} catch (Exception $e) {}
			
			return $retval;
		}
		
		public function OnBeforeHostUp(BeforeHostUpEvent $event) {
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ((array)$servers as $DBServer)
			{
				if (!$DBServer->IsSupported("0.5"))
				 	continue;
				
				$msg = new Scalr_Messaging_Msg_BeforeHostUp(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp,
					$event->DBServer->serverId
				);
				
				$msg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				$delayed = !($DBServer->serverId == $event->DBServer->serverId);
				
				$DBServer->SendMessage($msg, false, $delayed);
			}
		}
		
		public function OnEBSVolumeAttached(EBSVolumeAttachedEvent $event)
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ((array)$servers as $DBServer)
			{
				$msg = new Scalr_Messaging_Msg_BlockDeviceAttached(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp,
					$event->VolumeID,
					$event->DeviceName
				);
				
				$msg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				$DBServer->SendMessage($msg);
			}
		}
		
		public function OnEBSVolumeMounted(EBSVolumeMountedEvent $event)
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ((array)$servers as $DBServer)
			{
				$msg = new Scalr_Messaging_Msg_BlockDeviceMounted(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp,
					$event->VolumeID,
					$event->DeviceName,
					$event->Mountpoint,
					false,
					''
				);
				
				$msg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				$DBServer->SendMessage($msg);
			}
		}
		
		public function OnRebootComplete(RebootCompleteEvent $event) 
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ((array)$servers as $DBServer)
			{
				$msg = new Scalr_Messaging_Msg_RebootFinish(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp
				);
				
				$msg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				$DBServer->SendMessage($msg);
			}
		}
		
		public function OnHostUp(HostUpEvent $event)
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ((array)$servers as $DBServer)
			{
				$msg = new Scalr_Messaging_Msg_HostUp(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp
				);
				
				$msg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				$msg->serverIndex = $event->DBServer->index;
				$msg->farmRoleId = $event->DBServer->farmRoleId;
				
				if ($event->DBServer->farmRoleId != 0) {
					foreach (Scalr_Role_Behavior::getListForFarmRole($event->DBServer->GetFarmRoleObject()) as $behavior)
						$msg = $behavior->extendMessage($msg, $event->DBServer);
				}
				
				$DBServer->SendMessage($msg, false, true);
			}
			
			if ($event->DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) == 1 && $event->DBServer->IsSupported("0.7")) {
				$this->sendNewMasterUpMessage($event->DBServer, "");
			}
			
			if ($event->DBServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) == 1) {
				$this->sendNewDbMsrMasterUpMessage($event->DBServer);
			}
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, SERVER_STATUS::PENDING_TERMINATE)));		
			foreach ($servers as $DBServer)
			{									
				$msg = new Scalr_Messaging_Msg_BeforeHostTerminate(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name,
					$event->DBServer->localIp,
					$event->DBServer->remoteIp
				);
				
				$msg->farmRoleId = $event->DBServer->farmRoleId;
				$msg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				if ($event->DBServer->farmRoleId != 0) {
					foreach (Scalr_Role_Behavior::getListForFarmRole($event->DBServer->GetFarmRoleObject()) as $behavior)
						$msg = $behavior->extendMessage($msg, $event->DBServer);
				}
				
				$DBServer->SendMessage($msg, false, true);
			}
		}
		
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event)
		{
			$servers = DBFarm::LoadByID($this->FarmID)->GetServersByFilter(array('status' => array(SERVER_STATUS::RUNNING)));		
			foreach ($servers as $DBServer)
			{									
				$msg = new Scalr_Messaging_Msg_BeforeInstanceLaunch(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(),
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name
				);
				
				$msg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				$DBServer->SendMessage($msg, false, true);
			}
		}
		
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBServer->IsRebooting() == 1)
				return;
			
			if (!$this->FarmID)
				return;
				
			$dbFarm = DBFarm::LoadByID($this->FarmID);
			$servers = $dbFarm->GetServersByFilter(array('status' => array(SERVER_STATUS::RUNNING)));
			try
			{
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
				$is_synchronize = ($DBFarmRole->NewRoleID) ? true : false;
			}
			catch(Exception $e)
			{
				$is_synchronize = false;
			}

			try
			{
				$DBRole = DBRole::loadById($event->DBServer->roleId);
			}
			catch(Exception $e){}

			$first_in_role_handled = false;
			$first_in_role_server = null;
			foreach ($servers as $DBServer)
			{
				if (!($DBServer instanceof DBServer))
					continue;
				
				$isfirstinrole = '0';
				
				$isMaster = $event->DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) || $event->DBServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER);
				
				if ($isMaster && !$first_in_role_handled) {
					if (!$is_synchronize && $DBServer->farmRoleId == $event->DBServer->farmRoleId) {
						if (DBRole::loadById($DBServer->roleId)->hasBehavior(ROLE_BEHAVIORS::MYSQL) || 
							DBRole::loadById($DBServer->roleId)->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL) || 
							DBRole::loadById($DBServer->roleId)->hasBehavior(ROLE_BEHAVIORS::REDIS))
						{
							$first_in_role_handled = true;
							$first_in_role_server = $DBServer;
							$isfirstinrole = '1';
						}
					}	
				}
				
				$msg = new Scalr_Messaging_Msg_HostDown(
					($DBRole) ? $DBRole->getBehaviors() : '*Unknown*',
					($DBRole) ? $DBRole->name : '*Unknown*',
					$event->DBServer->localIp,
					$event->DBServer->remoteIp
				);
				$msg->isFirstInRole = $isfirstinrole;
				$msg->serverIndex = $event->DBServer->index;
				$msg->farmRoleId = $event->DBServer->farmRoleId;
				
				$msg->scripts = $this->getScripts($event, $event->DBServer, $DBServer);
				
				if ($event->DBServer->farmRoleId != 0) {
					foreach (Scalr_Role_Behavior::getListForRole(DBRole::loadById($event->DBServer->roleId)) as $behavior)
						$msg = $behavior->extendMessage($msg, $event->DBServer);
				}
				
				$DBServer->SendMessage($msg, false, true);
			}
				
			try {
				$event->DBServer->GetFarmRoleObject();
			} catch (Exception $e) {
				return;
			}
			
			if ($event->DBServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) ||
				$event->DBServer->GetFarmRoleObject()->GetSetting(Scalr_Db_Msr::SLAVE_TO_MASTER)
			) {
				//Check if master already running: do not send promote_to_master
				
				$msg = new Scalr_Messaging_Msg_DbMsr_PromoteToMaster();
				
				if ($event->DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL)) 
					$msg->dbType = Scalr_Db_Msr::DB_TYPE_POSTGRESQL;
				elseif ($event->DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS)) 
					$msg->dbType = Scalr_Db_Msr::DB_TYPE_REDIS;
				
				if (in_array($event->DBServer->platform, array(SERVER_PLATFORMS::EC2, SERVER_PLATFORMS::CLOUDSTACK))) {
					try {
						$volume = Scalr_Storage_Volume::init()->loadById(
							$event->DBServer->GetFarmRoleObject()->GetSetting(Scalr_Db_Msr::VOLUME_ID)
						);
						
						$msg->volumeConfig = $volume->getConfig();
					} catch (Exception $e) {
						$this->Logger->error(new FarmLogMessage($event->DBServer->farmId, "Cannot create volumeConfig for PromoteToMaster message: {$e->getMessage()}"));
					}
				}
				
				// Send Mysql_PromoteToMaster to the first server in the same avail zone as old master (if exists)
				// Otherwise send to first in role
				$platform = $event->DBServer->platform; 
				if ($platform == SERVER_PLATFORMS::EC2) {
					$availZone = $event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
				}	
				
				foreach ($servers as $DBServer) {
					
					if ($DBServer->serverId == $event->DBServer->serverId)
						continue;
						
					if ($DBServer->farmRoleId != $event->DBServer->farmRoleId)
						continue;
					
					if (($platform == SERVER_PLATFORMS::EC2 && $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE) == $availZone) || $platform != SERVER_PLATFORMS::EC2) {
						$DBFarmRole->SetSetting(Scalr_Db_Msr::SLAVE_TO_MASTER, 1);
						$DBServer->SetProperty(Scalr_Db_Msr::REPLICATION_MASTER, 1);
						$DBServer->SendMessage($msg);
						return;
					}
				}
				
				if ($first_in_role_server) {
					$DBFarmRole->SetSetting(Scalr_Db_Msr::SLAVE_TO_MASTER, 1);
					$first_in_role_server->SetProperty(Scalr_Db_Msr::REPLICATION_MASTER, 1);
					$first_in_role_server->SendMessage($msg);
				}
			}
			
			//LEGACY MYSQL CODE:
			if ($event->DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
				// If EC2 master down			
				if (($event->DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER)) &&
					$event->DBServer->IsSupported("0.5") &&
					$DBFarmRole)
				{
					$master = $dbFarm->GetMySQLInstances(true);
					if($master[0])
						return;
					
					$msg = new Scalr_Messaging_Msg_Mysql_PromoteToMaster(
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD),
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_REPL_PASSWORD),
						$DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_STAT_PASSWORD)
					);
					
					if ($event->DBServer->IsSupported("0.7"))
					{
						if (in_array($event->DBServer->platform, array(SERVER_PLATFORMS::EC2, SERVER_PLATFORMS::CLOUDSTACK))) {
							try {
								$volume = Scalr_Storage_Volume::init()->loadById(
									$DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_SCALR_VOLUME_ID)
								);
								
								$msg->volumeConfig = $volume->getConfig();
							} catch (Exception $e) {
								$this->Logger->error(new FarmLogMessage($event->DBServer->farmId, "Cannot create volumeConfig for PromoteToMaster message: {$e->getMessage()}"));
							}
						}
					}
					elseif ($event->DBServer->platform == SERVER_PLATFORMS::EC2)
						$msg->volumeId = $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID);
					
					// Send Mysql_PromoteToMaster to the first server in the same avail zone as old master (if exists)
					// Otherwise send to first in role
					$platform = $event->DBServer->platform; 
					if ($platform == SERVER_PLATFORMS::EC2) {
						$availZone = $event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
					}	
					
					foreach ($servers as $DBServer) {
						
						if ($DBServer->serverId == $event->DBServer->serverId)
							continue;
						
						if (($platform == SERVER_PLATFORMS::EC2 && $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE) == $availZone) || $platform != SERVER_PLATFORMS::EC2) {
							if (DBRole::loadById($DBServer->roleId)->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER, 1);
								$DBServer->SetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER, 1);
								$DBServer->SendMessage($msg);
								return;
							}
						}
					}
					
					if ($first_in_role_server) {
						$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER, 1);
						$first_in_role_server->SetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER, 1);
						$first_in_role_server->SendMessage($msg);
					}
				}
			}
		}
	}
?>
