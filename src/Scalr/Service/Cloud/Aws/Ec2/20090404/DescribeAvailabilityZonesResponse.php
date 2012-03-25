<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeAvailabilityZonesResponse
	{
		public $availabilityZoneInfo;
		
		public function __construct(SimpleXMLElement $response)
		{			
			$this->availabilityZoneInfo = new stdClass();
			$this->availabilityZoneInfo->item = array();
			foreach ($response->availabilityZoneInfo->item as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
				{
					$itm->{$k} = (string)$v;
				}
				
				$this->availabilityZoneInfo->item[] = $itm;
			}
		}		
	}