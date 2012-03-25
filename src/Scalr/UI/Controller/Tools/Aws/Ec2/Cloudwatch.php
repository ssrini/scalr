<?php

class Scalr_UI_Controller_Tools_Aws_Ec2_Cloudwatch extends Scalr_UI_Controller
{
	public function viewAction()
	{
		$amazonCloudWatch = AmazonCloudWatch::GetInstance(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('region')
		);

		$namespace = ($this->getParam('namespace')) ? $this->getParam('namespace') : "AWS/EC2";
		
		$result = $amazonCloudWatch->ListMetrics($namespace, array($this->getParam('object') => $this->getParam('objectId')));
		
		$metric = array();
		
		foreach ($result[$namespace] as $item=>$key)
		{
			$res = $amazonCloudWatch->GetMetricStatistics(
				$item,
				(time()-3600),
				time(),
				array('Average'),
				null,
				300,
				$namespace,
				array($this->getParam('object') => $this->getParam('objectId'))
			);
			
			$unit = $res['unit'];
			$step = 0;
			foreach ($res as $name=>$value){
				if($unit == "Bytes" || $unit == "Bytes/Second" )
				{
					if((float)$value['Average'] >= 1024 && (float)$value['Average'] <= 1048576 && $step < 2)	
						$step = 2;
				
					if((float)$value['Average'] >1048576 && (float)$value['Average'] <= (1048576*1024) && $step < 3)
						$step = 3;
					
					if ((float)$value['Average'] < 1024 && $step < 1)
						$step = 1;
				}
			}
			if($step == 1) $unit = "K".$unit;
			if($step == 2) $unit = "M".$unit;
			if($step == 3) $unit = "G".$unit;
			$metric[] = array(
				'name' => $item,
				'unit' => $unit
			);
		}
			
		$this->response->page('ui/tools/aws/ec2/cloudwatch/view.js', array ('metric' => $metric));
	}
	public function xGetMetricAction()
	{
		$amazonCloudWatch = AmazonCloudWatch::GetInstance(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('region')
		);
		
		$res = $amazonCloudWatch->GetMetricStatistics(
			$this->getParam('metricName'),
			strtotime($this->getParam('startTime')),
			strtotime($this->getParam('endTime')),
			array($this->getParam('type')),
			null,
			$this->getParam('period'),
			$this->getParam('namespace'),
			array($this->getParam('dValue') => $this->getParam('dType'))
		);
		
		$store = array();
		ksort($res);
    	foreach ($res as $time => $val)
		{
			if($time != 'unit') {
				if($this->getParam('Unit') == "MBytes" || $this->getParam('Unit') == "MBytes/Second")
				$store[] = array('time' => date($this->getParam('dateFormat'), $time), 'value' => (float)round($val[$this->getParam('type')]/1024, 2));
			else if($this->getParam('Unit') == "GBytes" || $this->getParam('Unit') == "GBytes/Second")
				$store[] = array('time' => date($this->getParam('dateFormat'), $time), 'value' => (float)round($val[$this->getParam('type')]/1024/1024, 2));
			else 
				$store[] = array('time' => date($this->getParam('dateFormat'), $time), 'value' => (float)round($val[$this->getParam('type')], 2));
		
			}
		}

		$this->response->data(array('data' => $store));
	}
}
