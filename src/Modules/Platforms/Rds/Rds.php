<?php
	class Modules_Platforms_Rds extends Modules_Platforms_Aws implements IPlatformModule
	{
		private $db;
		
		/** Properties **/
		const ACCOUNT_ID 	= 'rds.account_id';
		const ACCESS_KEY	= 'rds.access_key';
		const SECRET_KEY	= 'rds.secret_key';
		const PRIVATE_KEY	= 'rds.private_key';
		const CERTIFICATE	= 'rds.certificate';
		
		/**
		 * 
		 * @var AmazonRDS
		 */
		private $instancesListCache;
		
		public function __construct()
		{
			$this->db = Core::GetDBInstance();
		}	
		
		public function getRoleBuilderBaseImages() {}
		
		public function getPropsList()
		{
			return array(
				self::ACCOUNT_ID	=> 'AWS Account ID',
				self::ACCESS_KEY	=> 'AWS Access Key',
				self::SECRET_KEY	=> 'AWS Secret Key',
				self::CERTIFICATE	=> 'AWS x.509 Certificate',
				self::PRIVATE_KEY	=> 'AWS x.509 Private Key'
			);
		}
		
		public function GetServerCloudLocation(DBServer $DBServer)
		{
			return $DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION);
		}
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID);
		}
		
		public function GetServerFlavor(DBServer $DBServer)
		{
			return NULL;
		}
		
		public function IsServerExists(DBServer $DBServer)
		{
			return in_array(
				$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID), 
				array_keys($this->GetServersList($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)))
			);
		}
		
		public function getRdsClient(Scalr_Environment $environment, $region)
		{			
			return Scalr_Service_Cloud_Aws::newRds(
				$environment->getPlatformConfigValue(self::ACCESS_KEY),
				$environment->getPlatformConfigValue(self::SECRET_KEY),
				$region
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
			$Client = $DBServer->GetClient();
			
			$RDSClient = $this->getRdsClient(
				$DBServer->GetEnvironmentObject(),
				$DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)
			);
	        
	        
	        $iinfo = $RDSClient->DescribeDBInstances($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	        $iinfo = $iinfo->DescribeDBInstancesResult->DBInstances->DBInstance;
	        
	        $hostname = (string)$iinfo->Endpoint->Address;
	        
		    $ip = @gethostbyname($hostname);
		    if ($ip != $hostname)
		    {
			    return array(
			    	'localIp'	=> $ip,
			    	'remoteIp'	=> $ip
			    );
		    }
		}
		
		private function GetServersList(Scalr_Environment $environment, $region, $skipCache = false)
		{
			if (!isset($this->instancesListCache[$environment->id][$region]))
			{
				$RDSClient = $this->getRdsClient(
					$environment,
					$region
				);
				
		        try
				{
		            $results = $RDSClient->DescribeDBInstances();
		            $results = $results->DescribeDBInstancesResult;
				}
				catch(Exception $e)
				{
					throw new Exception(sprintf("Cannot get list of servers for platfrom rds: %s", $e->getMessage()));
				}
			
				if ($results->DBInstances)
	            	foreach ($results->DBInstances->children() as $item)
	                	$this->instancesListCache[$environment->id][$region][(string)$item->DBInstanceIdentifier] = (string)$item->DBInstanceStatus;
			}
			
			return $this->instancesListCache[$environment->id][$region];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$region = $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION);
			
			$RDSClient = $this->getRdsClient(
				$DBServer->GetEnvironmentObject(),
				$region
			);
			
			if (!$this->instancesListCache[$DBServer->GetEnvironmentObject()->id][$region][$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID)])
			{
		        try {
			        $iinfo = $RDSClient->DescribeDBInstances($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
			        $iinfo = $iinfo->DescribeDBInstancesResult->DBInstances->DBInstance;
			        $status = (string)$iinfo->DBInstanceStatus;
		        }
		        catch(Exception $e)
		        {
		        	if (stristr($e->getMessage(), "not found"))
		        		$status = 'not-found';
		        	else
		        		throw $e;
		        }
			}
			else
			{
				$status = $this->instancesListCache[$DBServer->GetEnvironmentObject()->id][$region][$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID)];
			}
			
	        return Modules_Platforms_Rds_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
			$RDSClient = $this->getRdsClient(
				$DBServer->GetEnvironmentObject(),
				$DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)
			);     

	        //TODO: Snapshot
	        $RDSClient->DeleteDBInstance($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	        
	        return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$RDSClient = $this->getRdsClient(
				$DBServer->GetEnvironmentObject(),
				$DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)
			);
	        
	        $RDSClient->RebootDBInstance($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	        
	        return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{
			foreach ($DBRole->getImageId(SERVER_PLATFORMS::EC2) as $location => $imageId)
			{
				$RDSClient = $this->getRdsClient(
					$DBRole->GetEnvironmentObject(),
					$location
				);
				
				$RDSClient->DeleteDBSnapshot($imageId);
			}
			
			return true;
		}
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
			
			$RDSClient = $this->getRdsClient(
				$DBServer->GetEnvironmentObject(),
				$DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)
			); 
			
			try
			{
				$info = $RDSClient->DescribeDBSnapshots(null, $BundleTask->snapshotId);
				$info = $info->DescribeDBSnapshotsResult->DBSnapshots->DBSnapshot;
				
				if ($info->Status == 'available')
				{
					$BundleTask->SnapshotCreationComplete($BundleTask->snapshotId);
				}
				elseif ($info->Status == 'creating')
				{
					return;
				}
				else
				{
					Logger::getLogger(__CLASS__)->error("CheckServerSnapshotStatus ({$BundleTask->id}) status = {$info->Status}");
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("CheckServerSnapshotStatus ({$BundleTask->id}): {$e->getMessage()}");
			}
		}
		
		public function CreateServerSnapshot(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
			
			$RDSClient = $this->getRdsClient(
				$DBServer->GetEnvironmentObject(),
				$DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)
			); 
	        
	        try
	        {
	        	$RDSClient->CreateDBSnapshot($BundleTask->roleName, $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));
	        	
	        	$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS;
	        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::RDS_SPT;
	        	$BundleTask->snapshotId = $BundleTask->roleName;
	        	
	        	$BundleTask->Log(sprintf(_("Snapshot creation initialized. SnapshotID: %s"), $BundleTask->snapshotId));
		        
		        $BundleTask->setDate('started');
		        
		        $BundleTask->Save();
	        }
	        catch(Exception $e)
	        {
	        	$BundleTask->SnapshotCreationFailed($e->getMessage());
	        }
		}
		
		public function GetServerConsoleOutput(DBServer $DBServer)
		{
			throw new Exception("Not supported by RDS platform module");
		}
		
		public function GetServerExtendedInformation(DBServer $DBServer)
		{
			try
			{
				$RDSClient = $this->getRdsClient(
					$DBServer->GetEnvironmentObject(),
					$DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)
				);  
		        
		        $iinfo = $RDSClient->DescribeDBInstances($DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID));		        
		        $iinfo = $iinfo->DescribeDBInstancesResult->DBInstances->DBInstance;
		        
		        if ($iinfo)
		        {
		        	$groups = array();
			        if ($iinfo->DBParameterGroups->DBParameterGroup->DBParameterGroupName)
			        	$groups[] = $iinfo->DBParameterGroups->DBParameterGroup->DBParameterGroupName;
			        else
			        {
			        	foreach ($iinfo->DBParameterGroups->DBParameterGroup as $item)
			        		$groups[] = $item->DBParameterGroupName;
			        }

		        	$sgroups = array();
			        if ($iinfo->DBSecurityGroups->DBSecurityGroup->DBParameterGroupName)
			        	$sgroups[] = $iinfo->DBSecurityGroups->DBSecurityGroup->DBSecurityGroupName;
			        else
			        {
			        	foreach ($iinfo->DBSecurityGroups->DBSecurityGroup as $item)
			        		$sgroups[] = $item->DBSecurityGroupName;
			        }
			        
			        return array(
			        	'Instance ID'			=> $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID),
			        	'Engine'				=> $iinfo->Engine,
			        	'Image ID (Snapshot)'	=> $DBServer->GetProperty(RDS_SERVER_PROPERTIES::SNAPSHOT_ID),
			        	'Backup Retention Period'	=> $iinfo->BackupRetentionPeriod,
			        	'Status'				=> $iinfo->DBInstanceStatus,
			        	'Preferred Backup Window' => $iinfo->PreferredBackupWindow,
			        	'Preferred Maintenance Window'	=> $iinfo->PreferredMaintenanceWindow,
			        	'Availability Zone'		=> $iinfo->AvailabilityZone,
			        	'Allocated Storage'		=> $iinfo->AllocatedStorage,
			        	'Instance Class'		=> $iinfo->DBInstanceClass,
			        	'Master Username'		=> $iinfo->MasterUsername,
			        	'Port'					=> $iinfo->Endpoint->Port,
			        	'Hostname'				=> $iinfo->Endpoint->Address,
			        	'Create Time'			=> $iinfo->InstanceCreateTime,
			        	'Parameter groups'		=> implode(", ", $groups),
			        	'Security groups'		=> implode(", ", $sgroups)
			        );
		        }
			}
			catch(Exception $e)
			{
				
			}
			
			return false;
		}
		
		public function LaunchServer(DBServer $DBServer, Scalr_Server_LaunchOptions $launchOptions = null)
		{
			$RDSClient = $this->getRdsClient(
				$DBServer->GetEnvironmentObject(),
				$DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)
			); 
	        
	        $DBRole = DBRole::loadById($DBServer->roleId);
	        
	        $server_id = "scalr-{$DBServer->serverId}";
	        
	        $avail_zone = $DBServer->GetProperty(RDS_SERVER_PROPERTIES::AVAIL_ZONE) ? $DBServer->GetProperty(RDS_SERVER_PROPERTIES::AVAIL_ZONE) : $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)."a";
	        
	        try
	        {
		        if ($DBRole->getImageId(SERVER_PLATFORMS::RDS, $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)) == 'ScalrEmpty')
		        {
			        $RDSClient->CreateDBInstance(
			        	$server_id,
			        	$DBServer->GetProperty(RDS_SERVER_PROPERTIES::STORAGE),
			        	$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_CLASS),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ENGINE),				
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::MASTER_USER),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::MASTER_PASS),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::PORT),
						null, //DBName
						null, //DBParameterGroup
						null, //$DBSecurityGroups
						$avail_zone,
						null, //$PreferredMaintenanceWindow  = null,
						null, //$BackupRetentionPeriod	= null ,
						null //$PreferredBackupWindow	= null
			        );
		        }
		        else
		        {
		        	$RDSClient->RestoreDBInstanceFromDBSnapshot(
		        		$DBRole->getImageId(SERVER_PLATFORMS::RDS, $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)),
		        		$server_id,
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_CLASS),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::PORT),
						$DBServer->GetProperty(RDS_SERVER_PROPERTIES::AVAIL_ZONE)
		        	);
		        }
		        
		        $DBServer->SetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID, $server_id);
		        $DBServer->SetProperty(RDS_SERVER_PROPERTIES::SNAPSHOT_ID, $DBRole->getImageId(SERVER_PLATFORMS::RDS, $DBServer->GetProperty(RDS_SERVER_PROPERTIES::REGION)));
		        return $DBServer;
	        }
	        catch(Exception $e)
	        {
	        	throw new Exception(sprintf(_("Cannot launch new instance. %s"), $e->getMessage()));
	        }
		}
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message)
		{
			
		}
		
		public function ClearCache()
		{
			$this->instancesListCache = array();
		}
	}

?>