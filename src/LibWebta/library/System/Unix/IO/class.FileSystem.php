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
     * @subpackage IO
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

    /**
	 * @name       FileSystem
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage IO
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	class FileSystem extends Core 
	{
		/**
		 * @ignore
		 *
		 */
		function __construct()
		{
			$this->Shell = ShellFactory::GetShellInstance();
		}
		
		
		/**
		* Get filesystem mount points
		* @access public
		* @return array Mounts
		*/
		public final  function GetMounts()
		{
			$file = file("/proc/mounts");
			foreach ($file as $line)
			{
				$retval[] = explode(" ", $line);
			}
			return $retval;
		}
		
		
		/**
		* Get mountpoint for specific folder
		* @access public
		* @return string Mountpoint
		*/
		public final  function GetFolderMount($path)
		{
			foreach ($this->GetMounts() as $mount)
			{
				if ($mount[0][0] == "/" && substr($path, 0, strlen($mount[1])) ==  $mount[1])
				{
					$retval = $mount;
				}
			}
			return $retval;
		}
		
		
		/**
		* Get mountpoint for home root
		* @access public
		* @return string Mountpoint
		*/
		public final  function GetHomeRootMount()
		{
			return $this->GetFolderMount(CF_ENV_HOMEROOT);
		}
		
		
		/**
		* Get filesystem block size
		* @access public
		* @param string $device Device
		* @return int block size in bytes
		*/
		public function GetFSBlockSize($device)
		{
			$retval = $this->Shell->QueryRaw("/sbin/dumpe2fs $device 2>/dev/null | grep \"Block size\" | awk '{print \$3}'");
			if (!is_int($retval))
			{
				Core::RaiseWarning(_("Cannot determine filesystem block size"));
				$retval = false;
			}
				
			return ($retval);
		}
		
		
	}
	
?>