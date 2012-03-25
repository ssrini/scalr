<?php
	
	class ScalrAPI_2_2_0 extends ScalrAPI_2_1_0
	{
		public function FarmGetDetails($FarmID)
		{
			$response = parent::FarmGetDetails($FarmID);
						
			foreach ($response->FarmRoleSet->Item as &$item)
			{
				$dbFarmRole = DBFarmRole::LoadByID($item->ID);
				
				$item->{"CloudLocation"} = $dbFarmRole->CloudLocation;
				
				if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL))
				{
					$item->{"MySQLProperties"} = new stdClass();
					$item->{"MySQLProperties"}->{"LastBackupTime"} = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LAST_BCP_TS);
					$item->{"MySQLProperties"}->{"LastBundleTime"} = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LAST_BUNDLE_TS);
					$item->{"MySQLProperties"}->{"IsBackupRunning"} = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING);
					$item->{"MySQLProperties"}->{"IsBundleRunning"} = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING);
				}
			}
			
			return $response;
		}		
	}
?>