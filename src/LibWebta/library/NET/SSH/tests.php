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
     * @package    NET
     * @subpackage SSH
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */
    
	Core::Load("NET/SSH/class.SSH2.php");
	
	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage SSH
     * @name  NET_SSH_Test
	 */
	class NET_SSH_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('SSH2 Class Test');
            $this->tmp = ini_get("session.save_path");
        }
        
        
		function testSSH2Remote()
        {
        	$base = dirname(__FILE__);
        	
        	$this->SSH2 = new SSH2();
        	
        	// Failed password
        	$retval = $this->SSH2->Connect("ns1.scalr.net", 22, "named", "ND78239hdu^&^2e");
        	$this->AssertTrue($retval, "Log in with password");
        	
        	$base = dirname(__FILE__);
        				
			$this->runTests();	

			
			print "<HR>";
			
			$this->SSH2 = new SSH2();
        	
		
        	// Failed password
        	$retval = $this->SSH2->Connect("67.19.90.82", 22, "named", "78y6^T%^fg32ieg");
        	$this->AssertTrue($retval, "Log in with password");
        	
        	$base = dirname(__FILE__);
        				
			$this->runTests();
        }
        
        function testSSH2Local()
        {
            /*
        	$base = dirname(__FILE__);
        	
        	$this->SSH2 = new SSH2();
        	
        	$this->SSH2->AddPassword("root", "");
        	
        	// Failed password
        	$retval = $this->SSH2->Connect("192.168.1.200", 22);
        	$this->AssertFalse($retval, "Failed to log in with incorrect root password");
        	
        	$base = dirname(__FILE__);
        	
        	// Correct pubkey
        	$this->SSH2->AddPubkey("root", "$base/keys/key.pub", "$base/keys/key", "111111");
        	$retval = $this->SSH2->Connect("192.168.1.200", 22);
			$this->AssertTrue($retval, "Logged in with correct public key");
			
			$this->runTests();	
			*/
        }
        
        
        function runTests()
        {
        	$res = $this->SSH2->Exec("ls /");
			$this->assertWantedPattern("/boot/", $res, "Received root directory listing");

			$res =  $this->SSH2->GetFile("/etc/named.conf");
			$this->assertTrue(strlen($res) > 5, "READ /etc/named.conf");
			
			$res =  $this->SSH2->SendFile("/tmp/test.file", __FILE__);
			$this->assertTrue($res, "File ".__FILE__." saved as /tmp/test.file");
			
			$res =  $this->SSH2->GetFile("/tmp/test.file");
			$this->assertWantedPattern("/NET_SSH_Test/", $res, "File /tmp/test.file readed");
			
			$res =  $this->SSH2->Exec("rm -rf /tmp/test.file");
			$this->assertTrue($res, "File /tmp/test.file succesfully deleted");
			
			$res =  $this->SSH2->GetFile("/tmp/test.file");
			$this->assertFalse($res, "Cannot read /tmp/test.file. It was deleted on previous step!");
        }
    }

?>