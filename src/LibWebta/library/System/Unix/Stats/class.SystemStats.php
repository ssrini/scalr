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
     * @package    System_Unix
     * @subpackage Stats
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	/**
	 * @name       SystemStats
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Stats
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	class SystemStats extends Core 
	{
		
		private $Shell;
		
		public $IsWindows;
		
		public $IsFreeBSD;
		
		public $IsLinux;
		
		/**
		 * @ignore
		 *
		 */
		function __construct()
		{
			parent::__construct();
			$this->Shell = ShellFactory::GetShellInstance();
			
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
				$this->IsWindows = true;
			elseif(strtoupper(PHP_OS) === 'FREEBSD')
				$this->IsFreeBSD = true;
			else 
				$this->IsLinux = true;
			
		}
		
		/**
		* Get system uptime
		* @access public
		* @return float Uptime in seconds
		*/
		public final  function GetUptime()
		{
			$file = file("/proc/uptime");
			$retval = preg_split("/[\s]+/", $file[0]);
			$retval = round($retval[1]);
			return $retval;
		}
		
		
		/**
		* Get system version
		* @access public
		* @return string System version info
		*/
		public final  function GetLinuxVersion()
		{
			$retval = file_get_contents("/proc/version");
			return $retval;
		}
		
		/**
		* Get system name
		* @access public
		* @return string System product name
		*/
		public final  function GetLinuxName()
		{
			$retval = $this->Shell->QueryRaw("cat /etc/*-release|grep -v LSB");
			$lines = explode("\n", $retval);
			$retval = $lines[0];
			return $retval;
		}

		/**
		* Get unix name
		* @access public
		* @return string System product name
		*/
		public final  function GetUnixName()
		{
			$retval = $this->Shell->QueryRaw("uname");
			return $retval;
		}
		
	}
	
?>