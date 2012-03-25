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
     * @subpackage IfConfig
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */
    
	Core::Load("Core");
	Core::Load("System/Unix/NET/IfConfig");
	Core::Load("System/Unix/Shell/Shell");
	
	/**
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage IfConfig
     * @name SystemUnixNETTest
	 *
	 */
	class SystemUnixNETTest extends UnitTestCase 
	{
        function SystemUnixNETTest() 
        {
            $this->UnitTestCase('System/Unix/NET Tests');
        }
        
        function testSystemUnixNET() 
        {
			$IfConfig = new IfConfig();
			$result = $IfConfig->GetIPAddressList();
			print_r($result);
			$this->assertTrue($result, "ValidateLicenseFile returned true");
			
        }
    }

?>