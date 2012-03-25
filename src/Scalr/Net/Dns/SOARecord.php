<?
	class Scalr_Net_Dns_SOARecord extends Scalr_Net_Dns_Record
	{

		const DEFAULT_REFRESH = 14400;
		const DEFAULT_RETRY = 1800;
		const DEFAULT_EXPIRY = 86400;
		const DEFAULT_MINIMUM = 10800;
		
		const DEFAULT_TEMPLATE = "
@	{ttl}	IN	SOA	{nameserver}	{email} (
	{serial}    ; serial, todays date+todays
	{refresh}   ; refresh, seconds
	{retry}     ; retry, seconds
	{expire}    ; expire, seconds
	{minimum} ) ; minimum, seconds
";
				
		public $ttl;
		public $name;
		public $nameserver;
		public $email;
		public $serial;
		public $type = "SOA";
		public $refresh;
		public $retry;
		public $expire;
		public $minimum;
			
		public function __construct($name, 
									$nameserver, 
									$email,
									$ttl = false, 
									$serial = false, 
									$refresh = false, 
									$retry = false, 
									$expire = false, 
									$minimum = false
		)
		{
							
				parent::__construct();
				
				// Defaults
				if (!$refresh)
					$refresh = self::DEFAULT_REFRESH;
				if (!$retry)
					$retry = self::DEFAULT_RETRY;	
				if (!$expire)
					$expire = self::DEFAULT_EXPIRY;	
				if (!$minimum)
					$minimum = self::DEFAULT_MINIMUM;
				if (!$serial)
				    $serial = date("Ymd")."01";
				
				// Email
				$this->email = str_replace('@', '.', $email);
				$this->email = trim($this->email, '.') . '.';
				
				// Name
				if (!$this->validator->IsDomain($name))
					throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid name for SOA record"), $name));
				else	
					$this->name = $this->dottify($name);
				
				// Nameserver
				if (!$this->validator->IsDomain($nameserver))
					throw new Scalr_Net_Dns_Exception(sprintf(_("'%s' is not a valid nameserver for SOA record"), $nameserver));
				else	
					$this->nameserver = $this->dottify($nameserver);
				
				// TTL
				$this->ttl = $ttl;
				
				// Serial
				$this->serial = $serial;
				
				// Refresh
				$this->refresh = $refresh;
				
				// Retry
				$this->retry = $retry;
				
				// Expire
				$this->expire = $expire;
				
				// Minimum
				$this->minimum = $minimum;
		}
		
		/**
		* Generate a new serial based on given one.
		*
		* This generates a new serial, based on the often used format
		* YYYYMMDDXX where XX is an ascending serial,
		* allowing up to 100 edits per day. After that the serial wraps
		* into the next day and it still works.
		*
		* @param int  $serial Current serial
		* @return int New serial
		*/
		static function raiseSerial($serial = 0)
		{
			if (substr($serial, 0, 8) == date('Ymd')) 
			{
				//Serial's today. Simply raise it.
				$serial = $serial + 1;
			} 
			elseif ($serial > date('Ymd00')) 
			{
				//Serial's after today.
				$serial = $serial + 1;
			} 
			else 
			{
				//Older serial. Generate new one.
				$serial = date('YmdH');
			}
			
			return intval($serial);
		}
		
		public function generate()
		{
			$tags = array(	
				"{name}"		=> $this->name,
				"{ttl}"			=> $this->ttl,
				"{nameserver}"	=> $this->nameserver,
				"{email}"		=> $this->email,
				"{serial}"		=> $this->serial,
				"{refresh}"		=> $this->refresh,
				"{retry}"		=> $this->retry,
				"{expire}"		=> $this->expire,
				"{minimum}"		=> $this->minimum
			);
		
			return str_replace(
				array_keys($tags),
				array_values($tags),
				self::DEFAULT_TEMPLATE
			);
		}
	}
	
?>
