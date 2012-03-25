<?php

class Scalr_UI_Controller_Environments_Platform extends Scalr_UI_Controller
{
	private $env;
	private $checkVarError;
	
	public function init()
	{
		$this->env = Scalr_Environment::init()->loadById($this->getParam(Scalr_UI_Controller_Environments::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($this->env);
	}

	public static function getApiDefinitions()
	{
		return array('xSaveEc2', 'xSaveRackspace', 'xSaveNimbula', 'xSaveCloudstack', 'xSaveOpenstack', 'xSaveEucalyptus');
	}

	private function checkVar($name, $type, $requiredError = '', $group = '')
	{
		$varName = str_replace('.', '_', ($group != '' ? $name . '.' . $group : $name));
		$errorName = $group != '' ? $name . '.' . $group : $name;

		switch ($type) {
			case 'int':
				if ($this->getParam($varName)) {
					return intval($this->getParam($varName));
				} else {
					$value = $this->env->getPlatformConfigValue($name, true, $group);
					if (!$value && $requiredError)
						$this->checkVarError[$errorName] = $requiredError;

					return $value;
				}
				break;

			case 'string':
				if ($this->getParam($varName)) {
					return $this->getParam($varName);
				} else {
					$value = $this->env->getPlatformConfigValue($name, true, $group);
					if ($value == '' && $requiredError)
						$this->checkVarError[$errorName] = $requiredError;

					return $value;
				}
				break;

			case 'password':
				if ($this->getParam($varName) && $this->getParam($varName) != '******') {
					return $this->getParam($varName);
				} else {
					$value = $this->env->getPlatformConfigValue($name, true, $group);
					if ($value == '' && $requiredError)
						$this->checkVarError[$errorName] = $requiredError;

					return $value;
				}
				break;

			case 'bool':
				return $this->getParam($varName) ? 1 : 0;

			case 'file':
				if (isset($_FILES[$varName]['tmp_name']) && ($value = @file_get_contents($_FILES[$varName]['tmp_name'])) != '') {
					return trim($value);
				} else {
					$value = $this->env->getPlatformConfigValue($name, true, $group);
					if ($value == '' && $requiredError)
						$this->checkVarError[$errorName] = $requiredError;

					return $value;
				}
				break;
		}
	}

	public function ec2Action()
	{
		$params = array();

		if (in_array(SERVER_PLATFORMS::EC2, $this->env->getEnabledPlatforms())) {
			$params['ec2.is_enabled'] = true;
			$params[Modules_Platforms_Ec2::ACCOUNT_ID] = $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID);
			$params[Modules_Platforms_Ec2::ACCESS_KEY] = $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY);
			$params[Modules_Platforms_Ec2::SECRET_KEY] = $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY) != '' ? '******' : '';
			$params[Modules_Platforms_Ec2::PRIVATE_KEY] = $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY) != '' ? 'Uploaded' : '';
			$params[Modules_Platforms_Ec2::CERTIFICATE] = $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE) != '' ? 'Uploaded' : '';
		}

		$this->response->page('ui/environments/platform/ec2.js', array(
			'env' => array(
				'id' => $this->env->id,
				'name' => $this->env->name
			),
			'params' => $params
		));
	}

	public function xSaveEc2Action()
	{
		$pars = array();
		$enabled = false;

		if ($this->getParam('ec2_is_enabled')) {
			$enabled = true;

			$pars[Modules_Platforms_Ec2::ACCOUNT_ID] = $this->checkVar(Modules_Platforms_Ec2::ACCOUNT_ID, 'string', "AWS Account Number required");

			if (! is_numeric($pars[Modules_Platforms_Ec2::ACCOUNT_ID]) || strlen($pars[Modules_Platforms_Ec2::ACCOUNT_ID]) != 12)
				//$err[Modules_Platforms_Ec2::ACCOUNT_ID] = _("AWS numeric account ID required (See <a href='/faq.html'>FAQ</a> for info on where to get it).");
				$this->checkVarError[Modules_Platforms_Ec2::ACCOUNT_ID] = _("AWS Account Number should be numeric");
			else
				$pars[Modules_Platforms_Ec2::ACCOUNT_ID] = preg_replace("/[^0-9]+/", "", $pars[Modules_Platforms_Ec2::ACCOUNT_ID]);

			$pars[Modules_Platforms_Ec2::ACCESS_KEY] = $this->checkVar(Modules_Platforms_Ec2::ACCESS_KEY, 'string', "AWS Access Key required");
			$pars[Modules_Platforms_Ec2::SECRET_KEY] = $this->checkVar(Modules_Platforms_Ec2::SECRET_KEY, 'password', "AWS Access Key required");
			$pars[Modules_Platforms_Ec2::PRIVATE_KEY] = trim($this->checkVar(Modules_Platforms_Ec2::PRIVATE_KEY, 'file', "AWS x.509 Private Key required"));
			$pars[Modules_Platforms_Ec2::CERTIFICATE] = trim($this->checkVar(Modules_Platforms_Ec2::CERTIFICATE, 'file', "AWS x.509 Certificate required"));

			// the same as EC2
			$pars[Modules_Platforms_Rds::ACCOUNT_ID] = $pars[Modules_Platforms_Ec2::ACCOUNT_ID];
			$pars[Modules_Platforms_Rds::ACCESS_KEY] = $pars[Modules_Platforms_Ec2::ACCESS_KEY];
			$pars[Modules_Platforms_Rds::SECRET_KEY] = $pars[Modules_Platforms_Ec2::SECRET_KEY];
			$pars[Modules_Platforms_Rds::PRIVATE_KEY] = $pars[Modules_Platforms_Ec2::PRIVATE_KEY];
			$pars[Modules_Platforms_Rds::CERTIFICATE] = $pars[Modules_Platforms_Ec2::CERTIFICATE];

			if (! count($this->checkVarError)) {
				if (
					$pars[Modules_Platforms_Ec2::ACCOUNT_ID] != $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID) or
					$pars[Modules_Platforms_Ec2::ACCESS_KEY] != $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY) or
					$pars[Modules_Platforms_Ec2::SECRET_KEY] != $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY) or
					$pars[Modules_Platforms_Ec2::PRIVATE_KEY] != $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY) or
					$pars[Modules_Platforms_Ec2::CERTIFICATE] != $this->env->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
				) {
					try {
						$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
							'us-east-1',
							$pars[Modules_Platforms_Ec2::PRIVATE_KEY],
							$pars[Modules_Platforms_Ec2::CERTIFICATE]
						);
						$AmazonEC2Client->describeInstances();
					} catch (Exception $e) {
						throw new Exception(_("Incorrect format of X.509 certificate or private key. Make sure that you are using files downloaded from AWS profile. ({$e->getMessage()})"));
					}

					try {
						$AmazonS3 = new AmazonS3($pars[Modules_Platforms_Ec2::ACCESS_KEY], $pars[Modules_Platforms_Ec2::SECRET_KEY]);
						$buckets = $AmazonS3->ListBuckets();
					} catch(Exception $e) {
						throw new Exception(sprintf(_("Failed to verify your EC2 access key and secret key: %s"), $e->getMessage()));
					}
				}
			} else {
				$this->response->failure();
				$this->response->data(array('errors' => $this->checkVarError));
				return;
			}
		}

		$this->db->BeginTrans();
		try {
			$this->env->enablePlatform(SERVER_PLATFORMS::EC2, $enabled);
			$this->env->enablePlatform(SERVER_PLATFORMS::RDS, $enabled);

			if ($enabled)
				$this->env->setPlatformConfig($pars);

			if (! $this->user->getAccount()->getSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED))
				$this->user->getAccount()->setSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED, time());

			$this->response->success('Environment saved');
			$this->response->data(array('enabled' => $enabled));
		} catch (Exception $e) {
			$this->db->RollbackTrans();
			throw new Exception(_('Failed to save AWS settings'));
		}
		$this->db->CommitTrans();
	}

	public function rackspaceAction()
	{
		$params = array();

		$rows = $this->db->GetAll('SELECT * FROM client_environment_properties WHERE env_id = ? AND name LIKE "rackspace.%" AND `group` != "" GROUP BY `group', $this->env->id);
		foreach ($rows as $value) {
			$cloud = $value['group'];
			$params[$cloud] = array(
				Modules_Platforms_Rackspace::USERNAME => $this->env->getPlatformConfigValue(Modules_Platforms_Rackspace::USERNAME, true, $cloud),
				Modules_Platforms_Rackspace::API_KEY => $this->env->getPlatformConfigValue(Modules_Platforms_Rackspace::API_KEY, true, $cloud),
				Modules_Platforms_Rackspace::IS_MANAGED => $this->env->getPlatformConfigValue(Modules_Platforms_Rackspace::IS_MANAGED, true, $cloud),
			);
		}

		$this->response->page('ui/environments/platform/rackspace.js', array(
			'env' => array(
				'id' => $this->env->id,
				'name' => $this->env->name
			),
			'params' => $params
		));
	}

	public function xSaveRackspaceAction()
	{
		$pars = array();
		$enabled = false;
		$locations = array('rs-ORD1', 'rs-LONx');

		foreach ($locations as $location) {
			if ($this->getParam("rackspace_is_enabled_{$location}")) {
				$enabled = true;

				$pars[$location][Modules_Platforms_Rackspace::USERNAME] = $this->checkVar(Modules_Platforms_Rackspace::USERNAME, 'string', "Username required", $location);
				$pars[$location][Modules_Platforms_Rackspace::API_KEY] = $this->checkVar(Modules_Platforms_Rackspace::API_KEY, 'string', "API Key required", $location);
				$pars[$location][Modules_Platforms_Rackspace::IS_MANAGED] = $this->checkVar(Modules_Platforms_Rackspace::IS_MANAGED, 'bool', "", $location);
			}
			else {
				$pars[$location][Modules_Platforms_Rackspace::USERNAME] = false;
				$pars[$location][Modules_Platforms_Rackspace::API_KEY] = false;
				$pars[$location][Modules_Platforms_Rackspace::IS_MANAGED] = false;
			}
		}

		if (count($this->checkVarError)) {
			$this->response->failure();
			$this->response->data(array('errors' => $this->checkVarError));
		} else {
			$this->db->BeginTrans();
			try {
				$this->env->enablePlatform(SERVER_PLATFORMS::RACKSPACE, $enabled);

				foreach ($pars as $cloud => $prs)
					$this->env->setPlatformConfig($prs, true, $cloud);

				if (! $this->user->getAccount()->getSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED))
					$this->user->getAccount()->setSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED, time());

				$this->response->success('Environment saved');
				$this->response->data(array('enabled' => $enabled));
			} catch (Exception $e) {
				$this->db->RollbackTrans();
				throw new Exception(_('Failed to save Rackspace settings'));
			}
			$this->db->CommitTrans();
		}
	}

	public function nimbulaAction()
	{
		$params = array();

		if (in_array(SERVER_PLATFORMS::NIMBULA, $this->env->getEnabledPlatforms())) {
			$params['nimbula.is_enabled'] = true;
			$params[Modules_Platforms_Nimbula::API_URL] = $this->env->getPlatformConfigValue(Modules_Platforms_Nimbula::API_URL);
			$params[Modules_Platforms_Nimbula::USERNAME] = $this->env->getPlatformConfigValue(Modules_Platforms_Nimbula::USERNAME);
			$params[Modules_Platforms_Nimbula::PASSWORD] = $this->env->getPlatformConfigValue(Modules_Platforms_Nimbula::PASSWORD);
		}

		$this->response->page('ui/environments/platform/nimbula.js', array(
			'env' => array(
				'id' => $this->env->id,
				'name' => $this->env->name
			),
			'params' => $params
		));
	}

	public function xSaveNimbulaAction()
	{
		$pars = array();
		$enabled = false;

		if ($this->getParam('nimbula_is_enabled')) {
			$enabled = true;

			$pars[Modules_Platforms_Nimbula::API_URL] = $this->checkVar(Modules_Platforms_Nimbula::API_URL, 'string', 'API URL required');
			$pars[Modules_Platforms_Nimbula::USERNAME] = $this->checkVar(Modules_Platforms_Nimbula::USERNAME, 'string', 'Username required');
			$pars[Modules_Platforms_Nimbula::PASSWORD] = $this->checkVar(Modules_Platforms_Nimbula::PASSWORD, 'string', 'Password required');
		}

		if (count($this->checkVarError)) {
			$this->response->failure();
			$this->response->data(array('errors' => $this->checkVarError));
		} else {
			$this->db->BeginTrans();
			try {
				$this->env->enablePlatform(SERVER_PLATFORMS::NIMBULA, $enabled);

				if ($enabled)
					$this->env->setPlatformConfig($pars);

				if (! $this->user->getAccount()->getSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED))
					$this->user->getAccount()->setSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED, time());

				$this->response->success('Environment saved');
				$this->response->data(arraY('enabled' => $enabled));
			} catch (Exception $e) {
				$this->db->RollbackTrans();
				throw new Exception(_('Failed to save Nimbula settings'));
			}
			$this->db->CommitTrans();
		}
	}

	public function cloudstackAction()
	{
		$params = array();

		if (in_array(SERVER_PLATFORMS::CLOUDSTACK, $this->env->getEnabledPlatforms())) {
			$params['cloudstack.is_enabled'] = true;
			$params[Modules_Platforms_Cloudstack::API_URL] = $this->env->getPlatformConfigValue(Modules_Platforms_Cloudstack::API_URL);
			$params[Modules_Platforms_Cloudstack::API_KEY] = $this->env->getPlatformConfigValue(Modules_Platforms_Cloudstack::API_KEY);
			$params[Modules_Platforms_Cloudstack::SECRET_KEY] = $this->env->getPlatformConfigValue(Modules_Platforms_Cloudstack::SECRET_KEY);
		}

		$this->response->page('ui/environments/platform/cloudstack.js', array(
			'env' => array(
				'id' => $this->env->id,
				'name' => $this->env->name
			),
			'params' => $params
		));
	}

	public function xSaveCloudstackAction()
	{
		$pars = array();
		$enabled = false;

		if ($this->getParam('cloudstack_is_enabled')) {
			$enabled = true;

			$pars[Modules_Platforms_Cloudstack::API_URL] = $this->checkVar(Modules_Platforms_Cloudstack::API_URL, 'string', 'API URL required');
			$pars[Modules_Platforms_Cloudstack::API_KEY] = $this->checkVar(Modules_Platforms_Cloudstack::API_KEY, 'string', 'API key required');
			$pars[Modules_Platforms_Cloudstack::SECRET_KEY] = $this->checkVar(Modules_Platforms_Cloudstack::SECRET_KEY, 'string', 'Secret key required');
		}

		if (count($this->checkVarError)) {
			$this->response->failure();
			$this->response->data(array('errors' => $this->checkVarError));
		} else {
			
			$cs = Scalr_Service_Cloud_Cloudstack::newCloudstack(
				$pars[Modules_Platforms_Cloudstack::API_URL],
				$pars[Modules_Platforms_Cloudstack::API_KEY],
				$pars[Modules_Platforms_Cloudstack::SECRET_KEY]
			);
			$accounts = $cs->listAccounts();
			foreach ($accounts as $account) {
				foreach ($account->user as $user) {
					if ($user->apikey == $pars[Modules_Platforms_Cloudstack::API_KEY]) {
						$dPars[Modules_Platforms_Cloudstack::ACCOUNT_NAME] = $user->account;
						$dPars[Modules_Platforms_Cloudstack::DOMAIN_NAME] = $user->domain;
						$dPars[Modules_Platforms_Cloudstack::DOMAIN_ID] = $user->domainid; 
					}
				}
			}
			
			if (!$dPars[Modules_Platforms_Cloudstack::ACCOUNT_NAME])
				throw new Exception("Cannot determine account name for provided keys");
			
			$this->db->BeginTrans();
			try {
				$this->env->enablePlatform(SERVER_PLATFORMS::CLOUDSTACK, $enabled);

				if ($enabled) {
					$this->env->setPlatformConfig($pars);
					$this->env->setPlatformConfig($dPars, false);
				}

				if (! $this->user->getAccount()->getSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED))
					$this->user->getAccount()->setSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED, time());

				$this->response->success('Environment saved');
				$this->response->data(array('enabled' => $enabled));
			} catch (Exception $e) {
				$this->db->RollbackTrans();
				throw new Exception(_('Failed to save Cloudstack settings'));
			}
			$this->db->CommitTrans();
		}
	}

	public function openstackAction()
	{
		$params = array();

		$rows = $this->db->GetAll('SELECT * FROM client_environment_properties WHERE env_id = ? AND name LIKE "openstack.%" AND `group` != "" GROUP BY `group`', $this->env->id);
		foreach ($rows as $value) {
			$cloud = $value['group'];
			$params[$cloud] = array(
				Modules_Platforms_Openstack::USERNAME => $this->env->getPlatformConfigValue(Modules_Platforms_Openstack::USERNAME, true, $cloud),
				Modules_Platforms_Openstack::API_URL => $this->env->getPlatformConfigValue(Modules_Platforms_Openstack::API_URL, true, $cloud),
				Modules_Platforms_Openstack::API_KEY => $this->env->getPlatformConfigValue(Modules_Platforms_Openstack::API_KEY, true, $cloud),
				Modules_Platforms_Openstack::PROJECT_NAME => $this->env->getPlatformConfigValue(Modules_Platforms_Openstack::PROJECT_NAME, true, $cloud),
			);
		}

		$this->response->page('ui/environments/platform/openstack.js', array(
			'env' => array(
				'id' => $this->env->id,
				'name' => $this->env->name
			),
			'params' => $params
		));
	}

	public function xSaveOpenstackAction()
	{
		$this->request->defineParams(array(
			'clouds' => array('type' => 'json')
		));

		$pars = array();
		$enabled = false;

		$clouds = $this->getParam('clouds');
		$cloudsDeleted = array();
		if (count($clouds)) {
			$enabled = true;

			foreach ($clouds as $cloud) {
				$pars[$cloud][Modules_Platforms_Openstack::API_URL] = $this->checkVar(Modules_Platforms_Openstack::API_URL, 'string', "API URL required", $cloud);
				$pars[$cloud][Modules_Platforms_Openstack::API_KEY] = $this->checkVar(Modules_Platforms_Openstack::API_KEY, 'string', "API Key required", $cloud);
				$pars[$cloud][Modules_Platforms_Openstack::USERNAME] = $this->checkVar(Modules_Platforms_Openstack::USERNAME, 'string', "Username required", $cloud);
				$pars[$cloud][Modules_Platforms_Openstack::PROJECT_NAME] = $this->checkVar(Modules_Platforms_Openstack::PROJECT_NAME, 'string', null, $cloud);
			}
		}

		// clear old cloud locations
		foreach ($this->db->GetAll('SELECT * FROM client_environment_properties WHERE env_id = ? AND name LIKE "openstack.%" AND `group` != "" GROUP BY `group', $this->env->id) as $key => $value) {
			if (! in_array($value['group'], $clouds))
				$cloudsDeleted[] = $value['group'];
		}

		if (count($this->checkVarError)) {
			$this->response->failure();
			$this->response->data(array('errors' => $this->checkVarError));
		} else {
			$this->db->BeginTrans();
			try {
				$this->env->enablePlatform(SERVER_PLATFORMS::OPENSTACK, $enabled);

				foreach ($cloudsDeleted as $key => $cloud)
					$this->db->Execute('DELETE FROM client_environment_properties WHERE env_id = ? AND `group` = ? AND name LIKE "openstack.%"', array($this->env->id, $cloud));

				foreach ($pars as $cloud => $prs)
					$this->env->setPlatformConfig($prs, true, $cloud);

				if (! $this->user->getAccount()->getSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED))
					$this->user->getAccount()->setSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED, time());

				$this->response->success(_('Environment saved'));
				$this->response->data(array('enabled' => $enabled));
			} catch (Exception $e) {
				$this->db->RollbackTrans();
				throw new Exception(_('Failed to save Openstack settings'));
			}
			$this->db->CommitTrans();
		}
	}

	public function eucalyptusAction()
	{
		$params = array();

		$rows = $this->db->GetAll('SELECT * FROM client_environment_properties WHERE env_id = ? AND name LIKE "eucalyptus.%" AND `group` != "" GROUP BY `group`', $this->env->id);
		foreach ($rows as $value) {
			$cloud = $value['group'];
			$params[$cloud] = array(
				Modules_Platforms_Eucalyptus::ACCOUNT_ID => $this->env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::ACCOUNT_ID, true, $cloud),
				Modules_Platforms_Eucalyptus::ACCESS_KEY => $this->env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::ACCESS_KEY, true, $cloud),
				Modules_Platforms_Eucalyptus::EC2_URL => $this->env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::EC2_URL, true, $cloud),
				Modules_Platforms_Eucalyptus::S3_URL => $this->env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::S3_URL, true, $cloud),
				Modules_Platforms_Eucalyptus::SECRET_KEY => $this->env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::SECRET_KEY, true, $cloud) != '' ? '******' : false,
				Modules_Platforms_Eucalyptus::PRIVATE_KEY => $this->env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::PRIVATE_KEY, true, $cloud) != '' ? 'Uploaded' : '',
				Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE => $this->env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE, true, $cloud) != '' ? 'Uploaded' : '',
				Modules_Platforms_Eucalyptus::CERTIFICATE => $this->env->getPlatformConfigValue(Modules_Platforms_Eucalyptus::CERTIFICATE, true, $cloud) != '' ? 'Uploaded' : ''
			);
		}

		$this->response->page('ui/environments/platform/eucalyptus.js', array(
			'env' => array(
				'id' => $this->env->id,
				'name' => $this->env->name
			),
			'params' => $params
		));
	}

	public function xSaveEucalyptusAction()
	{
		$this->request->defineParams(array(
			'clouds' => array('type' => 'json')
		));

		$pars = array();
		$enabled = false;

		$clouds = $this->getParam('clouds');
		$cloudsDeleted = array();
		if (count($clouds)) {
			$enabled = true;

			foreach ($clouds as $cloud) {
				$pars[$cloud][Modules_Platforms_Eucalyptus::ACCOUNT_ID] = $this->checkVar(Modules_Platforms_Eucalyptus::ACCOUNT_ID, 'string', "Account ID required", $cloud);
				$pars[$cloud][Modules_Platforms_Eucalyptus::ACCESS_KEY] = $this->checkVar(Modules_Platforms_Eucalyptus::ACCESS_KEY, 'string', "Access Key required", $cloud);
				$pars[$cloud][Modules_Platforms_Eucalyptus::EC2_URL] = $this->checkVar(Modules_Platforms_Eucalyptus::EC2_URL, 'string', "EC2 URL required", $cloud);
				$pars[$cloud][Modules_Platforms_Eucalyptus::S3_URL] = $this->checkVar(Modules_Platforms_Eucalyptus::S3_URL, 'string', "S3 URL required", $cloud);
				$pars[$cloud][Modules_Platforms_Eucalyptus::SECRET_KEY] = $this->checkVar(Modules_Platforms_Eucalyptus::SECRET_KEY, 'password', "Secret Key required", $cloud);
				$pars[$cloud][Modules_Platforms_Eucalyptus::PRIVATE_KEY] = $this->checkVar(Modules_Platforms_Eucalyptus::PRIVATE_KEY, 'file', "x.509 Private Key required", $cloud);
				$pars[$cloud][Modules_Platforms_Eucalyptus::CERTIFICATE] = $this->checkVar(Modules_Platforms_Eucalyptus::CERTIFICATE, 'file', "x.509 Certificate required", $cloud);
				$pars[$cloud][Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE] = $this->checkVar(Modules_Platforms_Eucalyptus::CLOUD_CERTIFICATE, 'file', "x.509 Cloud Certificate required", $cloud);
			}
		}

		// clear old cloud locations
		foreach ($this->db->GetAll('SELECT * FROM client_environment_properties WHERE env_id = ? AND name LIKE "eucalyptus.%" AND `group` != "" GROUP BY `group', $this->env->id) as $key => $value) {
			if (! in_array($value['group'], $clouds))
				$cloudsDeleted[] = $value['group'];
		}

		if (count($this->checkVarError)) {
			$this->response->failure();
			$this->response->data(array('errors' => $this->checkVarError));
		} else {
			$this->db->BeginTrans();
			try {
				$this->env->enablePlatform(SERVER_PLATFORMS::EUCALYPTUS, $enabled);

				foreach ($cloudsDeleted as $key => $cloud)
					$this->db->Execute('DELETE FROM client_environment_properties WHERE env_id = ? AND `group` = ? AND name LIKE "eucalyptus.%"', array($this->env->id, $cloud));

				foreach ($pars as $cloud => $prs)
					$this->env->setPlatformConfig($prs, true, $cloud);

				if (! $this->user->getAccount()->getSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED))
					$this->user->getAccount()->setSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED, time());

				$this->response->success(_('Environment saved'));
				$this->response->data(array('enabled' => $enabled));
			} catch (Exception $e) {
				$this->db->RollbackTrans();
				throw new Exception(_('Failed to save Eucalyptus settings'));
			}
			$this->db->CommitTrans();
		}
	}
}
