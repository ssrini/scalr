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
     * @name       SRVDNSRecord
     * @category   LibWebta
     * @package    NET
     * @subpackage DNS
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class SRVDNSRecord extends DNSRecord
	{
		public $Name;
		public $Priority;
		public $Weight;
		public $Port;
		public $Target;
		public $TTL;
		public $Class;
		public $Content;
		private $Error;
		public $Type;
		
		const DEFAULT_TEMPLATE = "{name} {ttl} {class} SRV {priority} {weight} {port} {target}";
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $rname
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $target, $ttl = false, $priority = 0, $weight = 0, $port = 0, $class = "IN")
		{
			parent::__construct();
			
			$this->Type = "SRV";
			
			$chunks = explode(".", $name);
	    	
			$service = trim(array_shift($chunks), "_");
	    	$proto = trim(array_shift($chunks), "_");
	    	$nname = implode(".", $chunks);
			
			// Name
			if (($this->Validator->MatchesPattern($nname, self::PAT_NON_FDQN) || 
				$nname === "" || 
				$this->Validator->IsDomain($nname)) && !$this->Validator->IsIPAddress(rtrim($nname, "."))
			   )
			   {
			   		//
			   }
			else 
			{
				self::RaiseWarning("'{$name}' is not a valid name for SRV record");
				$this->Error = true;
			}

			if (!preg_match("/^[A-Za-z-]+$/", $service) && $service != '*')
			{
				self::RaiseWarning("'{$service}' is not a valid service name for SRV record");
				$this->Error = true;
			}
			
			if ($proto != 'udp' && $proto != 'tcp')
			{
				self::RaiseWarning("'{$proto}' is not a valid protocol name for SRV record");
				$this->Error = true;
			}
			
			if (!$this->Error)
				$this->Name = $name;
			
			$priority = (int)$priority;
			$weight = (int)$weight;
			$port = (int)$port;
				
			if ($priority < 0 || $priority > 65535)
			{
                self::RaiseWarning("Allowed range for SRV record priority is 0 - 65535");
                $this->Error = true;
			}
			else 
                $this->Priority = $priority;
                
            if ($weight < 0 || $weight > 65535)
			{
                self::RaiseWarning("Allowed range for SRV record weight is 0 - 65535");
                $this->Error = true;
			}
			else 
                $this->Weight = $weight;
                
            if ($port < 0 || $port > 65535)
			{
                self::RaiseWarning("Allowed range for SRV record port is 0 - 65535");
                $this->Error = true;
			}
			else 
                $this->Port = $port;
				
			if (($this->Validator->MatchesPattern($target, self::PAT_NON_FDQN) || 
				$this->Validator->IsDomain($target)) && !$this->Validator->IsIPAddress(rtrim($target, "."))
			   )
				$this->Target = $target;
			else 
			{
				self::RaiseWarning("'{$target}' is not a valid target for SRV record");
				$this->Error = true;
			}
                
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
								"{class}"		=> $this->Class,
								"{priority}"	=> $this->Priority,
								"{weight}"		=> $this->Weight,
								"{port}"		=> $this->Port,
								"{target}"		=> $this->Target,
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
