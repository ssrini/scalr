<?php

	class Modules_Platforms_Rds_Helpers_Rds
	{
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			if ($settings[DBFarmRole::SETTING_RDS_STORAGE] < 5 || $settings[DBFarmRole::SETTING_RDS_STORAGE] > 1024)
				throw new Exception(sprintf(_("RDS storage for role %s should be between 5 and 1024 GB"), $rolename));

			if ($settings[DBFarmRole::SETTING_RDS_PORT] < 1150 || $settings[DBFarmRole::SETTING_RDS_STORAGE] > 65535)
				throw new Exception(sprintf(_("RDS port for role %s should be between 1150 and 65535"), $rolename));
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{
			// Modify instance if settings updated
			//TODO:
		}
	}

?>