<?php
class Scalr_UI_Controller_Statistics extends Scalr_UI_Controller
{
	public function defaultAction()
	{
		$this->serversUsageAction();
	}
	
	public function serversUsageAction()
	{
		$years = array();
		$results = $this->db->GetAll('SELECT `year` FROM servers_stats GROUP BY `year`');
		foreach ($results as $key => $value)
			$years[] = $value['year'];
		
		$envs = array();
		$envs[0] = 'All environments';
		foreach($this->user->getEnvironments() as $key => $value)
			$envs[$value['id']] = $value['name'];

		$this->response->page('ui/statistics/serversusage.js', array('years'=>$years, 'env'=>$envs));
	}
	
	public function xListFarmsAction() 
	{
		$sql = "SELECT id, name FROM farms WHERE env_id = ?"; //controller Farms getList
		$results = array();
		$results[0] = array(
			'id' => '0',
			'name' => 'All farms'
		);
		foreach ($this->db->GetAll($sql, array($this->getParam('envId'))) as $key => $value) {
			$results[] = $value;
		}
		
		$this->response->data(array('data'=>$results));
	}
	
	public function xListServersUsageAction() 
	{
		foreach($this->user->getEnvironments() as $key => $value)
			$env[] = $value['id'];
		$env = implode(',',$env);
		$params = array($this->getParam('year'));
		
		$sql = 'SELECT SUM(`usage`) as `usage`, `month`, `instance_type` as `instanceType`, `cloud_location` as `cloudLocation` FROM `servers_stats` WHERE `year` = ?';
		if($this->getParam('envId') != 0) {
			$sql.= " AND `env_id` = ?";
			$params[] = $this->getParam('envId');
		}else 
			$sql.= " AND `env_id` IN (".$env.")";
		if($this->getParam('farmId') != 0) {
			$sql.= " AND `farm_id` = ?";
			$params[] = $this->getParam('farmId');
		}
		$sql.= 'GROUP BY `month`, `instance_type`, `cloud_location`';
		
		$usages = $this->db->GetAll($sql, $params);
		$result = array();
		
		foreach ($usages as $value) {
			$key = "{$value['cloudLocation']}-{$value['instanceType']}";
			if (! isset($result[$key])) {
				$result[$key] = array(
					'cloudLocation' => $value['cloudLocation'],
					'instanceType' => $value['instanceType'],
					'usage' => array()
				);
			}
			
			$result[$key]['usage'][date( 'M', mktime(0, 0, 0, $value['month']))] = round(($value['usage'] / 60), 2);
		}

		$response = $this->buildResponseFromData($result);
		if ($this->getParam('action') == "download") {
			$fileContent = array();
			$fileContent[] = "cloudLocation;instanceType;Jan;Feb;Mar;Apr;May;Jun;Jul;Aug;Sep;Oct;Nov;Dec\r\n";

			foreach($response["data"] as $data) {
				$fileContent[] = "{$data['cloudLocation']};{$data['instanceType']};{$data['usage']['Jan']};{$data['usage']['Feb']};{$data['usage']['Mar']};{$data['usage']['Apr']};{$data['usage']['May']};{$data['usage']['Jun']};{$data['usage']['Jul']};{$data['usage']['Aug']};{$data['usage']['Sep']};{$data['usage']['Oct']};{$data['usage']['Nov']};{$data['usage']['Dec']}";
			}

			$this->response->setHeader('Content-Encoding', 'utf-8');
			$this->response->setHeader('Content-Type', 'text/csv', true);
			$this->response->setHeader('Expires', 'Mon, 10 Jan 1997 08:00:00 GMT');
			$this->response->setHeader('Pragma', 'no-cache');
			$this->response->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate');
			$this->response->setHeader('Cache-Control', 'post-check=0, pre-check=0');
			$this->response->setHeader('Content-Disposition', 'attachment; filename=' . "usageStatistic_" . Scalr_Util_DateTime::convertTz(time(), 'M-j-Y') . ".csv");
			$this->response->setResponse(implode("\n", $fileContent));
		} else
			$this->response->data($response);
	}

    public function xUsageLastStatAction()
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
        $months[$currentTime['mon']-1] = 'recent';
        $months[$currentTime['mon']] = 'current';
        if($currentTime['mon']==1)
            $months[12] = 'recent';
        $sql = 'SELECT `usage`, `month`, `farm_id`, `instance_type`, `cloud_location`  FROM `servers_stats` WHERE `year` = '.($currentTime['year']-1).' AND month IN (1,2)';
        $sql.= " AND `env_id` = ".$this->getEnvironmentId();
        $usages = $this->db->GetAll($sql);
        $farms = self::loadController('Farms')->getList();
        foreach ($usages as $value) {
            $stat[$value['farm_id']][$months[$value['month']]] += round($pricing[$value['cloud_location']][$value['instance_type']] * round(($value['usage'] / 60), 2), 2);
            $stat[$value['farm_id']]['farm_id'] = $value['farm_id'];
            $stat[$value['farm_id']]['farm'] = $farms[$value['farm_id']]['name'];
        }
        $stat = $this->buildResponseFromData($stat);
        $this->response->data($stat);
    }
}	