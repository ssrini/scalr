<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeImagesResponse
	{
		public $imageSet;
		
		public function __construct(SimpleXMLElement $response)
		{
			$this->imageSet = new stdClass();
			$this->imageSet->item = array();
			foreach ($response->imagesSet->item as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
				{
					if ($k == 'productCodes')
					{
						//TODO:
					}
					else
						$itm->{$k} = (string)$v;
				}
				
				$this->imageSet->item[] = $itm;
			}
		}		
	}