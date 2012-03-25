<?php

class Scalr_UI_Controller
{
	public $db;

	/**
	 * @var Scalr_UI_Request
	 */
	public $request;

	/**
	 * @var Scalr_UI_Response
	 */
	public $response;

	/**
	 * @var Scalr_Account_User
	 */
	public $user;

	/**
	 * @var Scalr_Account
	 */
	//public $account;

	/**
	 * @var Scalr_Environment
	 */
	protected $environment;
	
	/**
	 * @var string
	 */
	public $uiCacheKeyPattern;

	public function __construct()
	{
		$this->request = Scalr_UI_Request::getInstance();
		$this->response = Scalr_UI_Response::getInstance();
		$this->db = Core::getDBInstance();
		$this->user = $this->request->getUser();
		$this->environment = $this->request->getEnvironment();
		
		date_default_timezone_set(SCALR_SERVER_TZ);
	}
	
	public function init()
	{
		
	}

	/**
	 * @return Scalr_Util_CryptoTool
	 */
	protected function getCrypto()
	{
		if (! $this->crypto) {
			$this->crypto = new Scalr_Util_CryptoTool(MCRYPT_TRIPLEDES, MCRYPT_MODE_CFB, 24, 8);
			$this->cryptoKey = @file_get_contents(dirname(__FILE__)."/../../../etc/.cryptokey");
		}

		return $this->crypto;
	}
	
	public function getEnvironmentId()
	{
		if ($this->environment)
			return $this->environment->id;
		else
			throw new Scalr_Exception_Core("No environment defined for current session");
	}

	public function getEnvironment()
	{
		return $this->environment;
	}

	public function getParam($key)
	{
		return $this->request->getParam($key);
	}

	public function hasAccess()
	{
		if ($this->user) {
			// check admin, non-admin
			if ($this->user->getType() != Scalr_Account_User::TYPE_SCALR_ADMIN) {
				// check controller in permissions
				return true;
			} else
				return false;
		} else
			return false;
	}

	public function getModuleName($name, $onlyPath = false)
	{
		$fl = APPPATH . "/www/ui/js/{$name}";
		if (file_exists($fl))
			$tm = filemtime(APPPATH . "/www/ui/js/{$name}");
		else
			throw new Exception(sprintf('Js file not found'));

		$nameTm = str_replace('.js', "-{$tm}.js", $name);
		$nameTm = str_replace('.css', "-{$tm}.css", $nameTm);

		return "/ui/js/{$nameTm}";
	}

	protected $sortParams = array();
	protected function sort($item1, $item2)
	{
		foreach ($this->sortParams as $cond) {
			$field = $cond['property'];
			$result = strcmp($item1[$field], $item2[$field]);

			if ($result != 0)
				return $cond['direction'] == 'DESC' ? $result : ($result > 0 ? -1: 1);
		}

		return 0;
	}

	protected function buildResponseFromData(array $data, $filterFields = array())
	{
		$this->request->defineParams(array(
			'start' => array('type' => 'int', 'default' => 0),
			'limit' => array('type' => 'int', 'default' => 20)
		));

		if ($this->getParam('query') && count($filterFields) > 0) {
			foreach ($data as $k=>$v) {
				$found = false;
				foreach ($filterFields as $field)
				{
					if (stristr($v[$field], $this->getParam('query'))) {
						$found = true;
						break;
					}
				}

				if (!$found)
					unset($data[$k]);
			}
		}

		$response['total'] = count($data);

		$s = $this->getParam('sort');
		if (! is_array($s)) {
			$s = json_decode($this->getParam('sort'), true);
		}

		if (is_array($s)) {
			$sorts = array();
			if (count($s) && !is_array($s[0]))
				$s = array($s);

			foreach ($s as $param) {
				$sort = preg_replace("/[^A-Za-z0-9_]+/", "", $param['property']);
				$dir = (in_array(strtolower($param['direction']), array('asc', 'desc'))) ? $param['direction'] : 'ASC';

				$sortParams[] = array('property' => $sort, 'direction' => $dir);
			}
		} else if ($this->getParam('sort')) {
			$sort = preg_replace("/[^A-Za-z0-9_]+/", "", $this->getParam('sort'));
			$dir = (in_array(strtolower($this->getParam('dir')), array('asc', 'desc'))) ? $this->getParam('dir') : 'ASC';

			$sortParams[] = array('property' => $sort, 'direction' => $dir);
		}

		if (count($sortParams)) {
			$this->sortParams = $sortParams;
			usort($data, array($this, 'sort'));
		}

		$data = (count($data) > $this->getParam('limit')) ? array_slice($data, $this->getParam('start'), $this->getParam('limit')) : $data;

		$response["success"] = true;
		$response['data'] = array_values($data);

		return $response;
	}

