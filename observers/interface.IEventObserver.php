<?php

	interface IEventObserver
	{
		public function OnHostInit(HostInitEvent $event);
		
		public function OnHostUp(HostUpEvent $event);
		
		public function OnHostDown(HostDownEvent $event);
		
		public function OnHostCrash(HostCrashEvent $event);
						
		public function OnRebundleComplete(RebundleCompleteEvent $event);
		
		public function OnRebundleFailed(RebundleFailedEvent $event);
		
		public function OnRebootBegin(RebootBeginEvent $event);
		
		public function OnRebootComplete(RebootCompleteEvent $event);
		
		public function OnFarmLaunched(FarmLaunchedEvent $event);
		
		public function OnFarmTerminated(FarmTerminatedEvent $event);
		
		/**
		 * @deprecated
		 */
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event);
		
		public function OnNewDbMsrMasterUp(NewDbMsrMasterUpEvent $event);
		
		public function OnMysqlBackupComplete(MysqlBackupCompleteEvent $event);
		
		public function OnMysqlBackupFail(MysqlBackupFailEvent $event);
		
		public function OnIPAddressChanged(IPAddressChangedEvent $event);
		
		public function OnMySQLReplicationFail(MySQLReplicationFailEvent $event);
		
		public function OnMySQLReplicationRecovered(MySQLReplicationRecoveredEvent $event);
		
		public function OnDNSZoneUpdated(DNSZoneUpdatedEvent $event);
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event);
		
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event);
		
		public function OnEBSVolumeMounted(EBSVolumeMountedEvent $event);
		
		public function OnBeforeHostUp(BeforeHostUpEvent $event);
	}
?>