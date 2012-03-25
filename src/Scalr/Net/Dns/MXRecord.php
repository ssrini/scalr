<?
	class Scalr_Net_Dns_MXRecord extends Scalr_Net_Dns_Record
	{
		public $mame;
		public $rname;
		public $ttl;
		public $priority;
		public $type = "MX";
		
		const DEFAULT_TEMPLATE = "{name} {ttl} IN MX {priority} {rname}";
		
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $rname
		 * @param integer $pref
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $value, $ttl = false, $priority = 10)
		{
			parent::__construct();
			
			// Name
			if (($this->validator->MatchesPattern($name, self::PAT_NON_FDQN) || 
				$name == "@" || 
				$name === "" || 
				$this->validator->IsDomain($name)) && !$this->validator->IsIPAddress(rtrim($name, "."))
			   )
				$this->name = $name;
			else 
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid name for MX record"), $name));
				
				
			if (($this->validator->MatchesPattern($value, self::PAT_NON_FDQN) || 
				$this->validator->IsDomain($value)) && !$this->validator->IsIPAddress(rtrim($value, "."))
			   )
				$this->rname = $value;
			else 
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid value for MX record"), $value));
			
			$this->priority = $priority;
			$this->ttl = $ttl;
		}
		
		/**
		 * Magic function __toString
		 *
		 * @return string
		 */
		function generate()
		{
			$tags = array(	
				"{name}"		=> $this->name,
				"{ttl}"			=> $this->ttl,
				"{rname}"		=> $this->rname,
				"{priority}"	=> $this->priority
			);
			
			return str_replace(
				array_keys($tags),
				array_values($tags),
				self::DEFAULT_TEMPLATE
			);
		}
	}
	
?>
