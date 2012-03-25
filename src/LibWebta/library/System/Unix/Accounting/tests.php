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
     * @subpackage Accounting
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */
    
	Core::Load("System/Unix/Accounting");
	Core::Load("System/Unix/Stats");
	Core::Load("System/Independent/Shell/class.ShellFactory.php");

	define("CF_USERADD_PATH", "/usr/sbin/pw");
	define("CF_USERDEL_PATH", "/usr/sbin/pw");
	define("CF_USERMOD_PATH", "/usr/sbin/pw");
	define("CF_CHPASSWD_PATH", "/usr/sbin/pw");
	define("CF_PASSWORD_FILE_PATH", "/etc/master.passwd");
	define("CF_SHELL_PATH", "/usr/sbin/nologin");
	
	/**
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Accounting
     * @name System_Unix_Accounting_Test
	 *
	 */
	class System_Unix_Accounting_Test extends UnitTestCase 
	{
        function System_Unix_Accounting_Test() 
        {
            $this->UnitTestCase('System/Unix/Accounting Test');
        }
        
        function testSystemUserManager() 
        {

			$UserManager = new SystemUserManager();
			
			$Shell = ShellFactory::GetShellInstance();
			
			$User = $UserManager->GetUserByName("cptestuser");
			
			if ($User instanceof SystemUser)
				$User->Delete();
			
			// Create user
			$res = $UserManager->Create("cptestuser", "cptestuser");
			
			$this->assertTrue($res, "Test system user created");
			$this->assertTrue(is_a($res, "SystemUser"), "Returned user is an instance of SystemUser class");
			
			// Delete user
			$User = $UserManager->GetUserByName("cptestuser");
			$res = $User->Delete();
			$this->assertTrue($res, "Test system user deletedion did not return shell errors");
			$this->assertFalse($Shell->ExecuteRaw("cat /etc/passwd|grep cptestuser"), "User cptestuser not exists");
			
			// System users list
			$res = $UserManager->GetList();
			$this->assertTrue(is_array($res) && count($res) > 0, "System users list length more than 0");
			$this->assertTrue(is_a($res[0], "SystemUser"), "Returned user 0 is an instance of SystemUser class");
			
			// Create again
			$User = $UserManager->Create("cptestuser", "cptestuser");
			$this->assertTrue(is_a($User, "SystemUser"), "Returned user is an instance of SystemUser class");
			
			// Cahnge and get password
			$old = $User->GetPwdHash();
			$User->SetPassword("fuckaz");
			$this->assertFalse($User->GetPwdHash() == $old, "PwdHash is correct");
			
			// Cahnge shell
			$res = $User->SetShell("/bin/bash");
			$this->assertTrue($Shell->QueryRaw("cat /etc/passwd|grep ^{$User->Username}|grep -c bash") == 1, "{$User->Username}'s shell is bash");
			
			
			$User = $UserManager->GetUserByName("cptestuser");
			$User->Delete();
        }
    }


?>