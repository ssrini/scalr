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
     * @package    Security
     * @subpackage Crypto
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource 
     */

	Core::Load("Core");
	Core::Load("Security/Crypto");
	
	/**
	 * @category   LibWebta
     * @package    Security
     * @subpackage Crypto
     * @name Security_Crypto_Test
	 *
	 */
	class Security_Crypto_Test extends UnitTestCase 
	{
        function __construct() 
        {
            $this->UnitTestCase('Crypto Class Test');
        }
        
        function testSecurity_Crypto_Crypto() 
        {

			//
			// Encrypt and decrypt back
			//
			$input = "testAB*#~!CD123489-++)(7";
			$key = "627~@@h(728_=-2";
			
			// Encrypt
			$Crypto = new Crypto($key);
			$retval = $Crypto->Encrypt($input);
			$this->assertTrue(!empty($retval), "Crypto->Encrypt returned non-empty value");
			
			// Decrypt
			$retval = $Crypto->Decrypt($retval);
			$this->assertTrue(!empty($retval), "Crypto->Decrypt returned non-empty value");
			$this->assertEqual($retval, $input, "Decrypted string is equal to initial one");
			
			// Hash
			$Crypto = new Crypto($key);
			$retval = $Crypto->Hash($input);
			$retval2 = $Crypto->Hash($input."stuff");
			$this->assertTrue($retval != $retval2, "Crypto->Hash returned different hashes from different strings");
			
        }
    }

?>