<?php
	class Modules_Platforms_Rds_Observers_Rds extends EventObserver
	{
		public $ObserverName = 'RDS';
		
		function __construct()
		{
			parent::__construct();
		}

		/**
		 * Return new instance of AmazonRDS object
		 *
		 * @return AmazonRDS
		 */
		private function GetAmazonRDSClientObject($region)
		{
	    	// Get ClientID from database;
			$clientid = $this->DB->GetOne("SELECT clientid FROM farms WHERE id=?", array($this->FarmID));
			
			// Get Client Object
			$Client = Client::Load($clientid);
			
			$RDSClient = AmazonRDS::GetInstance($Client->AWSAccessKeyID, $Client->AWSAccessKey);
		    $RDSClient->SetRegion($region);
			
			return $RDSClient;
		}
	}
?>