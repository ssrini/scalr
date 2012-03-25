<?php

	class BehaviorEventObserver extends EventObserver
	{
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event) 
		{
			$dbServer = $event->DBServer;
			
			if ($dbServer->farmRoleId != 0) {
				foreach (Scalr_Role_Behavior::getListForFarmRole($dbServer->GetFarmRoleObject()) as $bObj) {
					$bObj->onBeforeInstanceLaunch($dbServer);
				}
			}
		}
		
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{
			$dbFarm = DBFarm::LoadByID($this->FarmID);
			foreach ($dbFarm->GetFarmRoles() as $dbFarmRole) {
				foreach (Scalr_Role_Behavior::getListForFarmRole($dbFarmRole) as $bObj) {
					$bObj->onFarmTerminated($dbFarmRole);
				}
			}
		}
	}
?>