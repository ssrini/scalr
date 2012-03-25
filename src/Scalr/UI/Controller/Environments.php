<?php

class Scalr_UI_Controller_Environments extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'envId';
	
	private $checkVarError;
	
	public static function getApiDefinitions()
	{
		return array('xListEnvironments', 'xGetInfo', 'xCreate', 'xSave', 'xRemove');
	}

	public function hasAccess()
	{
		if (parent::hasAccess()) {
			return $this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER ? true : false;
		} else
			return false;
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function viewAction()
	{
		$this->response->page('ui/environments/view.js');
	}

	public function xListEnvironmentsAction()
	{
		$this->request->defineParams(array(
			'sort' => array('type' => 'json')
		));
		
		$sql = "SELECT
			id,
			name,
			dt_added AS dtAdded,
			is_system AS isSystem
			FROM client_environments
			WHERE client_id = ? AND :FILTER:
			GROUP BY id
		";

		$response = $this->buildResponseFromSql($sql, array('id', 'name', 'dtAdded'), array(), array($this->user->getAccountId()));
		foreach ($response['data'] as &$row) {
			foreach (Scalr_Environment::init()->loadById($row['id'])->getEnabledPlatforms() as $platform)
				$row['platforms'][] = SERVER_PLATFORMS::GetName($platform);

			$row['platforms'] = implode(', ', $row['platforms']);
			$row['dtAdded'] = Scalr_Util_DateTime::convertTz($row['dtAdded']);
		}

		$this->response->data($response);
	}

	public function xRemoveAction()
	{
		$env = Scalr_Environment::init()->loadById($this->getParam('envId'));
		$this->user->getPermissions()->validate($env);
		$env->delete();
		
		if ($env->id == $this->getEnvironmentId())
			Scalr_Session::getInstance()->setEnvironmentId(null); // reset
		
		$this->response->success("Environment successfully removed");
		$this->response->data(array('envId' => $env->id, 'flagReload' => $env->id == $this->getEnvironmentId() ? true : false));
	}
	
	public function createAction()
	{
		$this->user->getAccount()->validateLimit(Scalr_Limits::ACCOUNT_ENVIRONMENTS, 1);
		$this->response->page('ui/environments/create.js', array());
	}
	
	public function xCreateAction()
	{
		$this->user->getAccount()->validateLimit(Scalr_Limits::ACCOUNT_ENVIRONMENTS, 1);
		$env = $this->user->getAccount()->createEnvironment($this->getParam('name'), false);
		
		$this->response->success("Environment successfully created");
		$this->response->data(array(
			'env' => array(
				'id' => $env->id,
				'name' => $env->name
			)
		));
	}

	protected function getEnvironmentInfo()
	{
		$env = Scalr_Environment::init();
		$env->loadById($this->getParam('envId'));
		$this->user->getPermissions()->validate($env);
		
		$params = array();

		$params[ENVIRONMENT_SETTINGS::TIMEZONE] = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::TIMEZONE);
		
		return array(
			'id' => $env->id,
			'name' => $env->name,
			'params' => $params,
			'enabledPlatforms' => $env->getEnabledPlatforms()
		);
	}

	public function editAction()
	{
		$platforms = SERVER_PLATFORMS::GetList();
		unset($platforms[SERVER_PLATFORMS::RDS]);

		//TODO:
		if (!$this->getParam('beta')) {
			unset($platforms[SERVER_PLATFORMS::OPENSTACK]);
		}

		$timezones = array();
		$timezoneAbbreviationsList = timezone_abbreviations_list();
		foreach ($timezoneAbbreviationsList as $timezoneAbbreviations) {
			foreach ($timezoneAbbreviations as $value) {
				if (preg_match( '/^(America|Antartica|Arctic|Asia|Atlantic|Europe|Indian|Pacific|Australia)\//', $value['timezone_id']))
					$timezones[$value['timezone_id']] = $value['offset'];
			}
		}

		@ksort($timezones);
		$timezones = array_keys($timezones);

		$this->response->page('ui/environments/edit.js', array(
			'environment' => $this->getEnvironmentInfo(),
			'platforms' => $platforms,
			'timezones' => $timezones
		));
	}

	public function xGetInfoAction()
	{
		$this->response->data(array('environment' => $this->getEnvironmentInfo()));
	}

	private function checkVar($name, $type, $env, $requiredError = '', $group = '')
	{
		$varName = str_replace('.', '_', ($group != '' ? $name . '.' . $group : $name));

		switch ($type) {
			case 'int':
				if ($this->getParam($varName)) {
					return intval($this->getParam($varName));
				} else {
					$value = $env->getPlatformConfigValue($name, true, $group);
					if (!$value && $requiredError)
						$this->checkVarError[$name] = $requiredError;

					return $value;
				}
				break;

			case 'string':
				if ($this->getParam($varName)) {
					return $this->getParam($varName);
				} else {
					$value = $env->getPlatformConfigValue($name, true, $group);
					if ($value == '' && $requiredError)
						$this->checkVarError[$name] = $requiredError;

					return $value;
				}
				break;

			case 'password':
				if ($this->getParam($varName) && $this->getParam($varName) != '******') {
					return $this->getParam($varName);
				} else {
					$value = $env->getPlatformConfigValue($name, true, $group);
					if ($value == '' && $requiredError)
						$this->checkVarError[$name] = $requiredError;

					return $value;
				}
				break;

			case 'bool':
				return $this->getParam($varName) ? 1 : 0;
		}
	}
	
	public function xSaveAction()
	{
		$this->request->defineParams(array('envId' => array('type' => 'int')));

		$env = Scalr_Environment::init()->loadById($this->getParam('envId'));
		$this->user->getPermissions()->validate($env);

		$pars = array();

		// check for settings
		$pars[ENVIRONMENT_SETTINGS::TIMEZONE] = $this->checkVar(ENVIRONMENT_SETTINGS::TIMEZONE, 'string', $env, "Timezone required");

		$env->setPlatformConfig($pars);
		
		if (! $this->user->getAccount()->getSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED))
			$this->user->getAccount()->setSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED, time());

		$this->response->success('Environment saved');
	}
}
