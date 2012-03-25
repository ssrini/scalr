<?php
	class Modules_Platforms_Ec2_Observers_Eip extends EventObserver
	{
		public $ObserverName = 'Elastic IPs';
		
		function __construct()
		{
			parent::__construct();
		}

		/**
		 * Return new instance of AmazonEC2 object
		 *
		 * @return AmazonEC2
		 */
		private function GetAmazonEC2ClientObject(Scalr_Environment $environment, $region)
		{
	    	// Return new instance of AmazonEC2 object
			$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
				$region, 
				$environment->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY), 
				$environment->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
			);
			
			return $AmazonEC2Client;
		}
		
		/**
		 * Release used elastic IPs if farm terminated
		 *
		 * @param FarmTerminatedEvent $event
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{
			$this->Logger->info(sprintf(_("Keep elastic IPs: %s"), $event->KeepElasticIPs));
			
			if ($event->KeepElasticIPs == 1)
				return;
			
			$DBFarm = DBFarm::LoadByID($this->FarmID);
			
			$ips = $this->DB->GetAll("SELECT * FROM elastic_ips WHERE farmid=?", array($this->FarmID));
			if (count($ips) > 0)
			{
				foreach ($ips as $ip)
				{
					try
					{
						$DBFarmRole = DBFarmRole::LoadByID($ip['farm_roleid']);
						$EC2Client = $this->GetAmazonEC2ClientObject($DBFarm->GetEnvironmentObject(), $DBFarmRole->CloudLocation);
						$EC2Client->ReleaseAddress($ip["ipaddress"]);
					}
					catch(Exception $e)
					{						
						if (!stristr($e->getMessage(), "does not belong to you"))
						{
							$this->Logger->error(sprintf(_("Cannot release elastic IP %s from farm %s: %s"),
								$ip['ipaddress'], $DBFarm->Name, $e->getMessage()
							));
							continue;
						}
					}
					
					$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
				}
			}
		}
		
		/**
		 * Check Elastic IP availability
		 * 
		 */
		private function CheckElasticIP($ipaddress, $EC2Client)
		{
			$this->Logger->debug(sprintf(_("Checking IP: %s"), $ipaddress));
			
			$DescribeAddressesType = new DescribeAddressesType();
			$DescribeAddressesType->AddAddress($ipaddress);
			
			try
			{
				$info = $EC2Client->DescribeAddresses($DescribeAddressesType);
				if ($info && $info->addressesSet->item)
					return true;
				else
					return false;
			}
			catch(Exception $e)
			{
				return false;
			}
		}
		
		/**
		 * Allocate and Assign Elastic IP to instance if role use it.
		 *
		 * @param HostUpEvent $event
		 */
		public function OnHostUp(HostUpEvent $event)
		{
			if ($event->DBServer->replaceServerID)
				return;
			
			try
			{
				$DBFarm = DBFarm::LoadByID($this->FarmID);
				
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
				if (!$DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_ELASIC_IPS))
					return;
					
				$EC2Client = $this->GetAmazonEC2ClientObject($DBFarm->GetEnvironmentObject(), $DBFarmRole->CloudLocation);
			}
			catch(Exception $e)
			{
				return;
			}
			
			// Check for already allocated and free elastic IP in database
			$ip = $this->DB->GetRow("SELECT * FROM elastic_ips WHERE farmid=? AND ((farm_roleid=? AND instance_index=?) OR server_id = ?)",
				array($this->FarmID, $DBFarmRole->ID, $event->DBServer->index, $event->DBServer->serverId)
			);
			
			$this->Logger->debug(sprintf(_("Found IP address: %s"), $ip['ipaddress']));
			
			//
			// Check IP address
			//
			if ($ip['ipaddress'])
			{
				if (!$this->CheckElasticIP($ip['ipaddress'], $EC2Client))
				{
					Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage(
						$this->FarmID, 
						sprintf(_("Elastic IP '%s' does not belong to you. Allocating new one."), 
							$ip['ipaddress']
						)
					));
					
					$this->DB->Execute("DELETE FROM elastic_ips WHERE ipaddress=?", array($ip['ipaddress']));
					
					$ip = false;
				}
			}
			
			// If free IP not found we must allocate new IP
			if (!$ip)
			{				
				$this->Logger->debug(sprintf(_("Farm role: %s, %s, %s"), 
					$DBFarmRole->GetRoleObject()->name, $DBFarmRole->AMIID, $DBFarmRole->ID
				));
				
				$alocated_ips = $this->DB->GetOne("SELECT COUNT(*) FROM elastic_ips WHERE farm_roleid=?",
					array($DBFarmRole->ID)
				);
				
				$this->Logger->debug(sprintf(_("Allocated IPs: %s, MaxInstances: %s"), 
					$alocated_ips, $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES)
				));
				
				// Check elastic IPs limit. We cannot allocate more than 'Max instances' option for role
				if ($alocated_ips < $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES))
				{
					try
					{
						// Alocate new IP address
						$address = $EC2Client->AllocateAddress();
					}
					catch (Exception $e)
					{
						Logger::getLogger(LOG_CATEGORY::FARM)->error(new FarmLogMessage(
							$this->FarmID, 
							sprintf(_("Cannot allocate new elastic ip for instance '%s': %s"), 
								$event->DBServer->serverId, 
								$e->getMessage()
							)
						));
						return;
					}
					
					// Add allocated IP address to database
					$this->DB->Execute("INSERT INTO elastic_ips SET env_id=?, farmid=?, farm_roleid=?, ipaddress=?, state='0', server_id='', clientid=?, instance_index=?",
						array($event->DBServer->envId, $this->FarmID, $DBFarmRole->ID, $address->publicIp, $DBFarm->ClientID, $event->DBServer->index)
					);
					
					$ip['ipaddress'] = $address->publicIp;
					
					Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage(
						$this->FarmID, 
						sprintf(_("Allocated new IP: %s"), 
							$ip['ipaddress']
						)
					));
					
					// Waiting...
					$this->Logger->debug(_("Waiting 5 seconds..."));
					sleep(5);
				}
				else
					$this->Logger->fatal(_("Limit for elastic IPs reached. Check zomby records in database."));
			}
			
			// If we have ip address
			if ($ip['ipaddress'])
			{
				$assign_retries = 1;
				try
				{
					while (true)
					{
						try
						{
							// Associate elastic ip address with instance
							$EC2Client->AssociateAddress(
								$event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID), 
								$ip['ipaddress']
							);
						}
						catch(Exception $e)
						{
							if (!stristr($e->getMessage(), "does not belong to you") || $assign_retries == 3)
								throw new Exception($e->getMessage());
							else
							{
								// Waiting...
								$this->Logger->debug(_("Waiting 2 seconds..."));
								sleep(2);
								$assign_retries++;
								continue;
							}
						}
						
						break;
					}
				}
				catch(Exception $e)
				{
					Logger::getLogger(LOG_CATEGORY::FARM)->error(new FarmLogMessage(
						$this->FarmID, 
						sprintf(_("Cannot associate elastic ip with instance: %s"), 
							$e->getMessage()
						)
					));
					return;
				}					
				
				$this->Logger->info("IP: {$ip['ipaddress']} assigned to instance '{$event->DBServer->serverId}'");
				
				// Update leastic IPs table
				$this->DB->Execute("UPDATE elastic_ips SET state='1', server_id=? WHERE ipaddress=?",
					array($event->DBServer->serverId, $ip['ipaddress'])
				);
								
				Scalr::FireEvent($this->FarmID, new IPAddressChangedEvent($event->DBServer, $ip['ipaddress']));
			}
			else
			{
				Logger::getLogger(LOG_CATEGORY::FARM)->fatal(new FarmLogMessage(
					$this->FarmID, 
					sprintf(_("Cannot allocate elastic ip address for instance %s on farm %s"),
						$event->DBServer->serverId,
						$DBFarm->Name
					)
				));
			}
		}
		
		/**
		 * Release IP address when instance terminated
		 *
		 * @param HostDownEvent $event
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBServer->IsRebooting())
				return;
				
			try
			{
				$DBFarm = DBFarm::LoadByID($this->FarmID);
				
				if ($event->replacementDBServer)
				{
					$ip = $this->DB->GetRow("SELECT * FROM elastic_ips WHERE server_id=?", array($event->DBServer->serverId));
					if ($ip)
					{
						$EC2Client = $this->GetAmazonEC2ClientObject($DBFarm->GetEnvironmentObject(), $event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION));
						try
						{
							// Associate elastic ip address with instance
							$EC2Client->AssociateAddress(
								$event->replacementDBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID), 
								$ip['ipaddress']
							);
							
							$this->DB->Execute("UPDATE elastic_ips SET state='1', server_id=? WHERE ipaddress=?",
								array($event->replacementDBServer->serverId, $ip['ipaddress'])
							);
							
							Scalr::FireEvent($this->FarmID, new IPAddressChangedEvent($event->replacementDBServer, $ip['ipaddress']));
						}
						catch(Exception $e)
						{
							if (!stristr($e->getMessage(), "does not belong to you"))
								throw new Exception($e->getMessage());
						}
					}
				}
				else
				{
					$this->DB->Execute("UPDATE elastic_ips SET state='0', server_id='' WHERE server_id=?", array($event->DBServer->serverId));
				}
			}
			catch(Exception $e)
			{
				//
			}
		}
	}
?>