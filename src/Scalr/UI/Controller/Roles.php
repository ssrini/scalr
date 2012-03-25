<?php

class Scalr_UI_Controller_Roles extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'roleId';
	
	public static function getPermissionDefinitions()
	{
		return array(
			'edit' => 'Edit',
			'xSaveRole' => 'Edit'
		);
	}
	
	public function hasAccess()
	{
		return true;
	}

	public function xGetListAction()
	{
		$moduleParams = array(
			'roles' => $this->getList(),
			'platforms' => self::loadController('Platforms')->getEnabledPlatforms(true),
			'groups' => ROLE_GROUPS::GetName(null, true)
		);

		$this->response->data($moduleParams);
	}

	public function getList($isBeta = false)
	{
		$roles = array();

		$e_platforms = $this->getEnvironment()->getEnabledPlatforms();
		$platforms = array();
		$l_platforms = SERVER_PLATFORMS::GetList();
		foreach ($e_platforms as $platform)
			$platforms[$platform] = $l_platforms[$platform];

		$roles_sql = "SELECT id FROM roles WHERE (env_id = 0 OR env_id=?) AND id IN (SELECT role_id FROM role_images WHERE platform IN ('".implode("','", array_keys($platforms))."'))";
		$args[] = $this->getEnvironmentId();

		$dbroles = $this->db->Execute($roles_sql, $args);
		while ($role = $dbroles->FetchRow()) {
			if ($this->db->GetOne("SELECT id FROM roles_queue WHERE role_id=?", array($role['id'])))
				continue;

			$dbRole = DBRole::loadById($role['id']);
			
			if ($dbRole->generation != 2 && $dbRole->origin == ROLE_TYPE::SHARED)
				continue;

	        $role_platforms = $dbRole->getPlatforms();
	        $role_locations = array();
	        foreach ($role_platforms as $platform)
	        	$role_locations[$platform] = $dbRole->getCloudLocations($platform);

	        $roles[] = array(
	        	'role_id'				=> $dbRole->id,
	        	'arch'					=> $dbRole->architecture,
	        	'group'					=> ROLE_GROUPS::GetConstByBehavior($dbRole->getBehaviors()),
	        	'name'					=> $dbRole->name,
	        	'generation'			=> $dbRole->generation,
	        	'behaviors'				=> implode(",", $dbRole->getBehaviors()),
	        	'origin'				=> $dbRole->origin,
	        	'isstable'				=> (bool)$dbRole->isStable,
	        	'platforms'				=> implode(",", $role_platforms),
	        	'locations'				=> $role_locations,
	        	'os'					=> $dbRole->os == 'Unknown' ? 'Unknown OS' : $dbRole->os,
	        	'tags'					=> $dbRole->getTags()
	        );
		}

		return $roles;
	}

	public function xRemoveAction()
	{
		$this->request->defineParams(array(
			'roles' => array('type' => 'json'),
			'removeFromCloud'
		));

		foreach ($this->getParam('roles') as $id) {
			$dbRole = DBRole::loadById($id);
			
			if ($this->user->getType() != Scalr_Account_User::TYPE_SCALR_ADMIN)
				$this->user->getPermissions()->validate($dbRole);

			if ($this->db->GetOne("SELECT COUNT(*) FROM farm_roles WHERE role_id=? AND farmid IN (SELECT id FROM farms WHERE clientid=?)", array($dbRole->id, $this->user->getAccountId())) == 0) {
				
				if ($this->getParam('removeFromCloud')) {
					$this->db->Execute("INSERT INTO roles_queue SET `role_id`=?, `action`=?, dtadded=NOW()", array($dbRole->id, 'remove'));
				} else {
					$dbRole->remove();
				}
			}
			else
				throw new Exception(sprintf(_("Role '%s' used by your farms and cannot be removed."), $dbRole->name));
		}

		$this->response->success('Selected roles successfully removed');
	}

	public function builderAction()
	{
		$platforms = array();

		foreach ($this->getEnvironment()->getEnabledPlatforms() as $platform) {
			if (in_array($platform, array(SERVER_PLATFORMS::RACKSPACE, SERVER_PLATFORMS::EC2)))
				$platforms[$platform] = SERVER_PLATFORMS::GetName($platform);
		}

		$images = array();
		foreach ($platforms as $platform => $name)
			$images[$platform] = PlatformFactory::NewPlatform($platform)->getRoleBuilderBaseImages();

		$this->response->page('ui/roles/builder.js', array(
			'platforms' => $platforms,
			'images' => $images,
			'environment' => '#/environments/' . $this->getEnvironmentId() . '/edit'
		), array(), array('ui/roles/builder.css'));
	}

	public function xBuildAction()
	{
		$this->request->defineParams(array(
			'platform' 		=> array('type' => 'string'),
			'architecture'	=> array('type' => 'string'),
			'behaviors'		=> array('type' => 'json'),
			'roleName'		=> array('type' => 'string'),
			'imageId'		=> array('type' => 'string'),
			'location'		=> array('type' => 'string'),
			'mysqlServerType' => array('type' => 'string'),
			'devScalarizrBranch' => array('type' => 'string')
		));

		if (strlen($this->getParam('roleName')) < 3)
			throw new Exception(_("Role name should be greater than 3 chars"));

		if (! preg_match("/^[A-Za-z0-9-]+$/si", $this->getParam('roleName')))
			throw new Exception(_("Role name is incorrect"));

		$chkRoleId = $this->db->GetOne("SELECT id FROM roles WHERE name=? AND (env_id = '0' OR env_id = ?)",
			array($this->getParam('roleName'), $this->getEnvironmentId())
		);
			
		if ($chkRoleId) {
			if (!$this->db->GetOne("SELECT id FROM roles_queue WHERE role_id=?", array($chkRoleId)))
				throw new Exception('Selected role name is already used. Please select another one.');
		}

		$imageId = $this->getParam('imageId');

		if ($this->getParam('platform') == SERVER_PLATFORMS::RACKSPACE)
			$imageId = str_replace('lon', '', $imageId);

		$behaviours = implode(",", array_values($this->getParam('behaviors')));

		// Create server
		$creInfo = new ServerCreateInfo($this->getParam('platform'), null, 0, 0);
		$creInfo->clientId = $this->user->getAccountId();
		$creInfo->envId = $this->getEnvironmentId();
		$creInfo->farmId = 0;
		$creInfo->SetProperties(array(
			SERVER_PROPERTIES::SZR_IMPORTING_BEHAVIOR => $behaviours,
			SERVER_PROPERTIES::SZR_KEY => Scalr::GenerateRandomKey(40),
			SERVER_PROPERTIES::SZR_KEY_TYPE => SZR_KEY_TYPE::PERMANENT,
			SERVER_PROPERTIES::SZR_VESION => "0.6",
			SERVER_PROPERTIES::SZR_IMPORTING_MYSQL_SERVER_TYPE => $this->getParam('mysqlServerType'),
			SERVER_PROPERTIES::SZR_DEV_SCALARIZR_BRANCH => $this->getParam('devScalarizrBranch')
		));

		$dbServer = DBServer::Create($creInfo, true);
		$dbServer->status = SERVER_STATUS::TEMPORARY;
		$dbServer->save();

		//Launch server
		$launchOptions = new Scalr_Server_LaunchOptions();
		$launchOptions->imageId = $imageId;
		$launchOptions->cloudLocation = $this->getParam('location');
		$launchOptions->architecture = $this->getParam('architecture');


		switch($this->getParam('platform')) {
			case SERVER_PLATFORMS::RACKSPACE:
				$launchOptions->serverType = 1;
				break;
			case SERVER_PLATFORMS::EC2:
				if ($this->getParam('architecture') == 'i386')
					$launchOptions->serverType = 'm1.small';
				else
					$launchOptions->serverType = 'm1.large';
					$launchOptions->userData = "#cloud-config\ndisable_root: false";
				break;
		}
		
		if ($this->getParam('serverType'))
			$launchOptions->serverType = $this->getParam('serverType');
			
		if ($this->getParam('availZone'))
			$launchOptions->availZone = $this->getParam('availZone');

		//Add Bundle task
		$creInfo = new ServerSnapshotCreateInfo(
			$dbServer,
			$this->getParam('roleName'),
			SERVER_REPLACEMENT_TYPE::NO_REPLACE
		);

		$bundleTask = BundleTask::Create($creInfo, true);

		$bundleTask->cloudLocation = $launchOptions->cloudLocation;
		$bundleTask->save();

		$bundleTask->Log(sprintf("Launching temporary server (%s)", serialize($launchOptions)));

		$dbServer->SetProperty(SERVER_PROPERTIES::SZR_IMPORTING_BUNDLE_TASK_ID, $bundleTask->id);

		try {
			PlatformFactory::NewPlatform($this->getParam('platform'))->LaunchServer($dbServer, $launchOptions);
			$bundleTask->Log(_("Temporary server launched. Waiting for running state..."));
		}
		catch(Exception $e) {
			$bundleTask->SnapshotCreationFailed(sprintf(_("Unable to launch temporary server: %s"), $e->getMessage()));
		}

		$this->response->data(array('bundleTaskId' => $bundleTask->id));
	}

	/**
	* View roles listView with filters
	*/
	public function viewAction()
	{
		$this->response->page('ui/roles/view.js', array(
			'locations' => self::loadController('Platforms')->getCloudLocations('all'),
			'isScalrAdmin' => ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN)
		));
	}

	/**
	* View edit role page
	*/
	public function editAction()
	{
		// declare types of input variables (available types: int, string (default), bool, json, array; may be include default value for variable)
		$this->request->defineParams(array(
			'roleId' => array('type' => 'int')
		));

		$params = array('platforms' => array(), 'isScalrAdmin' => ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN));

		if (! $params['isScalrAdmin'])
			$ePlatforms = $this->getEnvironment()->getEnabledPlatforms();
		else
			$ePlatforms = array_keys(SERVER_PLATFORMS::GetList());

		$lPlatforms = SERVER_PLATFORMS::GetList();

		$llist = array();
		foreach ($ePlatforms as $platform) {
			$locations = array();
			foreach (PlatformFactory::NewPlatform($platform)->getLocations() as $key => $loc) {
				$locations[] = array('id' => $key, 'name' => $loc);
				$llist[$key] = $loc;
			}

			$params['platforms'][] = array(
				'id' => $platform,
				'name' => $lPlatforms[$platform],
				'locations' => $locations
			);
		}

		if ($this->getParam('roleId')) {
			$dbRole = DBRole::loadById($this->getParam('roleId'));

			if ($this->user->getType() != Scalr_Account_User::TYPE_SCALR_ADMIN)
				$this->user->getPermissions()->validate($dbRole);

			$images = array();
			foreach ($dbRole->getImages() as $platform => $locations) {
				foreach ($locations as $location => $imageId)
					$images[] = array(
						'image_id' 		=> $imageId,
						'platform' 		=> $platform,
						'location' 		=> $location,
						'platform_name' => SERVER_PLATFORMS::GetName($platform),
						'location_name'	=> $llist[$location]
					);
			}

			$params['tags'] = array_flip($dbRole->getTags());

			$params['role'] = array(
				'id'			=> $dbRole->id,
				'name'			=> $dbRole->name,
				'arch'			=> $dbRole->architecture,
				'os'			=> $dbRole->os,
				'agent'			=> $dbRole->generation,
				'description'	=> $dbRole->description,
				'behaviors'		=> $dbRole->getBehaviors(),
				'properties'	=> array(DBRole::PROPERTY_SSH_PORT => $dbRole->getProperty(DBRole::PROPERTY_SSH_PORT)),
				'images'		=> $images,
				'parameters'	=> $dbRole->getParameters(),
				'szr_version'	=> $dbRole->szrVersion
			);

			if (!$params['role']['properties'][DBRole::PROPERTY_SSH_PORT])
				$params['role']['properties'][DBRole::PROPERTY_SSH_PORT] = 22;

			$this->response->page('ui/roles/edit.js', $params);
		} else {
			if ($this->user->getType() != Scalr_Account_User::TYPE_SCALR_ADMIN) {
				throw new Scalr_Exception_InsufficientPermissions();
			} else {
				$params['tags'] = array();
				$params['role'] = array(
					'id'			=> 0,
					'name'			=> "",
					'arch'			=> "i386",
					'agent'			=> 2,
					'description'	=> "",
					'behaviors'		=> array(),
					'properties'	=> array(DBRole::PROPERTY_SSH_PORT => 22),
					'images'		=> array(),
					'parameters'	=> array()
				);

				$this->response->page('ui/roles/edit.js', $params);
			}
		}
	}

	public function xGetRoleParamsAction()
	{
		$this->request->defineParams(array(
			'roleId' => array('type' => 'int'),
			'farmId' => array('type' => 'int'),
			'cloudLocation'
		));

		try {
			$dbRole = DBRole::loadById($this->getParam('roleId'));
			if ($dbRole->envId != 0)
				$this->user->getPermissions()->validate($dbRole);
		}
		catch (Exception $e) {
			$this->response->data(array('params' => array()));
			return;
		}

		$params = $this->db->GetAll("SELECT * FROM role_parameters WHERE role_id=? AND hash NOT IN('apache_http_vhost_template','apache_https_vhost_template')",
			array($dbRole->id)
		);

		foreach ($params as $key => $param) {
			$value = false;

			try {
				if($this->getParam('farmId')) {
					$dbFarmRole = DBFarmRole::Load($this->getParam('farmId'), $this->getParam('roleId'), $this->getParam('cloudLocation'));
	
					$value = $this->db->GetOne("SELECT value FROM farm_role_options WHERE farm_roleid=? AND hash=?",
						array($dbFarmRole->ID, $param['hash'])
					);
				}
			}
			catch(Exception $e) {}

			// Get field value
			if ($value === false || $value === null)
				$value = $param['defval'];

			$params[$key]['value'] = str_replace("\r", "", $value);
		}

		$this->response->data(array('params' => $params));
	}
	
	/**
	* Save role informatiom
	*/
	public function xSaveRoleAction()
	{
		$this->request->defineParams(array(
			'roleId' => array('type' => 'int'),
			'agent' => array('type' => 'int'),
			'behaviors' => array('type' => 'array'),
			'tags' => array('type' => 'array'),
			'arch', 'description', 'name', 'os',
			'parameters' => array('type' => 'json'),
			'remove_images' => array('type' => 'json'),
			'images' => array('type' => 'json'),
			'properties' => array('type' => 'json'),
			'szr_version' => array('type' => 'string')
		));

		$id = $this->getParam('roleId');
		$parameters = $this->getParam('parameters');

		if ($id == 0) {
			if ($this->user->getType() != Scalr_Account_User::TYPE_SCALR_ADMIN)
				throw new Scalr_Exception_InsufficientPermissions();

			$dbRole = new DBRole(0);

			$dbRole->generation = ($this->getParam('agent') == 'scalarizr' || $this->getParam('agent') == 2) ? 2 : 1; // ($post_agent != 'scalarizr') ? 1 : 2;
			$dbRole->architecture = $this->getParam('arch');
			$dbRole->origin = ROLE_TYPE::SHARED;
			$dbRole->envId = 0;
			$dbRole->clientId = 0;
			$dbRole->name = $this->getParam('name');
			$dbRole->os = $this->getParam('os');
			$dbRole->szrVersion = $this->getParam('szr_version');

			$rules = array(
				'icmp:-1:-1:0.0.0.0/0',
				'tcp:22:22:0.0.0.0/0',
				'tcp:8013:8013:0.0.0.0/0',
				'udp:8014:8014:0.0.0.0/0',
				'udp:161:162:0.0.0.0/0'
			);

			foreach ($this->getParam('behaviors') as $behavior) {
				if ($behavior == ROLE_BEHAVIORS::NGINX || $behavior == ROLE_BEHAVIORS::APACHE) {
					if (empty($parameters)) {
						$param = new stdClass();
						$param->name = 'Nginx HTTPS Vhost Template';
						$param->required = '1';
						$param->defval = @file_get_contents(dirname(__FILE__)."/../../../../templates/services/nginx/ssl.vhost.tpl");
						$param->type = 'textarea';
						$parameters[] = $param;
					}
				}

				if ($behavior == ROLE_BEHAVIORS::MYSQL) {
					$rules[] = "tcp:3306:3306:0.0.0.0/0";
				}

				if ($behavior == ROLE_BEHAVIORS::CASSANDRA) {
					$rules[] = "tcp:9160:9160:0.0.0.0/0";
				}
				
				if ($behavior == ROLE_BEHAVIORS::CF_DEA) {
					$rules[] = "tcp:12345:12345:0.0.0.0/0";
				}
				
				if ($behavior == ROLE_BEHAVIORS::CF_ROUTER) {
					$rules[] = "tcp:2222:2222:0.0.0.0/0";
				}
				
				$rules = array_merge($rules, Scalr_Role_Behavior::loadByName($behavior)->getSecurityRules());
			}

			$dbRole = $dbRole->save();

			foreach ($rules as $rule) {
				$this->db->Execute("INSERT INTO role_security_rules SET `role_id`=?, `rule`=?", array(
					$dbRole->id, $rule
				));
			}

			$soft = explode("\n", trim($this->getParam('software')));
			$software = array();
			if (count($soft) > 0) {
				foreach ($soft as $softItem) {
					$itm = explode("=", $softItem);
					$software[trim($itm[0])] = trim($itm[1]);
				}

				$dbRole->setSoftware($software);
			}

			$dbRole->setBehaviors(array_values($this->getParam('behaviors')));
		} else {
			$dbRole = DBRole::loadById($id);
			
			if ($this->user->getType() != Scalr_Account_User::TYPE_SCALR_ADMIN)
				$this->user->getPermissions()->validate($dbRole);
		}

		$dbRole->description = $this->getParam('description');

		foreach ($this->getParam('remove_images') as $imageId)
			$dbRole->removeImage($imageId);

		foreach ($this->getParam('images') as $image) {
			$image = (array)$image;
			$dbRole->setImage($image['image_id'], $image['platform'], $image['location']);
		}

		foreach ($this->getParam('properties') as $k => $v)
			$dbRole->setProperty($k, $v);

		$dbRole->setParameters($parameters);

		if ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN)
			$dbRole->setTags($this->getParam('tags'));

		$dbRole->save();

		$this->response->success('Role saved');
	}

	/**
	* Get list of roles for listView
	*/
	public function xListRolesAction()
	{
		$this->request->defineParams(array(
			'client_id' => array('type' => 'int'),
			'roleId' => array('type' => 'int'),
			'cloudLocation', 'origin', 'approval_state', 'query',
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC')
		));

		if ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN)
			$sql = "SELECT id from roles WHERE env_id = '0'";
		else
			$sql = "SELECT id from roles WHERE env_id IN ({$this->getEnvironmentId()},0)";

		if ($this->getParam('cloudLocation'))
			$sql .= " AND id IN (SELECT role_id FROM role_images WHERE cloud_location={$this->db->qstr($this->getParam('cloudLocation'))})";

		if ($this->getParam('roleId'))
			$sql .= " AND id='{$this->getParam('roleId')}'";

		if ($this->getParam('origin')) {
			$sql .= " AND origin = " . $this->db->qstr($this->getParam('origin'));
		}

		$response = $this->buildResponseFromSql($sql, array("name", "description"));

		foreach ($response["data"] as &$row) {
			$dbRole = DBRole::loadById($row['id']);

			$platforms = array();
			foreach ($dbRole->getPlatforms() as $platform)
				$platforms[] = SERVER_PLATFORMS::GetName($platform);

			$status = '<span style="color:gray;">Not used</span>';
			if ($this->db->GetOne("SELECT id FROM roles_queue WHERE role_id=?", array($dbRole->id)))
				$status = '<span style="color:red;">Deleting</span>';
			elseif ($this->db->GetOne("SELECT COUNT(*) FROM farm_roles WHERE role_id=? AND farmid IN (SELECT id FROM farms WHERE clientid=?)", array($dbRole->id, $this->user->getAccountId())) > 0)
				$status = '<span style="color:green;">In use</span>';

			$role = array(
				'name'			=> $dbRole->name,
				'behaviors'		=> implode(", ", $dbRole->getBehaviors()),
				'id'			=> $dbRole->id,
				'architecture'	=> $dbRole->architecture,
				'client_id'		=> $dbRole->clientId,
				'env_id'		=> $dbRole->envId,
				'status'		=> $status,
				'origin'		=> $dbRole->origin,
				'os'			=> $dbRole->os,
				'tags'			=> implode(", ", $dbRole->getTags()),
				'platforms'		=> implode(", ", $platforms),
				'generation'	=> ($dbRole->generation == 2) ? 'scalarizr' : 'ami-scripts'
			);

			try {
				$envId = $this->getEnvironmentId();

				$role['used_servers'] = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE role_id=? AND env_id=?",
					array($dbRole->id, $envId)
				);
			}
			catch(Exception $e) {
				if ($this->user->getAccountId() == 0) {
					$role['used_servers'] = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE role_id=?",
						array($dbRole->id)
					);

					if ($this->db->GetOne("SELECT COUNT(*) FROM farm_roles WHERE role_id=?", array($dbRole->id)) > 0)
						$status = '<span style="color:green;">In use</span>';

					$role['status'] = $status;
				}
			}

			if ($dbRole->clientId == 0)
				$role["client_name"] = "Scalr";
			else
				$role["client_name"] = $this->user->getAccount()->getOwner()->fullname;

			if (! $role["client_name"])
				$role["client_name"] = "";

			$row = $role;
		}

		$this->response->data($response);
	}

	/**
	* Get information about role
	*/
	public function infoAction()
	{
		$this->request->defineParams(array(
			'roleId' => array('type' => 'int')
		));

		$roleId = $this->getParam('roleId');

		$dbRole = DBRole::loadById($roleId);
		
		if ($dbRole->envId != 0)
			$this->user->getPermissions()->validate($dbRole);

		$dbRole->groupName = ROLE_GROUPS::GetNameByBehavior($dbRole->getBehaviors());
		$dbRole->behaviorsList = implode(", ", $dbRole->getBehaviors());
		foreach ($dbRole->getSoftwareList() as $soft)
			$dbRole->softwareList[] = "{$soft['name']} {$soft['version']}";

		$dbRole->softwareList = implode(", ", $dbRole->softwareList);
		$dbRole->tagsString = implode(", ", $dbRole->getTags());

		$dbRole->platformsList = array();
		foreach ($dbRole->getPlatforms() as $platform) {
			$dbRole->platformsList[] = array(
				'name' 		=> SERVER_PLATFORMS::GetName($platform),
				'locations'	=> implode(", ", $dbRole->getCloudLocations($platform))
			);
		}

		$this->response->page('ui/roles/info.js', array(
			'name' => $dbRole->name,
			'info' => get_object_vars($dbRole)
		));
	}
}
