<?php

	class ScalarizrEventObserver extends EventObserver
	{

		/**
		 * Constructor
		 *
		 */
		function __construct()
		{
			parent::__construct();
		}
		
		public function OnHostInit(HostInitEvent $event) {}
		
		public function OnHostUp(HostUpEvent $event) {}
		
		public function OnHostDown(HostDownEvent $event) {}
		
		public function OnRebundleComplete(RebundleCompleteEvent $event) {}
		
		public function OnRebundleFailed(RebundleFailedEvent $event) {}
		
		public function OnRebootBegin(RebootBeginEvent $event) {}
		
		public function OnRebootComplete(RebootCompleteEvent $event) {}
		
		public function OnFarmLaunched(FarmLaunchedEvent $event) {}
		
		public function OnFarmTerminated(FarmTerminatedEvent $event) 
		{
			$farmRoles = $event->DBFarm->GetFarmRoles();
			foreach ($farmRoles as $farmRole) {
				// For MySQL role need to reset slave2master flag 
				if ($farmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
					$farmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER, 0);
				} 
				
				if ($farmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL) || $farmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS)) {
					$farmRole->SetSetting(Scalr_Db_Msr::SLAVE_TO_MASTER, 0);
				}
			}
		}
		
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event) {}
		
		public function OnMysqlBackupComplete(MysqlBackupCompleteEvent $event) {}
		
		public function OnMysqlBackupFail(MysqlBackupFailEvent $event) {}
		
		public function OnIPAddressChanged(IPAddressChangedEvent $event) {}
		
		public function OnMySQLReplicationFail(MySQLReplicationFailEvent $event) {}
		
		public function OnMySQLReplicationRecovered(MySQLReplicationRecoveredEvent $event) {}
		
		public function OnEBSVolumeMounted(EBSVolumeMountedEvent $event) {}
		
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event) {}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event) {}
		
		public function OnDNSZoneUpdated(DNSZoneUpdatedEvent $event) {}
		
		public function OnRoleOptionChanged(RoleOptionChangedEvent $event) {}
		
		public function OnEBSVolumeAttached(EBSVolumeAttachedEvent $event) {}
	}
?>