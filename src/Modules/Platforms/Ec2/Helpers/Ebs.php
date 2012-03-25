<?php

	class Modules_Platforms_Ec2_Helpers_Ebs
	{
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			if ($settings[DBFarmRole::SETTING_AWS_USE_EBS] && $settings[DBFarmRole::SETTING_AWS_AVAIL_ZONE] == "")
				throw new Exception(sprintf(_("EBS cannot be enabled if Placement is set to 'Choose randomly'. Please select a different option for 'Placement' parameter for role '%s'."), $rolename));
				
			if ($settings[DBFarmRole::SETTING_AWS_USE_EBS])
			{
				if (!$settings[DBFarmRole::SETTING_AWS_EBS_SIZE] && !$settings[DBFarmRole::SETTING_AWS_EBS_SNAPID])
					throw new Exception(sprintf(_("EBS volume size or/and snapshot for role '%s' should be specified"), $rolename));
				
				if (!$settings[DBFarmRole::SETTING_AWS_EBS_SNAPID])
				{
					if ($settings[DBFarmRole::SETTING_AWS_EBS_SIZE] < 1 || $settings[DBFarmRole::SETTING_AWS_EBS_SIZE] > 1000)
						throw new Exception(sprintf(_("EBS volume size for role '%s' must be between 1 and 1000 GB"), $rolename));
				}
			}
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{
			$db = Core::GetDBInstance();
			
			if (!$newSettings[DBFarmRole::SETTING_AWS_USE_EBS] && $oldSettings[DBFarmRole::SETTING_AWS_USE_EBS])
			{
				$db->Execute("DELETE FROM ec2_ebs WHERE farm_roleid = ? AND ismanual='0'", array(
					$DBFarmRole->ID
				));
				
				//TODO: Remove Volume?
			}
			
			$DBFarm = $DBFarmRole->GetFarmObject();
			
			if ($newSettings[DBFarmRole::SETTING_AWS_USE_EBS] && !$oldSettings[DBFarmRole::SETTING_AWS_USE_EBS])
			{
				$servers = $DBFarmRole->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
				
				foreach ($servers as $DBServer)
				{
					if (!$db->GetRow("SELECT id FROM ec2_ebs WHERE server_id=? AND ismanual='0'", array($DBServer->serverId)))
					{
						$DBEBSVolume = new DBEBSVolume();
						$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::CREATING;
						$DBEBSVolume->isManual = false;
						$DBEBSVolume->ec2AvailZone = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_AVAIL_ZONE);
						$DBEBSVolume->ec2Region = $DBFarmRole->CloudLocation;
						$DBEBSVolume->farmId = $DBFarmRole->FarmID;
						$DBEBSVolume->farmRoleId = $DBFarmRole->ID;
						$DBEBSVolume->serverId = $DBServer->serverId;
						$DBEBSVolume->serverIndex = $DBServer->index;
						$DBEBSVolume->size = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SIZE);
						$DBEBSVolume->snapId = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SNAPID);
						$DBEBSVolume->mount = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_MOUNT);
						$DBEBSVolume->mountPoint = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_MOUNTPOINT);
						$DBEBSVolume->mountStatus = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_MOUNT)) ? EC2_EBS_MOUNT_STATUS::AWAITING_ATTACHMENT : EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
						$DBEBSVolume->clientId = $DBFarm->ClientID;
						$DBEBSVolume->envId = $DBFarm->EnvID;
						
						
						$DBEBSVolume->Save();
					}
				}
				
				if ($newSettings[DBFarmRole::SETTING_AWS_EBS_MOUNTPOINT] != $oldSettings[DBFarmRole::SETTING_AWS_EBS_MOUNTPOINT]) {
					$db->Execute("UPDATE ec2_ebs SET mountpoint=? WHERE farm_roleid=? AND ismanual='0'", 
						array($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_MOUNTPOINT), $DBFarmRole->ID)
					);
				}
			}
		}
	}

?>