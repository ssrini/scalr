<?php

	class Modules_Platforms_Eucalyptus_Helpers_Eucalyptus
	{   		
		public static function farmSave(DBFarm $DBFarm, array $roles)
		{
			foreach ($roles as $DBFarmRole)
			{
				if ($DBFarmRole->Platform != SERVER_PLATFORMS::EUCALYPTUS)
					continue;
				
				$location = $DBFarmRole->CloudLocation;
				
				$sshKey = Scalr_Model::init(Scalr_Model::SSH_KEY);
				if (!$sshKey->loadGlobalByFarmId($DBFarm->ID, $location))
				{
					$key_name = "FARM-{$DBFarm->ID}";
						
					$eucaClient = Scalr_Service_Cloud_Eucalyptus::newCloud(
						$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Eucalyptus::SECRET_KEY, true, $location),
						$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Eucalyptus::ACCESS_KEY, true, $location),
						$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Eucalyptus::EC2_URL, true, $location)
					);
					
					$result = $eucaClient->CreateKeyPair($key_name);
					if ($result->keyMaterial)
					{	
						$sshKey->farmId = $DBFarm->ID;
						$sshKey->clientId = $DBFarm->ClientID;
						$sshKey->envId = $DBFarm->EnvID;
						$sshKey->type = Scalr_SshKey::TYPE_GLOBAL;
						$sshKey->cloudLocation = $location;
						$sshKey->cloudKeyName = $key_name;
						$sshKey->platform = SERVER_PLATFORMS::EUCALYPTUS;
						
						$sshKey->setPrivate($result->keyMaterial);
						
						$sshKey->save();
		            }
				}
			}
		}
		
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{
			
		}
	}

?>