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
     * @subpackage Shell
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */    

	Core::Load("System/Unix/Shell/Shell");	

	/**
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Shell
     * @name System_Unix_Shell_Test
	 *
	 */
	class System_Unix_Shell_Test extends UnitTestCase 
	{
        function System_Unix_Shell_Test() 
        {
            $this->UnitTestCase('System/Unix/Shell test');
        }
        
        function testShell() 
        {
			
			$Shell = new Shell();
			
			//
			// Delete tmp file
			//
			@unlink("/tmp/shelltest");
			$this->assertFalse(file_exists("/tmp/shelltest"), "Tmp file does not exists");
			
			//
			// Create tmp file
			//
			$Shell->Execute("touch", array("/tmp/shelltest"));
			$this->assertTrue(file_exists("/tmp/shelltest"), "Tmp file exists");
			
			//
			// ls -al
			//
			$result = $Shell->Query("ls", array("-al"));
			$this->assertTrue(!empty($result), "Result not empty");
			
			// Query raw
			$Shell->QueryRaw("ls -al");
			$this->assertTrue(!empty($result), "Result not empty");
			
        }
    }


?>