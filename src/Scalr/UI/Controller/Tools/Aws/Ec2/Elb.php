<?php

class Scalr_UI_Controller_Tools_Aws_Ec2_Elb extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'elbName';

	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function xDeleteAction()
	{
		$roleid = $this->db->GetOne("SELECT farm_roleid FROM farm_role_settings WHERE name=? AND value=?",
		array(
			DBFarmRole::SETTING_BALANCING_NAME,
			$this->getParam('elbName')
		));

		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);

		$amazonELBClient->DeleteLoadBalancer($this->getParam('elbName'));
		
		if ($roleid) {
			$DBFarmRole = DBFarmRole::LoadByID($roleid);
			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB, 0);
			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME, "");
			$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_NAME, "");
		}

		$this->response->success("Selected Elastic Load Balancers successfully removed");
	}

	public function viewAction()
	{
		$this->response->page('ui/tools/aws/ec2/elb/view.js', array(
			'locations'	=> self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false)
		));
	}

	public function xDeleteListenersAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);
		
		$amazonELBClient->DeleteLoadBalancerListeners($this->getParam('elbName'), array($this->getParam('lbPort')));
		
		$this->response->success('Listener successfully removed from load balancer');
	}
	
	public function xCreateListenersAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);
		
		$listeners = new ELBListenersList();
		$listeners->AddListener($this->getParam('protocol'), $this->getParam('lbPort'), $this->getParam('instancePort'), $this->getParam('certificateId'));
		
		$res = $amazonELBClient->CreateLoadBalancerListeners($this->getParam('elbName'), $listeners);
		
		$this->response->success(_("New listener successfully created on load balancer"));
	}
	
	public function xDeregisterInstanceAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);
		
		$res = $amazonELBClient->DeregisterInstancesFromLoadBalancer($this->getParam('elbName'), array($this->getParam('awsInstanceId')));
		$this->response->success(_("Instance successfully deregistered from the load balancer"));
	}
	
	public function instanceHealthAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);
		
		$info = $amazonELBClient->DescribeInstanceHealth($this->getParam('elbName'), array($this->getParam('awsInstanceId')));
		$info = (array)$info->DescribeInstanceHealthResult->InstanceStates->member;
		
		$this->response->page('ui/tools/aws/ec2/elb/instanceHealth.js', $info);
	}
	
	public function xDeleteSpAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);
		
		$res = $amazonELBClient->DeleteLoadBalancerPolicy($this->getParam('elbName'), $this->getParam('policyName'));
		$this->response->success(_("Stickiness policy successfully removed"));
	}
	
	public function xCreateSpAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);
		
		if ($this->getParam('policyType') == 'AppCookie')
			$amazonELBClient->CreateAppCookieStickinessPolicy($this->getParam('elbName'), $this->getParam('policyName'), $this->getParam('cookieSettings'));
		else
			$amazonELBClient->CreateLBCookieStickinessPolicy($this->getParam('elbName'), $this->getParam('policyName'), $this->getParam('cookieSettings'));
			
		$this->response->success(_("Stickiness policy successfully created"));
	}
	
	public function xAssociateSpAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);
		
			$amazonELBClient->SetLoadBalancerPoliciesOfListener($this->getParam('elbName'), $this->getParam('elbPort'), $this->getParam('policyName'));
			
		$this->response->success(_("Stickiness policies successfully associated with listener"));
	}
	
	public function detailsAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);
		
		$info = $amazonELBClient->DescribeLoadBalancers(array($this->getParam('elbName')));
		$elb = @json_decode(@json_encode($info),1);
		$elb = $elb['DescribeLoadBalancersResult']['LoadBalancerDescriptions']['member'];
		
		
		if ($elb['Policies']['AppCookieStickinessPolicies']['member']["PolicyName"])
			$elb['Policies']['AppCookieStickinessPolicies']['member'] = array($elb['Policies']['AppCookieStickinessPolicies']['member']);
			
		$policies = array();
		foreach ($elb['Policies']['AppCookieStickinessPolicies']['member'] as $member) {
			$member['PolicyType'] = 'AppCookie';
			$member['CookieSettings'] = $member['CookieName'];
			unset($member['CookieName']);
			$policies[] = $member;
		}
			
		if ($elb['Policies']['LBCookieStickinessPolicies']['member']["PolicyName"])
			$elb['Policies']['LBCookieStickinessPolicies']['member'] = array($elb['Policies']['LBCookieStickinessPolicies']['member']);
	
		foreach ($elb['Policies']['LBCookieStickinessPolicies']['member'] as $member) {
			$member['PolicyType'] = 'LbCookie';
			$member['CookieSettings'] = $member['CookieExpirationPeriod'];
			unset($member['CookieExpirationPeriod']);
			$policies[] = $member;
		}
			
		$elb['Policies'] = $policies;
		
		if (!is_array($elb['AvailabilityZones']['member']))
			$elb['AvailabilityZones']['member'] = array($elb['AvailabilityZones']['member']);
		$elb['AvailabilityZones'] = $elb['AvailabilityZones']['member'];

		if ($elb['ListenerDescriptions']['member']["PolicyNames"])
			$elb['ListenerDescriptions']['member'] = array($elb['ListenerDescriptions']['member']);
		$elb['ListenerDescriptions'] = $elb['ListenerDescriptions']['member'];
		
		if ($elb['Instances']['member']["InstanceId"])
			$elb['Instances']['member'] = array($elb['Instances']['member']);
		$elb['Instances'] = $elb['Instances']['member'];
		
		$this->response->page('ui/tools/aws/ec2/elb/details.js', array('elb' => $elb));
	}
	
	public function xListElasticLoadBalancersAction()
	{
		$amazonELBClient = Scalr_Service_Cloud_Aws::newElb(
			$this->getParam('cloudLocation'), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);

		// Rows
		$aws_response = $amazonELBClient->DescribeLoadBalancers();
		$lb = (array)$aws_response->DescribeLoadBalancersResult->LoadBalancerDescriptions;
		
		$rowz = $lb['member'];
				
		if (!is_array($rowz))
			$rowz = array($rowz);
			
		$rowz1 = array();
		foreach ($rowz as $pk=>$pv)
		{
			if (!((string)$pv->DNSName))
				continue;
			
			$roleid = $this->db->GetOne("SELECT farm_roleid FROM farm_role_settings WHERE name=? AND value=?", 
				array(DBFarmRole::SETTING_BALANCING_HOSTNAME, (string)$pv->DNSName)
			);
			
			$farmId = false;
			$farmRoleId = false;
			$farmName = false;
			$roleName = false;
			
			if ($roleid) {
				try {
					$DBFarmRole = DBFarmRole::LoadByID($roleid);
					$farmId = $DBFarmRole->FarmID;
					$farmRoleId = $roleid;
					$farmName = $DBFarmRole->GetFarmObject()->Name;
					$roleName = $DBFarmRole->GetRoleObject()->name;
				}
				catch(Exception $e) {}
			}
			
			$rowz1[] = array(
				"name"		=> (string)$pv->LoadBalancerName,
				"dtcreated"	=> (string)$pv->CreatedTime,
				"dnsName"	=> (string)$pv->DNSName,
				"farmId"	=> $farmId,
				"farmRoleId" => $farmRoleId,
				"farmName"	=> $farmName,
				"roleName"	=> $roleName
			);
		}
		

		$response = $this->buildResponseFromData($rowz1, array('name', 'dnsname', 'farmName', 'roleName'));
		foreach($response['data'] as &$row) {
			$row['dtcreated'] = Scalr_Util_DateTime::convertTz($row['dtcreated']);
		}

		$this->response->data($response);
	}
}
