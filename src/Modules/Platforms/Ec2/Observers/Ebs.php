<?php
	class Modules_Platforms_Ec2_Observers_Ebs extends EventObserver
	{
		public $ObserverName = 'Elastic Block Storage';
		
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
		
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event)
		{			
			if ($event->DBServer->platform != SERVER_PLATFORMS::EC2)
				return;
			
			$DBFarm = $event->DBServer->GetFarmObject();
			$DBFarmRole = $event->DBServer->GetFarmRoleObject();
			$AmazonEC2Client = $this->GetAmazonEC2ClientObject(
				$event->DBServer->GetEnvironmentObject(), 
				$DBFarmRole->CloudLocation
			);

			// Create EBS volume for MySQLEBS
			if ($event->DBServer->IsSupported("0.6")) {
				
			}
			else // Only for old AMIs
			{
				if ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL) 
					&& $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE) == MYSQL_STORAGE_ENGINE::EBS)
				{
					$server = $event->DBServer;
					$masterServer = $DBFarm->GetMySQLInstances(true);
					$isMaster = !$masterServer || $masterServer[0]->serverId == $server->serverId;
					$farmMasterVolId = $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID);
									
					$createEbs = ($isMaster && !$farmMasterVolId); 
					
					if ($createEbs) {
						Logger::getLogger(LOG_CATEGORY::FARM)->info(
							new FarmLogMessage($event->DBServer->farmId, sprintf(_(
								"Need EBS volume for MySQL %s instance..."
							), $isMaster ? "Master" : "Slave"))
						);
						
						$CreateVolumeType = new CreateVolumeType();
	    				$CreateVolumeType->availabilityZone = $event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
	    				$CreateVolumeType->size = $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_EBS_VOLUME_SIZE);
						
						$res = $AmazonEC2Client->CreateVolume($CreateVolumeType);
					    if ($res->volumeId) {
					    	$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID, $res->volumeId);
					    	
					    	Logger::getLogger(LOG_CATEGORY::FARM)->info(
								new FarmLogMessage($event->DBServer->farmId, sprintf(_(
									"MySQL %S volume created. Volume ID: %s..."
								), $isMaster ? "Master" : "Slave", $res->volumeId))
							);
					    }
					}
				}
			}
		}
		
		/**
		 * 
		 *
		 * @param FarmTerminatedEvent $event
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{			
			$this->Logger->info("Keep EBS volumes: {$event->KeepEBS}");
			
			if ($event->KeepEBS == 1)
				return;
				
			$this->DB->Execute("UPDATE ec2_ebs SET attachment_status=? WHERE farm_id=? AND ismanual='0'", array(
				EC2_EBS_ATTACH_STATUS::DELETING, $this->FarmID
			));
		}
		
		public function OnEBSVolumeAttached(EBSVolumeAttachedEvent $event)
		{
			if ($event->DeviceName)
			{
				try {
					$DBEBSVolume = DBEBSVolume::loadByVolumeId($event->VolumeID);
					
					$DBEBSVolume->serverId = $event->DBServer->serverId;
					
					$DBEBSVolume->deviceName = $event->DeviceName;
					$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::ATTACHED;
					//$DBEBSVolume->isFsExists = 1;
					
					
					$DBEBSVolume->save();
				}
				catch(Exception $e)
				{
					
				}
			}
		}
		
		public function OnEBSVolumeMounted(EBSVolumeMountedEvent $event)
		{
			$DBEBSVolume = DBEBSVolume::loadByVolumeId($event->VolumeID);
			$DBEBSVolume->mountStatus = EC2_EBS_MOUNT_STATUS::MOUNTED;
			$DBEBSVolume->deviceName = $event->DeviceName;
			$DBEBSVolume->isFsExists = 1;
			$DBEBSVolume->save();
		}
		
		public function OnHostUp(HostUpEvent $event)
		{
			if ($event->DBServer->platform != SERVER_PLATFORMS::EC2)
				return;
				
			// Scalarizr will attach and mount volumes by itself
			if ($event->DBServer->IsSupported("0.7.36"))
				return;
				
			$volumes = $this->DB->GetAll("SELECT volume_id FROM ec2_ebs WHERE farm_roleid=? AND server_index=?", array(
				$event->DBServer->farmRoleId,
				$event->DBServer->index
			));
			
			$this->Logger->info(new FarmLogMessage($this->FarmID, 
				sprintf(_("Found %s volumes for server: %s"),
					count($volumes),
					$event->DBServer->serverId
				)
			));
			
			foreach ($volumes as $volume)
			{
				if ($volume['volume_id'])
				{
					$this->Logger->info(new FarmLogMessage($this->FarmID, 
						sprintf(_("Preparing volume #%s for attaching to server: %s."), $volume['volume_id'], $event->DBServer->serverId)
					));
					
					try
					{
						$DBEBSVolume = DBEBSVolume::loadByVolumeId($volume['volume_id']);
						$DBEBSVolume->serverId = $event->DBServer->serverId;
						$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::ATTACHING;
						$DBEBSVolume->mountStatus = ($DBEBSVolume->mount) ? EC2_EBS_MOUNT_STATUS::AWAITING_ATTACHMENT : EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
						$DBEBSVolume->save();
					}
					catch(Exception $e)
					{
						$this->Logger->fatal($e->getMessage());
					}
				}
			}
		}
		
		/**
		 * 
		 *
		 * @param HostInitEvent $event
		 */
		public function OnHostInit(HostInitEvent $event)
		{
			if ($event->DBServer->platform != SERVER_PLATFORMS::EC2)
				return;
			
			$DBFarmRole = $event->DBServer->GetFarmRoleObject();
			if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_USE_EBS))
			{			
				if (!$this->DB->GetOne("SELECT id FROM ec2_ebs WHERE farm_roleid=? AND server_index=? AND ismanual='0'", array(
					$event->DBServer->farmRoleId,
					$event->DBServer->index
				)))
				{
					$DBEBSVolume = new DBEBSVolume();
					$DBEBSVolume->attachmentStatus = EC2_EBS_ATTACH_STATUS::CREATING;
					$DBEBSVolume->isManual = 0;
					$DBEBSVolume->ec2AvailZone = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_AVAIL_ZONE);
					$DBEBSVolume->ec2Region = $event->DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION);
					$DBEBSVolume->farmId = $DBFarmRole->FarmID;
					$DBEBSVolume->farmRoleId = $DBFarmRole->ID;
					$DBEBSVolume->serverId = $event->DBServer->serverId;
					$DBEBSVolume->serverIndex = $event->DBServer->index;
					$DBEBSVolume->size = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SIZE);
					$DBEBSVolume->snapId = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SNAPID);
					$DBEBSVolume->isFsExists = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_SNAPID)) ? 1 : 0; 
					$DBEBSVolume->mount = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_MOUNT);
					$DBEBSVolume->mountPoint = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_MOUNTPOINT);
					$DBEBSVolume->mountStatus = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_EBS_MOUNT)) ? EC2_EBS_MOUNT_STATUS::AWAITING_ATTACHMENT : EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
					$DBEBSVolume->clientId = $event->DBServer->GetFarmObject()->ClientID;
					$DBEBSVolume->envId = $event->DBServer->envId;
					
					$DBEBSVolume->Save();
				}
			}
		}
		
		/**
		 * 
		 *
		 * @param HostDownEvent $event
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBServer->platform != SERVER_PLATFORMS::EC2)
				return;
			if ($event->DBServer->IsRebooting()) 
				return;			
			
			$this->DB->Execute("UPDATE ec2_ebs SET attachment_status=?, mount_status=?, device='', server_id='' WHERE server_id=?", array(
				EC2_EBS_ATTACH_STATUS::AVAILABLE,
				EC2_EBS_MOUNT_STATUS::NOT_MOUNTED,
				$event->DBServer->serverId
			));
		}
	}
?>