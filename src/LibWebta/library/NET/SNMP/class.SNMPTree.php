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
     * @package    NET
     * @subpackage SNMP
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */
	
    Core::Load("NET/SNMP/SNMP");
    
	/**
	 * @name SNMPTree
	 * @package NET
	 * @subpackage SNMP
	 * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
	 *
	 */	
	class SNMPTree extends SNMP
	{
		
		/**
		 * Root MIB
		 *
		 * @var string
		 */
		public $MIB;
		
		/**
		 * Prefix that will be added to all nodes
		 *
		 * @var unknown_type
		 */
		public $MIBPrefix;
		
		
		/**
		 * SNMP Tree Constructor
		 *
		 * @ignore
		 */
		function __construct()
		{
			// Default values
			$this->MIB = "RFC1213-MIB"; // Standard MIB
			$this->MIBPrefix = "";
		}
		
		
		/**
		 * Set a root MIB
		 *
		 * @param string $mib MIB name
		 * @param string $prefix Prefix to be added to all nodes
		 */
		public function SetMIB($mib, $prefix = "")
		{
			$this->MIB = $mib;
			$this->MIBPrefix = $prefix;
		}
		
		
		/**
		 * Placeholder for all Get* and GetAll* method calls
		 *
		 * @param string $method Method name
		 * @param array $args Method call arguments
		 * @return unknown
		 */	
		public function __call($method, $args)
   		{
   			// Get an array
     		if (substr($method, 0, 6) == "GetAll")
     		{
     			$key = substr($method, 6);
     			
     			$path = $this->MIB ."::{$this->MIBPrefix}{$key}";
     			//die($path);
     			$retval = $this->GetTree($path);
     			
     			return $retval;
     		}
     				
   			// Get a single value
   			if (substr($method, 0, 3) == "Get")
     		{
     			$index = $args[0] ? $args[0] : "0";
     				
     			$key = substr($method, 3);
     			$path = $this->MIB ."::{$this->MIBPrefix}{$key}.$index";
     			$retval = $this->Get($path);
     			
     			return $retval;
     		}
     		
     		
   		}
   		
   		

		
	}
	
?>
