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
     * @package    IO
     * @subpackage Transports
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */

    Core::Load("IO/Transports/class.TransportFactory.php");
    
	/**
	 * Tests for IO/Transports
	 * 
	 * @category   LibWebta
     * @package    IO
     * @subpackage Transports
     * @name IO_Transports_Test
	 *
	 */
	class IO_Transports_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('IO/Transports Tests');
        }
        
        function testTransportFactory()
        {
            $transports = TransportFactory::GetAvaiableTransports();
           
            $this->assertTrue(in_array("SSH", $transports), "List of avaiable transports received");
            
            $ssh_host = "42.23.4.5";
            $ssh_port = "22";
            $ssh_login = "root";
            $ssh_password = "234234sdfs23";
            
            $this->Transport = TransportFactory::GetTransport("SSH", $ssh_host, $ssh_port, $ssh_login, $ssh_password);
            $this->assertTrue($this->Transport, "Successfully created SSH Transport instance");
            $this->doTests();
            
            $this->Transport = TransportFactory::GetTransport("Local");
            $this->assertTrue($this->Transport, "Successfully created Local Transport instance");
            $this->doTests();
        }
        
        function doTests()
        {
            $res = $this->Transport->Read("/etc/passwd");
            $this->assertTrue(stristr($res, "root"), "Successfully Readed /etc/.passwd");
            
            $res = $this->Transport->Write("/tmp/test.txt", "test", true);
            $this->assertTrue($res, "Successfully Writed /tmp/test.txt");
            
            $res = $this->Transport->Chmod("/tmp/test.txt", "0777");
            $this->assertTrue($res, "Successfully Chmoded /tmp/test.txt");
            
            $res = $this->Transport->Copy("/tmp/test.txt", "/tmp/test2.txt");
            $this->assertTrue($res, "Successfully Copied /tmp/test.txt to /tmp/test2.txt");
            
            $res = $this->Transport->Chmod("/tmp/test2.txt", "0777");
            $this->assertTrue($res, "Successfully Chmoded /tmp/test2.txt");
        }
    }

?>