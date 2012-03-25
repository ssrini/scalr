<?php
	class Modules_Platforms_Rackspace implements IPlatformModule
	{
		private $db;
		
		/** Properties **/
		const USERNAME 		= 'rackspace.username';
		const API_KEY		= 'rackspace.api_key';
		const IS_MANAGED	= 'rackspace.is_managed';
		
		private $instancesListCache = array();
		
		
		public $_tmpVar;
		
		/**
		 * @return Scalr_Service_Cloud_Rackspace_CS
		 */
		private function getRsClient(Scalr_Environment $environment, $cloudLocation)
		{
			return Scalr_Service_Cloud_Rackspace::newRackspaceCS(
				$environment->getPlatformConfigValue(self::USERNAME, true, $cloudLocation),
				$environment->getPlatformConfigValue(self::API_KEY, true, $cloudLocation),
				$cloudLocation
			);
		}
		
		public function __construct()
		{
			
		}
		
		public function getRoleBuilderBaseImages()
		{
			try {
				$env = Scalr_Session::getInstance()->getEnvironment();
				$isManagedOrd1 = $env->getPlatformConfigValue(self::IS_MANAGED, true, 'rs-ORD1');
				$isManagedLonx = $env->getPlatformConfigValue(self::IS_MANAGED, true, 'rs-LONx');
			}
			catch(Exception $e) {
				return array();	
			}

			if (!$isManagedOrd1) {
				$images['rs-ORD1'] = array(
					'112'	=> array('name' => 'Ubuntu 10.04','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
					'115'	=> array('name' => 'Ubuntu 11.04','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
					'119'	=> array('name' => 'Ubuntu 11.10','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),					
					'114'	=> array('name' => 'CentOS 5.6',  'os_dist' => 'centos', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
					'110'	=> array('name' => 'RHEL 5.5',    'os_dist' => 'rhel',   'location' => 'rs-ORD1', 'architecture' => 'x86_64')
				);
			} else {
				$images['rs-ORD1'] = array(
					'65'	=> array('name' => 'Ubuntu 10.04','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
					'73'	=> array('name' => 'Ubuntu 10.10','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
					'67'	=> array('name' => 'CentOS 5.5',  'os_dist' => 'centos', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
					'63'	=> array('name' => 'RHEL 5.5',    'os_dist' => 'rhel',   'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
				);
			}
				
			if (!$isManagedLonx) {
				$images['rs-LONx'] = array(
					'lon112'	=> array('name' => 'Ubuntu 10.04','os_dist' => 'ubuntu', 'location' => 'rs-LONx', 'architecture' => 'x86_64'),
					'lon115'	=> array('name' => 'Ubuntu 11.04','os_dist' => 'ubuntu', 'location' => 'rs-LONx', 'architecture' => 'x86_64'),
					'lon119'	=> array('name' => 'Ubuntu 11.10','os_dist' => 'ubuntu', 'location' => 'rs-LONx', 'architecture' => 'x86_64'),
					'lon114'	=> array('name' => 'CentOS 5.6',  'os_dist' => 'centos', 'location' => 'rs-LONx', 'architecture' => 'x86_64'),
					'lon110'	=> array('name' => 'RHEL 5.5',    'os_dist' => 'rhel',   'location' => 'rs-LONx', 'architecture' => 'x86_64')
				);	
			}
			else {
				$images['rs-LONx'] = array(
					'lon65'	=> array('name' => 'Ubuntu 10.04','os_dist' => 'ubuntu', 'location' => 'rs-LONx', 'architecture' => 'x86_64'),
					'lon73'	=> array('name' => 'Ubuntu 10.10','os_dist' => 'ubuntu', 'location' => 'rs-LONx', 'architecture' => 'x86_64'),
					'lon67'	=> array('name' => 'CentOS 5.5',  'os_dist' => 'centos', 'location' => 'rs-LONx', 'architecture' => 'x86_64'),
					'lon63'	=> array('name' => 'RHEL 5.5',    'os_dist' => 'rhel',   'location' => 'rs-LONx', 'architecture' => 'x86_64')
				);
			}
			
			/*
			foreach (array_keys($this->getLocations()) as $location) {
				try {
					$isManaged = $env->getPlatformConfigValue(self::IS_MANAGED, true, $location);
					if (!$isManaged) {
						$images[$location] = array(
							'112'	=> array('name' => 'Ubuntu 10.04','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
							'115'	=> array('name' => 'Ubuntu 11.04','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
							'119'	=> array('name' => 'Ubuntu 11.10','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),					
							'114'	=> array('name' => 'CentOS 5.6',  'os_dist' => 'centos', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
							'110'	=> array('name' => 'RHEL 5.5',    'os_dist' => 'rhel',   'location' => 'rs-ORD1', 'architecture' => 'x86_64')
						);
					} else {
						$images[$location] = array(
							'65'	=> array('name' => 'Ubuntu 10.04','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
							'73'	=> array('name' => 'Ubuntu 10.10','os_dist' => 'ubuntu', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
							'67'	=> array('name' => 'CentOS 5.5',  'os_dist' => 'centos', 'location' => 'rs-ORD1', 'architecture' => 'x86_64'),
							'63'	=> array('name' => 'RHEL 5.5',    'os_dist' => 'rhel',   'location' => 'rs-ORD1', 'architecture' => 'x86_64')
						);
					}
				} catch (Exception $e) {
				}
			}
			*/
			
			
			$locations = array_keys($this->getLocations());
			$retval = array();
			foreach ($locations as $location) {
				$retval = $retval + $images[$location];
			}
			
			return $retval;
		}
		
		public function getLocations()
		{
			/*
			return array(
				'rs-ORD1' => 'Rackspace / ORD1',
				'rs-LONx' => 'Rackspace / LONx'
			);
			*/
			
			try {
				$envId = Scalr_Session::getInstance()->getEnvironmentId();
			}
			catch(Exception $e) {
				return array();	
			}
			
			//Eucalyptus locations defined by client. Admin cannot get them
			$db = Core::GetDBInstance();
			$locations = $db->GetAll("SELECT DISTINCT(`group`) as `name` FROM client_environment_properties WHERE `name` = ? AND env_id = ?", array(
				self::API_KEY, $envId
			));
			$retval = array();
			foreach ($locations as $location)
				$retval[$location['name']] = "Rackspace / {$location['name']}";
				
			return $retval;
		}
		
		public function getPropsList()
		{
			return array(
				self::USERNAME	=> 'Username',
				self::API_KEY	=> 'API Key',
			);
		}
		
		public function GetServerCloudLocation(DBServer $DBServer)
		{
			return $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER);
		}
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID);
		}
		
		public function GetServerFlavor(DBServer $DBServer)
		{
			return $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::FLAVOR_ID);
		}
		
		public function IsServerExists(DBServer $DBServer, $debug = false)
		{
			return in_array(
				$DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID), 
				array_keys($this->GetServersList($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER)))
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
			$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER));
			
			$result = $rsClient->getServerDetails($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
		    
		    return array(
		    	'localIp'	=> $result->server->addresses->private[0],
		    	'remoteIp'	=> $result->server->addresses->public[0]
		    );
		}
		
		public function GetServersList(Scalr_Environment $environment, $cloudLocation, $skipCache = false)
		{
			if (!$this->instancesListCache[$environment->id][$cloudLocation] || $skipCache)
			{
				$rsClient = $this->getRsClient($environment, $cloudLocation);
				
				$result = $rsClient->listServers(true);

				$this->_tmpVar = $result;
				
				foreach ($result->servers as $server)
					$this->instancesListCache[$environment->id][$cloudLocation][$server->id] = $server->status;
			}
	        
			return $this->instancesListCache[$environment->id][$cloudLocation];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$cloudLocation = $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER);
			$environment = $DBServer->GetEnvironmentObject();
			
			$iid = $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID);
			if (!$iid)
			{
				$status = 'not-found';
			}
			elseif (!$this->instancesListCache[$environment->id][$cloudLocation][$iid])
			{
		        $rsClient = $this->getRsClient($environment, $cloudLocation);
				
		        try {
					$result = $rsClient->getServerDetails($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
					$status = $result->server->status;
		        }
		        catch(Exception $e)
		        {
		        	if (stristr($e->getMessage(), "404"))
		        		$status = 'not-found';
		        	else
		        		throw $e;
		        }
			}
			else
			{
				$status = $this->instancesListCache[$environment->id][$cloudLocation][$DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID)];
			}
			
			return Modules_Platforms_Rackspace_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
			$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER));
	        
	        $rsClient->deleteServer($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
	        
	        return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER));
	        
	        $rsClient->rebootServer($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
	        
	        return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{			
			foreach ($DBRole->getImageId(SERVER_PLATFORMS::RACKSPACE) as $location => $imageId) {

				$rsClient = $this->getRsClient($DBRole->getEnvironmentObject(), $location);
				
				try {
					$rsClient->deleteImage($imageId);
				}
				catch(Exception $e)
				{
					if (stristr($e->getMessage(), "Cannot destroy a destroyed snapshot") || 
						stristr($e->getMessage(), "com.rackspace.cloud.service.servers.ItemNotFoundFault") ||
						stristr($e->getMessage(), "Bad username or password") ||
						stristr($e->getMessage(), "NotFoundException")
					)
						return true;
					else
						throw $e;
				}
			}
			
			return true;
		}
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask)
		{
			
		}
		
		public function CreateServerSnapshot(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
        	$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS;
        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::RS_CFILES;
    	
        	$msg = new Scalr_Messaging_Msg_Rebundle(
        		$BundleTask->id,
				$BundleTask->roleName,
				array()
        	);

        	if (!$DBServer->SendMessage($msg))
        	{
        		$BundleTask->SnapshotCreationFailed("Cannot send rebundle message to server. Please check event log for more details.");
        		return;
        	}
        	else
        	{
	        	$BundleTask->Log(sprintf(_("Snapshot creating initialized (MessageID: %s). Bundle task status changed to: %s"), 
	        		$msg->messageId, $BundleTask->status
	        	));
        	}
			
			$BundleTask->setDate('started');
        	$BundleTask->Save();        	
		}
		
		private function ApplyAccessData(Scalr_Messaging_Msg $msg)
		{
			
			
		}
		
		public function GetServerConsoleOutput(DBServer $DBServer)
		{
			throw new Exception("Not supported by Rackspace");
		}
		
		public function GetServerExtendedInformation(DBServer $DBServer)
		{
			try
			{
				try	{
					$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER));
					$iinfo = $rsClient->getServerDetails($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID));
				}
				catch(Exception $e){}
	
		        if ($iinfo)
		        {
			        return array(
			        	'Server ID'				=> $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID),
			        	'Image ID'				=> $iinfo->server->imageId,
			        	'Flavor ID'				=> $iinfo->server->flavorId,
			        	'Public IP'				=> implode(", ", $iinfo->server->addresses->public),
			        	'Private IP'			=> implode(", ", $iinfo->server->addresses->private),
			        	'Status'				=> $iinfo->server->status,
			        	'Name'					=> $iinfo->server->name,
			        	'Host ID'				=> $iinfo->server->hostId,
			        	'Progress'				=> $iinfo->server->progress
			        );
		        }
			}
			catch(Exception $e){}
			
			return false;
		}
		
		/**
		 launchOptions: imageId
		 */
		public function LaunchServer(DBServer $DBServer, Scalr_Server_LaunchOptions $launchOptions = null)
		{
			if (!$launchOptions)
			{
				$launchOptions = new Scalr_Server_LaunchOptions();
				$DBRole = DBRole::loadById($DBServer->roleId);
				
				$launchOptions->imageId = $DBRole->getImageId(SERVER_PLATFORMS::RACKSPACE, $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER));
				$launchOptions->serverType = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_RS_FLAVOR_ID);
   				$launchOptions->cloudLocation = $DBServer->GetFarmRoleObject()->CloudLocation;
				
				foreach ($DBServer->GetCloudUserData() as $k=>$v)
	        		$u_data .= "{$k}={$v};";
				
				$launchOptions->userData = trim($u_data, ";");
				
				$launchOptions->architecture = 'x86_64';
			}

			$rsClient = $this->getRsClient($DBServer->GetEnvironmentObject(), $launchOptions->cloudLocation);
			//Cannot launch new instance. Request to Rackspace failed (Code: 413): {"overLimit":{"message":"Too many requests...","code":413,"retryAfter":"2012-03-12T09:44:56.343-05:00"}} (19641119, 4)
						
			
			try {
				$result = $rsClient->createServer(
					$DBServer->serverId,
					$launchOptions->imageId,
					$launchOptions->serverType,
					array(),
					array(
						'path'		=> '/etc/scalr/private.d/.user-data',
						'contents'	=> base64_encode($launchOptions->userData)
					)
				);
			} catch (Exception $e) {
				 throw new Exception(sprintf(_("Cannot launch new instance. %s (%s, %s)"), $e->getMessage(), $launchOptions->imageId, $launchOptions->serverType));
			}
	        
	        if ($result->server)
	        {
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::SERVER_ID, $result->server->id);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::IMAGE_ID, $result->server->imageId);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::FLAVOR_ID, $result->server->flavorId);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::ADMIN_PASS, $result->server->adminPass);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::NAME, $DBServer->serverId);
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::HOST_ID, $result->server->hostId);
	        	
	        	$DBServer->SetProperty(SERVER_PROPERTIES::ARCHITECTURE, $launchOptions->architecture);
	        	
	        	$DBServer->SetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER, $launchOptions->cloudLocation);
	        	
		        return $DBServer;
	        }
	        else 
	            throw new Exception(sprintf(_("Cannot launch new instance. %s (%s, %s)"), serialize($result), $launchOptions->imageId, $launchOptions->serverType));
		}
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message)
		{
			$put = false;
			$put |= $message instanceof Scalr_Messaging_Msg_Rebundle;
			$put |= $message instanceof Scalr_Messaging_Msg_HostInitResponse;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_PromoteToMaster;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_NewMasterUp;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_CreateDataBundle;
			$put |= $message instanceof Scalr_Messaging_Msg_Mysql_CreateBackup;
			
			$put |= $message instanceof Scalr_Messaging_Msg_DbMsr_PromoteToMaster;
			$put |= $message instanceof Scalr_Messaging_Msg_DbMsr_CreateDataBundle;
			$put |= $message instanceof Scalr_Messaging_Msg_DbMsr_CreateBackup;
			
			
			if ($put) {
				$environment = $DBServer->GetEnvironmentObject();
	        	$accessData = new stdClass();
	        	$accessData->username = $environment->getPlatformConfigValue(self::USERNAME, true, $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER));
	        	$accessData->apiKey = $environment->getPlatformConfigValue(self::API_KEY, true, $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER));
	        	
	        	switch ($DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER))
	        	{
	        		case 'rs-ORD1':
						$accessData->authHost = 'auth.api.rackspacecloud.com';
					break;
					
					case 'rs-LONx':
						$accessData->authHost = 'lon.auth.api.rackspacecloud.com';
					break;
	        	} 
	        	
	        	$message->platformAccessData = $accessData;
			}
			
		}
		
		public function ClearCache ()
		{
			$this->instancesListCache = array();
		}
	}

	
	
?>
