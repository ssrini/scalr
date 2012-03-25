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
     * @package    IO
     * @subpackage PCNTL
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */

    declare(ticks = 1);
    Core::Load("IO/PCNTL/class.ProcessManager.php");
    Core::Load("IO/PCNTL/class.SignalHandler.php");
    Core::Load("IO/PCNTL/interface.IProcess.php");
    Core::Load("IO/PCNTL/class.JobLauncher.php");
    
    /**
	 * Tests for IO/Transports
	 * 
	 * @category   LibWebta
     * @package    IO
     * @subpackage Transports
     * @name IO_Transports_Test
	 *
	 */
	class IO_PCNTL_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('IO/PCNTL Tests');
        }
    
        function testJobLauncher()
        {
            $JobLauncher = new JobLauncher(dirname(__FILE__));
            $JobLauncher->Launch();
        }
	}
?>