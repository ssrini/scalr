<?php
class Scalr_UI_Controller_Security_Groups extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'securityGroupId';

	public static function getPermissionDefinitions()
	{
		return array();
	}

	/**
	* View roles listView with filters
	*/
	public function viewAction()
	{		
		if (!$this->getParam('platform'))
			throw new Exception ('Platform should be specified');
		
		$this->response->page('ui/security/groups/view.js', array(
			'locations' => self::loadController('Platforms')->getCloudLocations(array($this->getParam('platform')), false)
		));
	}
	
	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'rules' => array('type' => 'json')
		));
		
		if ($this->getParam('farmRoleId'))
			$securityGroupId = "scalr-role.{$this->getParam('farmRoleId')}";
		else
			$securityGroupId = $this->getParam('securityGroupId');
		
		$rules = $this->getRules($securityGroupId);
		$newRules = $this->getParam('rules');

		foreach ($newRules as $r) {
			if ($r['id']) {
				
			} else {
				$rule = "{$r['ipProtocol']}:{$r['fromPort']}:{$r['toPort']}:{$r['cidrIp']}";
				$id = md5($rule);
				if (!$rules[$id]) {
					$addRulesSet[] = $r;
					if ($r['comment']) {
						$this->db->Execute("REPLACE INTO `comments` SET `env_id` = ?, `comment` = ?, `sg_name` = ?, `rule` = ?", array(
							$this->getEnvironmentId(), $r['comment'], $securityGroupId, $rule
						));
					}
				}
			}
		}
		
		foreach ($rules as $r) {
			$found = false;
			foreach ($newRules as $nR) {
				if ($nR['id'] == $r['id'])
					$found = true;
			}
			
			if (!$found)
				$remRulesSet[] = $r;
		}
		
		if (count($addRulesSet) > 0)
			$this->updateRules($addRulesSet, 'add', $securityGroupId);
		
		if (count($remRulesSet) > 0)
			$this->updateRules($remRulesSet, 'remove', $securityGroupId);
		
		$this->response->success("Security group successfully saved");
	}
	
	public function editAction()
	{		
		if ($this->getParam('farmRoleId'))
			$securityGroupId = "scalr-role.{$this->getParam('farmRoleId')}";
		else
			$securityGroupId = $this->getParam('securityGroupId');
			
		$this->request->setParams(array('securityGroupId' => $securityGroupId));
		
		$rules = $this->getRules($securityGroupId);
		foreach ($rules as &$rule) {
			$rule['comment'] = $this->db->GetOne("SELECT `comment` FROM `comments` WHERE `env_id` = ? AND `rule` = ? AND `sg_name` = ?", array(
				$this->getEnvironmentId(), $rule['rule'], $securityGroupId
			));
		}
		
		$this->response->page('ui/security/groups/edit.js', array(
			'securityGroupId' => $securityGroupId,
			'rules' => $rules
		));
	}
	
	public function xRemoveAction()
	{
		$this->request->defineParams(array(
			'groups' => array('type' => 'json')
		));

		$platformClient = $this->getPlatformClient();
		
		foreach ($this->getParam('groups') as $groupName) {
			try {
				//TODO: Multiplatform
				$platformClient->DeleteSecurityGroup($groupName);
			} catch (Exception $e){}
		}

		$this->response->success('Selected security groups successfully removed');
	}
	
	public function xListGroupsAction()
	{
		if (!$this->getParam('platform'))
			throw new Exception ('Platform should be specified');
		
		switch ($this->getParam('platform')) {
			case SERVER_PLATFORMS::EC2:
				$platformClient = $this->getPlatformClient();
				
				$aws_response = $platformClient->DescribeSecurityGroups();
				
				$rows = $aws_response->securityGroupInfo->item;
				foreach ($rows as $row)
				{					
					// Show only scalr security groups
					if (stristr($row->groupName, CONFIG::$SECGROUP_PREFIX) || stristr($row->groupName, "scalr-role.") || $this->getParam('showAll'))
						$rowz[] = array('id' => $row->groupName, 'name' => $row->groupName, 'description' => $row->groupDescription);
				}
				
				break;
		}
		
		$response = $this->buildResponseFromData($rowz, array('name', 'description'));

		$this->response->data($response);
	}
	
	private function getRules($securityGroupId)
	{
		$platformClient = $this->getPlatformClient();
		$rules = array();
		switch ($this->getParam('platform')) {
			case SERVER_PLATFORMS::EC2:
				$sgInfo = $platformClient->DescribeSecurityGroups($securityGroupId);
				$sgInfo = $sgInfo->securityGroupInfo->item;
				if (!is_array($sgInfo->ipPermissions->item))
					$sgInfo->ipPermissions->item = array($sgInfo->ipPermissions->item);
				$ipPermissions = $sgInfo->ipPermissions;
				foreach ($ipPermissions->item as $rule) {
					
					if (!is_array($rule->ipRanges->item))
						$rule->ipRanges->item = array($rule->ipRanges->item);
					
					foreach ($rule->ipRanges->item as $ipRange) {
						if ($ipRange) {
							$r = array(
								'ipProtocol' => $rule->ipProtocol,
								'fromPort'	=> $rule->fromPort,
								'toPort' => $rule->toPort
							);
							
							$r['cidrIp'] = $ipRange->cidrIp;
							$r['rule'] = "{$r['ipProtocol']}:{$r['fromPort']}:{$r['toPort']}:{$r['cidrIp']}";
							$r['id'] = md5($r['rule']);
							
							if (!$rules[$r['id']]) {
								$rules[$r['id']] = $r;
							}
						}
					}
				}		
			break;
		}
		
		return $rules;
	}
	
	private function updateRules(array $rules, $method, $securityGroupId)
	{
		$platformClient = $this->getPlatformClient();
		
		switch ($this->getParam('platform'))
		{
			case SERVER_PLATFORMS::EC2:
				
				$ipPermissionSet = new IpPermissionSetType();
				foreach ($rules as $rule) {
					$ipPermissionSet->AddItem(
						$rule['ipProtocol'], 
						$rule['fromPort'], 
						$rule['toPort'], 
						null, 
						array($rule['cidrIp'])
					);
				}
				
				$accountId = $this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID);
				
				if ($method == 'add')
					$platformClient->AuthorizeSecurityGroupIngress($accountId, $securityGroupId, $ipPermissionSet);
				else
					$platformClient->RevokeSecurityGroupIngress($accountId, $securityGroupId, $ipPermissionSet);
				
				break;
		}
	}
	
	private function getPlatformClient()
	{
		if (!$this->getParam('platform'))
			throw new Exception ('Platform should be specified');
		
		switch ($this->getParam('platform')) {
			case SERVER_PLATFORMS::EC2:
				return Scalr_Service_Cloud_Aws::newEc2(
					$this->getParam('cloudLocation'),
					$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
					$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
				);
			break;
			
			default:
				throw new Exception("Platfrom not suppored");
				break;
		}
	}
}
