<?php
	class Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeSecurityGroupsResponse
	{
		public $securityGroupInfo;
		
		public function __construct(SimpleXMLElement $response)
		{			
			$this->securityGroupInfo = new stdClass();
			$this->securityGroupInfo->item = array();
			foreach ($response->securityGroupInfo->item as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
				{
					if ($k == 'ipPermissions')
					{
						$itm->ipPermissions = new stdClass();
						
						if ($v->item)
						{
							$itm->ipPermissions->item = array();
							
							foreach ($v->item as $rule)
							{
								$ritem = new stdClass();
								foreach ($rule as $kk=>$vv)
								{
									if ($kk == 'ipRanges')
									{
										$ritem->ipRanges->item->cidrIp = (string)$vv->item->cidrIp;
									}
									elseif ($kk == 'groups')
									{
										//TODO:
									}
									else
										$ritem->{$kk} = (string)$vv;
								}
								
								$itm->ipPermissions->item[] = $ritem;
							}

						}
					}
					else
						$itm->{$k} = (string)$v;
				}
				
				$this->securityGroupInfo->item[] = $itm;
			}
		}		
	}