<?php

	class Modules_Platforms_Ec2_Helpers_Elb
	{
		public static function farmValidateRoleSettings($settings, $rolename)
		{
			
		}
		
		public static function farmUpdateRoleSettings(DBFarmRole $DBFarmRole, $oldSettings, $newSettings)
		{

			try {
				$DBFarm = $DBFarmRole->GetFarmObject();
				
				$Client = Client::Load($DBFarm->ClientID);
				
				$AmazonELB = Scalr_Service_Cloud_Aws::newElb(
					$DBFarmRole->CloudLocation,
					$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
					$DBFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
				);
				
				// Load balancer settings
	        	if ($newSettings[DBFarmRole::SETTING_BALANCING_USE_ELB] == 1)
	        	{
	        		// Listeners
					$DBFarmRole->ClearSettings("lb.role.listener");
	        		$ELBListenersList = new ELBListenersList();
				    $li = 0;
				    foreach ($newSettings as $sk=>$sv)
				    {
				    	if (stristr($sk, "lb.role.listener"))
				    	{
				    		$li++;
				    		$listener_chunks = explode("#", $sv);
				    		$ELBListenersList->AddListener($listener_chunks[0], $listener_chunks[1], $listener_chunks[2], $listener_chunks[3]);
				    		$DBFarmRole->SetSetting("lb.role.listener.{$li}", $sv);
				    	}
				    }			
	        		
				    $avail_zones = array();
	        		$avail_zones_setting_hash = "";
				    foreach ($newSettings as $skey => $sval)
				    {
				    	if (preg_match("/^lb.avail_zone.(.*)?$/", $skey, $macthes)) {
				    		if ($macthes[1] != 'hash' && $macthes[1] != '.hash') {
				    			if ($sval == 1)
				    				array_push($avail_zones, $macthes[1]);
				    			
				    			$avail_zones_setting_hash .= "[{$macthes[1]}:{$sval}]";
				    		}
				    	}
				    }
				    
				    if (!$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME))
	        		{
	        			$elb_name = sprintf("scalr-%s-%s", $DBFarm->Hash, rand(100,999));
		        			
	        			//CREATE NEW ELB
	        			$elb_dns_name = $AmazonELB->CreateLoadBalancer($elb_name, $avail_zones, $ELBListenersList);
	        			
	        			if ($elb_dns_name)
	        			{
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME, $elb_dns_name);
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_NAME, $elb_name);
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_AZ_HASH, $avail_zones_setting_hash);
		        			
		        			$register_servers = true;
	        			}
	        		}
	        		
	        		if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME))
	        		{
						$ELBHealthCheckType = new ELBHealthCheckType(
							$newSettings[DBFarmRole::SETTING_BALANCING_HC_TARGET],
							$newSettings[DBFarmRole::SETTING_BALANCING_HC_HTH],
							$newSettings[DBFarmRole::SETTING_BALANCING_HC_INTERVAL],
							$newSettings[DBFarmRole::SETTING_BALANCING_HC_TIMEOUT],
							$newSettings[DBFarmRole::SETTING_BALANCING_HC_UTH]
						);
	        			
						$hash = md5(serialize($ELBHealthCheckType));
						
						if ($elb_name || ($hash != $DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_HC_HASH)))
						{
		        			//UPDATE CURRENT ELB
		        			$AmazonELB->ConfigureHealthCheck(
		        				$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
		        				$ELBHealthCheckType
		        			);
		        			
		        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HC_HASH, $hash);
						}
						
						// Configure AVAIL zones for the LB
						if (!$elb_name && $avail_zones_setting_hash != $DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_AZ_HASH))
						{
							$info = $AmazonELB->DescribeLoadBalancers(array($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME)));
							$elb = $info->DescribeLoadBalancersResult->LoadBalancerDescriptions->member;
							
							$c = (array)$elb->AvailabilityZones;
							
							if (!is_array($c['member']))
								$c_zones = array($c['member']);
							else
								$c_zones = $c['member'];
								
							$add_avail_zones = array();
							$rem_avail_zones = array();
							foreach ($newSettings as $skey => $sval)
						    {
						    	if (preg_match("/^lb.avail_zone.(.*)?$/", $skey, $m))
						    	{
									if ($sval == 1 && !in_array($m[1], $c_zones))
										array_push($add_avail_zones, $m[1]);
									
									if ($sval == 0 && in_array($m[1], $c_zones))
										array_push($rem_avail_zones, $m[1]);
						    	}
						    }
						    
						    if (count($add_avail_zones) > 0)
						    {
						    	$AmazonELB->EnableAvailabilityZonesForLoadBalancer(
									$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
									$add_avail_zones
								);
						    }
						    
							if (count($rem_avail_zones) > 0)
						    {
						    	$AmazonELB->DisableAvailabilityZonesForLoadBalancer(
									$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
									$rem_avail_zones
								);
						    }
							
							$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_AZ_HASH, $avail_zones_setting_hash);
						}
	        		}
	        		
	        		if ($register_servers)
	        		{
		        		$servers = $DBFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));
		        		$instances = array();
		        		foreach ($servers as $DBServer)
			        		$instances[] = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
		        			
			        	if (count($instances) > 0)
			        	{
			        		$AmazonELB->RegisterInstancesWithLoadBalancer(
			        			$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME),
			        			$instances
			        		);
			        	}
	        		}
	        	}
	        	else
	        	{
	        		if ($oldSettings[DBFarmRole::SETTING_BALANCING_HOSTNAME])
	        		{
	        			try {
	        				$AmazonELB->DeleteLoadBalancer($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_NAME));
	        			} catch (Exception $e) {}
	        					        			
	        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_NAME, "");
	        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME, "");
	        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB, "0");
	        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HC_HASH, "");
	        			$DBFarmRole->ClearSettings("lb.avail_zone");
	        			$DBFarmRole->ClearSettings("lb.healthcheck");
	        			$DBFarmRole->ClearSettings("lb.role.listener");
	        		}
	        	}
			} catch (Exception $e) {
				throw new Exception("Error with ELB on Role '{$DBFarmRole->GetRoleObject()->name}': {$e->getMessage()}");
			}
		}
	}

?>