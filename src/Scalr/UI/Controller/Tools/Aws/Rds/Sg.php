<?php

class Scalr_UI_Controller_Tools_Aws_Rds_Sg extends Scalr_UI_Controller
{
	public function viewAction()
	{
		$this->response->page('ui/tools/aws/rds/sg/view.js', array(
			'locations'	=> self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false)
		));
	}
	
	public function xListAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		
		// Rows		
		$aws_response = $amazonRDSClient->DescribeDBSecurityGroups();
		$result = json_decode(json_encode($aws_response->DescribeDBSecurityGroupsResult), true);
		$sGroups = $result['DBSecurityGroups']['DBSecurityGroup'];
		if ($sGroups['DBSecurityGroupName'])
			$sGroups = array($sGroups);
		
		$response = $this->buildResponseFromData($sGroups, array('DBSecurityGroupDescription', 'DBSecurityGroupName'));
		
		$this->response->data($response);
	}

	public function xCreateAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		
		$amazonRDSClient->CreateDBSecurityGroup($this->getParam('dbSecurityGroupName'), $this->getParam('dbSecurityGroupDescription'));
		
		$this->response->success("DB security group successfully created");
	}

	public function xDeleteAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
			
		$amazonRDSClient->DeleteDBSecurityGroup($this->getParam('dbSgName'));
		$this->response->success("DB security group successfully removed");
	}
	
	public function editAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		
		$response = $amazonRDSClient->DescribeDBSecurityGroups($this->getParam('dbSgName'));	
		$result = json_decode(json_encode($response->DescribeDBSecurityGroupsResult), true);
		$group = $result['DBSecurityGroups']['DBSecurityGroup'];
		
		if ($group)
		{
			if ($group['IPRanges']['IPRange']['CIDRIP'])
				$group['IPRanges']['IPRange'] = array($group['IPRanges']['IPRange']);
				
			foreach ($group['IPRanges']['IPRange'] as $r)
				$ipRules[] = $r;
							
			if ($group['EC2SecurityGroups']['EC2SecurityGroup']['EC2SecurityGroupOwnerId'])
				$group['EC2SecurityGroups']['EC2SecurityGroup'] = array($group['EC2SecurityGroups']['EC2SecurityGroup']);
				
			foreach ($group['EC2SecurityGroups']['EC2SecurityGroup'] as $r)
				$groupRules[] = $r;
		}
		
		$rules = array('ipRules' => $ipRules, 'groupRules' => $groupRules);
		
		$this->response->page('ui/tools/aws/rds/sg/edit.js', array('rules' => $rules));
	}
	
	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'rules' => array('type' => 'json')
		));
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);			
		
		$response = $amazonRDSClient->DescribeDBSecurityGroups($this->getParam('dbSgName'));	
		$result = json_decode(json_encode($response->DescribeDBSecurityGroupsResult), true);
		$group = $result['DBSecurityGroups']['DBSecurityGroup'];
		
		$rules = array();
		if ($group)
		{
			if ($group['IPRanges']['IPRange']['CIDRIP'])
				$group['IPRanges']['IPRange'] = array($group['IPRanges']['IPRange']);
				
			foreach ($group['IPRanges']['IPRange'] as $r) {
				$r['id'] = md5($r['CIDRIP']);
				$rules[$r['id']] = $r;
			}
							
			if ($group['EC2SecurityGroups']['EC2SecurityGroup']['EC2SecurityGroupOwnerId'])
				$group['EC2SecurityGroups']['EC2SecurityGroup'] = array($group['EC2SecurityGroups']['EC2SecurityGroup']);
				
			foreach ($group['EC2SecurityGroups']['EC2SecurityGroup'] as $r) {
				$r['id'] = md5($r['EC2SecurityGroupName'].$r['EC2SecurityGroupOwnerId']);
				$rules[$r['id']] = $r;
			}
		}
		
		foreach ($rules as $id => $r) {
			$found = false;
			foreach ($this->getParam('rules') as $rule){
				if ($rule['Type'] == 'CIDR IP')
					$rid = md5($rule['CIDRIP']);
				else 
					$rid = md5($rule['EC2SecurityGroupName'].$rule['EC2SecurityGroupOwnerId']);
					
				if ($id == $rid)
					$found = true;
			}
			
			if (!$found) {
				if ($r['CIDRIP'])
					$amazonRDSClient->RevokeDBSecurityGroupIngress($this->getParam('dbSgName'), $r['CIDRIP']);
				else
					$amazonRDSClient->RevokeDBSecurityGroupIngress($this->getParam('dbSgName'), null, $r['EC2SecurityGroupName'], $r['EC2SecurityGroupOwnerId']);
			}
		}
		
		foreach ($this->getParam('rules') as $rule){
			
			if($rule['Status'] == 'new'){
				if ($rule['Type'] == 'CIDR IP')
					$amazonRDSClient->AuthorizeDBSecurityGroupIngress($this->getParam('dbSgName'), $rule['CIDRIP']);
				else
			    	$amazonRDSClient->AuthorizeDBSecurityGroupIngress($this->getParam('dbSgName'), null, $rule['EC2SecurityGroupName'], $rule['EC2SecurityGroupOwnerId']);
			}
		}
		$this->response->success("DB security group successfully updated");
	}
}