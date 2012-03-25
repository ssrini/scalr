<?php
	class Modules_Platforms_Cloudstack_Observers_Cloudstack extends EventObserver
	{
		public $ObserverName = 'Cloudstack';
		
		function __construct()
		{
			parent::__construct();
		}
		
		private function getCloudStackClient($environment, $cloudLoction=null)
		{
			return Scalr_Service_Cloud_Cloudstack::newCloudstack(
				$environment->getPlatformConfigValue(Modules_Platforms_Cloudstack::API_URL),
				$environment->getPlatformConfigValue(Modules_Platforms_Cloudstack::API_KEY),
				$environment->getPlatformConfigValue(Modules_Platforms_Cloudstack::SECRET_KEY)
			);
		}
				
		public function OnHostInit(HostInitEvent $event)
		{
			if ($event->DBServer->platform != SERVER_PLATFORMS::CLOUDSTACK)
				return;

			if ($event->DBServer->farmRoleId) {
				$dbFarmRole = $event->DBServer->GetFarmRoleObject();
				$networkType = $dbFarmRole->GetSetting(DBFarmRole::SETTING_CLOUDSTACK_NETWORK_TYPE);
				if ($networkType == 'Direct')
					return true;
			} 
			
			try {
				$environment = $event->DBServer->GetEnvironmentObject();
				$cloudLocation = $event->DBServer->GetCloudLocation();
				
				$sharedIpId = $environment->getPlatformConfigValue(Modules_Platforms_Cloudstack::SHARED_IP_ID.".{$cloudLocation}", false);
					
				$cs = $this->getCloudStackClient(
			    	$environment, 
			    	$cloudLocation
			    );
				
				// Create port forwarding rules for scalarizr
	        	$port = $environment->getPlatformConfigValue(Modules_Platforms_Cloudstack::SZR_PORT_COUNTER.".{$cloudLocation}", false);
	        	if (!$port)
	        		$port = 35000;
	        	else
	        		$port++;
	        		
	        	$result1 = $cs->createPortForwardingRule($sharedIpId, 8013, "tcp", $port, $event->DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID));
	        	$result2 = $cs->createPortForwardingRule($sharedIpId, 8014, "udp", $port, $event->DBServer->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID));
	        	
	        	$event->DBServer->SetProperties(array(
	        		SERVER_PROPERTIES::SZR_CTRL_PORT => $port,
	        		SERVER_PROPERTIES::SZR_SNMP_PORT => $port
	        	));
	        	
	        	$environment->setPlatformConfig(array(Modules_Platforms_Cloudstack::SZR_PORT_COUNTER.".{$cloudLocation}" => $port), false);		
			} catch (Exception $e) {
				$this->Logger->info(new FarmLogMessage($this->FarmID, 
					sprintf(_("Cloudstack handler failed: %s."), $e->getMessage())
				));
			}
		}
	}
?>