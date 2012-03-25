<?
	class Scalr_Net_Dns_NSRecord extends Scalr_Net_Dns_Record
	{
		public $name;
		public $rname;
		public $ttl;
		public $type = "NS";
		
		const DEFAULT_TEMPLATE = "{name} {ttl} IN NS {rname}";

		function __construct($name, $value, $ttl = false, $class = "IN")
		{
			parent::__construct();
			
			// Name
			if (!$this->validator->IsDomain($name))
			{
				if ($name == "@" || $name === "")
					$this->name = $name;
				else
					throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid name for NS record"), $name)); 
			}
			elseif (!$this->validator->IsIPAddress(rtrim($name, ".")))
				$this->name = $this->dottify($name);
			else 
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid name for NS record"), $name));
				
				
			if (!$this->validator->IsDomain($value))
			{
				if ($this->validator->MatchesPattern($value, self::PAT_NON_FDQN) || 
					$this->validator->IsIPAddress($value)
				   )
					$this->rname = $value;
				else 
					throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid value for NS record"), $value));
			}
			else 
				$this->rname = $value;
				
			$this->ttl = $ttl;
		}
		
		function generate()
		{
			$tags = array(	
				"{name}"	=> $this->name,
				"{ttl}"		=> $this->ttl,
				"{rname}"	=> $this->rname
			);
			
			return str_replace(
				array_keys($tags),
				array_values($tags),
				self::DEFAULT_TEMPLATE
			);
		}
	}
	
?>
