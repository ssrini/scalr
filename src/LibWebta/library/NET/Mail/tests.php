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
     * @package    NET
     * @subpackage Mail
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */


    /**
     * @category   LibWebta
     * @package    NET
     * @subpackage Mail
     * @name PHPSmartyMailerTest
     */
	class PHPSmartyMailerTest extends UnitTestCase 
	{
        function PHPSmartyMailerTest() 
        {
            $this->UnitTestCase('PHPSmartyMailer test');
        }
        
        function testPHPSmartyMailer() 
        {
			$Mailer = new PHPSmartyMailer(CF_EMAIL_DSN);
			
			//$Mailer->SMTPDebug = true;
			
			$Mailer->SmartyBody = array("signup.eml", array("login"=>"TEST", "password"=>"TEST"));
			$Mailer->Subject = "Test Email";
							
			$Mailer->AddAddress("test@example.com", "");
			
			$Mailer->From = CF_EMAIL_ADMIN;
			$send = $Mailer->Send();
			
			$this->assertTrue(!is_array($Mailer->Body), "Parse body");
			$this->assertTrue($send, "Sending mail");
        }
    }


?>