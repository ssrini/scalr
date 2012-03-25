<?
	class Scalr_Net_Dns_SRVRecord extends Scalr_Net_Dns_Record
	{
		public $name;
		public $priority;
		public $weight;
		public $port;
		public $target;
		public $ttl;
		public $type = "SRV";
		
		const DEFAULT_TEMPLATE = "{name} {ttl} IN SRV {priority} {weight} {port} {target}";
		
		/**
		 * Constructor
		 *
		 * @param string $name
		 * @param string $rname
		 * @param integer $ttl
		 * @param string $class
		 */
		function __construct($name, $value, $ttl = false, $priority = 0, $weight = 0, $port = 0)
		{
			parent::__construct();
			
			$chunks = explode(".", $name);
	    	
			$service = trim(array_shift($chunks), "_");
	    	$proto = trim(array_shift($chunks), "_");
	    	$nname = implode(".", $chunks);
			
			// Name
			if (($this->validator->MatchesPattern($nname, self::PAT_NON_FDQN) || 
				$nname === "" || 
				$this->validator->IsDomain($nname)) && !$this->validator->IsIPAddress(rtrim($nname, "."))
			   )
			   {
			   		//
			   }
			else 
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid name for SRV record"), $nname));

			if (!preg_match("/^[A-Za-z-]+$/", $service) && $service != '*')
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid service for SRV record"), $service));
			
			if ($proto != 'udp' && $proto != 'tcp')
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid protocol for SRV record"), $proto));
			
			$this->name = $name;
			
			$priority = (int)$priority;
			$weight = (int)$weight;
			$port = (int)$port;
				
			if ($priority < 0 || $priority > 65535)
                throw new Scalr_Net_Dns_Exception(sprintf(_("Priority for SRV record should be between 0 and 65535")));
			else 
                $this->priority = $priority;
                
            if ($weight < 0 || $weight > 65535)
				throw new Scalr_Net_Dns_Exception(sprintf(_("Weight for SRV record should be between 0 and 65535")));
			else 
                $this->weight = $weight;
                
            if ($port < 0 || $port > 65535)
				throw new Scalr_Net_Dns_Exception(sprintf(_("Port for SRV record should be between 0 and 65535")));
			else 
                $this->port = $port;
				
			if (($this->validator->MatchesPattern($value, self::PAT_NON_FDQN) || 
				$this->validator->IsDomain($value)) && !$this->validator->IsIPAddress(rtrim($value, "."))
			   )
				$this->target = $value;
			else 
				throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid target for SRV record"), $value));
                
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
				"{priority}"	=> $this->priority,
				"{weight}"		=> $this->weight,
				"{port}"		=> $this->port,
				"{target}"		=> $this->target,
			);
			
			return str_replace(
				array_keys($tags),
				array_values($tags),
				self::DEFAULT_TEMPLATE
			);
		}
	}
	
?>
