<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_CreateVolumeResponse
	{
		public $volumeId;
		public $size;
		public $status;
		public $createTime;
		public $availabilityZone;
		public $snapshotId;
		
		public function __construct(SimpleXMLElement $response)
		{			
			$this->volumeId = (string)$response->volumeId;
			$this->size = (string)$response->size;
			$this->status = (string)$response->status;
			$this->createTime = (string)$response->createTime;
			$this->availabilityZone = (string)$response->availabilityZone;
			$this->snapshotId = (string)$response->snapshotId;
		}		
	}