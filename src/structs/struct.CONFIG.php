<?		
	final class CONFIG
	{
		public static $DB_DRIVER;
		public static $DB_HOST;
		public static $DB_NAME;
		public static $DB_USER;
		public static $DB_PASS;
		
		public static $DEBUG_PROFILING;
		public static $DEBUG_APP;
		public static $DEBUG_LEVEL;
		public static $DEBUG_DB;


		/**
		 * Encrypted registrar CP password
		 *
		 * @staticvar string
		 */
		public static $ADMIN_PASSWORD;
		
		public static $ADMIN_LOGIN;
		
		public static $CRYPTOKEY;

		public static $CRYPTO_ALGO;
		
		public static $PAGING_ITEMS;
		
		public static $EMAIL_ADDRESS;
		
		public static $EMAIL_NAME;
		public static $TEAM_EMAILS;
		
		public static $AWS_ACCOUNTID;
		
		public static $AWS_KEYNAME;
		public static $AWS_ACCESSKEY;
		public static $AWS_ACCESSKEY_ID;
		
		/******* DNS ****************/
		
		public static $DEF_SOA_OWNER;
		public static $DEF_SOA_TTL = 14400;
		public static $DEF_SOA_REFRESH = 14400;
		public static $DEF_SOA_RETRY = 7200;
		public static $DEF_SOA_EXPIRE = 3600000;
		public static $DEF_SOA_MINTTL = 300;
		public static $DEF_SOA_PARENT;

		public static $DNS_TEST_DOMAIN_NAME = 'example.com';
		public static $SYSDNS_SYSTEM = 0;
		
		/*******************************/
		
		public static $EVENTHANDLER_URL;
		
		public static $SECGROUP_PREFIX;
		
		public static $EMAIL_DSN;
		
		public static $REBOOT_TIMEOUT;
				
		public static $NAMEDCONFTPL;
		
		public static $DYNAMIC_A_REC_TTL;
		
		public static $S3CFG_TEMPLATE;
		
		public static $SNMPTRAP_PATH;
		
		public static $LOG_DAYS = 10;
		
		public static $LAUNCH_TIMEOUT;
		
		public static $CLIENT_MAX_INSTANCES = 20;
		public static $CLIENT_MAX_EIPS = 5;
		
		
		public static $PRICE;
		public static $PAYPAL_BUSINESS;
		public static $PAYPAL_RECEIVER;
		public static $PAYPAL_ISDEMO;
		public static $PAYMENT_TERM;
		public static $PAYMENT_DESCRIPTION;
		
		/**
		 * Cache lifetimes
		 */
		public static $EVENTS_RSS_CACHE_LIFETIME;
		public static $EVENTS_TIMELINE_CACHE_LIFETIME;
		public static $AJAX_PROCESSLIST_CACHE_LIFETIME;
		
		public static $HTTP_PROTO = "http";
		
		//**** Statistics and monitoring *******//
		public static $RRDTOOL_PATH;
		public static $RRD_DEFAULT_FONT_PATH;
		public static $RRD_DB_DIR;
		public static $RRD_STATS_URL;
		public static $RRD_GRAPH_STORAGE_TYPE;
		public static $RRD_GRAPH_STORAGE_PATH;
		
		public static $MONITORING_TYPE = MONITORING_TYPE::REMOTE;
		public static $MONITORING_SERVER_URL = '';
		public static $MONITORING_GRAPHS_URL = '';
		
		
		
		
		public static $APACHE_DOCROOT_DIR;
		public static $APACHE_LOGS_DIR;
		
		public static $CRON_PROCESSES_NUMBER = 5;
		
		public static $MYSQL_STAT_USERNAME = "scalr_stat";
		public static $MYSQL_STAT_SEC_GROUP = "scalr.mysql-stat";
		
		public static $APP_SYS_IPADDRESS;
				
		public static $SYNC_TIMEOUT = 300; // Minutes
		
		public static $SYNCHRONOUS_SCRIPT_TIMEOUT = 180; // seconds
		public static $ASYNCHRONOUS_SCRIPT_TIMEOUT = 1200; // seconds
		
		private static $SCRIPT_BUILTIN_VARIABLES_LOADED = false;
		private static $SCRIPT_BUILTIN_VARIABLES = array( 
			"image_id" 		=> 1,
			"role_name" 	=> 1, 
			"isdbmaster" 	=> 1,
			"farm_id"		=> 1,
			"farm_name"		=> 1,
			"behaviors"		=> 1,
			"server_id"		=> 1,
		
			//TODO: Remove this vars
			"ami_id" 		=> 1,
			"instance_index"=> 1, 
			"region" 		=> 1, 
			"avail_zone" 	=> 1,
			"external_ip" 	=> 1, 
			"internal_ip" 	=> 1, 
			"instance_id" 	=> 1
		);
		
		public static function getScriptingBuiltinVariables()
		{
			foreach (self::$SCRIPT_BUILTIN_VARIABLES as $k=>$v)
				self::$SCRIPT_BUILTIN_VARIABLES["event_{$k}"] = $v;
			
			if (!self::$SCRIPT_BUILTIN_VARIABLES_LOADED)
			{
				$ReflectEVENT_TYPE = new ReflectionClass("EVENT_TYPE");
			    $event_types = $ReflectEVENT_TYPE->getConstants();
			    foreach ($event_types as $event_type)
			    {
			    	if (class_exists("{$event_type}Event"))
			    	{
			    		$ReflectClass = new ReflectionClass("{$event_type}Event");
			    		$retval = $ReflectClass->getMethod("GetScriptingVars")->invoke(null);
			    		if (!empty($retval))
			    		{
			    			foreach ($retval as $k=>$v)
			    			{
			    				if (!CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k])
			    				{
				    				CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k] = array(
				    					"PropName"	=> $v,
				    					"EventName" => "{$event_type}"
				    				);
			    				}
			    				else
			    				{
			    					if (!is_array(CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k]['EventName']))
			    						$events = array(CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k]['EventName']);
			    					else
			    						$events = CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k]['EventName'];
			
			    					$events[] = $event_type;
			
			    					CONFIG::$SCRIPT_BUILTIN_VARIABLES[$k] = array(
				    					"PropName"	=> $v,
				    					"EventName" => $events
				    				);
			    				}
			    			}
			    		}
			    	}
			    }
			    
			    CONFIG::$SCRIPT_BUILTIN_VARIABLES_LOADED = true;
			}
			
			return CONFIG::$SCRIPT_BUILTIN_VARIABLES;
		}
		
		public static $PMA_INSTANCE_IP_ADDRESS = '';
		
		/**
		 * List all available properties through reflection
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @return Array or names
		 */
		public static function GetKeys()
		{ 
			$retval = array();
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			foreach($ReflectionClassThis->getStaticProperties() as $Property)
			{
				$retval[] = $Property->name;
			}
			return($retval);
		}
		
		/**
		 * Get all values
		 * FIXME: Move to superclass, when php will have late static binding
		 *
		 * @param  $key Key name
		 * @return array Array or values
		 */
		public static function GetValues($key)
		{
			return get_class_vars(__CLASS__);
		}
		
		/**
		 * Get value of property by it's name
		 * FIXME: Move to parent class Struct, when php will have late static binding
		 *
		 * @param  $key Key name
		 * @return string
		 */
		public static function GetValue($key)
		{
			//property_exists
			$ReflectionClassThis = new ReflectionClass(__CLASS__);
			if ($ReflectionClassThis->hasProperty($key))
			{
				return $ReflectionClassThis->getStaticPropertyValue($key);
			}
			else 
			{
				throw new Exception(sprintf(_("Called %s::GetValue('{$key}') for non-existent property {$key}"), __CLASS__));
			}
		}
	}
	
?>
