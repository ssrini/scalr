<?php
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
     * @filesource
     */

	Core::Load("System/Unix/IO/FileSystem");
	Core::Load("System/Unix/IO/QuotaManager");

	/**
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage IO
     * @name System_Unix_IO_Test
	 *
	 */
	class System_Unix_IO_Test extends UnitTestCase 
	{
        function System_Unix_IO_Test()  
        {
            $this->UnitTestCase('System/Unix/IO test');
        }
        
        function testFileSystem() 
        {
			
			$FileSystem = new FileSystem();
			
			
			//
			// Get Mounts
			//
			$retval = $FileSystem->GetMounts();
			$this->assertTrue(is_array($retval) && count($retval[0]) == 6, "GetMounts returned something nice");
			
			//
			// Get home root mount
			//
			$retval = $FileSystem->GetHomeRootMount();
			$this->assertNotNull(is_array($retval), "GetHomeRootMount returned array");
			
			//
			// Get folder mount
			//
			$retval = $FileSystem->GetFolderMount("/home");
			$this->assertNotNull(is_array($retval), "GetFolderMount returned array");
			
			//
			// Get block size
			//
			$retval = $FileSystem->GetFSBlockSize("/proc");
			$this->assertNotNull(is_array($retval), "GetFSBlockSize returned /proc");
        }
        
        function testQuotas() 
        {
			
			// Create account if doesnt exists yet
			$AccountManager = new AccountManager();
			$Account = $AccountManager->GetUserByName("cptest");
			if (!$Account)
				$Account = $AccountManager->Create("cptest", "cptest", "webta.net");
			
			$FileSystem = new FileSystem();
			
			$homemount = $FileSystem->GetHomeRootMount();
			$aquotapath = "{$homemount[1]}/aquota.user";	
			
			//
			// Check either quotas exist
			//
			$this->assertTrue(file_exists($aquotapath) && filesize($aquotapath) > 0, "$aquotapath size is more than zero");

			
			//
			// Set quota
			//
			$QuotaManager = new QuotaManager($aid);
			$retval = $QuotaManager->SetQuota("cptest", 5,5,5,5);
			
			$this->assertTrue($retval, "SetQuota returned true ($retval)");
			
        }
    }


?>