<?php
class Scalr_UI_Controller_Dashboard_Widget_Usagelaststat extends Scalr_UI_Controller_Dashboard_Widget
{
	public function getDefinition()
	{
		return array(
			'type' => 'local'
		);
	}

	public function getContent($params = array())
	{
		$pricing = array('us-east-1','us-west-1', 'us-west-2', 'eu-west-1', 'ap-southeast-1', 'ap-northeast-1', 'sa-east-1');
		foreach ($pricing as $value) {
			if ($value == 'us-west-1' || $value == 'us-east-1') {
				$pricing[$value]['m1.small'] = 0.085;
				$pricing[$value]['m1.large'] = 0.34;
				$pricing[$value]['m1.xlarge'] = 0.68;
				$pricing[$value]['t1.micro'] = 0.02;
				$pricing[$value]['m2.xlarge'] = 0.050;
				$pricing[$value]['m2.2xlarge'] = 1.00;
				$pricing[$value]['m2.4xlarge'] = 2.00;
				$pricing[$value]['c1.medium'] = 0.17;
				$pricing[$value]['c1.xlarge'] = 0.68;
				$pricing[$value]['cc1.4xlarge'] = 0;
				$pricing[$value]['cc2.8xlarge'] = 0;
				$pricing[$value]['cg1.4xlarge'] = 0;
				if($value == 'us-east-1') {
					$pricing[$value]['cc1.4xlarge'] = 1.30;
					$pricing[$value]['cc2.8xlarge'] = 2.40;
					$pricing[$value]['cg1.4xlarge'] = 2.10;
				}

			}
			if ($value == 'us-west-2' || $value == 'eu-west-1' || $value == 'ap-southeast-1') {
				$pricing[$value]['m1.small'] = 0.095;
				$pricing[$value]['m1.large'] = 0.38;
				$pricing[$value]['m1.xlarge'] = 0.76;
				$pricing[$value]['t1.micro'] = 0.025;
				$pricing[$value]['m2.xlarge'] = 0.057;
				$pricing[$value]['m2.2xlarge'] = 1.14;
				$pricing[$value]['m2.4xlarge'] = 2.28;
				$pricing[$value]['c1.medium'] = 0.19;
				$pricing[$value]['c1.xlarge'] = 0.76;
				$pricing[$value]['cc1.4xlarge'] = 0;
				$pricing[$value]['cc2.8xlarge'] = 0;
				$pricing[$value]['cg1.4xlarge'] = 0;
			}

			if ($value == 'ap-northeast-1') {
				$pricing[$value]['m1.small'] = 0.10;
				$pricing[$value]['m1.large'] = 0.40;
				$pricing[$value]['m1.xlarge'] = 0.80;
				$pricing[$value]['t1.micro'] = 0.027;
				$pricing[$value]['m2.xlarge'] = 0.060;
				$pricing[$value]['m2.2xlarge'] = 1.20;
				$pricing[$value]['m2.4xlarge'] = 2.39;
				$pricing[$value]['c1.medium'] = 0.20;
				$pricing[$value]['c1.xlarge'] = 0.80;
				$pricing[$value]['cc1.4xlarge'] = 0;
				$pricing[$value]['cc2.8xlarge'] = 0;
				$pricing[$value]['cg1.4xlarge'] = 0;
			}
			if ($value == 'sa-east-1') {
				$pricing[$value]['m1.small'] = 0.115;
				$pricing[$value]['m1.large'] = 0.46;
				$pricing[$value]['m1.xlarge'] = 0.92;
				$pricing[$value]['t1.micro'] = 0.027;
				$pricing[$value]['m2.xlarge'] = 0.068;
				$pricing[$value]['m2.2xlarge'] = 1.36;
				$pricing[$value]['m2.4xlarge'] = 2.72;
				$pricing[$value]['c1.medium'] = 0.23;
				$pricing[$value]['c1.xlarge'] = 0.92;
				$pricing[$value]['cc1.4xlarge'] = 0;
				$pricing[$value]['cc2.8xlarge'] = 0;
				$pricing[$value]['cg1.4xlarge'] = 0;
			}
		}
		$stat = array();
		$currentTime = getdate();
		$months = array();
		/*$months[$currentTime['mon']-1] = 'recent';
		$months[$currentTime['mon']] = 'current';*/
		$months[1] = 'recent';
		$months[2] = 'current';
		if($currentTime['mon']==1)
			$months[12] = 'recent';
		$sql = 'SELECT `usage`, `month`, `farm_id`, `instance_type`, `cloud_location`  FROM `servers_stats` WHERE `year` = '.($currentTime['year']-1).' AND month IN (1,2)';/*'.($currentTime['mon']-1).','.$currentTime['mon']2.')';*/
		$sql.= " AND `env_id` = ".$this->getEnvironmentId();
		$usages = $this->db->GetAll($sql);
		$farms = self::loadController('Farms')->getList();
		foreach ($usages as $value) {
			//var_dump($value['month']);
			$stat[$value['farm_id']][$months[$value['month']]] += round($pricing[$value['cloud_location']][$value['instance_type']] * round(($value['usage'] / 60), 2), 2);
			$stat[$value['farm_id']]['farm_id'] = $value['farm_id'];
			$stat[$value['farm_id']]['farm'] = $farms[$value['farm_id']]['name'];
		}
		//$stat = $this->buildResponseFromData($stat);
		return $stat;
	}
}