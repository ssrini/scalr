<?php

	class Scalr_Account extends Scalr_Model
	{
		protected $dbTableName = 'clients';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "Account #%s not found in database";

		const STATUS_ACTIVE = 'Active';
		const STATUS_INACIVE = 'Inactive';
		const STATUS_SUSPENDED = 'Suspended';
		
		const SETTING_SUSPEND_REASON = 'system.suspend.reason';
		const SETTING_OWNER_PWD_RESET_HASH = 'system.owner_pwd.hash';
		
		const SETTING_DATE_FIRST_LOGIN = 'date.first_login';
		const SETTING_DATE_ENV_CONFIGURED = 'date.env_configured';
		const SETTING_DATE_FARM_CREATED = 'date.farm_created';
		
		const SETTING_TRIAL_MAIL_SENT = 'mail.trial_sent';
		
		const SETTING_BILLING_ALERT_OLD_PKG = 'alerts.billing.old_package';
		const SETTING_BILLING_ALERT_PAYPAL = 'alerts.billing.paypal';

		protected $dbPropertyMap = array(
			'id'			=> 'id',
			'name'			=> 'name',
			'status'		=> 'status',
			'comments'		=> 'comments',
			'dtadded'		=> array('property' => 'dtAdded', 'update' => false, 'type' => 'datetime', 'createSql' => 'NOW()')
		);

		public $name;
		public $dtAdded;
		public $status;

		/**
		 * @return Scalr_Account
		 */
		public function loadById($id) {
			return parent::loadById($id);
		}
		
		/**
		 * @return Scalr_Account_User
		 */
		public function getOwner()
		{
			$userId = $this->db->GetOne("SELECT id FROM account_users WHERE `account_id` = ? AND `type` = ?", array($this->id, Scalr_Account_User::TYPE_ACCOUNT_OWNER));
			return Scalr_Account_User::init()->loadById($userId);
		}
		
		/**
		 * 
		 * @return Scalr_Account
		 */
		public function loadBySetting($name, $value)
		{
			$id = $this->db->GetOne("SELECT clientid FROM client_settings WHERE `key` = ? AND `value` = ?", 
				array($name, $value)
			);
			if (!$id)
				return false;
			else 
				return $this->loadById($id);
		}

		public function delete() {
			
			parent::delete();
			
			//TODO: Use models
			$this->db->Execute("DELETE FROM account_audit WHERE account_id=?", array($this->id));
			$this->db->Execute("DELETE FROM account_users WHERE account_id=?", array($this->id));
			$this->db->Execute("DELETE FROM account_teams WHERE account_id=?", array($this->id));
			$this->db->Execute("DELETE FROM account_limits WHERE account_id=?", array($this->id));
			$this->db->Execute("DELETE FROM client_environments WHERE client_id=?", array($this->id));
			
			$this->db->Execute("DELETE FROM servers WHERE client_id=?", array($this->id));
			$this->db->Execute("DELETE FROM ec2_ebs WHERE client_id=?", array($this->id));
			$this->db->Execute("DELETE FROM apache_vhosts WHERE client_id=?", array($this->id));
			$this->db->Execute("DELETE FROM scheduler WHERE account_id=?", array($this->id));
			
			$farms = $this->db->GetAll("SELECT id FROM farms WHERE clientid='{$this->id}'");
			foreach ($farms as $farm)
			{
				$this->db->Execute("DELETE FROM farms WHERE id=?", array($farm["id"]));
				$this->db->Execute("DELETE FROM farm_roles WHERE farmid=?", array($farm["id"]));
				$this->db->Execute("DELETE FROM farm_role_options WHERE farmid=?", array($farm["id"]));
                $this->db->Execute("DELETE FROM farm_role_scripts WHERE farmid=?", array($farm["id"]));
                $this->db->Execute("DELETE FROM farm_event_observers WHERE farmid=?", array($farm["id"]));
                $this->db->Execute("DELETE FROM elastic_ips WHERE farmid=?", array($farm["id"]));
			}
				    
			$roles = $this->db->GetAll("SELECT id FROM roles WHERE client_id='{$this->id}'");
			foreach ($roles as $role)
			{
				$this->db->Execute("DELETE FROM roles WHERE id = ?", array($role['id']));
			
				$this->db->Execute("DELETE FROM role_behaviors WHERE role_id = ?", array($role['id']));
				$this->db->Execute("DELETE FROM role_images WHERE role_id = ?", array($role['id']));
				$this->db->Execute("DELETE FROM role_parameters WHERE role_id = ?", array($role['id']));
				$this->db->Execute("DELETE FROM role_properties WHERE role_id = ?", array($role['id']));
				$this->db->Execute("DELETE FROM role_security_rules WHERE role_id = ?", array($role['id']));
				$this->db->Execute("DELETE FROM role_software WHERE role_id = ?", array($role['id']));
			}
		}
		
		/**
		 *
		 * @param string $name
		 * @return Scalr_Account_Group
		 */
		public function createTeam($name)
		{
			if (!$this->id)
				throw new Exception("Account is not created");

			$group = Scalr_Account_Group::init();
			$group->accountId = $this->id;
			$group->name = $name;
			$group->isActive = 1;

			return $group->save();
		}

		/**
		 *
		 * @param integer $groupId
		 * @param string $login
		 * @param string $password
		 * @param string $email
		 * @return Scalr_Account_User
		 */
		public function createUser($email, $password, $type)
		{
			if (!$this->id)
				throw new Exception("Account is not created");

			$this->validateLimit(Scalr_Limits::ACCOUNT_USERS, 1);

			$user = Scalr_Account_User::init()->create($email, $this->id);
			$user->updatePassword($password);
			$user->type = $type;
			$user->status = Scalr_Account_User::STATUS_ACTIVE;

			$user->save();
			
			$keys = Scalr::GenerateAPIKeys();
			
			$user->setSetting(Scalr_Account_User::SETTING_API_ACCESS_KEY, $keys['id']);
			$user->setSetting(Scalr_Account_User::SETTING_API_SECRET_KEY, $keys['key']);

			return $user;
		}
		
		/**
		 *
		 * @param string $name
		 * @param boolean $isSystem
		 * @throws Scalr_Exception_LimitExceeded
		 * @return Scalr_Environment
		 */
		public function createEnvironment($name, $isSystem = false)
		{
			if (!$this->id)
				throw new Exception("Account is not created");

			$this->validateLimit(Scalr_Limits::ACCOUNT_ENVIRONMENTS, 1);

			$env = Scalr_Environment::init()->create($name, $this->id, $isSystem);

			$config[ENVIRONMENT_SETTINGS::TIMEZONE] = "America/Adak";

			$env->setPlatformConfig($config, false);

			return $env;
		}

		/**
		 * Returns client setting value by name
		 *
		 * @param string $name
		 * @return mixed $value
		 */
		public function getSetting($name)
		{
			return $this->db->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?",
				array($this->id, $name)
			);
		}

		/**
		 * Set client setting
		 * @param string $name
		 * @param mixed $value
		 * @return void
		 */
		public function setSetting($name, $value)
		{
			$this->db->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, clientid=?",
				array($name, $value, $this->id)
			);
		}

		public function clearSettings ($filter)
		{
			$this->db->Execute(
				"DELETE FROM client_settings WHERE `key` LIKE '{$filter}' AND clientid = ?",
				array($this->id)
			);
		}

		public function isFeatureEnabled($feature)
		{
			$limit = Scalr_Limits::init()->Load($feature, $this->id);
			return $limit->check(1);
		}
		
		public function setLimit($limitName, $limitValue) {
			$limit = Scalr_Limits::init()->Load($limitName, $this->id);
			$limit->setLimitValue($limitValue);
			$limit->save();
		}

		public function setLimits(array $limits) {
			foreach ($limits as $k=>$v)
				$this->setLimit($k, $v);
		}

		/**
		 *
		 * @param string $limitName
		 * @param string $limitValue
		 * @throws Scalr_Exception_LimitExceeded
		 */
		public function validateLimit($limitName, $limitValue) {
			if (!$this->checkLimit($limitName, $limitValue))
				throw new Scalr_Exception_LimitExceeded($limitName);
		}

		/**
		 *
		 * @param string $limitName
		 * @param integer $limitValue
		 * @return boolean
		 */
		public function checkLimit($limitName, $limitValue) {
			return Scalr_Limits::init()->Load($limitName, $this->id)->check($limitValue);
		}
		
		/*
		 * @return boolean
		 */
		public function resetLimits()
		{
			foreach ($this->getLimits() as $limitName => $limit) {
				$this->setLimit($limitName, -1);
			}
		}
		
		/**
		 * @return array $limits
		 */
		public function getLimits()
		{
			$l = array(Scalr_Limits::ACCOUNT_ENVIRONMENTS, Scalr_Limits::ACCOUNT_FARMS, Scalr_Limits::ACCOUNT_SERVERS, Scalr_Limits::ACCOUNT_USERS);
			$limits = array();
			foreach ($l as $limitName) {
				$limit = Scalr_Limits::init()->Load($limitName, $this->id);
				$limits[$limitName] = array(
					'limit' => $limit->getLimitValue(),
					'usage' => $limit->getCurrentUsage()
				);
			}
			
			return $limits;
		}

		/**
		 *
		 * @return Scalr_Account
		 */
		public static function init() {
			return parent::init();
		}
	}
