<?php

	class DBRole
	{
		public
			$id,
			$name,
			$imageId,
			$envId,
			$origin,
			$clientId,
			$description,
			$isStable,
			$approvalState,
			$generation,
			$os,
			$architecture,
			$szrVersion;

		private
			$db,
			$behaviors,
			$tags = array(),
			$images,
			$behaviorsRaw,
			$history,
			$environment;


		/*Temp*/
		public $instanceType;

		private static $FieldPropertyMap = array(
			'id' 			=> 'id',
			'client_id'		=> 'clientId',
			'origin'		=> 'origin',
			'name'			=> 'name',
			'architecture'	=> 'architecture',
			'env_id'		=> 'envId',
			'description'	=> 'description',
			'is_stable'		=> 'isStable',
			'generation'	=> 'generation',
			'os'			=> 'os',
			'approval_state'=> 'approvalState',
			'history'		=> 'history',
			'behaviors'		=> 'behaviorsRaw',
			'szr_version'	=> 'szrVersion'
		);

		const PROPERTY_SSH_PORT = 'system.ssh-port';
		const PROPERTY_NIMBULA_ENTRY = 'nimbula.entry';
		const PROPERTY_NIMBULA_INIT_ROOT_USER = 'nimbula.init.root_user';
		const PROPERTY_NIMBULA_INIT_ROOT_PASS = 'nimbula.init.root_pass';


		public function __construct($id)
		{
			$this->id = $id;
			$this->db = Core::GetDBInstance();
		}

		public function setBehaviors($behaviors)
		{
			//TODO: validation

			$this->behaviorsRaw = implode(",", $behaviors);
			$this->behaviors = null;
		}

		public function setProperty($name, $value)
		{
			$this->db->Execute("REPLACE INTO role_properties SET
				`role_id` = ?,
				`name`	= ?,
				`value`	= ?
			", array(
				$this->id,
				$name,
				$value
			));
		}

		public function getProperty($name)
		{
			return $this->db->GetOne("SELECT value FROM role_properties WHERE `role_id` = ? AND `name` = ?", array(
				$this->id, $name
			));
		}

		public function getSecurityRules()
		{
			return $this->db->GetAll("SELECT * FROM role_security_rules WHERE role_id=?", array($this->id));
		}

		public function getRoleHistory($get_last = true)
		{
			$history = explode(",", $this->history);

			if ($get_last)
				return array_pop($history);
			else
				return $history;
		}

		public function getTags()
		{
			$this->loadTagsCache();

			return array_keys($this->tags);
		}

		public function hasTag($tag)
		{
			$this->loadTagsCache();

			return ($this->tags[$tag] == 1);
		}

		public function hasBehavior($behavior)
		{
			return (in_array($behavior, $this->getBehaviors()));
		}

		public function getSoftwareList()
		{
			$retval = array();
			foreach ((array)$this->db->GetAll("SELECT * FROM role_software WHERE role_id=?", array($this->id)) as $soft)
				$retval[$soft['software_key']] = array('name' => $soft['software_name'], 'version' => $soft['software_version']);

			return $retval;
		}

		public function getBehaviors()
		{
			if (!$this->behaviors)
				$this->behaviors = explode(",", $this->behaviorsRaw);

			return $this->behaviors;
		}

		private function loadTagsCache()
		{
			if (!$this->tags)
			{
				$tags = $this->db->Execute("SELECT * FROM role_tags WHERE role_id=?", array($this->id));
				while ($t = $tags->FetchRow())
					$this->tags[$t['tag']] = 1;
			}
		}

		private function loadImagesCache()
		{
			if (!$this->images)
			{
				$images = $this->db->GetAll("SELECT * FROM role_images WHERE role_id=?", array($this->id));
				foreach ($images as $image)
				{
					$this->images[$image['platform']][$image['cloud_location']] = $image['image_id'];
				}
			}
		}

		public function getCloudLocations($platform = null)
		{
			$retval = array();

			if (!$platform)
			{
				foreach ($this->getPlatforms() as $platform)
					$retval = array_merge($this->images[$platform], $retval);
			}
			else
				$retval = $this->images[$platform];

			return array_keys($retval);
		}

		public function getPlatforms()
		{
			$this->loadImagesCache();

			return array_keys($this->images);
		}

		public function getImages()
		{
			$this->loadImagesCache();

			return $this->images;
		}

		public function getImagesString()
		{
			$this->loadImagesCache();

			$images = "";

			foreach ($this->images as $p => $r)
			{
				foreach ($r as $rk => $i)
				{
					$images .= "{$p}/{$rk}: {$i}, ";
				}
			}

			$images = trim($images, ", ");

			return $images;
		}

		public function getImageId($platform = null, $cloud_location = null)
		{
			$this->loadImagesCache();

			if ($platform)
			{
				if ($cloud_location)
				{
					return $this->images[$platform][$cloud_location];
				}
				else
					return $this->images[$platform];
			}
			else
				return array_shift(array_values(array_values($this->images)));
		}

		/**
		 * @return Scalr_Environment
		 * Enter description here ...
		 */
		public function getEnvironmentObject()
		{
			if (!$this->environment)
				$this->environment = Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadById($this->envId);

			return $this->environment;
		}

		public static function loadByFilter(array $filter)
		{
			$db = Core::GetDBInstance();

			$sql = "SELECT id FROM roles WHERE 1=1";
			$args = array();
			foreach ($filter as $k=>$v)
			{
				$sql .= " AND `{$k}`=?";
				$args[] = $v;
			}

			$roles = $db->GetAll($sql, $args);
			if (count($roles) == 1)
			{
				return self::loadById($roles[0]['id']);
			}
			else
			{
				$retval = array();
				foreach ($roles as $role)
					$retval[] = self::loadById($role['id']);

				return $retval;
			}
		}

		/**
		 * @return DBRole
		 * @param unknown_type $id
		 */
		public static function loadById($id)
		{
			$db = Core::GetDBInstance();

			$roleinfo = $db->GetRow("SELECT * FROM roles WHERE id=?", array($id));
			if (!$roleinfo)
				throw new Exception(sprintf(_("Role ID#%s not found in database"), $id));

			$DBRole = new DBRole($id);

			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if (isset($roleinfo[$k]))
					$DBRole->{$v} = $roleinfo[$k];
			}

			return $DBRole;
		}

		private function getVersionInfo($v) {
			if (preg_match("/^([0-9]+)\.([0-9]+)[-\.]?([0-9]+)?$/si", $v, $matches)) {
				$verInfo = array_map("intval", array_slice($matches, 1));
				while (count($verInfo) < 3) {
					$verInfo[] = 0;
				}
				return $verInfo;
			} else {
				return array(0, 0, 0);
			}
		}
		
		public function isSupported($v) {
			return $this->getVersionInfo($this->szrVersion) >= $this->getVersionInfo($v);
		}
		
		public function save()
		{
			if (!$this->id) {
				$this->db->Execute("INSERT INTO roles SET
					name		= ?,
					description	= ?,
					architecture= ?,
					generation	= ?,
					origin		= ?,
					env_id		= ?,
					is_stable	= '1',
					approval_state	= ?,
					client_id	= ?,
					szr_version	= ?,
					behaviors	= ?,
					os			= ?
				", array($this->name, $this->description, $this->architecture, $this->generation,
				$this->origin, $this->envId, APPROVAL_STATE::APPROVED, $this->clientId, $this->szrVersion, $this->behaviorsRaw, $this->os));

				$this->id = $this->db->Insert_ID();

				$this->db->Execute("DELETE FROM role_behaviors WHERE role_id = ?", array($this->id));
				foreach ($this->getBehaviors() as $behavior)
					$this->db->Execute("INSERT INTO role_behaviors SET role_id = ?, behavior = ?", array($this->id, $behavior));

			} else {
				$this->db->Execute("UPDATE roles SET
					name		= ?,
					description	= ?,
					behaviors	= ?
				WHERE id =?
				", array($this->name, $this->description, $this->behaviorsRaw, $this->id));

				$this->db->Execute("DELETE FROM role_behaviors WHERE role_id = ?", array($this->id));
				foreach ($this->getBehaviors() as $behavior)
					$this->db->Execute("INSERT INTO role_behaviors SET role_id = ?, behavior = ?", array($this->id, $behavior));

			}
			
			return $this;
		}

		public function remove($removeImage = false)
		{
			if ($removeImage)
			{				
				$platforms = array_keys($this->getImages());
				foreach ($platforms as $platform)
					PlatformFactory::NewPlatform($platform)->RemoveServerSnapshot($this);
			}

			$this->db->Execute("DELETE FROM roles WHERE id = ?", array($this->id));
			$this->db->Execute("DELETE FROM roles_queue WHERE role_id = ?", array($this->id));
		}

		public function isUsed()
		{
			return (bool)$this->db->GetOne("SELECT id FROM farm_roles WHERE role_id=? OR new_role_id=?",
				array($this->id, $this->id)
			);
		}

		public function removeImage($imageId)
		{
			$this->db->Execute("DELETE FROM role_images WHERE image_id = ? AND role_id = ?", array($imageId, $this->id));
		}

		public function setImage($imageId, $platform, $cloud_location)
		{
			// @HACK for shared roles
			if ($this->origin == ROLE_TYPE::SHARED || !$this->db->GetOne("SELECT id FROM role_images WHERE image_id = ? AND role_id != ?", array(trim($imageId), $this->id)))
			{
				$this->db->Execute("INSERT INTO role_images SET
					role_id			= ?,
					cloud_location	= ?,
					image_id		= ?,
					platform		= ?
					ON DUPLICATE KEY UPDATE image_id = ? 
				", array(
					$this->id,
					$cloud_location,
					trim($imageId),
					$platform,
					trim($imageId)
				));
			}
			else
				throw new Exception("Role with such imageId already exists in database");
		}

		public function setTags(array $tags = array())
		{
			$this->db->Execute("DELETE FROM role_tags WHERE role_id = ?", array($this->id));
			foreach ($tags as $tag) {
				$this->db->Execute("INSERT INTO role_tags SET role_id = ?, `tag` = ?", array($this->id, $tag));
			}
		}

		public function setSoftware(array $software = array())
		{
			//TODO: validate

			foreach ($software as $software_key => $software_version) {
				$this->db->Execute("REPLACE INTO role_software SET
					role_id			= ?,
					software_name	= ?,
					software_version= ?,
					software_key	= ?
				", array(
					$this->id,
					$software_key,
					$software_version,
					$software_key
				));
			}
		}

		public function getParameters()
		{
			$dbParams = $this->db->Execute("SELECT * FROM role_parameters WHERE role_id = ?", array($this->id));
			$retval = array();
			while ($param = $dbParams->FetchRow()) {
				$retval[] = array(
					'name'	=> $param['name'],
					'type'	=> $param['type'],
					'required'	=> $param['isrequired'],
					'defval'	=> $param['defval']
				);
			}

			return $retval;
		}

		public function setParameters(array $params = array())
		{
			$this->db->Execute("DELETE FROM role_parameters WHERE role_id = ?", array($this->id));
			foreach ($params as $param) {
				$param = (array)$param;

				$this->db->Execute("INSERT INTO role_parameters SET
					`role_id`		= ?,
					`name`			= ?,
					`type`			= ?,
					`isrequired`	= ?,
					`defval`		= ?,
					`allow_multiple_choice`	= 0,
					`options`		= '',
					`hash`			= ?,
					`issystem`		= 1
				", array(
					$this->id,
					$param['name'],
					$param['type'],
					$param['required'],
					$param['defval'],
					str_replace(" ", "_", strtolower($param['name']))
				));
			}
		}

		public static function createFromBundleTask(BundleTask $BundleTask)
		{
			$db = Core::GetDBInstance();

			if ($BundleTask->prototypeRoleId) {
				$proto_role = $db->GetRow("SELECT * FROM roles WHERE id=?", array($BundleTask->prototypeRoleId));

				$DBServer = DBServer::LoadByID($BundleTask->serverId);
			} else {
				$DBServer = DBServer::LoadByID($BundleTask->serverId);
				if ($DBServer->platform != SERVER_PLATFORMS::RDS) 
				{
					$proto_role = array(
						"behaviors" => $DBServer->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_BEHAVIOR),
						"architecture" => $DBServer->GetProperty(SERVER_PROPERTIES::ARCHITECTURE),
						"name" => "*import*"
					);
				}
			}

			if (!$BundleTask->cloudLocation) {
				if ($DBServer)
					$BundleTask->cloudLocation = $DBServer->GetCloudLocation();
			}

			$meta = $BundleTask->getSnapshotDetails();
			if ($meta) {
				if ($meta['os'])
					$os = $meta['os']->version;
			}
			else
				$os = $proto_role['os'];

			$db->Execute("INSERT INTO roles SET
				name			= ?,
				origin			= ?,
				client_id		= ?,
				env_id			= ?,
				description		= ?,
				behaviors		= ?,
				architecture	= ?,
				is_stable		= '1',
				history			= ?,
				approval_state	= ?,
				generation		= ?,
				os				= ?,
				szr_version		= ?
			", array(
				$BundleTask->roleName,
				ROLE_TYPE::CUSTOM,
				$BundleTask->clientId,
				$BundleTask->envId,
				$BundleTask->description,
				$proto_role['behaviors'],
				$proto_role['architecture'],
				trim("{$proto_role['history']},{$proto_role['name']}", ","),
				APPROVAL_STATE::APPROVED,
				($DBServer->IsSupported("0.5")) ? 2 : 1,
				($os) ? $os : "Unknown",
				$meta['szr_version']
			));

			$role_id = $db->Insert_Id();

			$BundleTask->roleId = $role_id;
			$BundleTask->Save();

			$BundleTask->Log(sprintf("Created new role. Role name: %s. Role ID: %s",
				$BundleTask->roleName, $BundleTask->roleId
			));

			$role =  self::loadById($role_id);

			$behaviors = explode(",",$proto_role['behaviors']);
			foreach ($behaviors as $behavior) {
				$db->Execute("INSERT INTO role_behaviors SET
					role_id			= ?,
					behavior		= ?
				", array(
					$role_id,
					$behavior
				));
			}

			// Set image
			$role->setImage($BundleTask->snapshotId, $BundleTask->platform, $BundleTask->cloudLocation);

			// Set params
			if ($proto_role['id'])
			{
				$dbParams = $db->GetAll("SELECT name,type,isrequired,defval,allow_multiple_choice,options,hash,issystem
					FROM role_parameters WHERE role_id = ?", array($proto_role['id'])
				);
				$role->setParameters($dbParams);

				$dbSecRules = $db->GetAll("SELECT * FROM role_security_rules WHERE role_id = ?", array($proto_role['id']));
				foreach ($dbSecRules as $dbSecRule) {
					$db->Execute("INSERT INTO role_security_rules SET role_id = ?, rule = ?", array(
						$role_id, $dbSecRule['rule']
					));
				}

				$props = $db->GetAll("SELECT * FROM role_properties WHERE role_id=?", array($proto_role['id']));
				foreach ($props as $prop) {
					$role->setProperty($prop['name'], $prop['value']);
				}
			} else {
				
				if ($role->hasBehavior(ROLE_BEHAVIORS::NGINX)) {
					// Add nginx parameter
					$params[] = array(
						'name' => 'Nginx HTTPS Vhost Template',
						'type' => 'textarea',
						'required' => '1',
						'defval' => @file_get_contents(dirname(__FILE__)."/../templates/services/nginx/ssl.vhost.tpl")
					);
				
					$role->setParameters($params);
				}
			}
			
			// Set software
			if ($meta) {

				$software = array();
				foreach ((array)$meta['software'] as $soft)
					$software[$soft->name] = $soft->version;

				$role->setSoftware($software);
				
				$role->setTags((array)$meta['tags']);
				
				if ($BundleTask->platform == SERVER_PLATFORMS::NIMBULA) {
					$props = array(
						array('name' => self::PROPERTY_NIMBULA_INIT_ROOT_USER, 'value' => $meta['init_root_user']),
						array('name' => self::PROPERTY_NIMBULA_INIT_ROOT_PASS, 'value' => $meta['init_root_pass']),
						array('name' => self::PROPERTY_NIMBULA_ENTRY, 'value' => '')
					);
					foreach ($props as $prop)
						$role->setProperty($prop['name'], $prop['value']);
				}
			}

			return $role;
		}
	}
?>