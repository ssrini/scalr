<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_AllocateAddressResponse
	{
		public $publicIp;
		
		public function __construct(SimpleXMLElement $response)
		{
			$this->publicIp = $response->publicIp;
		}		
	}