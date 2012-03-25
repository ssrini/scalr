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
     * @package    Core
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */ 
	
	/**
	 * @category   LibWebta
     * @package    Core
     * @name Core_Test
	 *
	 */
	class Core_Test extends UnitTestCase 
	{
		
        function __construct() 
        {
            $this->UnitTestCase('Core test');
        }
        
        function testCore_Test_Core() 
        {
			
			//
			// Core load() function
			//
			
			// Load single class
			Core::Load("Core");
			$this->assertTrue(class_exists("Core"), "Core class is loaded");
			
			// Load single class
			Core::Load("NET/API/WHM");
			$this->assertTrue(class_exists("WHM") && class_exists("CPanel"), "WHM and CPanel classes loaded");
			
			$memory_start = @memory_get_usage();
			
			//Check GetInstance			
			$class = Core::GetInstance("WHM", array("hostname" => "test", "login" => "login"));
			$this->assertTrue(($class instanceOf WHM && $class->Host == "test"), "WHM instance created");
			
						
			for($i = 0; $i < 5000; $i++)
			{
				$class = Core::GetInstance("WHM", array("hostname" => "test", "login" => "login"));
			}
			
			$memory_end = @memory_get_usage();
			
			$change = abs(round(($memory_start-$memory_end)/$memory_start*100, 2));
			$this->assertTrue(($change < 50), "No memory leaks detected. Memory before test {$memory_start}, after test {$memory_end}");
        }
        
        /**
         * @todo Test CoreException here
         */
        function testCore_Test_CoreException() 
        {
        	
        }
        
    }


?>