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
     * @package    Security
     * @subpackage OpenSSL
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */    

	include_once("../Server/System/class.SSLManager.php");
	
	/**
	 * @category   LibWebta
     * @package    Security
     * @subpackage OpenSSL
     * @name SSLManagerTest
	 *
	 */
	class SSLManagerTest extends UnitTestCase 
	{
        function SSLManagerTest() 
        {
            $this->UnitTestCase('SSLManager test');
        }
        
        function testSSLManager() 
        {
			
			$SSLManager = new SSLManager();
			
			//
			// Generate RSA
			//
			$retval = $SSLManager->GenerateRSAKey("cptest", "webta.net");
			$retval = $SSLManager->GenerateRSAKey("cptest", "webta.net");
			$this->assertTrue($retval, "GenerateRSAKey returned true");
			
			//
			// Delete file
			//
			$retval = $SSLManager->DeleteFile("cptest", "webta.net", "key.".date("j-n-Y"));
			$this->assertTrue($retval, "Deleted file succesfully");
			
			//
			// Generate signing request
			//
			$retval = $SSLManager->GenerateSigningRequest("cptest", "webta.net", "test@example.com", "Sevastopol", "UA", "CR", "Webta Labs");
			$this->assertTrue($retval, "Signing request generated succesfully");
			
			//
			// Generate cert
			//
			$retval = $SSLManager->GenerateCert("cptest", "webta.net", "test@example.com", "Sevastopol", "UA", "CR", "Webta Labs");
			$this->assertTrue($retval, "Certificate generated succesfully");
			
			
			
        }
    }


?>