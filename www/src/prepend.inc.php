<?
	@session_start();
	require_once (dirname(__FILE__)."/../../src/prepend.inc.php");

	Scalr_Session::restore();

	if (!defined('SCALR_NOT_CHECK_SESSION')) {
		if (/*isset($_SERVER['HTTP_X_AJAX_SCALR']) && 0 || 0 ||*/ !Scalr_Session::getInstance()->isAuthenticated()) {
			// @TODO: must be "!Scalr_Session::getInstance()->isAuthenticated()", this is for testing purpose
			Scalr_Session::destroy();

			if (isset($_SERVER['HTTP_X_AJAX_SCALR']))
				header("HTTP/1.0 403 Forbidden");
			else
				header('Location: https://scalr.net/login.html');

			exit();
		}
	}

	if ( !defined("NO_TEMPLATES")) { // @TODO: remove after clearing old ajax handlers
		if (Scalr_Session::getInstance()->getEnvironment()) {
			$env = array('list' => array(), 'current' => Scalr_Session::getInstance()->getEnvironment()->name);
			$current = Scalr_Session::getInstance()->getEnvironment()->id;
			foreach (Scalr_Session::getInstance()->getEnvironment()->loadByFilter(array('clientId' => Scalr_Session::getInstance()->getClientId())) as $value) {
				$env['list'][] = array('text' => $value['name'], 'envId' => $value['id'], 'checked' => ($value['id'] == $current) ? true : false, 'group' => 'env', 'style' => 'width: 124px');
			}

			$Smarty->assign('session_environments', json_encode($env));
		}
	}

	// All uncaught exceptions will raise ApplicationException
	function exception_handler($exception)
	{
		UI::DisplayException($exception);
	}
	set_exception_handler("exception_handler");


	Core::load("XMLNavigation", dirname(__FILE__)); // @TODO: delete xml menu, replace with new one
	define("NOW", str_replace("..","", substr(basename($_SERVER['PHP_SELF']),0, -4))); // @TODO: remove with old templates

	// title
	if (Scalr_Session::getInstance()->getClientId() != 0)
	{
		define("SCALR_SERVER_TZ", date_default_timezone_get());

		$tz = Scalr_Session::getInstance()->getEnvironment()->getPlatformConfigValue(ENVIRONMENT_SETTINGS::TIMEZONE);
		if ($tz)
			date_default_timezone_set($tz);
	}
