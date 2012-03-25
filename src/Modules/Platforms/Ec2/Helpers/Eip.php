<?php

	class Modules_Platforms_Ec2_Helpers_Eip
	{
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{
			$db = Core::GetDBInstance();
			
			$DBFarm = $DBFarmRole->GetFarmObject();
			
			if (!$oldSettings[DBFarmRole::SETTING_AWS_USE_ELASIC_IPS] && $newSettings[DBFarmRole::SETTING_AWS_USE_ELASIC_IPS])
			{
				$servers = $DBFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));
				
				if (count($servers) == 0)
					return;
				
				$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
					$DBFarmRole->CloudLocation, 
					$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY), 
					$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
				);
				
				foreach ($servers as $DBServer)
				{
					$address = $AmazonEC2Client->AllocateAddress();
					
					$db->Execute("INSERT INTO elastic_ips SET env_id=?, farmid=?, farm_roleid=?, ipaddress=?, state='0', instance_id='', clientid=?, instance_index=?",
						array($DBServer->envId, $DBServer->farmId, $DBServer->farmRoleId, $address->publicIp, $DBServer->clientId, $DBServer->index)
					);
					
					Logger::getLogger(__CLASS__)->info(sprintf(_("Allocated new IP: %s"), $address->publicIp));
					
					// Waiting...
					Logger::getLogger(__CLASS__)->debug(_("Waiting 5 seconds..."));
					sleep(5);
					
					$assign_retries = 1;
					while (true)
					{
						try
						{
							// Associate elastic ip address with instance
							$AmazonEC2Client->AssociateAddress(
								$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID), 
								$address->publicIp
							);
						}
						catch(Exception $e)
						{
							if (!stristr($e->getMessage(), "does not belong to you") || $assign_retries == 3)
								throw new Exception($e->getMessage());
							else
							{
								// Waiting...
								Logger::getLogger(__CLASS__)->debug(_("Waiting 2 seconds..."));
								sleep(2);
								$assign_retries++;
								continue;
							}
						}
						
						break;
					}

					Logger::getLogger(__CLASS__)->info(sprintf(_("IP: %s assigned to instance '%s'"), $address->publicIp, $DBServer->serverId));
					
					// Update leastic IPs table
					$db->Execute("UPDATE elastic_ips SET state='1', server_id=? WHERE ipaddress=?",
						array($DBServer->serverId, $address->publicIp)
					);
					
					Scalr::FireEvent($DBFarmRole->FarmID, new IPAddressChangedEvent($DBServer, $address->publicIp));
				}
			}
		}
	}

?>