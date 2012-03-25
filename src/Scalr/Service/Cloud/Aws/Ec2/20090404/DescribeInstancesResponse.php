<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeInstancesResponse
	{
		public $reservationSet;
		
		public function __construct(SimpleXMLElement $response)
		{			
			$this->reservationSet = new stdClass();
			$this->reservationSet->item = array();
			foreach ($response->reservationSet->item as $rset)
			{
				$itm = new stdClass();
				$itm->reservationId = (string)$rset->reservationId;
				$itm->ownerId = (string)$rset->ownerId;
				foreach ($rset->groupSet->item as $item)
				{
					$itm->groupSet = new stdClass();
					$itm->groupSet->item = array();
					
					$iitm = new stdClass();
					foreach ($item as $k=>$v)
						$iitm->{$k} = (string)$v;
					
					$itm->groupSet->item[] = $iitm;
				}
				
				$itm->instancesSet = new stdClass();
				$itm->instancesSet->item = array();
				foreach ($rset->instancesSet->item as $item)
				{
					$iitm = new stdClass();
					foreach ($item as $k=>$v)
					{
						if ($k == 'instanceState')
						{
							$iitm->instanceState = new stdClass();
							$iitm->instanceState->code = (string)$v->code;
							$iitm->instanceState->name = (string)$v->name;
						}
						elseif ($k == 'productCodes')
						{
							$iitm->productCodes = '';
						}
						elseif ($k == 'placement')
						{
							$iitm->placement = new stdClass();
							$iitm->placement->availabilityZone = (string)$v->availabilityZone;
						}
						elseif ($k == 'monitoring')
						{
							$iitm->monitoring->state = ((string)$v->state == 'false') ? false : true;
						}
						else
							$iitm->{$k} = (string)$v;
					}
					
					$itm->instancesSet->item[] = $iitm;
				}

				$this->reservationSet->item[] = $itm;
			}
		}		
	}