<?php

class Scalr_UI_Controller_Tools_Aws_Ec2_Eips extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'elasticIp';

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
		$amazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$this->getParam('cloudLocation'),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);

		$amazonEC2Client->ReleaseAddress($this->getParam('elasticIp'));

		$this->response->success();
	}

	public function associateAction()
	{
		$dbServers = $this->db->GetAll("SELECT server_id FROM servers WHERE platform=? AND status=? AND env_id=?", array(
			SERVER_PLATFORMS::EC2,
			SERVER_STATUS::RUNNING,
			$this->getEnvironmentId()
		));

		if (count($dbServers) == 0)
			throw new Exception("You have no running servers on EC2 platform");

		$servers = array();
		foreach ($dbServers as $dbServer) {
			$dbServer = DBServer::LoadByID($dbServer['server_id']);

			if ($dbServer->GetProperty(EC2_SERVER_PROPERTIES::REGION) == $this->getParam('cloudLocation')) {

			}
		}

		$this->response->page('ui/tools/aws/ec2/eips/associate.js', array(
			'servers' => ''
		));
	}

	public function viewAction()
	{
		$this->response->page('ui/tools/aws/ec2/eips/view.js', array(
			'locations'	=> self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false)
		));
	}

	public function xListEipsAction()
	{
		$amazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$this->getParam('cloudLocation'),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);

		// Rows
		$aws_response = $amazonEC2Client->DescribeAddresses();
		$rowz = $aws_response->addressesSet->item;

		if ($rowz instanceof stdClass)
			$rowz = array($rowz);

		foreach ($rowz as &$pv) {
			$item = array(
				'ipaddress' => $pv->publicIp,
				'instance_id' => $pv->instanceId
			);

			$info = $this->db->GetRow("SELECT * FROM elastic_ips WHERE ipaddress=?", array($pv->publicIp));
			if ($info) {
				$item['farm_id'] = $info['farmid'];
				$item['farm_roleid'] = $info['farm_roleid'];
				$item['server_id'] = $info['server_id'];
				$item['indb'] = true;
				$item['server_index'] = $info['instance_index'];

				//WORKAROUND: EIPS not imported correclty from 1.2 to 2.0
				if (!$item['server_id'] && $info['state'] == 1) {
					try {
						$DBServer = DBServer::LoadByPropertyValue(EC2_SERVER_PROPERTIES::INSTANCE_ID, $item['instance_id']);
						$item['server_id'] = $DBServer->serverId;
					}
					catch(Exception $e){}
				}

				if ($item['farm_roleid']) {
					try {
						$DBFarmRole = DBFarmRole::LoadByID($item['farm_roleid']);
						$item['role_name'] = $DBFarmRole->GetRoleObject()->name;
						$item['farm_name'] = $DBFarmRole->GetFarmObject()->Name;
					}
					catch(Exception $e){}
				}
			} else {
				try {
					$DBServer = DBServer::LoadByPropertyValue(EC2_SERVER_PROPERTIES::INSTANCE_ID, $pv->instanceId);
					$item['server_id'] = $DBServer->serverId;
					$item['farm_id'] = $DBServer->farmId;
				}
				catch(Exception $e){}
			}

			$pv = $item;
		}

		$response = $this->buildResponseFromData($rowz);
		$this->response->data($response);
	}
}
