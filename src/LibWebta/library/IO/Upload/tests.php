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
     * @subpackage Upload
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */

	Core::Load("Core");
	Core::Load("CoreException");
	Core::Load("IO/Upload/UploadManager");
	
	/**
	 * Tests for IO/Upload
	 * 
	 * @category   LibWebta
     * @package    IO
     * @subpackage Upload
     * @name IO_Upload_Test
	 *
	 */
	class IO_Upload_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('IO/Upload Tests');
        }

        function testIO_Upload_UploadManager() 
        {
			$base = dirname(__FILE__);
			
			$Uploadmanager = new UploadManager();
			
			$path 		= "/tmp/sjack/tmp";
			$filename 	= "filename.gif";

			$Uploadmanager->SetDestinationDir("/tmp/test.file");
			
			//
			// Uplaod from URL
			//
			$res = $Uploadmanager->UploadFromURL("http://webta.net/images/webta_guy.jpg");
			$this->assertTrue($res, "File uploaded from URL");
			
			print_r($GLOBALS['warnings']);
			
			$file = array(
				"name" => $filename,
				"size" => 1023,
				"type" => "image/txt",
				"error"=> 0
			);
			
			// check upload
			$res = $Uploadmanager->Upload($file);
			$this->assertFalse($res, "File not uploaded");
				
			// check Generate Path
			$result = $Uploadmanager->BuildDir($path, $filename);
			$md5  = md5($filename);
			$newpath = $path . "/". substr($md5, 0, 2) ."/". substr($md5, 2, 2);
			
			$this->assertEqual($result, $newpath, "Result path is valid: $result");
			
			
			// check SetDestination
			$Uploadmanager->SetDestinationDir($result ."/". $filename);
			$this->assertTrue(is_dir($result), "Destination created $result");
			
			$valid = array("txt", "jpeg", "tar", "rar", "zip", "gif");
			$Uploadmanager->SetValidExtensions($valid);
			
			// check Validate function / must be public access
			//$res = $Uploadmanager->Validate();
			//$this->assertFalse($res, "Uploaded File Not validated");
        }
    }

?>