	protected function buildResponseFromSql2($sql, $sortFields = array(), $filterFields = array(), $args = array(), $noLimit = false)
	{
		if ($this->getParam('query') && count($filterFields) > 0) {
			$filter = $this->db->qstr('%' . trim($this->getParam('query')) . '%');
			foreach($filterFields as $field) {
				$fs = explode('.', $field);
				foreach($fs as &$f) {
					$f = "`{$f}`";
				}
				$field = implode('.', $fs);
				$likes[] = "{$field} LIKE {$filter}";
			}
			$sql = str_replace(':FILTER:', '(' . implode(' OR ', $likes) . ')', $sql);
		} else {
			$sql = str_replace(':FILTER:', 'true', $sql);
		}

		if (! $noLimit) {
			$response['total'] = $this->db->Execute($sql, $args)->RecordCount();
		}

		if (is_array($this->getParam('sort'))) {
			$sort = $this->getParam('sort');
			$sortSql = array();
			if (count($sort) && !is_array($sort[0]))
				$sort = array($sort);

			foreach ($sort as $param) {
				$property = preg_replace('/[^A-Za-z0-9_]+/', '', $param['property']);
				$direction = (in_array(strtolower($param['direction']), array('asc', 'desc'))) ? $param['direction'] : 'asc';

				if (in_array($property, $sortFields))
					$sortSql[] = "`{$property}` {$direction}";
			}

			if (count($sortSql))
				$sql .= ' ORDER BY ' . implode($sortSql, ',');
		}

		if (! $noLimit) {
			$start = $this->getParam('start');
			$limit = $this->getParam('limit');
			$sql .= " LIMIT $start, $limit";
		}

		$response['success'] = true;
		$response['data'] = $this->db->GetAll($sql, $args);

		return $response;
	}

	protected function buildResponseFromSql($sql, $filterFields = array(), $groupSQL = "", $simpleQuery = true, $noLimit = false)
	{
		$this->request->defineParams(array(
			'start' => array('type' => 'int', 'default' => 0),
			'limit' => array('type' => 'int', 'default' => 20)
		));

		if (is_array($groupSQL)) {
			return $this->buildResponseFromSql2($sql, $filterFields, $groupSQL, is_array($simpleQuery) ? $simpleQuery : array(), $noLimit);
		}

		if ($this->getParam('query') && count($filterFields) > 0) {
			$filter = $this->db->qstr('%' . trim($this->getParam('query')) . '%');
			foreach($filterFields as $field) {
				if ($simpleQuery)
					$likes[] = "`{$field}` LIKE {$filter}";
				else
					$likes[] = "{$field} LIKE {$filter}";
			}
			$sql .= " AND (";
			$sql .= implode(" OR ", $likes);
			$sql .= ")";
		}

		if ($groupSQL)
			$sql .= "{$groupSQL}";

		if (! $noLimit) {
			if (stristr($sql, "SELECT * FROM")) {
				$response["total"] = $this->db->GetOne(str_replace("SELECT * FROM", "SELECT COUNT(*) FROM", $sql), $args);
			} else
				$response["total"] = $this->db->Execute($sql, $args)->RecordCount();
		}

		// @TODO replace with simple code (legacy code)
		$s = $this->getParam('sort');
		if (! is_array($s)) {
			$s = json_decode($this->getParam('sort'), true);
		}
		
		if (is_array($s)) {
			$sorts = array();
			if (count($s) && !is_array($s[0]))
				$s = array($s);

			foreach ($s as $param) {
				$sort = preg_replace("/[^A-Za-z0-9_]+/", "", $param['property']);
				$dir = (in_array(strtolower($param['direction']), array('asc', 'desc'))) ? $param['direction'] : 'ASC';
				
				$sorts[] = "`{$sort}` {$dir}";
			}
			
			$sql .= " ORDER BY " . implode($sorts, ',');
		} else if ($this->getParam('sort')) {
			$sort = preg_replace("/[^A-Za-z0-9_]+/", "", $this->getParam('sort'));
			$dir = (in_array(strtolower($this->getParam('dir')), array('asc', 'desc'))) ? $this->getParam('dir') : 'ASC';
			$sql .= " ORDER BY `{$sort}` {$dir}";
		}

		if (! $noLimit) {
			$start = $this->getParam('start');
			$limit = $this->getParam('limit');
			$sql .= " LIMIT $start, $limit";
		}

		$response["success"] = true;
		$response["data"] = $this->db->GetAll($sql);

		return $response;
	}

