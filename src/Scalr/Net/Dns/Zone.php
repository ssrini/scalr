<?
	class Scalr_Net_Dns_Zone
	{	

		public $records = array();
		public $ttl;
		public $recordsSortMap;
		public $soa;
		
		private $template;
		private $soaExists;
		private $mxPrefs;
		
		const DEFAULT_ZONE_CONFIG = '
//{name}-BEGIN
zone "{name}" {
   type master;
   file "client_zones/{name}.db";
   {allow_transfer}
   {also-notify}
};
//{name}-END
';
		
		const DEFAULT_TEMPLATE = "; Zone file for {name}
\$ORIGIN {name}
{ttl}";
				
		function __construct()
		{
			$this->ttl = false;
			$this->recordsSortMap = array("SOA", "NS", "PTR", "A", "CNAME", "MX", "TXT", "SRV");
			$this->template = self::DEFAULT_TEMPLATE;
		}
			
		/**
		* Add a record
		* @access public
		* @param DNSRecord $record DNSRecord or derived object
		* @return void
		*/ 
		public function addRecord($record)
		{
			if ($record instanceof Scalr_Net_Dns_SOARecord)
				if (!$this->soaExists)
				{
					array_push($this->records, $record);
					$this->soa = &$this->records[count($this->records)-1];
					$this->soaExists = true;
				}
				else 
					throw new Scalr_Net_Dns_Exception(_("SOA record already defined"));
			else
				array_push($this->records, $record);
		}
		
		public static function validateRecords(array $records_array)
		{
			$rCache = array();
			
			foreach ($records_array as $key => $record)
			{
				if (!$record['value'] && !$record['name'])
					continue;
				
				if (!$rCache[$record['type']])
				{
					$r = new ReflectionClass("Scalr_Net_Dns_{$record['type']}Record");
					
					$params = array();
					foreach ($r->getConstructor()->getParameters() as $p)
						$params[] = $p->name;
					
					$rCache[$record['type']] = array(
						'reflect'	=> $r,
						'params'	=> $params
					);
				}
				
				$args = array();
				foreach ($rCache[$record['type']]['params'] as $p)
					$args[$p] = $record[$p];
					
				try
				{
					$rCache[$record['type']]['reflect']->newInstanceArgs($args);
				}
				catch(Scalr_Net_Dns_Exception $e)
				{
					$err[$key] = $e->getMessage();
				}
			}
			
			return (count($err) == 0) ? true : $err;
		}
		
		/**
		* Generate a text zone file
		* @access public
		* @return string Zone file content
		*/ 
		public function generate($axfrAllowedHosts = '', $config_contents)
		{
			$zone_db = $this->template;
			
			if (count($this->records) == 0)
				throw new Scalr_Net_Dns_Exception(_("You should add at least one record to zone"));
				
			foreach ($this->recordsSortMap as $recordType)
			{
				foreach((array)$this->records as $record)
				{
					if ($record->type == $recordType)
					{						
						// Set TTL
						if ($this->ttl && !$record->ttl)
							$record->ttl = "";
						elseif (!$record->ttl && $recordType != "SOA")
							$record->ttl = $record->defaultTTL;
									
						// Raise Preference for MX record			
						if ($recordType == "MX")
							$record->priority = $this->raiseMXPref($record->priority);

						if ($recordType == "SOA")
							$soa = $record;

						if (!$config_contents)
							$zone_db .= $record->generate()."\n";
					}						
				}
			}
			
			if (!$config_contents)
			{
				$tags = array("{name}" => $soa->name);
				if ($this->ttl)
					$tags["{ttl}"] = '$TTL '.$this->ttl;
				else 
					$tags["{ttl}"] = "";
				
				$zone_db = str_replace(
					array_keys($tags),
					array_values($tags),
					$zone_db
				);
			}
			else
			{
				$ctags = array(
					"{name}" => trim($soa->name, "."), 
					"{allow_transfer}" => 'allow-transfer { none; };', 
					"{also-notify}" => ''
				);
				if ($axfrAllowedHosts)
				{
					$ctags['{allow_transfer}'] = "allow-transfer { {$axfrAllowedHosts}; };";
					$ctags['{also-notify}'] = "also-notify { {$axfrAllowedHosts}; };";
				}
				
				$config = self::DEFAULT_ZONE_CONFIG;
				$config = str_replace(
					array_keys($ctags),
					array_values($ctags),
					$config
				);
			}
			
			return ($config_contents) ? $config : $zone_db;	
		}		
		
		/**
		* Raise MX pref on 10
		*
		* @param string $pref Preferences
		* @return string Reversed IP
		* @access protected
		*/
		protected function raiseMXPref($pref)
		{
			$pref = (int)$pref;
			
			if ($pref)
				return $pref;
			
			// Increase forcefully in case if this pref already assigned
			// to another MX record or pref is not set (default)
			if (count($this->maxPrefs))
			{
				if (in_array($pref, $this->maxPrefs) || !$pref)
					$retval = max($this->maxPrefs) + 10;
				else
					$retval = $pref;
			}
			else
				$retval = $pref;
				
			// Add this new pref to stack
			$this->maxPrefs[] = $retval;
			return($retval);
		}
		
	}
	
?>
