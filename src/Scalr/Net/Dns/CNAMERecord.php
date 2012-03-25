<?
	class Scalr_Net_Dns_CNAMERecord extends Scalr_Net_Dns_Record
	{
		
		public $name;
		public $cname;
		public $ttl;
		public $type;
		
		const DEFAULT_TEMPLATE = "{name} {ttl} IN CNAME {cname}";
		
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
			
			$this->type = "CNAME";
			
			// Name
			if ($this->validator->MatchesPattern($name, self::PAT_NON_FDQN) ||
			   $this->validator->MatchesPattern($name, '/^\*\.[A-Za-z0-9]+$/si') ||
			   //default._domainkey.vip
			   $this->validator->MatchesPattern($name, '/^[A-Za-z0-9]+[_\.A-Za-z0-9-]*[A-Za-z0-9]+$/si') ||
				($this->validator->IsDomain($name)) && !$this->validator->IsIPAddress(rtrim($name, ".")) || $name == "*")
				$this->name = $name;
			else 
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid name for CNAME record"), $name));
			
			// cname
			if (!$this->validator->IsDomain($value))
			{
				if ($this->validator->MatchesPattern($value, self::PAT_NON_FDQN) || 
				$this->validator->MatchesPattern($value, '/^[A-Za-z0-9]+[_\.A-Za-z0-9-]*[A-Za-z0-9]+[\.]*$/si'))
					$this->cname = $value;
				else
					 throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid value for CNAME record"), $value));
			}
			else 
				$this->cname = $this->dottify($value);
				
			$this->ttl = $ttl;
		}
		
		/**
		 * __ToString Magic function
		 *
		 * @return string
		 */
		function generate()
		{
			$tags = array(	
				"{name}"	=> $this->name,
				"{ttl}"		=> $this->ttl,
				"{cname}"	=> $this->cname
			);
			
			return str_replace(
				array_keys($tags),
				array_values($tags),
				self::DEFAULT_TEMPLATE
			);
		}
	}
	
?>
