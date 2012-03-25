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
     * @subpackage HTTP
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource 
     */


	$base = dirname(__FILE__);
		
	Core::Load("NET/HTTP/class.HTTPClient.php");
	
	/**
	 * @category   LibWebta
     * @package    NET
     * @subpackage HTTP
     * @name NET_HTTP_HTTP_Test
	 */
	class NET_HTTP_HTTPClient_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('NET/HTTP/HTTPClient test');
        }
        
        function testNET_HTTP_HTTPClient() 
        {
			
			$robot = new HTTPClient();

			// connect to url 
			$res = $robot->Fetch("http://webta.net");
			$this->assertTrue($res, "Can't fetch url");


			/* end of tests */
        }
        
    }


?>