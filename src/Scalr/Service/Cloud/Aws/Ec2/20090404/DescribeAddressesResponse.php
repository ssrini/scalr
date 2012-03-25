<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeAddressesResponse
	{
		public $addressesSet;
		
		public function __construct(SimpleXMLElement $response)
		{
			$this->addressesSet = new stdClass();
			$this->addressesSet->item = array();
			foreach ($response->addressesSet->item as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
				{
					$itm->{$k} = (string)$v;
				}
				
				$this->addressesSet->item[] = $itm;
			}
		}		
	}