<?php

class Scalr_UI_Controller_Tools_Aws_Rds_Instances extends Scalr_UI_Controller
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
		$this->response->page('ui/tools/aws/rds/instances/view.js', array(
			'locations' => self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false)
		));
	}

	public function createAction()
	{
		$this->response->page('ui/tools/aws/rds/instances/create.js', array(
			'locations' => self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false)
		));
	}
	
	public function editAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		$info = $amazonRDSClient->DescribeDBInstances($this->getParam(self::CALL_PARAM_NAME));
		$dbinstance = $info->DescribeDBInstancesResult->DBInstances->DBInstance;
		
		$this->response->page('ui/tools/aws/rds/instances/create.js', array(
			'locations' => self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false),
			'instance' => $dbinstance
		));
	}
	
	public function xModifyInstanceAction(){
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		$amazonRDSClient->ModifyDBInstance(
			$this->getParam('DBInstanceIdentifier'),
			$this->getParam('DBParameterGroup'),
			$this->getParam('DBSecurityGroups'),
			$this->getParam('PreferredMaintenanceWindow'),
			$this->getParam('MasterUserPassword') ? $this->getParam('MasterUserPassword') : null,
			$this->getParam('AllocatedStorage'),
			$this->getParam('DBInstanceClass'),
			null,
			$this->getParam('BackupRetentionPeriod'),
			$this->getParam('PreferredBackupWindow'),
			($this->getParam('MultiAZ') ? 1 : 0)
		);
		$this->response->success("DB Instance successfully modified");
	}
	
	public function xLaunchInstanceAction(){
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		$amazonRDSClient->CreateDBInstance(
			$this->getParam('DBInstanceIdentifier'),
			$this->getParam('AllocatedStorage'),
			$this->getParam('DBInstanceClass'),
			$this->getParam('Engine'),
			$this->getParam('MasterUsername'),
			$this->getParam('MasterUserPassword'),
			$this->getParam('Port'),
			$this->getParam('DBName'),
			$this->getParam('DBParameterGroup'),
			$this->getParam('DBSecurityGroups'),
			$this->getParam('AvailabilityZone'),
			$this->getParam('PreferredMaintenanceWindow'),
			$this->getParam('BackupRetentionPeriod'),
			$this->getParam('PreferredBackupWindow'),
			$this->getParam('MultiAZ')
		);
		$this->response->success("DB Instance successfully created");
	}
	public function detailsAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);

		$info = $amazonRDSClient->DescribeDBInstances($this->getParam(self::CALL_PARAM_NAME));
		$dbinstance = $info->DescribeDBInstancesResult->DBInstances->DBInstance;

		$sGroups = array();
		$sg = (array) $dbinstance->DBSecurityGroups;
		if (is_array($sg['DBSecurityGroup'])) {
			foreach ($sg['DBSecurityGroup'] as $g)
				$sGroups[] = "{$g->DBSecurityGroupName} ({$g->Status})";
		} else
			$sGroups[] = "{$sg['DBSecurityGroup']->DBSecurityGroupName} ({$sg['DBSecurityGroup']->Status})";

		$pGroups = array();
		$pg = (array)$dbinstance->DBParameterGroups;

		if (is_array($pg['DBParameterGroup'])) {
			foreach ($pg['DBParameterGroup'] as $g)
				$pGroups[] = "{$g->DBParameterGroupName} ({$g->ParameterApplyStatus})";
		} else
			$pGroups[] = "{$pg['DBParameterGroup']->DBParameterGroupName} ({$pg['DBParameterGroup']->ParameterApplyStatus})";

		$form = array(
			array(
				'xtype' => 'fieldset',
				'labelWidth' => 200,
				'items' => array(
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Name',
						'value' => (string) $dbinstance->DBInstanceIdentifier
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Engine',
						'value' => $dbinstance->PendingModifiedValues->Engine ? (string) $dbinstance->Engine. ' <i><font color="red">New value (' . $dbinstance->PendingModifiedValues->Engine . ') is pending</font></i>' : (string) $dbinstance->Engine
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'DNS Name',
						'value' => (string) $dbinstance->Endpoint->Address
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Port',
						'value' => $dbinstance->PendingModifiedValues->Port ? (string) $dbinstance->Endpoint->Port . ' <i><font color="red">New value (' . $dbinstance->PendingModifiedValues->Port . ') is pending</font></i>' : (string)$dbinstance->Endpoint->Port
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Created at',
						'value' => Scalr_Util_DateTime::convertTz((string)$dbinstance->InstanceCreateTime)
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Status',
						'value' => (string) $dbinstance->DBInstanceStatus
					)
				)
			),
			array(
				'xtype' => 'fieldset',
				'labelWidth' => 200,
				'items' => array(
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Availability Zone',
						'value' => (string) $dbinstance->AvailabilityZone
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'MultiAZ',
						'value' => ($dbinstance->MultiAZ == 'true' ? 'Enabled' : 'Disabled') . ($dbinstance->PendingModifiedValues->MultiAZ ? ' <i><font color="red">New value(' . $dbinstance->PendingModifiedValues->MultiAZ . ') is pending</font></i>' : '')
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Type',
						'value' => $dbinstance->PendingModifiedValues->DBInstanceClass ? (string) $dbinstance->DBInstanceClass . ' <i><font color="red">New value ('.$dbinstance->PendingModifiedValues->DBInstanceClass.') is pending</font></i>' : (string) $dbinstance->DBInstanceClass
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Allocated storage',
						'value' => $dbinstance->PendingModifiedValues->AllocatedStorage ? (string) $dbinstance->AllocatedStorage . ' GB' . ' <i><font color="red">New value (' . $dbinstance->PendingModifiedValues->AllocatedStorage . ') is pending</font></i>' : (string) $dbinstance->AllocatedStorage
					)
				)
			),
			array(
				'xtype' => 'fieldset',
				'labelWidth' => 200,
				'items' => array(
					'xtype' => 'displayfield',
					'labelWidth' => 200,
					'fieldLabel' => 'Security groups',
					'value' => implode(', ', $sGroups)
				)
			),
			array(
				'xtype' => 'fieldset',
				'labelWidth' => 200,
				'items' => array(
					'xtype' => 'displayfield',
					'labelWidth' => 200,
					'fieldLabel' => 'Parameter groups',
					'value' => implode(', ', $pGroups)
				)
			),
			array(
				'xtype' => 'fieldset',
				'labelWidth' => 200,
				'items' => array(
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Preferred maintenance window',
						'value' => (string) $dbinstance->PreferredMaintenanceWindow
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Preferred backup window',
						'value' => (string) $dbinstance->PreferredBackupWindow
					),
					array(
						'xtype' => 'displayfield',
						'labelWidth' => 200,
						'fieldLabel' => 'Backup retention period',
						'value' => $dbinstance->PendingModifiedValues->BackupRetentionPeriod ? (string) $dbinstance->BackupRetentionPeriod. ' <i><font color="red">(Pending Modified)</font></i>' : (string) $dbinstance->BackupRetentionPeriod
					)
				)
			)
		);

		$this->response->page('ui/tools/aws/rds/instances/details.js', $form);
	}

	public function xRebootAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);

		$amazonRDSClient->RebootDBInstance($this->getParam('instanceId'));
		$this->response->success();
	}

	public function xTerminateAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);

		$amazonRDSClient->DeleteDBInstance($this->getParam('instanceId'));
		$this->response->success();
	}

	public function xGetParametersAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);

		$amazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$this->getParam('cloudLocation'),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);
		
		$dbParameterGroups = $amazonRDSClient->DescribeDBParameterGroups();
		$groups = (array) $dbParameterGroups->DescribeDBParameterGroupsResult->DBParameterGroups;
		$groups = $groups['DBParameterGroup'];

		if ($groups) {
			if (!is_array($groups))
				$groups = array($groups);
		}

		$describeDBSecurityGroups = $amazonRDSClient->DescribeDBSecurityGroups();
		$sgroups = (array) $describeDBSecurityGroups->DescribeDBSecurityGroupsResult->DBSecurityGroups;
		$sgroups = $sgroups['DBSecurityGroup'];

		if ($sgroups) {
			if (!is_array($sgroups))
				$sgroups = array($sgroups);
		}

		$response = $amazonEC2Client->DescribeAvailabilityZones();
		if ($response->availabilityZoneInfo->item instanceOf stdClass)
			$response->availabilityZoneInfo->item = array($response->availabilityZoneInfo->item);

		foreach ($response->availabilityZoneInfo->item as $zone) {
			if (stristr($zone->zoneState,'available')) {
				$zones[] = array(
					'id' => (string)$zone->zoneName,
					'name' => (string)$zone->zoneName
				);
			}
		}

		$this->response->data(array(
			'groups' => $groups,
			'sgroups' => $sgroups,
			'zones' => $zones
		));
	}

	public function xListInstancesAction()
	{
		$this->request->defineParams(array(
			'cloudLocation',
			'sort' => array('type' => 'json', 'default' => array('property' => 'id', 'direction' => 'ASC'))
		));

		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);

		$awsResponse = $amazonRDSClient->DescribeDBInstances();
		$rows = $awsResponse->DescribeDBInstancesResult->DBInstances->DBInstance;
		$rowz = array();

		if ($rows instanceof stdClass)
			$rows = array($rows);

		foreach ($rows as $pv)
			$rowz[] = array(
				'engine'	=> (string)$pv->Engine,
				'status'	=> (string)$pv->DBInstanceStatus,
				'hostname'	=> (string)$pv->Endpoint->Address,
				'port'		=> (string)$pv->Endpoint->Port,
				'name'		=> (string)$pv->DBInstanceIdentifier,
				'username'	=> (string)$pv->MasterUsername,
				'type'		=> (string)$pv->DBInstanceClass,
				'storage'	=> (string)$pv->AllocatedStorage,
				'dtadded'	=> (string)$pv->InstanceCreateTime,
				'avail_zone'=> (string)$pv->AvailabilityZone
			);

		$response = $this->buildResponseFromData($rowz);
		foreach ($response['data'] as &$row) {
			$row['dtadded'] = $row['dtadded'] ? Scalr_Util_DateTime::convertTz($row['dtadded']) : '';
		}
		$this->response->data($response);
	}
	public function restoreAction()
	{
		$amazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$this->getParam('cloudLocation'),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);
		$response = $amazonEC2Client->DescribeAvailabilityZones();
		if ($response->availabilityZoneInfo->item instanceOf stdClass)
			$response->availabilityZoneInfo->item = array($response->availabilityZoneInfo->item);

		foreach ($response->availabilityZoneInfo->item as $zone) {
			if (stristr($zone->zoneState,'available')) {
				$zones[] = array(
					'id' => (string)$zone->zoneName,
					'name' => (string)$zone->zoneName
				);
			}
		}
		$this->response->page('ui/tools/aws/rds/instances/restore.js', array('zones' => $zones));
	}
	public function xRestoreInstanceAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
			$this->getParam('cloudLocation')
		);
		$amazonRDSClient->RestoreDBInstanceFromDBSnapshot(
			$this->getParam('Snapshot'), 
			$this->getParam('DBInstanceIdentifier'), 
			$this->getParam('DBInstanceClass'), 
			$this->getParam('Port'), 
			$this->getParam('AvailabilityZone'),
			$this->getParam('MultiAZ')
		);
		$this->response->success("DB Instance successfully restore from Snapshot");
	}
}
