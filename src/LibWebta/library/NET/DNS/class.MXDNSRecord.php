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
     * @subpackage DNS
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */
		
	/**
     * @name       MXDNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class MXDNSRecord extends DNSRecord
	{
		public $Name;
		public $Rname;
		public $TTL;
		public $Class;
		public $Pref;
		public $Content;
		private $Error;
		public $Type;
		
		const DEFAULT_TEMPLATE = "{name} {ttl} {class} MX {pref} {rname}";
		
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $rname
		 * @param integer $pref
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $rname, $ttl = false, $pref = 10, $class = "IN")
		{
			parent::__construct();
			
			$this->Type = "MX";
			
			// Name
			if (($this->Validator->MatchesPattern($name, self::PAT_NON_FDQN) || 
				$name == "@" || 
				$name === "" || 
				$this->Validator->IsDomain($name)) && !$this->Validator->IsIPAddress(rtrim($name, "."))
			   )
				$this->Name = $name;
			else 
			{
				self::RaiseWarning("'{$name}' is not a valid name for MX record");
				$this->Error = true;
			}
				
				
			if (($this->Validator->MatchesPattern($rname, self::PAT_NON_FDQN) || 
				$this->Validator->IsDomain($rname)) && !$this->Validator->IsIPAddress(rtrim($rname, "."))
			   )
				$this->Rname = $rname;
			else 
			{
				self::RaiseWarning("'{$rname}' is not a valid value for MX record");
				$this->Error = true;
			}
			
			$this->Pref = $pref;
			
			$this->TTL = $ttl;
			
			$this->Class = $class;
		}
		
		/**
		 * Magic function __toString
		 *
		 * @return string
		 */
		function __toString()
		{
			if (!$this->Error)
			{
				$tags = array(	"{name}"		=> $this->Name,
								"{ttl}"			=> $this->TTL,
								"{rname}"		=> $this->Rname,
								"{class}"		=> $this->Class,
								"{pref}"		=> $this->Pref
							);
				
				$this->Content = str_replace(
					array_keys($tags),
					array_values($tags),
					self::DEFAULT_TEMPLATE
				);
				
				return $this->Content;
			}
			else 
				return "";
		}
	}
	
?>
