<?
	define("TRANSACTION_ID", uniqid("tran"));
	define("DEFAULT_LOCALE", "en_US");

	@date_default_timezone_set(@date_default_timezone_get());

	// 	Attempt to normalize settings
	@error_reporting(E_ALL ^E_NOTICE ^E_USER_NOTICE ^E_DEPRECATED);
	@ini_set('magic_quotes_runtime', '0');
	@ini_set('magic_quotes_gpc', '0');
	@ini_set('variables_order', 'GPCS');
	@ini_set('gpc_order', 'GPC');

	@ini_set('session.bug_compat_42', '0');
	@ini_set('session.bug_compat_warn', '0');

	// Increase execution time limit
	set_time_limit(180);

	// A kind of sanitization :-/
	if (get_magic_quotes_gpc())
	{
		function mstripslashes(&$item, $key)
		{
			$item = stripslashes($item);
		}

		array_walk_recursive($_POST, "mstripslashes");
		array_walk_recursive($_GET, "mstripslashes");
		array_walk_recursive($_REQUEST, "mstripslashes");
	}

	//
	// Locale init
	//
	$locale = DEFAULT_LOCALE;
	define("LOCALE", $locale);

	// Globalize
	@extract($_GET, EXTR_PREFIX_ALL, "get");
	@extract($_POST, EXTR_PREFIX_ALL, "post");
	@extract($_SESSION, EXTR_PREFIX_ALL, "sess");
	@extract($_REQUEST, EXTR_PREFIX_ALL, "req");

	// Environment stuff
	$base = dirname(__FILE__);
	define("SRCPATH", $base);
	define("APPPATH", "{$base}/..");
	define("CACHEPATH", "$base/../cache");
	define("SCALR_VERSION", @file_get_contents(APPPATH."/etc/version"));

	$ADODB_CACHE_DIR = CACHEPATH."/adodb";

	define("CF_TEMPLATES_PATH", APPPATH."/templates/".LOCALE);
	define("CF_SMARTYBIN_PATH", CACHEPATH."/smarty_bin/".LOCALE);
	define("CF_SMARTYCACHE_PATH", CACHEPATH."/smarty/".LOCALE);

	// Require autoload definition
	$classpath[] = dirname(__FILE__);
	$classpath[] = dirname(__FILE__) . "/externals/ZF-1.10.8";
	set_include_path(get_include_path() . PATH_SEPARATOR . join(PATH_SEPARATOR, $classpath));
	
	require_once (SRCPATH."/autoload.inc.php");

	spl_autoload_register("__autoload");
	
	// Define log4php contants
	define("LOG4PHP_DIR", SRCPATH.'/externals/apache-log4php-2.0.0-incubating/src/main/php');
	require_once LOG4PHP_DIR . '/Logger.php';
	
	// require sanitizer
	require_once(SRCPATH."/exceptions/class.ApplicationException.php");
	require_once(SRCPATH."/class.UI.php");
	require_once(SRCPATH."/class.Debug.php");
	require_once(SRCPATH."/class.TaskQueue.php");
	require_once(SRCPATH."/class.FarmTerminationOptions.php");

	require_once(SRCPATH."/class.DataForm.php");
	require_once(SRCPATH."/class.DataFormField.php");

	require_once(SRCPATH."/queue_tasks/abstract.Task.php");

	require_once(SRCPATH."/queue_tasks/class.FireDeferredEventTask.php");

	////////////////////////////////////////
	// LibWebta		                      //
	////////////////////////////////////////
	require(SRCPATH."/LibWebta/prepend.inc.php");

	Core::Load("Security/Crypto");
	Core::Load("NET/Mail/PHPMailer");
	Core::Load("NET/Mail/PHPSmartyMailer");
	Core::Load("Data/Formater/class.Formater.php");
	Core::Load("Data/Validation/class.Validator.php");
	Core::Load("System/Independent/Shell/class.ShellFactory.php");
	Core::Load("NET/API/AWS/AmazonEC2");
	Core::Load("NET/API/AWS/AmazonS3");
	Core::Load("NET/API/AWS/AmazonSQS");
	Core::Load("NET/API/AWS/AmazonCloudFront");
	Core::Load("NET/API/AWS/AmazonELB");
	Core::Load("NET/API/AWS/AmazonRDS");
	Core::Load("NET/API/AWS/AmazonCloudWatch");
	Core::Load("NET/API/AWS/AmazonVPC");

	require_once(SRCPATH . '/externals/adodb5/adodb-exceptions.inc.php');
	require_once(SRCPATH . '/externals/adodb5/adodb.inc.php');

	require_once(SRCPATH . '/externals/Smarty-2.6.26/libs/Smarty.class.php');
	require_once(SRCPATH . '/externals/Smarty-2.6.26/libs/Smarty_Compiler.class.php');

	$cfg = @parse_ini_file(APPPATH."/etc/config.ini", true);
	if (!count($cfg)) {
		die(_("Cannot parse config.ini file"));
	};

	// ADODB init
	for ($i = 1; $i <= 3; $i++) {
		try	{
			$db = Core::GetDBInstance($cfg["db"]);
			if ($db)
				break;
		}
		catch(Exception $e){}
	}
	
	if (!$db) {
		@mail("igor@scalr.com", "DB Connect issues", $e->getMessage());
			
		throw new Exception("Service is temporary not available. Please try again in a minute.");
		//TODO: Notify about this.
	}

	$ADODB_CACHE_DIR = CACHEPATH."/adodb";

	// Select config from db
	foreach ($db->GetAll("SELECT * FROM config") as $rsk)
		$cfg[$rsk["key"]] = $rsk["value"];


	$ConfigReflection = new ReflectionClass("CONFIG");

	// Define Constants and paste config into CONFIG struct
	foreach ($cfg as $k=>$v)
	{
		if (is_array($v))
			foreach ($v as $kk=>$vv)
			{
				$key = strtoupper("{$k}_{$kk}");

				if ($ConfigReflection->hasProperty($key))
					CONFIG::$$key = $vv;

				define("CF_{$key}", $vv);
			}
		else
		{
			if (is_array($k))
				$nk = strtoupper("{$k[0]}_{$k[1]}");
			else
				$nk = strtoupper("{$k}");

			if ($ConfigReflection->hasProperty($nk))
				CONFIG::$$nk = $v;

			define("CF_{$nk}", $v);
		}
	}

	unset($cfg);

	require_once (SRCPATH.'/class.LoggerAppenderScalr.php');
	require_once (SRCPATH.'/class.LoggerPatternLayoutScalr.php');
	require_once (SRCPATH.'/class.FarmLogMessage.php');
	require_once (SRCPATH.'/class.ScriptingLogMessage.php');
	require_once (SRCPATH.'/class.LoggerPatternParserScalr.php');
	require_once (SRCPATH.'/class.LoggerBasicPatternConverterScalr.php');
	require_once (SRCPATH.'/class.LoggerFilterCategoryMatch.php');

	Logger::configure(APPPATH.'/etc/log4php.xml', 'LoggerConfiguratorXml');
	$Logger = Logger::getLogger('Application');


	// Define json_encode function if extension not installed
	if (!function_exists("json_encode"))
	{
		Core::Load("Data/JSON/JSON.php");
		$json = new Services_JSON(SERVICES_JSON_LOOSE_TYPE);
		function json_encode($text)
		{
			global $json;
			return $json->encode($text);
		}

		function json_decode($text, $assoc = true)
		{
			global $json;
			return $json->decode($text);
		}
	}

	// Smarty init
	if (!defined("NO_TEMPLATES"))
	{
		$Smarty = Core::GetSmartyInstance();
		// Cache control
		if (CONFIG::$DEBUG_APP)
		{
			$Smarty->clear_all_cache();
			$Smarty->caching = false;
		}
		else
			$Smarty->caching = false;

		$Smarty->register_function('get_static_url', 'get_static_url');
		function get_static_url($params, &$smarty)
		{
			return "{$params['path']}";
		}
	}

	// PHPSmartyMailer init
	$Mailer = Core::GetPHPSmartyMailerInstance();
	$Mailer->From 		= CONFIG::$EMAIL_ADDRESS;
	$Mailer->FromName 	= CONFIG::$EMAIL_NAME;
	$Mailer->CharSet 	= "UTF-8";

	//TODO: Move all timeouts to config UI.

    CONFIG::$HTTP_PROTO = (CONFIG::$HTTP_PROTO) ? CONFIG::$HTTP_PROTO : "http";

    // cache lifetime
    CONFIG::$EVENTS_RSS_CACHE_LIFETIME = 300; // in seconds
    CONFIG::$EVENTS_TIMELINE_CACHE_LIFETIME = 300; // in seconds
    CONFIG::$AJAX_PROCESSLIST_CACHE_LIFETIME = 120; // in seconds
    CONFIG::$SYSDNS_SYSTEM = 0;

    // Require observer interfaces
    require_once (APPPATH.'/observers/interface.IDeferredEventObserver.php');
    require_once (APPPATH.'/observers/interface.IEventObserver.php');

    require_once (SRCPATH.'/class.Scalr.php');

?>
