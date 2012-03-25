<?php

	class Modules_Platforms_Ec2_Helpers_Ec2
	{   		
		public static function farmSave(DBFarm $DBFarm, array $roles)
		{
			$buckets   = array();
			foreach ($roles as $DBFarmRole) {	
				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_S3_BUCKET))
					$buckets[$DBFarmRole->CloudLocation] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_S3_BUCKET);
			}
			
			foreach ($roles as $DBFarmRole)
			{
				if ($DBFarmRole->Platform != SERVER_PLATFORMS::EC2)
					continue;
				
				$location = $DBFarmRole->CloudLocation;
				
				$sshKey = Scalr_Model::init(Scalr_Model::SSH_KEY);
				if (!$sshKey->loadGlobalByFarmId($DBFarm->ID, $location))
				{
					$key_name = "FARM-{$DBFarm->ID}";
						
					$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
						$location, 
						$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY), 
						$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
					);
					
					$result = $AmazonEC2Client->CreateKeyPair($key_name);
					if ($result->keyMaterial)
					{	
						$sshKey->farmId = $DBFarm->ID;
						$sshKey->clientId = $DBFarm->ClientID;
						$sshKey->envId = $DBFarm->EnvID;
						$sshKey->type = Scalr_SshKey::TYPE_GLOBAL;
						$sshKey->cloudLocation = $location;
						$sshKey->cloudKeyName = $key_name;
						$sshKey->platform = SERVER_PLATFORMS::EC2;
						
						$sshKey->setPrivate($result->keyMaterial);
						
						$sshKey->save();
		            }
				}
			
				try {
					if (!$DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_S3_BUCKET))
					{
						if (!$buckets[$location])
						{
							$aws_account_id = $DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID);
							
							$bucket_name = "farm-{$DBFarm->Hash}-{$aws_account_id}-{$location}";
			        
							//
			                // Create S3 Bucket (For MySQL, BackUs, etc.)
			                //
			                $AmazonS3 = new AmazonS3(
			                	$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			                	$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
			                );
			                $buckets = $AmazonS3->ListBuckets();
			                $create_bucket = true;
			                foreach ($buckets as $bucket)
			                {
			                	if ($bucket->Name == $bucket_name)
			                   	{
									$create_bucket = false;
									$buckets[$location] = $bucket_name;
									break;
								}
							}
			                    
			                if ($create_bucket)
			                {
			                	if ($AmazonS3->CreateBucket($bucket_name, $location))
									$buckets[$location] = $bucket_name;
			                }
						}
						
						$DBFarmRole->SetSetting(
							DBFarmRole::SETTING_AWS_S3_BUCKET, 
							$buckets[$location]
						);
					}
				} catch (Exception $e) {
					throw new Exception("Amazon S3: {$e->getMessage()}");
				}
			}
		}
		
		
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{
			
		}
		
		/**
		* // Creates a list of Amazon's security groups  
		* 
		*/
		public static function loadSecurityGroups()
		{  
		 	/*
						 	 
		       			
			$securityGroupSet = $AmazonEC2Client->DescribeSecurityGroups();
			
			$i = 0;			
			foreach($securityGroupSet->securityGroupInfo->item as $sgroup)			
			{  	 				
				$securityGroupNamesSet[$i]['name'] = (string)$sgroup->groupName;	
			    $i++;
			}
			
			return $securityGroupNamesSet;
			*/		
		}
	}

?>