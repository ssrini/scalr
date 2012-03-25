<?php

	class Scalr_Session
	{
		private
			$clientId,
			$userId,
			$userGroup,
			$envId,
			$environment,
			$sessionId,
			$authToken,
			$sault,
			$hash,
			$hashpwd;

		private static $_session = null;

		const SESSION_CLIENT_ID = 'clientId';
		const SESSION_USER_ID ='userId';
		const SESSION_ENV_ID = 'envId';
		const SESSION_VARS = 'vars';
		const SESSION_USER_GROUP = 'userGroup';
		const SESSION_HASH = 'hash';
		const SESSION_SAULT = 'sault';

		public $watchList = array();
		public $watchEnable = false;
		public $watchClientId = 0;
		public $watchClientHash = '';

		/**
		 * @return Scalr_Session
		 */
		public static function getInstance()
		{
			
			if (self::$_session === null) {
				self::$_session = new Scalr_Session();
				self::$_session->sessionId = session_id();
				self::$_session->hashpwd = Scalr_Util_CryptoTool::hash(@file_get_contents(dirname(__FILE__)."/../etc/.cryptokey"));

				if (
					isset($_COOKIE['scalr_debug_enable']) &&
					($_COOKIE['scalr_debug_enable'] == '1') &&
					isset($_COOKIE['scalr_debug_client_id']) &&
					isset($_COOKIE['scalr_debug_client_hash']) &&
					in_array($_COOKIE['scalr_debug_client_id'], self::$_session->watchList)
				) {
					self::$_session->watchEnable = true;
					self::$_session->watchClientId = $_COOKIE['scalr_debug_client_id'];
					self::$_session->watchClientHash = $_COOKIE['scalr_debug_client_hash'];
				}
			}

			return self::$_session;
		}

		public static function create($clientId, $userId, $userGroup, $addToWatchList = true)
		{
			$_SESSION[__CLASS__][self::SESSION_CLIENT_ID] = $clientId;
			$_SESSION[__CLASS__][self::SESSION_USER_ID] = $userId;
			$_SESSION[__CLASS__][self::SESSION_USER_GROUP] = $userGroup;

			$sault = Scalr_Util_CryptoTool::sault();
			$_SESSION[__CLASS__][self::SESSION_SAULT] = $sault;
			$_SESSION[__CLASS__][self::SESSION_HASH] = self::createHash($clientId, $userId, $userGroup, $sault);

			if (in_array($clientId, self::getInstance()->watchList) && $addToWatchList) {
				// set cookie flag
				setcookie('scalr_debug_client_id', $clientId, time() + 86400 * 30, '/'); // 30 days
				setcookie('scalr_debug_client_hash', Scalr_Util_CryptoTool::sault(), time() + 86400 * 30, '/'); // 30 days
				setcookie('scalr_debug_enable', 1, time() + 86400 * 30, '/');
			}

			self::restore();
		}

		protected static function createHash($clientId, $userId, $userGroup, $sault, $withoutIP = false)
		{
			$db = Core::GetDBInstance();
			return Scalr_Util_CryptoTool::hash("{$clientId}:{$userId}:{$userGroup}:".self::getInstance()->hashpwd.":{$sault}");
		}

		public static function isCookieKeepSession()
		{
			$db = Core::GetDBInstance();

			// check for session restore
			if (isset($_COOKIE['scalr_user_id']) &&
				isset($_COOKIE['scalr_client_id']) &&
				isset($_COOKIE['scalr_user_group']) &&
				isset($_COOKIE['scalr_sault']) &&
				isset($_COOKIE['scalr_hash']) &&
				isset($_COOKIE['scalr_signature'])
			) {
				$signature = Scalr_Util_CryptoTool::hash("{$_COOKIE['scalr_sault']}:{$_COOKIE['scalr_hash']}:{$_COOKIE['scalr_user_id']}:{$_COOKIE['scalr_client_id']}:{$_COOKIE['scalr_user_group']}:".self::getInstance()->hashpwd);

				$hash = self::createHash($_COOKIE['scalr_client_id'], $_COOKIE['scalr_user_id'], $_COOKIE['scalr_user_group'], $_COOKIE['scalr_sault']);

				if ($signature == $_COOKIE['scalr_signature'] && $hash == $_COOKIE['scalr_hash']) {
					$_SESSION[__CLASS__][self::SESSION_CLIENT_ID] = $_COOKIE['scalr_client_id'];
					$_SESSION[__CLASS__][self::SESSION_USER_ID] = $_COOKIE['scalr_user_id'];
					$_SESSION[__CLASS__][self::SESSION_USER_GROUP] = $_COOKIE['scalr_user_group'];
					$_SESSION[__CLASS__][self::SESSION_SAULT] = $_COOKIE['scalr_sault'];
					$_SESSION[__CLASS__][self::SESSION_HASH] = $_COOKIE['scalr_hash'];

					return true;
				}
			}

			return false;
		}

		public static function restore($checkKeepSessionCookie = true)
		{
			$db = Core::GetDBInstance();
			$session = self::getInstance();
			$session->clientId = isset($_SESSION[__CLASS__][self::SESSION_CLIENT_ID]) ? $_SESSION[__CLASS__][self::SESSION_CLIENT_ID] : 0;
			$session->userId = isset($_SESSION[__CLASS__][self::SESSION_USER_ID]) ? $_SESSION[__CLASS__][self::SESSION_USER_ID] : 0;
			$session->userGroup = isset($_SESSION[__CLASS__][self::SESSION_USER_GROUP]) ? $_SESSION[__CLASS__][self::SESSION_USER_GROUP] : 0;

			$session->sault = isset($_SESSION[__CLASS__][self::SESSION_SAULT]) ? $_SESSION[__CLASS__][self::SESSION_SAULT] : '';
			$session->hash = isset($_SESSION[__CLASS__][self::SESSION_HASH]) ? $_SESSION[__CLASS__][self::SESSION_HASH] : '';

			if ($session->watchEnable) {
				Scalr_Session::logUiDebug('Scalr_Session::restore');
			}

			$newhash = self::createHash($session->clientId, $session->userId, $session->userGroup, $session->sault);
			if (! ($newhash == $session->hash && !empty($session->hash))) {
				// reset session (invalid)
				$session->clientId = 0;
				$session->userId = 0;
				$session->userGroup = 0;
				$session->hash = '';

				if ($checkKeepSessionCookie && self::isCookieKeepSession())
					self::restore(false);
			}

			if ($session->clientId) {
				$session->envId = isset($_SESSION[__CLASS__][self::SESSION_ENV_ID]) ?
					$_SESSION[__CLASS__][self::SESSION_ENV_ID] :
					Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadDefault($session->clientId)->id;
				$session->environment = Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadById($session->envId);
			}

			$session->authToken = new Scalr_AuthToken($session);
		}

		public static function logUiDebug($url, $includeText = null, $includeReports = array('headers', 'request', 'session', 'cookie'))
		{
			$db = Core::GetDBInstance();
			$session = Scalr_Session::getInstance();
			$report = array();

			if (in_array('headers', $includeReports))
				$report[] = '$headers = ' . print_r(apache_request_headers(), true);

			if (in_array('request', $includeReports))
				$report[] = '$request = ' . print_r($_REQUEST, true);

			if (in_array('cookie', $includeReports))
				$report[] = '$cookie = ' . print_r($_COOKIE, true);

			if (in_array('session', $includeReports))
				$report[] = '$session = ' . print_r($_SESSION, true);

			if (is_array($includeText))
				$report = array_merge($report, $includeText);
			else if ($includeText)
				$report[] = $includeText;

			$report = implode($report, "\n\n");

			$db->Execute("INSERT INTO ui_debug_log (ipaddress, dtadded, url, report, env_id, client_id, watch_client_id, watch_client_hash) VALUES(?, NOW(), ?, ?, ?, ?, ?, ?)", array(
				$_SERVER['REMOTE_ADDR'],
				$url,
				$report,
				$session->getEnvironment() ? $session->getEnvironment()->id: 0,
				$session->getClientId(),
				$session->watchClientId,
				$session->watchClientHash
			));
		}

		public static function destroy()
		{
			@session_start();
			@session_destroy();

			@setcookie("tender_email", "0", time()-86400, "/");
			@setcookie("tender_expires", "0", time()-86400, "/");
			@setcookie("tender_hash", "0", time()-86400, "/");
			@setcookie("tender_name", "0", time()-86400, "/");
			@setcookie("_tender_session", "0", time()-86400, "/");
			@setcookie("anon_token", "0", time()-86400, "/");

			setcookie("scalr_client_id", "0", time() - 86400, "/");
			setcookie("scalr_user_id", "0", time() - 86400, "/");
			setcookie("scalr_user_group", "0", time() - 86400, "/");
			setcookie("scalr_hash", "0", time() - 86400, "/");
			setcookie("scalr_sault", "0", time() - 86400, "/");
			setcookie("scalr_signature", "0", time() - 86400, "/");
		}

		public static function keepSession()
		{
			$session = self::getInstance();
			$db = Core::GetDBInstance();

			$tm = time() + 86400 * 30;

			setcookie('scalr_user_id', $session->userId, $tm, "/");
			setcookie('scalr_client_id', $session->clientId, $tm, "/");
			setcookie('scalr_user_group', $session->userGroup, $tm, "/");

			setcookie('scalr_sault', $session->sault, $tm, "/");
			setcookie('scalr_hash', $session->hash, $tm, "/");

			setcookie("scalr_signature", Scalr_Util_CryptoTool::hash("{$session->sault}:{$session->hash}:{$session->userId}:{$session->clientId}:{$session->userGroup}:".self::getInstance()->hashpwd), $tm, "/");
		}

		public function getVar($name, $default = null)
		{
			if (isset($_SESSION[__CLASS__][self::SESSION_VARS][$name]))
				return unserialize($_SESSION[__CLASS__][self::SESSION_VARS][$name]);
			else
				return $default;
		}

		public function setVar($name, $value)
		{
			$_SESSION[__CLASS__][self::SESSION_VARS][$name] = serialize($value);
		}

		public function  getAccountId()
		{
			return $this->clientId;
		}

		public function getClientId()
		{
			return $this->clientId;
		}

		public function getUserGroup()
		{
			return $this->userGroup;
		}

		public function getUserId()
		{
			return $this->userId;
		}

		public function isAuthenticated()
		{
			return $this->hash != '' ? true : false;
		}

		/**
		 * @return Scalr_AuthToken
		 */
		public function getAuthToken()
		{
			return $this->authToken;
		}

		public function setEnvironmentId($envId)
		{
			@session_start();
			$_SESSION[__CLASS__][self::SESSION_ENV_ID] = $envId;
		}
		
		public function getEnvironmentIdS()
		{
			return $_SESSION[__CLASS__][self::SESSION_ENV_ID];
		}

		/**
		 * @return Scalr_Environment
		 */
		public function getEnvironment()
		{
			return $this->environment;
		}

		/**
		 *
		 * @throws Exception
		 * @return integer
		 */
		public function getEnvironmentId()
		{
			if ($this->environment)
				return $this->environment->id;
			else
				throw new Exception("No environment defined for current session");
		}

		public function getSessionId()
		{
			return $this->sessionId;
		}

		public function logEvent($message) { }
	}
