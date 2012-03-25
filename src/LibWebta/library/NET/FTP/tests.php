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
     * @subpackage FTP
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */

	Core::Load("NET/FTP/class.FTP.php");

	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage FTP
     * @name NET_FTP_Test
	 *
	 */
	class NET_FTP_Test extends UnitTestCase 
	{
        function NET_FTP_Test() 
        {
            $this->UnitTestCase('FTP test');
        }
        
        function testFTP() 
        {
			
        	// Create FTP Instance
        	$FTP = new FTP("example.com", "username", "password", 21, true);
        	$this->assertTrue($FTP, "FTP Resource created");
        	
        	// Try get File
        	$file = $FTP->GetFile("/", "80snow.com.db", 1);
        	$this->assertTrue($file, "File '80snow.com.db' received");
        }
    }


?>