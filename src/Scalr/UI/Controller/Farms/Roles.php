<?php
class Scalr_UI_Controller_Farms_Roles extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'farmRoleId';

	/**
	 *
	 * @var DBFarm
	 */
	private $dbFarm;

	public function init()
	{
		$this->dbFarm = DBFarm::LoadByID($this->getParam(Scalr_UI_Controller_Farms::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($this->dbFarm);
	}
	
	public function defaultAction()
	{
		$this->viewAction();
	}

	public function getListAction()
	{
		$this->request->defineParams(array(
			'behaviors' => array('type' => 'json')
		));

		$list = $this->getList($this->getParam('behaviors'));

		$this->response->data(array('farmRoles' => $list));
	}

	public function getList(array $behaviors = array())
	{
		$retval = array();
		$s = $this->db->execute("SELECT id, platform, role_id FROM farm_roles WHERE farmid = ?", array($this->dbFarm->ID));
		while ($farmRole = $s->fetchRow()) {
			try {
				$dbRole = DBRole::loadById($farmRole['role_id']);
				$farmRole['name'] = $dbRole->name;

				if (!empty($behaviors)) {
					$bFilter = false;
					foreach ($behaviors as $behavior) {
						if ($dbRole->hasBehavior($behavior)) {
							$bFilter = true;
							break;
						}
					}

					if (!$bFilter)
						continue;
				}
			} catch (Exception $e) {
				$farmRole['name'] = '*removed*';
			}

			$retval[$farmRole['id']] = $farmRole;
		}

		return $retval;
	}

	public function viewAction()
	{
		$this->response->page('ui/farms/roles/view.js', array('farmName' => $this->dbFarm->Name));
	}

	public function extendedInfoAction()
	{
		$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
		$this->user->getPermissions()->validate($dbFarmRole);

		$scalingManager = new Scalr_Scaling_Manager($dbFarmRole);
		$scaling_algos = array();
		foreach ($scalingManager->getFarmRoleMetrics() as $farmRoleMetric)
			$scaling_algos[] = array(
				'name' => $farmRoleMetric->getMetric()->name,
				'last_value' => $farmRoleMetric->lastValue ? $farmRoleMetric->lastValue : 'Unknown',
				'date'		=> Scalr_Util_DateTime::convertTz($farmRoleMetric->dtLastPolled)
			);

		$form = array(
			array(
				'xtype' => 'fieldset',
				'title' => 'General',
				'labelWidth' => 250,
				'defaults' => array('labelWidth' => 250),
				'items' => array(
					array(
						'xtype' => 'displayfield',
						'fieldLabel' => 'Farm Role ID',
						'value' => $dbFarmRole->ID
					),
					array(
						'xtype' => 'displayfield',
						'fieldLabel' => 'Farm ID',
						'value' => $dbFarmRole->FarmID
					),
					array(
						'xtype' => 'displayfield',
						'fieldLabel' => 'Role ID',
						'value' => $dbFarmRole->RoleID
					),
					array(
						'xtype' => 'displayfield',
						'fieldLabel' => 'Role name',
						'value' => $dbFarmRole->GetRoleObject()->name
					),
					array(
						'xtype' => 'displayfield',
						'fieldLabel' => 'Platform',
						'value' => $dbFarmRole->Platform
					)
				)
			)
		);

		$it = array();
		foreach ($scaling_algos as $algo) {
			$it[] = array(
				'xtype' => 'displayfield',
				'fieldLabel' => $algo['name'],
				'value' => ($algo['date']) ? "Checked at {$algo['date']}. Value: {$algo['last_value']}" : "Never checked"
			);
		}

		$form[] = array(
			'xtype' => 'fieldset',
			'labelWidth' => 250,
			'defaults' => array('labelWidth' => 250),
			'title' => 'Scalr information',
			'items' => $it
		);


		$it = array();
		foreach ($dbFarmRole->GetAllSettings() as $name => $value) {
			$it[] = array(
				'xtype' => 'displayfield',
				'fieldLabel' => $name,
				'value' => $value
			);
		}

		$form[] = array(
			'xtype' => 'fieldset',
			'labelWidth' => 250,
			'defaults' => array('labelWidth' => 250),
			'title' => 'Scalr internal properties',
			'items' => $it
		);

		$this->response->page('ui/farms/roles/extendedinfo.js', array(
			'form' => $form, 'farmName' => $this->dbFarm->Name, 'roleName' => $dbFarmRole->GetRoleObject()->name
		));
	}
	
	public function xDowngradeAction()
	{
		if (!$this->getParam('roleId'))
			throw new Exception("Please select role on which you want to downgrade");
		
		
		$dbFarmRole = DBFarmRole::LoadByID($this->getParam(self::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($dbFarmRole);
		
		if ($dbFarmRole->NewRoleID)
			throw new Exception("You cannot downgrade role that has active bundle task");
		
		$newRole = DBRole::loadById($this->getParam('roleId'));
		if ($newRole->envId != 0)
			$this->user->getPermissions()->validate($newRole);
			
		$currentRole = $dbFarmRole->GetRoleObject();
		
		if ($currentRole->id == $newRole->id)
			throw new Exception("You're already using selected role");
			
		$dbFarmRole->RoleID = $newRole->id;
		$dbFarmRole->Save();
		
		$this->response->success("Farm role successfully downgraded to older version of role. This action doesn't affect current running instances.");
	}
	
	public function downgradeAction()
	{
		$dbFarmRole = DBFarmRole::LoadByID($this->getParam(self::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($dbFarmRole);
		
		$dbRole = $dbFarmRole->GetRoleObject();
		
		$history = array();
		foreach ($dbRole->getRoleHistory(false) as $roleName)
		{
			$item = false;
			try {
				$hRole = DBRole::loadByFilter(array('name' => $roleName));
				if ($hRole->envId != 0)
					$this->user->getPermissions()->validate($hRole);
			} catch (Exception $e) { $hRole = false; }
			
			if ($hRole){
				$item = array(
					'xtype' => 'radiofield',
					'name' => 'roleId',
					'boxLabel' => $hRole->name,
					'inputValue' => $hRole->id
				);
			} elseif ($roleName) {
				$item = array(
					'xtype' => 'displayfield',
					'hideLabel' => true,
					'value' => $roleName . " [ <span style='color:gray;font-style:italic;'>Role was removed and no longer available</span> ]"
				);
			}
			
			if ($item)
				$history[] = $item;
		}
		
		$history[]  = array(
			'xtype' => 'displayfield',
			'hideLabel' => true,
			'value' => "<b>".$dbRole->name."</b> [ Current role ]"
		);
		
		$this->response->page('ui/farms/roles/downgrade.js', array(
			'history' => $history
		));
	}

	public function xGetRoleSshPrivateKeyAction()
	{
		$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
		$dbFarm = $dbFarmRole->GetFarmObject();

		$this->user->getPermissions()->validate($dbFarmRole);

		$sshKey = Scalr_SshKey::init()->loadGlobalByFarmId(
			$dbFarm->ID,
			$dbFarmRole->CloudLocation
		);

		if (!$sshKey)
			throw new Exception("Key not found");

		$retval = $sshKey->getPrivate();

		$this->response->setHeader('Pragma', 'private');
		$this->response->setHeader('Cache-control', 'private, must-revalidate');
		$this->response->setHeader('Content-type', 'plain/text');
		$this->response->setHeader('Content-Disposition', 'attachment; filename="'.$dbFarm->Name.'-'.$dbFarmRole->GetRoleObject()->name.'.pem"');
		$this->response->setHeader('Content-Length', strlen($retval));

		$this->response->setResponse($retval);
	}

	public function xLaunchNewServerAction()
	{
		$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
		$dbFarm = $dbFarmRole->GetFarmObject();

		$this->user->getPermissions()->validate($dbFarmRole);

		if ($dbFarm->Status != FARM_STATUS::RUNNING)
			throw new Exception("You can launch servers only on running farms");

		$dbRole = $dbFarmRole->GetRoleObject();

		$isSzr = $dbFarmRole->GetRoleObject()->isSupported("0.5");
		$pendingInstancesCount = $dbFarmRole->GetPendingInstancesCount();
		if ($pendingInstancesCount >= 5 && !$isSzr)
			throw new Exception("There are {$pendingInstancesCount} pending instances. You cannot launch new instances while you have 5 pending ones.");

		$maxInstances = $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);
		$minInstances = $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);

		if ($maxInstances < $minInstances+1) {
			$dbFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $maxInstances+1);

			$warnMsg = sprintf(_("Server successfully launched. The number of running %s instances is equal to maximum instances setting for this role. Maximum Instances setting for role %s has been increased automatically"),
				$dbRole->name, $dbRole->name
			);
		}

		$runningInstancesCount = $dbFarmRole->GetRunningInstancesCount();

		if ($runningInstancesCount+$pendingInstancesCount >= $minInstances)
			$dbFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, $minInstances+1);

		$serverCreateInfo = new ServerCreateInfo($dbFarmRole->Platform, $dbFarmRole);

		Scalr::LaunchServer($serverCreateInfo);

		if ($warnMsg)
			$this->response->warning($warnMsg);
		else
			$this->response->success('Server successfully launched');
	}

	public function xListFarmRolesAction()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int'),
			'farmRoleId' => array('type' => 'int'),
			'roleId' => array('type' => 'int'),
			'id' => array('type' => 'int'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC')
		));

		$sql = "SELECT * from farm_roles WHERE farmid=".$this->db->qstr($this->getParam('farmId'));

		if ($this->getParam('roleId'))
			$sql .= " AND role_id=".$this->db->qstr($this->getParam('roleId'));

		if ($this->getParam('farmRoleId'))
			$sql .= " AND id=".$this->db->qstr($this->getParam('farmRoleId'));

		$response = $this->buildResponseFromSql($sql, array("role_id", "platform"));
		foreach ($response['data'] as &$row) {
			$row["running_servers"] = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_roleid='{$row['id']}' AND status IN ('Pending', 'Initializing', 'Running', 'Temporary')");
			$row["non_running_servers"] = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_roleid='{$row['id']}' AND status NOT IN ('Pending', 'Initializing', 'Running', 'Temporary')");

			$row['farm_status'] = $this->db->GetOne("SELECT status FROM farms WHERE id=?", array($row['farmid']));

			$row["domains"] = $this->db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE farm_roleid=? AND status != ? AND farm_id=?",
				array($row["id"], DNS_ZONE_STATUS::PENDING_DELETE, $row['farmid'])
			);

			$DBFarmRole = DBFarmRole::LoadByID($row['id']);

			$row['min_count'] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
			$row['max_count'] = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);

			$row['location'] = $DBFarmRole->CloudLocation;

			$DBRole = DBRole::loadById($row['role_id']);
			$row["name"] = $DBRole->name;
			$row['image_id'] = $DBRole->getImageId(
				$DBFarmRole->Platform,
				$DBFarmRole->CloudLocation
			);

			$row['shortcuts'] = $this->db->GetAll("SELECT * FROM farm_role_scripts WHERE farm_roleid=? AND ismenuitem='1'",
				array($row['id'])
			);
			foreach ($row['shortcuts'] as &$shortcut)
				$shortcut['name'] = $this->db->GetOne("SELECT name FROM scripts WHERE id=?", array($shortcut['scriptid']));


			$scalingManager = new Scalr_Scaling_Manager($DBFarmRole);
			$scaling_algos = array();
			foreach ($scalingManager->getFarmRoleMetrics() as $farmRoleMetric)
				$scaling_algos[] = $farmRoleMetric->getMetric()->name;

			if (count($scaling_algos) == 0)
				$row['scaling_algos'] = _("Scaling disabled");
			else
				$row['scaling_algos'] = implode(', ', $scaling_algos);
		}

		$this->response->data($response);
	}
}
