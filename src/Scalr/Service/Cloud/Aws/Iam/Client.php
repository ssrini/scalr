<?php
	class Scalr_Service_Cloud_Aws_Iam_Client extends Scalr_Service_Cloud_Aws_Iam_20100508_Client
	{
		/**
		 * 
		 * Constructor
		 * 
		 * @param string $accessKey
		 * @param string $accessKeyId
		 */
		public function __construct($accessKey, $accessKeyId) 		
		{
	      	parent::__construct();
			
			$this->accessKey = $accessKey;
			$this->accessKeyId = $accessKeyId;
			
			$this->ec2Url = 'https://iam.amazonaws.com';
		}
	} 
?>