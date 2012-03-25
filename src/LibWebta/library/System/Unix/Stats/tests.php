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
     * @subpackage Stats
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */

	Core::Load("/System/Unix/Stats/SystemStats");
	
	/**
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage Stats
     * @name System_Unix_Stats_Test
	 *
	 */
	class System_Unix_Stats_Test extends UnitTestCase 
	{
        function System_Unix_Stats_Test() 
        {
            $this->UnitTestCase('System/Unix/Stats Test');
        }
        
        function testSystemStats() 
        {
			
			$SystemStats = new SystemStats();
			
			//
			// Get uptime
			//
			$retval = $SystemStats->GetUptime();
			$this->assertTrue( is_double($retval), "System uptime is double number");
			
			//
			// Get linux ver
			//
			$retval = $SystemStats->GetLinuxVersion();
			$this->assertTrue(is_string($retval), "System version is string");
			
			//
			// Get linux name
			//
			$retval = $SystemStats->GetLinuxName();
			$this->assertTrue(is_string($retval), "System version is string");
			
			
        }
    }


?>