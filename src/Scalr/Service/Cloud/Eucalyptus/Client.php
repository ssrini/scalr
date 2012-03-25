<?php

	class Scalr_Service_Cloud_Eucalyptus_Client extends Scalr_Service_Cloud_Aws_Ec2_20090404_Client
	{
		/**
		 * 
		 * Constructor
		 * ec2 URL: http://192.168.1.100:8773/Service/Eucalyptus
		 * 
		 * @param string $accessKey
		 * @param string $accessKeyId
		 * @param string $ec2Url
		 */
		public function __construct($accessKey, $accessKeyId, $ec2Url) 		
		{
	      	parent::__construct();
			
			$this->accessKey = $accessKey;
			$this->accessKeyId = $accessKeyId;
			
			$this->ec2Url = $ec2Url;
			//$this->uri = '/services/Eucalyptus/';
		}
	} 
?>
