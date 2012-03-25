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
     * @package    Data_DB
     * @subpackage MySQL
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     * @filesource
     */
	
    Core::Load("Data/DB/MySQL");
	
	/**
	 * @name Data_DB_MySQL_Test
	 * @category   LibWebta
     * @package    Data_DB
     * @subpackage MySQL
	 *
	 */
	class Data_DB_MySQL_Test extends UnitTestCase 
	{
        function MySQLToolTest() 
        {
            $this->UnitTestCase('MySQLTool test');
        }
        
        function testMySQLTool() 
        {
			
			$MySQLTool = new MySQLTool();
			
			//
			// List databases
			// 
			$retval = $MySQLTool->ListDatabases();
			$this->assertTrue(count($retval)> 0, "There are more than 0 database listed");
			
			//
			// Create DB
			// 
			$retval = $MySQLTool->CreateDatabase("cptest", "test1");
			$this->assertTrue($retval, "Database created succesfully");
			
			//
			// Create User
			// 
			$retval = $MySQLTool->CreateUser("cptest", "test1", "testpass0");
			$this->assertTrue($retval, "User created succesfully");
			
			//
			// Privileges
			// 
			$privileges = array("SELECT", "UPDATE");
			$retval = $MySQLTool->SetPrivileges("cptest_test1", "cptest_test1", $privileges);
			$this->assertTrue($retval, "Granted rights succesfully");
			
			//
			// Delete DB
			// 
			$retval = $MySQLTool->DropDatabase("cptest", "test1");
			$this->assertTrue(!in_array("cptest_test1", $MySQLTool->ListDatabases()), "Deleted DB does not exist");
			
			//
			// Delete User
			// 
			$retval = $MySQLTool->DropUser("cptest_test1");
			$this->assertTrue($retval, "User deleted succesfully");
			
			//
			// Server info
			//
			$retval = $MySQLTool->GetSQLServerVersion();
			$this->assertTrue(count($retval) > 2, "SQL server version retreived");
			
			//
			// List users
			//
			$retval = $MySQLTool->ListUsers();
			$this->assertTrue(is_array($retval) && count($retval) > 0, "Users list is non-empty array");
			
        }
    }


?>