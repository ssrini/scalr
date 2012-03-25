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
     * @subpackage Archive
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */

	Core::Load("IP/Archive");

	/**
	 * @category   LibWebta
     * @package    IO
     * @subpackage Archive
	 * @name Data_Archive_Test
	 */
	class Data_Archive_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Data/Compress/ZipArchive test');
        }
        
        function testZipArchive() 
        {
			
			$Compressor = new ZipArchive();
			$Compressor->AddFile(__FILE__);
			$Compressor->AddFile(dirname(__FILE__). '/class.ZipArchive.php');
			
			$result = $Compressor->Pack();
			
			$this->assertTrue($result, "Archived successfully created");
			
			$content = $Compressor->GetArchive();
			$this->assertTrue(trim($content), "Archived successfully gotten");
			
			file_put_contents("/tmp/archive.zip", $content);
			chmod("/tmp/archive.zip", 0777);
        }
    }
?>
