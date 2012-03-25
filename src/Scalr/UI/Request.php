<?php
class Scalr_UI_Request
{
	protected
		$params = array(),
		$definitions = array(),
		$requestParams = array(),
		$user,
		$environment,
		$requestType,
		$paramErrors = array(),
		$paramsIsValid = true;

	const REQUEST_TYPE_UI = 'ui';
	const REQUEST_TYPE_API = 'api';

	/**
	 * 
	 * @var Scalr_UI_Request
	 */
	private static $_instance = null;

	public static function getInstance()
	{
		if (self::$_instance === null)
			throw new Scalr_Exception_Core('Scalr_UI_Request not initialized');
			
		return self::$_instance;
	}
	
	public function __construct($type)
	{
		$this->requestType = $type;
	}
	
	public static function initializeInstance($type, $userId, $envId)
	{
		$instance = new Scalr_UI_Request($type);
		
		if ($userId) {
			try {
				$user = Scalr_Account_User::init();
				$user->loadById($userId);
			} catch (Exception $e) {
				throw new Exception('User account is no longer available.');
			}
	
			if ($user->status != Scalr_Account_User::STATUS_ACTIVE)
				throw new Exception('User account has been deactivated. Please contact your account owner.');
			
			if ($user->getType() != Scalr_Account_User::TYPE_SCALR_ADMIN) {
				try {
					if ($envId) {
						$environment = Scalr_Environment::init()->loadById($envId);

						if (! $user->getPermissions()->check($environment))
							$envId = 0;
					} else {
						$envId = (int) $user->getSetting(Scalr_Account_User::SETTING_DEFAULT_ENVIRONMENT);
						
						if ($envId) {
							$environment = Scalr_Environment::init()->loadById($envId);
							if (! $user->getPermissions()->check($environment))
								$envId = 0;
						}
					}
				} catch (Exception $e) {
					$envId = 0;
				}

				if (! $envId) {
					$envs = $user->getEnvironments();
					
					if (count($envs)) {
						$environment = Scalr_Environment::init()->loadById($envs[0]['id']);
					} else
						throw new Scalr_Exception_Core('You don\'t have access to any environment.');
				}
				
				$user->getPermissions()->validate($environment);
				$user->getPermissions()->setEnvironmentId($environment->id);
			}

			if ($user->getAccountId()) {
				if ($user->getAccount()->status == Scalr_Account::STATUS_INACIVE) {
					if ($user->getType() == Scalr_Account_User::TYPE_TEAM_USER)
						throw new Exception('Scalr account has been deactivated. Please contact scalr team.');
				} else if ($user->getAccount()->status == Scalr_Account::STATUS_SUSPENDED) {
					if ($user->getType() == Scalr_Account_User::TYPE_TEAM_USER)
						throw new Exception('Account was suspended. Please contact your account owner to solve this situation.');
				}
			}

			$instance->user = $user;
			$instance->environment = $environment;
		}
		
		self::$_instance = $instance;
		return $instance;
	}
	
	/**
	 * 
	 * @return Scalr_Account_User
	 */
	public function getUser()
	{
		return $this->user;
	}
	
	/**
	 * 
	 * @return Scalr_Environment
	 */
	public function getEnvironment()
	{
		return $this->environment;
	}
	
	public function getRequestType()
	{
		return $this->requestType;
	}

	public function defineParams($defs)
	{
		foreach ($defs as $key => $value) {
			if (is_array($value))
				$this->definitions[$key] = $value;

			if (is_string($value))
				$this->definitions[$value] = array();
		}

		$this->params = array();
	}

	public function getRequestParam($key)
	{
		$key = str_replace('.', '_', $key);

		if (isset($this->requestParams[$key]))
			return $this->requestParams[$key];
		else
			return NULL;
	}

	public function hasParam($key)
	{
		return isset($this->requestParams[$key]);
	}

	public function getRemoteAddr()
	{
		return $_SERVER['REMOTE_ADDR'];
	}

	public function setParam($key, $value)
	{
		$this->requestParams[$key] = $value;
		$this->params[$key] = $value;
	}
	
	public function setParams($params)
	{
		$this->requestParams = array_merge($this->requestParams, $params);
	}

	public function getParam($key)
	{
		if (isset($this->params[$key]))
			return $this->params[$key];

		if (isset($this->definitions[$key])) {
			$value = $this->getRequestParam($key);
			$rule = $this->definitions[$key];
			
			if ($value == NULL && isset($rule['default'])) {
				$value = $rule['default'];
			} else {
				switch ($rule['type']) {
					case 'integer': case 'int':
						$value = intval($value);
						break;
	
					case 'bool':
						$value = ($value == 'true' || $value == 'false') ? ($value == 'true' ? true : false) : (bool) $value;
						break;
	
					case 'json':
						$value = is_array($value) ? $value : json_decode($value, true);
						break;
	
					case 'array':
						settype($value, 'array');
						break;
	
					case 'string': default:
						$value = strval($value);
						break;
				}
			}

			$this->params[$key] = $value;
			return $value;
		}

		$this->params[$key] = $this->getRequestParam($key);
		return $this->params[$key];
	}
	
	public function getParams()
	{
		foreach ($this->definitions as $key => $value) {
			$this->getParam($key);
		}
		
		return $this->params;
	}
	
	/**
	 * 
	 * @return Scalr_UI_Request
	 */
	public function validate()
	{
		$this->paramErrors = array();
		$this->paramsIsValid = true;
		$validator = new Scalr_Validator();

		foreach ($this->definitions as $key => $value) {
			if (isset($value['validator'])) {
				$result = $validator->validate($this->getParam($key), $value['validator']);
				if ($result !== true)
					$this->addValidationErrors($key, $result);
			}
		}
		
		if (count($this->paramErrors))
			$this->paramsIsValid = false;
			
		return $this;
	}
	
	public function isValid()
	{
		return $this->paramsIsValid;
	}
	
	public function addValidationErrors($field, $errors)
	{
		$this->paramsIsValid = false;
		if (! isset($this->paramErrors[$field]))
			$this->paramErrors[$field] = array();

		$this->paramErrors[$field] = array_merge($this->paramErrors[$field], $errors);
	}
	
	public function getValidationErrors()
	{
		return array('errors' => $this->paramErrors);
	}
	
	public function getValidationErrorsMessage()
	{
		$message = '';
		foreach ($this->paramErrors as $key => $value) {
			$message .= "Field '{$key}' has following errors: <ul>";
			foreach ($value as $error)
				$message .= "<li>{$error}</li>";
			$message .= "</ul>";
		}
		
		return $message;
	}
}
