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
     * @name       CNAMEDNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class CNAMEDNSRecord extends DNSRecord
	{
		
		public $Name;
		public $Cname;
		public $TTL;
		public $Class;
		public $Content;
		private $Error;
		public $Type;
		
		const DEFAULT_TEMPLATE = "{name} {ttl} {class} CNAME {cname}";
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $rname
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $cname, $ttl = false, $class = "IN")
		{
			parent::__construct();
			
			$this->Type = "CNAME";
			
			// Name
			if ($this->Validator->MatchesPattern($name, self::PAT_NON_FDQN) ||
				($this->Validator->IsDomain($name)) && !$this->Validator->IsIPAddress(rtrim($name, ".")) || $name == "*")
				$this->Name = $name;
			else 
			{
				self::RaiseWarning("'{$name}' is not a valid name for CNAME record");
				$this->Error = true;
			}
			
			// cname
			if (!$this->Validator->IsDomain($cname))
			{
				if ($this->Validator->MatchesPattern($cname, self::PAT_NON_FDQN))
					$this->Cname = $cname;
				else 
				{
					self::RaiseWarning("'{$cname}' is not a valid cname for CNAME record");
					$this->Error = true;
				}
			}
			else 
				$this->Cname = $this->Dottify($cname);
				
			$this->TTL = $ttl;
			
			$this->Class = $class;
		}
		
		/**
		 * __ToString Magic function
		 *
		 * @return string
		 */
		function __toString()
		{
			if (!$this->Error)
			{
				$tags = array(	"{name}"		=> $this->Name,
								"{ttl}"			=> $this->TTL,
								"{cname}"		=> $this->Cname,
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
