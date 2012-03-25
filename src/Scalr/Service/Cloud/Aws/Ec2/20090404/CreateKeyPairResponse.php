<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_CreateKeyPairResponse
	{
		public $keyName;
		public $keyFingerprint;
		public $keyMaterial;
		
		public function __construct(SimpleXMLElement $response)
		{			
			$this->keyName = (string)$response->keyName;
			$this->keyFingerprint = (string)$response->keyFingerprint;
			$this->keyMaterial = (string)$response->keyMaterial;
		}		
	}