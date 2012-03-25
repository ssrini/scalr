<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
	 * This source file is subject to version 2 of the GPL license,
	 * that is bundled with this package in the file license.txt and is
	 * available through the world-wide-web at the following url:
	 * http://www.gnu.org/copyleft/gpl.html
     *
     * @category   LibWebta
     * @package    Core
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */ 
    
    /**
     * 
     */
    define("LIBWEBTA_BASE", dirname(__FILE__));	
	define("LIB_BASE", dirname(__FILE__)."/../../Lib");
	
	define("TEMPLATES_PATH", LIBWEBTA_BASE."/../../../templates");
	define("SMARTYBIN_PATH", LIBWEBTA_BASE."/../../../cache/smarty_bin");
	define("SMARTYCACHE_PATH", LIBWEBTA_BASE."/../../../cache/smarty");
	
	define("LIBWEBTA_DB_SERVER_MASTER", "master");
	define("LIBWEBTA_DB_SERVER_SLAVE", "slave");
	
	/**
     * @name Core
     * @category   LibWebta
     * @package    Core
     * @abstract 
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */	
	abstract class Core
	{
		/**
		 * ADODB Instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $DB = array();
		
		/**
		 * ADODB instances for multiprocess scripts
		 */
		private static $DB_THREADS = array();
		
		/**
		 * PDO Instances
		 * 
		 * @var array
		 * @access private
		 * @static 
		 */
		private static $PDO = array();
		
		/**
		 * Shell instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $Shell;
		
		/**
		 * Smarty instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $Smarty;
		
		/**
		 * Validator instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $Validator;
		
		/**
		 * PHPMAiler instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
		private static $PHPMailer;
		
		/**
		 * PHPSmartyMailer instance
		 *
		 * @var object
		 * @access private
		 * @static 
		 */
  		private static $PHPSmartyMailer;
		
		/**
		 * Debug level
		 */
		const DEBUG_LEVEL = 0; // Lowest debug level by default
		
		
		/**
		* Debug Level.
		* @var $DebugLevel
		* @access protected
		* @see RaiseWarning RaiseError
		*/
		protected static $DebugLevel;
		
		/**
		 * Exception class name
		 *
		 * @var string
		 */
		public static $ExceptionClassName = "ApplicationException"; 
		
		/**
		 * Reflection Exception Class
		 *
		 * @var ReflectionClass
		 */
		public static $ReflectionException;
		
		/**
		* Constructor
		* @access public
		* @return void
		* @ignore 
		*/
		function __construct()
		{
			self::$DebugLevel = defined("CF_DEBUG_LEVEL") ? CF_DEBUG_LEVEL : self::DEBUG_LEVEL;
			self::$ReflectionException = new ReflectionClass(self::$ExceptionClassName);
		}
		
		
		/**
		* Load class or namespace.
		* Priority:
		* 1 - Fully-clarified file name within LIBWEBTA_BASE
		* 2 - Simplified path to a class file
		* 3 - All classes in directory
		* @param string $path Path to load
		* @param string $loadbase Load base
		* @return bool True is loaded succesfull or false if not found
		* @throws Exception
		* @static 
		*/
		public static function Load($path, $loadbase = false)
		{
			$loadbase = $loadbase ? $loadbase : LIBWEBTA_BASE;
			// XSS prevention
			if (strstr($path, ".."))
			    Core::RaiseError(_("Cannot use path traversals while loading namespace from "). LIBWEBTA_BASE, E_ERROR);
							
			$dirname = dirname($path);
			
			// Full path to file specified?
			$fullpath = "{$loadbase}/{$path}";
					
			if (is_file($fullpath))
			{
				include_once($fullpath);
			}
			else
			{
				
				// Full path to class. file specified?
				$basename = basename($path);
				$classpath = "{$loadbase}/{$dirname}/class.{$basename}.php";
	
				if (is_file($classpath))
				{
					include_once($classpath);
				}
				
				// Directory specified. Loading all classes inside
				elseif (is_dir($fullpath))
				{
					$files = (array)scandir($fullpath);
					foreach ($files as $file)
					{
						$basename = basename($file);
						if (substr($basename,0, 6) == "class." && substr($basename,-4) == ".php")
							include_once("{$fullpath}/{$file}");
					}
				}
				else
					Core::RaiseError(sprintf(_("Cannot load %s"), $path), E_ERROR);
				
			}
		}
		
		/**
		 * Universal singleton
		 *
		 * @param string $objectname
		 * @return object
		 * @static 
		 */
		public static function GetInstance($objectname)
		{
			if (!$GLOBALS[$objectname])
			{
				// Check class exists or no
				if (!class_exists($objectname))
					Core::RaiseError(sprintf(_("Cannot find %s declaration. Use Core::Load() to load it."), $objectname), E_ERROR);
					
				// Get Constructor Reflection	
				if (is_callable(array($objectname, "__construct")))
					$reflect = new ReflectionMethod($objectname, "__construct");
				elseif (is_callable(array($objectname, $objectname)))
					$reflect = new ReflectionMethod($objectname, $objectname);
				
				// Delete $objectname from arguments
				$num_args = func_num_args()-1;
				$args = func_get_args();
				array_shift($args);
					
				if ($reflect)
				{
					$required_params = $reflect->getNumberOfRequiredParameters();
					
					if ($required_params > $num_args)
						Core::RaiseError(sprintf(_("Missing some required arguments for %s constructor. Passed: %s, expected: %s."),$objectname, $num_args, $required_params), E_ERROR);							
				}
				
				$reflect = new ReflectionClass($objectname);
				
				if ($reflect && $reflect->isInstantiable())
				{
					if (count($args) > 0)
						$GLOBALS[$objectname] = $reflect->newInstanceArgs($args);
					else 
						$GLOBALS[$objectname] = $reflect->newInstance(true);						
				}
				else 
					Core::RaiseError(_("Object not instantiable."), E_ERROR);							
			}
			
			return $GLOBALS[$objectname];
		}
	
		
		/**
		 * Get PHPSmartyMailer instance
		 * @param string $dsn Email DSN (username:password@host:port)
		 * @return object
		 * @static 
		 */
		public static function GetPHPSmartyMailerInstance($dsn = "")
		{
		    if (!class_exists("PHPSmartyMailer"))
		      Core::Load("NET/Mail/PHPSmartyMailer");
		    
		    if (!class_exists("PHPMailer"))
		      Core::Load("NET/Mail/PHPMailer");
		      
			if (!self::$PHPSmartyMailer)
			{
				if (!$GLOBALS["Mailer"])
				{
				    if ($dsn == "")
					   $dsn = (defined("CF_EMAIL_DSN")) ? CF_EMAIL_DSN : "";
					
					self::$PHPSmartyMailer = new PHPSmartyMailer($dsn);
				}
				else
					self::$PHPSmartyMailer = $GLOBALS["Mailer"];
			}

			return self::$PHPSmartyMailer;
		}

		
		/**
		 * Get PHPMailer instance
		 * @return object
		 * @static 
		 */
		public static function GetPHPMailerInstance()
		{
		    if (!class_exists("PHPMailer"))
		      Core::Load("NET/Mail/PHPMailer");
		    
			if (!self::$PHPMailer)
			{
				if (!$GLOBALS["mail"])
					self::$PHPMailer = new PHPMailer();
				else
					self::$PHPMailer = $GLOBALS["mail"];
			}

			return self::$PHPMailer;
		}
		
		
		/**
		 * Get Validator instance
		 * @return Validator
		 * @static 
		 */
		public static function GetValidatorInstance()
		{
		    if (!class_exists("Validator"))
		      Core::Load("Data/Validation");
		    
			if (!self::$Validator)
				self::$Validator = new Validator();
			
		    return self::$Validator;
		}
		
		
		/**
		 * Get Smarty instance
		 * @return object
		 * @static 
		 */
		public static function GetSmartyInstance($smarty_classame = "Smarty")
		{
		    if (!class_exists($smarty_classame))
		    {
                if (self::$ExceptionClassName == 'ApplicationException')
                    throw new CoreException(_("Cannot find Smarty declaration. Use Core::Load() to load it."), E_ERROR);
                else
                    Core::RaiseError(_("Cannot find Smarty declaration. Use Core::Load() to load it."), E_ERROR);
		    }
		    
			if (!self::$Smarty)
			{
			     if ($GLOBALS["Smarty"])
			         self::$Smarty = $GLOBALS["Smarty"];
			     elseif ($GLOBALS["smarty"])
			         self::$Smarty = $GLOBALS["smarty"];
			     else 
			     {			         
			         self::$Smarty = new $smarty_classame;
			         self::$Smarty->template_dir = defined("CF_TEMPLATES_PATH") ? CF_TEMPLATES_PATH : TEMPLATES_PATH;
			         self::$Smarty->compile_dir = defined("CF_SMARTYBIN_PATH") ? CF_SMARTYBIN_PATH : SMARTYBIN_PATH;
			         self::$Smarty->cache_dir = defined("CF_SMARTYCACHE_PATH") ? CF_SMARTYCACHE_PATH : SMARTYCACHE_PATH;
			     }
			    
			}
			
			return self::$Smarty;
		}
		
		
		/**
		 * Get ADODB instance
		 * @param array $connection_info
		 * @param bool $use_nconnect
		 * @param string $driver
		 * @return object
		 * @static 
		 */
		public static function GetDBInstance($connection_info = NULL, $use_nconnect = false, $driver = 'mysqli', $conn_type = DB_SERVER_MASTER)
		{		    
		    if (function_exists("NewADOConnection"))
		    {
		        // Connect to database;
                $host = ($connection_info["host"]) ? $connection_info["host"] : (defined("CF_DB_HOST") ? CF_DB_HOST : "");
                $user = ($connection_info["user"]) ? $connection_info["user"] : (defined("CF_DB_USER") ? CF_DB_USER : "");
                $pass = ($connection_info["pass"]) ? $connection_info["pass"] : (defined("CF_DB_PASS") ? CF_DB_PASS : "");
                $name = ($connection_info["name"]) ? $connection_info["name"] : (defined("CF_DB_NAME") ? CF_DB_NAME : "");
                $driver = ($connection_info["driver"]) ? $connection_info["driver"] : (defined("CF_DB_DRIVER") ? CF_DB_DRIVER : "");
		    	
                $dsn = ($connection_info && !is_array($connection_info)) ? $connection_info : CF_DB_DSN;
                
                if (function_exists("posix_getpid"))
				{
					$pid = posix_getpid();
				}
				else
					$pid = 0;
                
		    	if (!self::$DB[$pid][$conn_type] || self::$DB[$pid][$conn_type] == null || $use_nconnect)
                {                   
                    if (($dsn && defined("CF_DB_DSN")))
                    {
                        self::$DB[$pid][$conn_type] = &NewADOConnection($dsn);
                    }
                    else 
                    {                            
                        if ($host == "")
                            Core::RaiseError("Database host not specified", E_ERROR);
                        
                        try
                        {                                
                    	    self::$DB[$pid][$conn_type] = &NewADOConnection($driver);
                    	    
                    	    if ($use_nconnect || $pid != 0)
                                self::$DB[$pid][$conn_type]->NConnect($host, $user, $pass, $name);
                            else 
                                self::$DB[$pid][$conn_type]->Connect($host, $user, $pass, $name);
                        }
                        catch (ADODB_Exception $e)
                        {
							Core::RaiseError("Cannot connect to database: {$e->getMessage()}", E_ERROR);
                        }
                                                  	
                    	if (!self::$DB[$pid][$conn_type] || !self::$DB[$pid][$conn_type]->IsConnected()) 
                    	    Core::RaiseError("Cannot connect to database", E_ERROR);
                        
                    	self::$DB[$pid][$conn_type]->debug = defined("CF_DEBUG_DB") ? CF_DEBUG_DB : false;
                    	
                    	self::$DB[$pid][$conn_type]->cacheSecs = defined("CF_DB_CACHE") ? CF_DB_CACHE : 0;
                    	self::$DB[$pid][$conn_type]->SetFetchMode(ADODB_FETCH_ASSOC);   
                    }
                }         	
                
    			return self::$DB[$pid][$conn_type];
		    }
		    else 
                Core::RaiseError(_("ADODB not loaded."));	
		}
		
		public static function GetPDOInstance ($config=null, $use_nconnect = false, $conn_type = DB_SERVER_MASTER)
		{
			if (is_array($config))
			{
				$dsn = sprintf('%s:dbname=%s;host=%s', $config['driver'], $config['name'], $config['host']);
				$user = $config['user'];
				$pass = $config['pass'];
			} 
			else if (is_string($config))
			{
				$pu = parse_url($config);
				$dsn = sprintf('%s:dbname=%s;host=%s', $pu['scheme'], substr($pu['path'], 1), $pu['host']);
				$user = $pu['user'];
				$pass = $pu['pass'];
			}
			else if (!self::$PDO[$conn_type])
			{
				throw new Exception('Connection config not specified');
			}
			
			if (self::$PDO[$conn_type] === null || $use_nconnect)
			{                   
				$db = new PDO($dsn, $user, $pass);
				$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
				$db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
				
				self::$PDO[$conn_type] = $db;
			}
			
			return self::$PDO[$conn_type];
		}
		
		
		/**
		 * Shell Singleton
		 * @deprecated Use System/Independent/ShellFactory instead!
		 */
		public static function GetShellInstance()
		{
			self::RaiseWarning("GetShellInstance() is deprecated. Use System/Independent/ShellFactory instead!");
			if (!self::$Shell)
				self::$Shell = new Shell();

			return self::$Shell;
		}
		
		public static function SetExceptionClassName($name)
		{
		    if (class_exists($name))
		    {
                self::$ExceptionClassName = $name;
                self::$ReflectionException = new ReflectionClass($name);
		    }
		    else 
		      Core::RaiseError("Core::SetExceptionClassName failed. Class '{$name}' not found");
		}
		
		/**
		* Raise warning
		* @access public
		* @param string $str Error message
		* @return void
		* @static 
		*/
		public static function RaiseWarning($str, $print = true)
		{
			$GLOBALS["warnings"][] = $str;
		}
		
		/**
		 * Clear warnings array
		 *
		 * @return bool
		 * @static 
		 */
		public static function ClearWarnings()
		{
			$GLOBALS["warnings"] = array();
			return true;
		}
		
		/**
		 * Return true if We have at least one warning
		 *
		 * @return bool
		 * @static 
		 */
		public static function HasWarnings()
		{
			return (count($GLOBALS["warnings"]) > 0) ? true : false;
		}
		
		/**
		* Raise fatal error (We're gonna die!)
		* @access public
		* @param string $str Error message
		* @return void
		* @static 
		*/
		public static function RaiseError($str, $code = E_USER_ERROR)
		{		    
		    self::ThrowException($str, $code);
		}
		
		/**
		* Raise fatal error (We're gonna die!)
		* @access public
		* @param string $str Error message
		* @return void
		* @static 
		*/
		public static function ThrowException($str, $code = E_USER_ERROR)
		{		    
		    if (self::$ReflectionException instanceof ReflectionClass)
				throw self::$ReflectionException->newInstanceArgs(array($str, $code));
			else
			{
				throw new Exception($str, $code);
			}
		}
		
		
		/**
		* Return last warning
		* @access public
		* @return string
		* @static 
		*/
		public static function GetLastWarning()
		{
			return $GLOBALS["warnings"][count($GLOBALS["warnings"])-1];
		}
		
		
		/**
		 * Return current timestampt with microseconds
		 *
		 */
		public static function GetTimeStamp()
		{
		    list($usec, $sec) = explode(" ", microtime());
            return ((float)$usec + (float)$sec);
		}
	}

?>