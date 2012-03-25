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
     * @subpackage RPC
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource 
     */

	Core::Load("NET/RPC/RPCClient");
	
	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage RPC
     * @name NET_RPC_RPC_Test
	 */
	class NET_RPC_Test extends UnitTestCase 
	{

        function __construct() 
        {
            $this->UnitTestCase('NET/RPC/RPC test');
        }
        
        function testNET_PRC_RPCClient() 
        {
			
			$rpc = new RPCClient("http://www.livejournal.com/interface/xmlrpc");
			
			$result = $rpc->__call("LJ.XMLRPC.getfriends", array(
				"username" => "username",
				"password" => "password",
				"ver" => 1
			));
			
			$this->assertTrue($result['friends'], "Invalid success response");
			
			$result = $rpc->__call("exec", array());
			
			$this->assertTrue($result['faultString'], "Invalid fault response");
			
        }
        
    }

?>