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
     * @name       ADNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @author Alex Kovalyov <http://webta.net/company.html>
     */
	class ADNSRecord extends DNSRecord
	{
		
		public $Name;
		public $IP;
		public $TTL;
		public $Class;
		public $Content;
		private $Error;
		public $Type;
		
		const DEFAULT_TEMPLATE = "{name} {ttl} {class} A {ip}";
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $ip
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $ip, $ttl = false, $class = "IN")
		{
			parent::__construct();
			
			$this->Type = "A";
			
			// Name
			if (($this->Validator->MatchesPattern($name, self::PAT_NON_FDQN) || 
				$name == "@" || 
				$name === "" || 
				$name == "*" ||
				$this->Validator->MatchesPattern($name, "/^\*\.[A-Za-z0-9]+[A-Za-z0-9\-\.]+[A-Za-z]+\.$/") ||
				$this->Validator->IsDomain($name)) && !$this->Validator->IsIPAddress(rtrim($name, "."))
			   )
				$this->Name = $name;
			else 
			{
				self::RaiseWarning("'{$name}' is not a valid name for A record");
				$this->Error = true;
			}
				
				
			if (!$this->Validator->IsIPAddress($ip))
			{
				self::RaiseWarning("'{$ip}' is not a valid ip address for A record");
				$this->Error = true;	
			}
			else 
				$this->IP = $ip;
				
			$this->TTL = $ttl;
			
			$this->Class = $class;

		}
		
		function __toString()
		{
			if ($this->Error !== true)
			{
				$tags = array(	"{name}"		=> $this->Name,
								"{ttl}"			=> $this->TTL,
								"{ip}"			=> $this->IP,
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
