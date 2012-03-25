<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeVolumesResponse
	{
		public $volumeSet;
		
		public function __construct(SimpleXMLElement $response)
		{			
			$this->volumeSet = new stdClass();
			$this->volumeSet->item = array();
			foreach ($response->volumeSet->item as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
				{
					$itm->{$k} = (string)$v;
				}
				
				$this->volumeSet->item[] = $itm;
			}
		}		
	}