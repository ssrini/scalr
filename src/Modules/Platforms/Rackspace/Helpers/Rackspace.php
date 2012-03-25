<?php

	class Modules_Platforms_Rackspace_Helpers_Rackspace
	{   		
		public static function farmSave(DBFarm $DBFarm, array $roles)
		{
			
		}
		
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			if (!$settings[DBFarmRole::SETTING_RS_FLAVOR_ID])
				throw new Exception(sprintf(_("Flavor for '%s' rackspace role should be selected on 'Placement and type' tab"), $rolename));
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{
			
		}
	}

?>