<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_RunInstancesResponse
	{
		public $groupSet;
		public $instancesSet;
		public $reservationId;
		public $ownerId;
		
		public function __construct(SimpleXMLElement $response)
		{			
			$this->reservationId = (string)$response->reservationId;
			$this->ownerId = (string)$response->ownerId;
			
			$this->groupSet = new stdClass();
			$this->groupSet->item = array();
			foreach ($response->groupSet->item as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
					$itm->{$k} = (string)$v;
				
				$this->groupSet->item[] = $itm;
			}
			
			$this->instancesSet = new stdClass();
			$this->instancesSet->item = array();
			foreach ($response->instancesSet->item as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
				{
					if ($k == 'instanceState')
					{
						$itm->instanceState = new stdClass();
						$itm->instanceState->code = (string)$v->code;
						$itm->instanceState->name = (string)$v->name;
					}
					elseif ($k == 'productCodes')
					{
						$itm->productCodes = '';
					}
					elseif ($k == 'placement')
					{
						$itm->placement = new stdClass();
						$itm->placement->availabilityZone = (string)$v->availabilityZone;
					}
					elseif ($k == 'monitoring')
					{
						$itm->monitoring->state = $v->state;
					}
					else
						$itm->{$k} = (string)$v;
				}
				
				$this->instancesSet->item[] = $itm;
			}
		}		
	}