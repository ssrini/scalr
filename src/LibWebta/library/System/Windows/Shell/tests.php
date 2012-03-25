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
     * @package    System_Windows
     * @subpackage Shell
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */
	
    /**
     * @category   LibWebta
     * @package    System_Windows
     * @subpackage Shell
     * @name System_Windows_Shell_Test
     *
     */
	class System_Windows_Shell_Test extends UnitTestCase 
	{
        function __construct() 
        {
            load("System/Windows/Shell/Shell");
        	$this->UnitTestCase('System/Windows/Shell test');
        }
        
        function testShell() 
        {
			
			$Shell = new WinShell();
			
			//
			// Delete tmp file
			//
			$tmpfile = ini_get("session.save_path")."/shelltest";
			@unlink($tmpfile);
			$this->assertFalse(file_exists($tmpfile), "$tmpfile does not exists");
			
			//
			// dir
			//
			$result = $Shell->Query("dir");
			$this->assertTrue(!empty($result), "dir command result is not empty");
			
			// Query raw
			$result = $Shell->QueryRaw("dir C:\\", false);
			$this->assertTrue(is_array($result), "dir C:\\ command result is array");
        }
    }


?>