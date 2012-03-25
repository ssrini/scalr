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
	 * @name       QuotaManager
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage IO
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	class QuotaManager extends FileSystem 
	{
        /**
         * @ignore
         *
         */
		function __construct()
		{
			parent::__construct();
			$this->Shell = CP::GetShellInstance();
		}
		
		
		/**
		* Set quota for user
		* @access public
		* @param string $softmbs Soft disk space limit in megabytes
		* @param string $hardmbs Hard disk space limit in megabytes
		* @param string $softfiles Soft files count limit
		* @param string $hardfiles Hard files count limit
		* @return bool
		*/

		public function EnableQuota()
		{ 
			// TODO: enable quotas
		}

		/**
		* Set quota for user
		* @access public
		* @param string $softmbs Soft disk space limit in megabytes
		* @param string $hardmbs Hard disk space limit in megabytes
		* @param string $softfiles Soft files count limit
		* @param string $hardfiles Hard files count limit
		* @return bool
		*/

		public function SetQuota($username, $softmbs, $hardmbs, $softfiles, $hardfiles)
		{ 
			// Get device for system home folder
			$mountpoint = $this->GetHomeRootMount();
			$device = $mountpoint[0];
			
			// Get block size
			$blocksize = $this->GetFSBlockSize($device);
			$softblocks = ($softmbs*1024*1024)/$blocksize;
			$hardblocks = ($hardmbs*1024*1024)/$blocksize;
			$retval = $this->Shell->Execute("/usr/sbin/setquota", array($username, $softblocks, $hardblocks, $softfiles, $hardfiles, $mountpoint[1]));
			return($retval);
		}
		
		
	}

?>