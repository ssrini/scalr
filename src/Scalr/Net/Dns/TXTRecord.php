<?
	class Scalr_Net_Dns_TXTRecord extends Scalr_Net_Dns_Record
	{
		
		public $name;
		public $value;
		public $ttl;
		public $type = "TXT";
		
		const DEFAULT_TEMPLATE = "{name} {ttl} IN TXT \"{value}\"";
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $rname
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $value, $ttl = false)
		{
			parent::__construct();
			
			// Name
			$vname = str_replace("_", "", $name);
			
			if (($this->validator->MatchesPattern($vname, self::PAT_NON_FDQN) || 
				$vname == "@" || 
				$vname === "" || 
				$this->validator->IsDomain($vname)) && !$this->validator->IsIPAddress(rtrim($vname, "."))
			   )
				$this->name = $name;
			else 
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid name for TXT record"), $name));
				
				
			if (strlen($value) > 255)
				throw new Scalr_Net_Dns_Exception(sprintf(_("Value fro TXT record cannot be longer than 255 chars")));
			else 
                $this->value = $value;
				
			$this->ttl = $ttl;
		}
		
		function generate()
		{
			$tags = array(	"{name}"		=> $this->name,
							"{ttl}"			=> $this->ttl,
							"{value}"		=> $this->value
						);
			
			return str_replace(
				array_keys($tags),
				array_values($tags),
				self::DEFAULT_TEMPLATE
			);
		}
	}
	
?>
