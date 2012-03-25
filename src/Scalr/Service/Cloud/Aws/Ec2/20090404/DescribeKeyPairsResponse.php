<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeKeyPairsResponse
	{
		public $keySet;
		
		public function __construct(SimpleXMLElement $response)
		{
			$this->keySet = new stdClass();
			$this->keySet->item = array();
			foreach ($response->keySet->item as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
				{
					$itm->{$k} = (string)$v;
				}
				
				$this->keySet->item[] = $itm;
			}
		}		
	}