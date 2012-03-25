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
     * @name       TXTDNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class TXTDNSRecord extends DNSRecord
	{
		
		public $Name;
		public $Value;
		public $TTL;
		public $Class;
		public $Content;
		private $Error;
		public $Type;
		
		const DEFAULT_TEMPLATE = "{name} {ttl} {class} TXT \"{value}\"";
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $rname
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $value, $ttl = false, $class = "IN")
		{
			parent::__construct();
			
			$this->Type = "TXT";
			
			// Name
			
			$vname = str_replace("_", "", $name);
			
			if (($this->Validator->MatchesPattern($vname, self::PAT_NON_FDQN) || 
				$vname == "@" || 
				$vname === "" || 
				$this->Validator->IsDomain($vname)) && !$this->Validator->IsIPAddress(rtrim($vname, "."))
			   )
				$this->Name = $name;
			else 
			{
				self::RaiseWarning("'{$name}' is not a valid name for TXT record");
				$this->Error = true;
			}
				
				
			if (strlen($value) > 255)
			{
                self::RaiseWarning("TXT record value cannot be longer than 65536 bytes");
                $this->Error = true;
			}
			else 
                $this->Value = $value;
				
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
								"{value}"		=> $this->Value,
								"{class}"		=> $this->Class
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
