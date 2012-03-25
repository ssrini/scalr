<?php
	class Modules_Platforms_Openstack implements IPlatformModule
	{
		private $db;
		
		/** Properties **/
		const USERNAME 		= 'openstack.username';
		const API_KEY		= 'openstack.api_key';
		const API_URL		= 'openstack.api_url';
		const PROJECT_NAME	= 'openstack.project_name';
		
		private $instancesListCache = array();
		
		/**
		 * @return Scalr_Service_Cloud_Openstack_v1_1_Client
		 */
		private function getOsClient(Scalr_Environment $environment, $cloudLocation)
		{
			return Scalr_Service_Cloud_Openstack::newNovaCC(
				$environment->getPlatformConfigValue(self::API_URL, true, $cloudLocation),
				$environment->getPlatformConfigValue(self::USERNAME, true, $cloudLocation),
				$environment->getPlatformConfigValue(self::API_KEY, true, $cloudLocation),
				$environment->getPlatformConfigValue(self::PROJECT_NAME, true, $cloudLocation)
			);
		}
		
		public function __construct()
		{
			
		}
		
		public function getRoleBuilderBaseImages()
		{
			return array();
		}
		
		public function getLocations()
		{
			try {
				$envId = Scalr_Session::getInstance()->getEnvironmentId();
			}
			catch(Exception $e) {
				return array();	
			}
			
			//Eucalyptus locations defined by client. Admin cannot get them
			$db = Core::GetDBInstance();
			$locations = $db->GetAll("SELECT DISTINCT(`group`) as `name` FROM client_environment_properties WHERE `name` = ? AND env_id = ?", array(
				self::API_URL, $envId
			));
			$retval = array();
			foreach ($locations as $location)
				$retval[$location['name']] = "Openstack / {$location['name']}";
				
			return $retval;
		}
		
		public function getPropsList()
		{
			return array(
				self::USERNAME	=> 'Username',
				self::API_KEY	=> 'API Key',
				self::API_KEY	=> 'API URL',
				self::PROJECT_NAME	=> 'Project name',
			);
		}
		
		public function GetServerCloudLocation(DBServer $DBServer)
		{
			return $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION);
		}
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID);
		}
		
		public function GetServerFlavor(DBServer $DBServer)
		{
			return $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::FLAVOR_ID);
		}
		
		public function IsServerExists(DBServer $DBServer, $debug = false)
		{
			return in_array(
				$DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID), 
				array_keys($this->GetServersList($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION)))
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
			$osClient = $this->getOsClient($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
			
			$result = $osClient->serverGetDetails($DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID));
		    
			foreach ($result->server->addresses->private as $addr)
				if ($addr->version == 4) {
					$localIp = $addr->addr;
					break;
				}
			
			foreach ($result->server->addresses->public as $addr)
				if ($addr->version == 4) {
					$remoteIp = $addr->addr;
					break;
				}
					
		    return array(
		    	'localIp'	=> $localIp,
		    	'remoteIp'	=> $remoteIp
		    );
		}
		
		public function GetServersList(Scalr_Environment $environment, $cloudLocation, $skipCache = false)
		{
			if (!$this->instancesListCache[$environment->id][$cloudLocation] || $skipCache)
			{
				$osClient = $this->getOsClient($environment, $cloudLocation);
				
				$result = $osClient->serversList();
				foreach ($result->servers as $server)
					$this->instancesListCache[$environment->id][$cloudLocation][$server->id] = $server->status;
			}
	        
			return $this->instancesListCache[$environment->id][$cloudLocation];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$cloudLocation = $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION);
			$environment = $DBServer->GetEnvironmentObject();
			
			$iid = $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID);
			if (!$iid)
			{
				$status = 'not-found';
			}
			elseif (!$this->instancesListCache[$environment->id][$cloudLocation][$iid])
			{
		        $osClient = $this->getOsClient($environment, $cloudLocation);
				
		        try {
					$result = $osClient->serverGetDetails($DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID));
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
				$status = $this->instancesListCache[$environment->id][$cloudLocation][$DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID)];
			}
			
			return Modules_Platforms_Openstack_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
			$osClient = $this->getOsClient($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
	        
	        $osClient->serverDelete($DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID));
	        
	        return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$osClient = $this->getOsClient($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
	        
	        $osClient->serverReboot($DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID));
	        
	        return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{			
			foreach ($DBRole->getImageId(SERVER_PLATFORMS::OPENSTACK) as $location => $imageId) {

				$osClient = $this->getOsClient($DBRole->getEnvironmentObject(), $location);
				
				try {
					$osClient->imageDelete($imageId);
				}
				catch(Exception $e)
				{
					if (stristr($e->getMessage(), "Cannot destroy a destroyed snapshot"))
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
        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::OSTACK_GLANCE;
    	
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
			throw new Exception("Not supported by Openstack");
		}
		
		public function GetServerExtendedInformation(DBServer $DBServer)
		{
			try
			{
				try	{
					$osClient = $this->getOsClient($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
					$iinfo = $osClient->serverGetDetails($DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
				}
				catch(Exception $e){}
	
		        if ($iinfo)
		        {
			        return array(
			        	'Server ID'				=> $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID),
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
				
				$launchOptions->imageId = $DBRole->getImageId(SERVER_PLATFORMS::OPENSTACK, $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
				$launchOptions->serverType = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_OPENSTACK_FLAVOR_ID);
   				$launchOptions->cloudLocation = $DBServer->GetFarmRoleObject()->CloudLocation;
				
				foreach ($DBServer->GetCloudUserData() as $k=>$v)
	        		$u_data .= "{$k}={$v};";
				
				$launchOptions->userData = trim($u_data, ";");
				
				$launchOptions->architecture = 'x86_64';
			}

			$osClient = $this->getOsClient($DBServer->GetEnvironmentObject(), $launchOptions->cloudLocation);
			
			$result = $osClient->serverCreate(
				$DBServer->serverId,
				$launchOptions->imageId,
				$launchOptions->serverType,
				array(),
				array(
					'path'		=> '/etc/scalr/private.d/.user-data',
					'contents'	=> base64_encode($launchOptions->userData)
				)
			);
	        
	        if ($result->server)
	        {
	        	//TODO:
	        	
	        	$DBServer->SetProperty(OPENSTACK_SERVER_PROPERTIES::SERVER_ID, $result->server->id);
	        	$DBServer->SetProperty(OPENSTACK_SERVER_PROPERTIES::IMAGE_ID, $result->server->image->id);
	        	$DBServer->SetProperty(OPENSTACK_SERVER_PROPERTIES::FLAVOR_ID, $result->server->flavor->id);
	        	$DBServer->SetProperty(OPENSTACK_SERVER_PROPERTIES::ADMIN_PASS, $result->server->adminPass);
	        	$DBServer->SetProperty(OPENSTACK_SERVER_PROPERTIES::NAME, $DBServer->serverId);
	        	$DBServer->SetProperty(OPENSTACK_SERVER_PROPERTIES::HOST_ID, $result->server->hostId);
	        	
	        	$DBServer->SetProperty(SERVER_PROPERTIES::ARCHITECTURE, $launchOptions->architecture);
	        	
	        	$DBServer->SetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION, $launchOptions->cloudLocation);
	        	
		        return $DBServer;
	        }
	        else 
	            throw new Exception(sprintf(_("Cannot launch new instance. %s"), $result->faultstring));
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
	        	$accessData->username = $environment->getPlatformConfigValue(self::USERNAME, true, $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
	        	$accessData->apiKey = $environment->getPlatformConfigValue(self::API_KEY, true, $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
	        	$accessData->authUrl = $environment->getPlatformConfigValue(self::API_URL, true, $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
	        	$accessData->projectId = $environment->getPlatformConfigValue(self::PROJECT_NAME, true, $DBServer->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION));
	        
	        	
	        	$message->platformAccessData = $accessData;
			}
			
		}
		
		public function ClearCache ()
		{
			$this->instancesListCache = array();
		}
	}

	
	
?>
