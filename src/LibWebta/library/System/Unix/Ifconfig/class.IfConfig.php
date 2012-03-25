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
     * @package    System_Unix
     * @subpackage IfConfig
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	
	define("IFCONFIG_BIN", "/sbin/IfConfig");
	define("DEFAULT_ETH", "eth0");
	
	/**
	 * @name       IfConfig
	 * @category   LibWebta
     * @package    System_Unix
     * @subpackage IfConfig
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
	class IfConfig extends Core
	{
		
		/**
		* Path to IfConfig binary
		* @var IfConfig
		* @access public
		*/
		public $IfConfigBin;
		
		/**
		* Default external interface
		* @var DefaultEth
		* @access public
		*/
		public $Eth;
		
		
		function __construct()
		{
			parent::__construct();
			$this->Shell = Core::GetShellInstance();			
			$this->IfConfigBin = FCONFIG_BIN;
			$this->Eth = DEFAULT_ETH;
		}
		
		
		/**
		* Add new IP to $this->DefaultEth
		* @access public
		* @return bool True on success, false on failure
		*/
		public function IPExists($eth = NULL)
		{
			return($retval);
		}
		
		
		/**
		* Add new IP to $this->DefaultEth
		* @access public
		* @return bool True on success, false on failure
		*/
		public function GetIPAddressList($eth = NULL)
		{
			if (!$eth)
				$eth = $this->Eth;
			foreach ($this->Shell->QueryRaw("{$this->IfConfigBin}", false) as $line)
			{
				echo $line;
			};
		}
		
		
		/**
		* Add new IP to $this->DefaultEth
		* @access public
		* @return bool True on success, false on failure
		*/
		public function GetAliasesList($eth = NULL)
		{
			return($retval);
		}
		
		
		/**
		* Add new IP to $this->DefaultEth
		* @access public
		* @return bool True on success, false on failure
		*/
		public function AddIpAlias($ip, $netmask)
		{
			// TODO: add to internal array
			$retval = $this->RebuildAliases();
			return($retval);
		}
		
		
		/**
		* Rebuild IP pool from database
		* @access public
		* @return bool Transaction status
		*/
		protected function RebuildIs()
		{
			$i = 0;
			foreach ($this->GetAliasesList() as $ip)
			{
				$exec = "{$this->IfConfigBin} {$this->Eth}:{$i} {$ip['ip']} netmask {$ip['netmask']}";
				$retval = $this->Shell->ExecuteRaw($exec);
				if ($retval)
					$i++;
				else
				{	
					$this->RaiseWarning("Cannont execute {$exec}");
					return false;
				}
			}
			return true;
		}
		
		
		/**
		* Delete IP alias from the system
		* @param string $ip IP 
		* @param string $netmask Netmask 
		* @access public
		* @return void
		*/
		public function DeleteAlias($ip, $netmask)
		{

			// TODO: delete from array
			
			// Rebuild
			$retval = $this->RebuildAliases();
			
			return($retval);
			
		}
		
		
	}

?>