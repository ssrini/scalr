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
     * @subpackage Basic
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @ignore
     */

	Core::Load("IO/Basic");
	
	if (!defined("DIRECTORY_SEPARATOR"))
	{
		if (PHP_OS == "WINNT")
			define("DIRECTORY_SEPARATOR", "\\");
		else
			define("DIRECTORY_SEPARATOR", "/");
	}
	
	/**
	 * @category   LibWebta
     * @package    IO
     * @subpackage Basic
     * @name IO_Basic_Test
	 */
	class IO_Basic_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('IO/basic Tests');
        }

        function testIO_Basic() 
        {
        	//
			// Delete tmp file
			//
			$tmpdir = ini_get("session.save_path") ? ini_get("session.save_path") : "/tmp";
			
			$s = DIRECTORY_SEPARATOR;
			$tmpdir = "{$tmpdir}{$s}iotest{$s}1{$s}2{$s}3";
			@mkdir($tmpdir, 0777, true);
			
			
			$this->AssertTrue(file_exists($tmpdir), "Directory exists before IOTool::UnlinkRecursive()");
			
			$p = "{$tmpdir}{$s}iotest";
			IOTool::UnlinkRecursive($p);
			$this->AssertFalse(file_exists($p), "Directory does not exist after IOTool::UnlinkRecursive()");
			
        }
    }

?>