	public function call($pathChunks, $permissionFlag = true)
	{
		$arg = array_shift($pathChunks);
		
		if ($this->user) {
			if ($this->user->getType() == Scalr_Account_User::TYPE_TEAM_USER) {
				if (!$this->user->isTeamUserInEnvironment($this->getEnvironmentId(), Scalr_Account_Team::PERMISSIONS_OWNER) &&
					!$this->user->isTeamUserInEnvironment($this->getEnvironmentId(), Scalr_Account_Team::PERMISSIONS_FULL)
				) {
					if (method_exists($this, 'getPermissionDefinitions')) {
						// rules defined for this controller
						$cls = get_class($this);
						$clsShort = str_replace('Scalr_UI_Controller_', '', $cls);
						$methodShort = str_replace('Action', '', $method);
						$clsPermissions = $cls::getPermissionDefinitions();
						
						$permissions = $this->user->getGroupPermissions($this->getEnvironmentId());
						if (array_key_exists($clsShort, $permissions)) {
							$perm = $permissions[$clsShort];
							
							if (in_array('VIEW', $perm, true) || in_array('FULL', $perm, true))
								$permissionFlag = true;
							else
								$permissionFlag = false;
						} else
							$permissionFlag = false;
					}
				}
			}
		}

		try {
			$subController = self::loadController($arg, get_class($this), true);
		} catch (Scalr_UI_Exception_NotFound $e) {
			$subController = null;
		}

		if ($subController) {
			$this->addUiCacheKeyPatternChunk($arg);
			$subController->uiCacheKeyPattern = $this->uiCacheKeyPattern;
			$subController->call($pathChunks, $permissionFlag);

		} else if (($action = $arg . 'Action') && method_exists($this, $action)) {
			$this->addUiCacheKeyPatternChunk($arg);
			$this->response->setHeader('X-Scalr-Cache-Id', $this->uiCacheKeyPattern);

			if (! $permissionFlag)
				throw new Scalr_Exception_InsufficientPermissions();

			$this->callActionMethod($action);

		} else if (count($pathChunks) > 0) {
			$const = constant(get_class($this) . '::CALL_PARAM_NAME');
			if ($const) {
				$this->request->setParams(array($const => $arg));
				$this->addUiCacheKeyPatternChunk('{' . $const . '}');
			} else {
				// TODO notice
			}

			$this->call($pathChunks, $permissionFlag);

		} else if (method_exists($this, 'defaultAction') && $arg == '') {
			$this->response->setHeader('X-Scalr-Cache-Id', $this->uiCacheKeyPattern);
			
			if (! $permissionFlag)
				throw new Scalr_Exception_InsufficientPermissions();
			
			$this->callActionMethod('defaultAction');

		} else {
			throw new Scalr_UI_Exception_NotFound();
		}
	}
	
