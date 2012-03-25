<?php

class Scalr_UI_Controller_Tools_Aws_Rds_Snapshots extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'instanceId';

	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function viewAction()
	{
		$this->response->page('ui/tools/aws/rds/snapshots.js', array(
			'locations' => self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false)
		));
	}

	public function xListSnapshotsAction()
	{
		$this->request->defineParams(array(
			'cloudLocation', 'dbinstance',
			'sort' => array('type' => 'json', 'default' => array('property' => 'id', 'direction' => 'ASC'))
		));

		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);

		$awsResponse = $amazonRDSClient->DescribeDBSnapshots($this->getParam('dbinstance'));
		$rows = $awsResponse->DescribeDBSnapshotsResult->DBSnapshots->DBSnapshot;
		$rowz = array();

		if ($rows instanceof stdClass)
			$rows = array($rows);

		foreach ($rows as $pv)
			$rowz[] = array(
				"dtcreated"		=> Scalr_Util_DateTime::convertTz($pv->SnapshotCreateTime),
				"port"			=> (string)$pv->Port,
				"status"		=> (string)$pv->Status,
				"engine"		=> (string)$pv->Engine,
				"avail_zone"	=> (string)$pv->AvailabilityZone,
				"idtcreated"	=> Scalr_Util_DateTime::convertTz($pv->InstanceCreateTime),
				"storage"		=> (string)$pv->AllocatedStorage,
				"name"			=> (string)$pv->DBSnapshotIdentifier,
				"id"			=> (string)$pv->DBSnapshotIdentifier,
			);

		$response = $this->buildResponseFromData($rowz);
		$this->response->data($response);
	}

	public function xCreateSnapshotAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);

		$snapId = "scalr-manual-" . dechex(microtime(true)*10000) . rand(0,9);

		try {
			$amazonRDSClient->CreateDBSnapshot($snapId, $this->getParam('dbinstance'));
			$this->db->Execute("INSERT INTO rds_snaps_info SET snapid=?, comment=?, dtcreated=NOW(), region=?",
				array($snapId, "manual RDS instance snapshot", $this->getParam('cloudLocation')));
		} catch (Exception $e) {
			throw new Exception (sprintf(_("Can't create db snapshot: %s"), $e->getMessage()));
		}

		$this->response->success(sprintf(_("DB snapshot '%s' successfully create"), $snapId));
	}

	public function xDeleteSnapshotsAction()
	{
		$this->request->defineParams(array(
			'snapshots' => array('type' => 'json')
		));

		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);

		$i = 0;
		$errors = array();
		foreach ($this->getParam('snapshots') as $snapName) {
			try {
				$amazonRDSClient->DeleteDBSnapshot($snapName);
				$this->db->Execute("DELETE FROM rds_snaps_info WHERE snapid=? ", array($snapName));
				$i++;
			} catch(Exception $e) {
				$errors[] = sprintf(_("Can't delete db snapshot %s: %s"), $snapName, $e->getMessage());
			}
		}
		$message = sprintf(_("%s db snapshot(s) successfully removed"), $i);
		
		if (count($errors))
			$this->response->warning(nl2br(implode("\n", (array_merge(array($message), $errors)))));
		else
			$this->response->success($message);
	}
}
