<?php
	class Modules_Platforms_Cloudstack implements IPlatformModule
	{
		private $db;
		
		/** Properties **/
		const API_KEY 	= 'cloudstack.api_key';
		const SECRET_KEY	= 'cloudstack.secret_key';
		const API_URL = 'cloudstack.api_url';
		
		const ACCOUNT_NAME = 'cloudstack.account_name';
		const DOMAIN_NAME  = 'cloudtsack.domain_name';
		const DOMAIN_ID  = 'cloudtsack.domain_id';
		const SHARED_IP = 'cloudstack.shared_ip';
		const SHARED_IP_ID = 'cloudstack.shared_ip_id';
		const SHARED_IP_INFO = 'cloudstack.shared_ip_info';
		const SZR_PORT_COUNTER = 'cloudstack.szr_port_counter';
		
		
		/**
		 * 
		 * @var AmazonRDS
		 */
		private $instancesListCache;
		
		public function __construct()
		{
			$this->db = Core::GetDBInstance();
		}	
		
		/**
		 * 
		 * @param unknown_type $environment
		 * @param unknown_type $region
		 * @return Scalr_Service_Cloud_Nimbula_Client
		 */
		private function getCloudStackClient($environment, $cloudLoction=null)
		{
			return Scalr_Service_Cloud_Cloudstack::newCloudstack(
				$environment->getPlatformConfigValue(self::API_URL),
				$environment->getPlatformConfigValue(self::API_KEY),
				$environment->getPlatformConfigValue(self::SECRET_KEY)
			);
		}
		
		public function getRoleBuilderBaseImages() {}
		
		public function getLocations() {
			try {
				$environment = Scalr_Session::getInstance()->getEnvironment();
			}
			catch(Exception $e) {
				return array();	
			}
			
			if (!$environment || !$environment->isPlatformEnabled(SERVER_PLATFORMS::CLOUDSTACK))
				return array();
			
			try {
				$cs = Scalr_Service_Cloud_Cloudstack::newCloudstack(
					$environment->getPlatformConfigValue(self::API_URL),
					$environment->getPlatformConfigValue(self::API_KEY),
					$environment->getPlatformConfigValue(self::SECRET_KEY)
				);
				
				foreach ($cs->listZones() as $zone)
					$retval[$zone->name] = "Cloudstack / {$zone->name}";
			} catch (Exception $e) {
				return array();
			}
				
			return $retval;
		}
		
		public function getPropsList()
		{
			return array(
				self::API_URL			=> 'API URL',
				self::API_KEY			=> 'API Key',
				self::SECRET_KEY		=> 'Secret Key'
			);
		}
		
		public function GetServerCloudLocation(DBServer $DBServer)
		{
			return $DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::CLOUD_LOCATION);
		}
		
		public function GetServerID(DBServer $DBServer)
		{
			return $DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID);
		}
		
		public function GetServerFlavor(DBServer $DBServer)
		{
			return NULL;
		}
		
		public function IsServerExists(DBServer $DBServer)
		{
			return in_array(
				$DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID), 
				array_keys($this->GetServersList($DBServer->GetEnvironmentObject(), $this->GetServerCloudLocation($DBServer)))
			);
		}
		
		public function GetServerIPAddresses(DBServer $DBServer)
		{
		  	//
		}
		
		private function GetServersList(Scalr_Environment $environment, $region, $skipCache = false)
		{
			if (!$region)
				return array();
			
			if (!$this->instancesListCache[$environment->id][$region] || $skipCache) {
				$cs = $this->getCloudStackClient($environment, $region);
		        
		        try {
		            $results = $cs->listVirtualMachines(null, $region);        
				}
				catch(Exception $e) {
					throw new Exception(sprintf("Cannot get list of servers for platfrom ec2: %s", $e->getMessage()));
				}


				if (count($results) > 0) {					
					foreach ($results as $item)
						$this->instancesListCache[$environment->id][$region][$item->id] = $item->state;
				}
			}
	        
			return $this->instancesListCache[$environment->id][$region];
		}
		
		public function GetServerRealStatus(DBServer $DBServer)
		{
			$region = $this->GetServerCloudLocation($DBServer);
			$iid = $DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID);
			
			if (!$iid || !$region) {
				$status = 'not-found';
			}
			elseif (!$this->instancesListCache[$DBServer->GetEnvironmentObject()->id][$region][$iid]) {
		        
				$cs = $this->getCloudStackClient($DBServer->GetEnvironmentObject(), $region);
		        
		        try {
		        	$iinfo = $cs->listVirtualMachines($iid);		        	
		        	$iinfo = (is_array($iinfo)) ? $iinfo[0] : false;
			        
			        if ($iinfo)
			        	$status = $iinfo->state;
			        else
			        	$status = 'not-found';
		        }
		        catch(Exception $e) {
		        	if (stristr($e->getMessage(), "Not Found"))
		        		$status = 'not-found';
		        }
			}
			else {
				$status = $this->instancesListCache[$DBServer->GetEnvironmentObject()->id][$region][$DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID)];
			}
			
			return Modules_Platforms_Cloudstack_Adapters_Status::load($status);
		}
		
		public function TerminateServer(DBServer $DBServer)
		{
		    $cs = $this->getCloudStackClient($DBServer->GetEnvironmentObject(), $this->GetServerCloudLocation($DBServer));
		    $cs->destroyVirtualMachine($DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID));
		    return true;
		}
		
		public function RebootServer(DBServer $DBServer)
		{
			$cs = $this->getCloudStackClient($DBServer->GetEnvironmentObject(), $this->GetServerCloudLocation($DBServer));
		    $cs->rebootVirtualMachine($DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID));
		    return true;
		}
		
		public function RemoveServerSnapshot(DBRole $DBRole)
		{
			//TODO:
		}
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask)
		{
			//TODO:
		}
		
		public function CreateServerSnapshot(BundleTask $BundleTask)
		{
			$DBServer = DBServer::LoadByID($BundleTask->serverId);
        	$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS;
        	$BundleTask->bundleType = SERVER_SNAPSHOT_CREATION_TYPE::CSTACK_DEF;
    	
        	$msg = new Scalr_Messaging_Msg_Rebundle(
        		$BundleTask->id,
				$BundleTask->roleName,
				array()
        	);

        	if (!$DBServer->SendMessage($msg)) {
        		$BundleTask->SnapshotCreationFailed("Cannot send rebundle message to server. Please check event log for more details.");
        		return;
        	}
        	else {
	        	$BundleTask->Log(sprintf(_("Snapshot creating initialized (MessageID: %s). Bundle task status changed to: %s"), 
	        		$msg->messageId, $BundleTask->status
	        	));
        	}
			
			$BundleTask->setDate('started');
        	$BundleTask->Save();
		}
		
		public function GetServerConsoleOutput(DBServer $DBServer)
		{
			//NOT SUPPORTED
		}
		
		public function GetServerExtendedInformation(DBServer $DBServer)
		{
			$cs = $this->getCloudStackClient($DBServer->GetEnvironmentObject(), $this->GetServerCloudLocation($DBServer));
	        
		    try {
	        	$iinfo = $cs->listVirtualMachines($DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID));
	        	$iinfo = (is_array($iinfo)) ? $iinfo[0] : null;
	        } catch (Exception $e) {}
	        
	        if ($iinfo->id && $iinfo->id == $DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID))
	        {
	        	return array(
	        		'ID'			=> $iinfo->id,
	        		'Name'			=> $iinfo->name,
	        		'State'			=> $iinfo->state,
	        		'Group'			=> $iinfo->group,
	        		'Zone'			=> $iinfo->zonename,
	        		'Template name' => $iinfo->templatename,
	        		'Offering name' => $iinfo->serviceofferingname,
	        		'Root device type' => $iinfo->rootdevicetype,
	        		'Internal IP'	=> $iinfo->nic[0]->ipaddress,
	        		'Hypervisor'    => $iinfo->hypervisor
	        	);
	        }
	        
	        return false;
		}
		
		public function LaunchServer(DBServer $DBServer, Scalr_Server_LaunchOptions $launchOptions = null)
		{
			$environment = $DBServer->GetEnvironmentObject();
			
			$farmRole = $DBServer->GetFarmRoleObject();
			
			if (!$launchOptions)
			{
				$launchOptions = new Scalr_Server_LaunchOptions();
				$dbRole = DBRole::loadById($DBServer->roleId);
				
				$launchOptions->imageId = $dbRole->getImageId(SERVER_PLATFORMS::CLOUDSTACK, $DBServer->GetFarmRoleObject()->CloudLocation);
				$launchOptions->serverType = $DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_CLOUDSTACK_SERVICE_OFFERING_ID);
   				$launchOptions->cloudLocation = $DBServer->GetFarmRoleObject()->CloudLocation;
				
				/*
		         * User Data
		         */
		        foreach ($DBServer->GetCloudUserData() as $k=>$v)
	        		$u_data .= "{$k}={$v};";

				$launchOptions->userData = trim($u_data, ";");
				
				$launchOptions->architecture = 'x86_64';
			}
			
			$cs = $this->getCloudStackClient(
		    	$environment, 
		    	$launchOptions->cloudLocation
		    );
		    
		    $networkType = $farmRole->GetSetting(DBFarmRole::SETTING_CLOUDSTACK_NETWORK_TYPE);
			if ($networkType == 'Virtual') {
			    $sharedIpId = $environment->getPlatformConfigValue(self::SHARED_IP_ID.".{$launchOptions->cloudLocation}", false);
			    if (!$sharedIpId)
			    {
			    	$ipResult = $cs->associateIpAddress($launchOptions->cloudLocation);
			    	$ipId = $ipResult->id;
			    	if ($ipId) {
			    		while (true) {
			    			$ipInfo = $cs->listPublicIpAddresses($ipId);
			    			$ipInfo = $ipInfo->publicipaddress[0];
	
			    			if (!$ipInfo)
			    				throw new Exception("Cannot allocate IP address: listPublicIpAddresses -> failed");
			    				
			    			if ($ipInfo->state == 'Allocated') {
			    				$environment->setPlatformConfig(array(self::SHARED_IP_ID.".{$launchOptions->cloudLocation}" => $ipId), false);
			    				$environment->setPlatformConfig(array(self::SHARED_IP.".{$launchOptions->cloudLocation}" => $ipInfo->ipaddress), false);
			    				$environment->setPlatformConfig(array(self::SHARED_IP_INFO.".{$launchOptions->cloudLocation}" => serialize($ipInfo)), false);
			    				
			    				$sharedIpId = $ipId;
			    				break;
			    			} else if ($ipInfo->state == 'Allocating') {
			    				sleep(1);
			    			} else {
			    				throw new Exception("Cannot allocate IP address: ipAddress->state = {$ipInfo->state}");
			    			}
			    		}
			    	}
			    	else 
			    		throw new Exception("Cannot allocate IP address: associateIpAddress -> failed");
			    }
			}
		    
		    $sshKey = Scalr_SshKey::init();
		    try {
				if (!$sshKey->loadGlobalByFarmId($DBFarm->ID, $launchOptions->cloudLocation))
				{
					$key_name = "FARM-{$DBServer->farmId}";
					
					$result = $cs->createSSHKeyPair($key_name);
					if ($result->keypair->privatekey)
					{	
						$sshKey->farmId = $DBServer->farmId;
						$sshKey->clientId = $DBServer->clientId;
						$sshKey->envId = $DBServer->envId;
						$sshKey->type = Scalr_SshKey::TYPE_GLOBAL;
						$sshKey->cloudLocation = $launchOptions->cloudLocation;
						$sshKey->cloudKeyName = $key_name;
						$sshKey->platform = SERVER_PLATFORMS::CLOUDSTACK;
						
						$sshKey->setPrivate($result->keypair->privatekey);
						$sshKey->setPublic($sshKey->generatePublicKey());
						
						$sshKey->save();
		            }
				}
		    } catch (Exception $e) { }
		    
	        $keyName = $sshKey->cloudKeyName;
		    
		    $vResult = $cs->deployVirtualMachine(
		    	$launchOptions->serverType, 
		    	$launchOptions->imageId, 
		    	$launchOptions->cloudLocation,
		    	null, //account
		    	null, // diskoffering
		    	"", //displayName
		    	null, //domainId
		    	$farmRole->GetRoleObject()->name,
		    	null, //hostId
		    	null, //hypervisor
		    	$keyName,
		    	"", //name
		    	$farmRole->GetSetting(DBFarmRole::SETTING_CLOUDSTACK_NETWORK_ID),
		    	null, //securityGroupIds
		    	null, //SecGroupNames
		    	null, //size
		    	base64_encode($launchOptions->userData)
		    );
		    if ($vResult->id) {
	        	$DBServer->SetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID, $vResult->id);
	        	$DBServer->SetProperty(CLOUDSTACK_SERVER_PROPERTIES::CLOUD_LOCATION, $launchOptions->cloudLocation);
	        	$DBServer->SetProperty(CLOUDSTACK_SERVER_PROPERTIES::LAUNCH_JOB_ID, $vResult->jobid);
	        	
		        return $DBServer;
		    } else
		    	throw new Exception(sprintf("Cannot launch new instance: %s", $vResult->errortext));
		}
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message)
		{
			$put = false;
			$put |= $message instanceof Scalr_Messaging_Msg_Rebundle;
			$put |= $message instanceof Scalr_Messaging_Msg_BeforeHostUp;
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
	        	$accessData->apiKey = $environment->getPlatformConfigValue(self::API_KEY);
	        	$accessData->secretKey = $environment->getPlatformConfigValue(self::SECRET_KEY);
	        	$accessData->apiUrl = $environment->getPlatformConfigValue(self::API_URL);
	        	
	        	$message->platformAccessData = $accessData;
			}
		}
		
		public function ClearCache()
		{
			$this->instancesListCache = array();
		}
	}