	public function callActionMethod($method)
	{
		if ($this->request->getRequestType() == Scalr_UI_Request::REQUEST_TYPE_API) {
			$apiMethodCheck = false;
			if (method_exists($this, 'getApiDefinitions')) {
				$api = $this::getApiDefinitions();
				$m = str_replace('Action', '', $method);
				if (in_array($m, $api)) {
					$apiMethodCheck = true;
				}
			}

			if (! $apiMethodCheck)
				throw new Scalr_UI_Exception_NotFound();
		}
		
		if ($this->user) {
			if ($this->user->getType() == Scalr_Account_User::TYPE_TEAM_USER) {
				if (!$this->user->isTeamUserInEnvironment($this->getEnvironmentId(), Scalr_Account_Team::PERMISSIONS_OWNER) &&
					!$this->user->isTeamUserInEnvironment($this->getEnvironmentId(), Scalr_Account_Team::PERMISSIONS_FULL)
				) {
					if (method_exists($this, 'getPermissionDefinitions')) {
						// rules defined for this controller
						$cls = get_class($this);
						$clsShort = str_replace('Scalr_UI_Controller_', '', $cls);
						$methodShort = str_replace('Action', '', $method);
						$clsPermissions = $cls::getPermissionDefinitions();
						
						$permissions = $this->user->getGroupPermissions($this->getEnvironmentId());
						if (array_key_exists($clsShort, $permissions)) {
							// rules for user and such controller
							$perm = $permissions[$clsShort];
							
							if (! in_array('FULL', $perm, true)) {
								// user doesn't has full privilegies
								if (array_key_exists($methodShort, $clsPermissions)) {
									// standalone rule for this method
									if (! in_array($clsPermissions[$methodShort], $perm))
										throw new Scalr_Exception_InsufficientPermissions();
								} else {
									// VIEW rule
									if (! in_array('VIEW', $perm))
										throw new Scalr_Exception_InsufficientPermissions();
								}
							}
		
						} else
							throw new Scalr_Exception_InsufficientPermissions();
							
					}
				}
			}
		}
			
		$this->{$method}();
	}

	public function addUiCacheKeyPatternChunk($chunk)
	{
		$this->uiCacheKeyPattern .= "/{$chunk}";
	}

	static public function handleRequest($pathChunks, $params)
	{
		if ($pathChunks[0] == '')
			$pathChunks = array('guest');

		try {
			Scalr_UI_Request::getInstance()->setParams($params);
			$controller = self::loadController(array_shift($pathChunks), 'Scalr_UI_Controller', true);

			if (! Scalr_UI_Request::getInstance()->getUser() && get_class($controller) != 'Scalr_UI_Controller_Guest') {
				throw new Scalr_UI_Exception_AccessDenied();
			} else {
				$controller->uiCacheKeyPattern = '';
				$user = Scalr_UI_Request::getInstance()->getUser();

				if ($user &&
					$user->getAccountId() &&
					$user->getAccount()->status != Scalr_Account::STATUS_ACTIVE &&
					$user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER &&
					get_class($controller) != 'Scalr_UI_Controller_Billing' &&
					get_class($controller) != 'Scalr_UI_Controller_Guest'
				) {
					// suspended account, user = owner, replace controller with billing or allow billing action/guest action
					$controller = self::loadController('Billing', 'Scalr_UI_Controller', true);
					$controller->addUiCacheKeyPatternChunk(strtolower((array_pop(explode('_', get_class($controller))))));
					$controller->call();
				} else {
					$controller->addUiCacheKeyPatternChunk(strtolower((array_pop(explode('_', get_class($controller))))));
					$controller->call($pathChunks);
				}
			}

		} catch (Scalr_UI_Exception_AccessDenied $e) {
			Scalr_UI_Response::getInstance()->setHttpResponseCode(403);

		} catch (Scalr_Exception_InsufficientPermissions $e) {
			Scalr_UI_Response::getInstance()->failure($e->getMessage());

		} catch (Scalr_UI_Exception_NotFound $e) {
			Scalr_UI_Response::getInstance()->setHttpResponseCode(404);

		} catch (Exception $e) {
			Scalr_UI_Response::getInstance()->failure($e->getMessage());
		}

		Scalr_UI_Response::getInstance()->sendResponse();
	}

	/**
	 * 
	 * @return Scalr_UI_Controller
	 */
	static public function loadController($controller, $prefix = 'Scalr_UI_Controller', $checkPermissions = false)
	{
		if (preg_match("/^[a-z0-9]+$/i", $controller)) {
			$controller = ucwords(strtolower($controller));
			$className = "{$prefix}_{$controller}";
			if (file_exists(SRCPATH . '/' . str_replace('_', '/', $prefix) . '/' . $controller . '.php') && class_exists($className)) {
				$o = new $className();
				$o->init();
				if (!$checkPermissions || $o->hasAccess())
					return $o;
				else
					throw new Scalr_Exception_InsufficientPermissions();
			}
		}

		throw new Scalr_UI_Exception_NotFound($className);
	}
}
