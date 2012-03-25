<?php
	class Modules_Platforms_Ec2_Observers_Ec2 extends EventObserver
	{
		public $ObserverName = 'EC2';
		
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
	    	$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
		    	$region, 
		    	$environment->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY), 
		    	$environment->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	    	);
			
			return $AmazonEC2Client;
		}
	}
?>