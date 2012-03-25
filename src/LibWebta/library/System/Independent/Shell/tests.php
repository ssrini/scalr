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
     * @package    System
     * @subpackage Shell
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */
	
	/**
	 * @category   LibWebta
     * @package    System
     * @subpackage Shell
     * @name  System_Independent_Shell_Test
	 *
	 */
	class System_Independent_Shell_Test extends UnitTestCase 
	{
		
        function __construct() 
        {
        	Core::Load("System/Independent/Shell/ShellFactory");
            $this->UnitTestCase('System/Independent/Shell test');
        }
        
        function testFactory() 
        {
			
			$Shell = ShellFactory::GetShellInstance();
			$this->assertTrue(is_a($Shell, "Shell") || is_a($Shell, "WinShell"), 
			"ShellFactory::GetShellInstance returned Shell or WinShell instance");
							
        }
        
    }


?>