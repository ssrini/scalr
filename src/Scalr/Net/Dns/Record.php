<?
class Scalr_Net_Dns_Record
{
	
	/**
	 * Validator 
	 */
	protected $validator;
	
	public $defaultTTL;
	
	const PAT_NON_FDQN = '/^[A-Za-z0-9]+([A-Za-z0-9-]*[A-Za-z0-9]+)?$/';
	const PAT_CIDR = '/^([0-9]{1,3}\.){3}[0-9]{1,3}\/[0-9]{1,3}$/si';
	
	function __construct ()
	{
		$this->defaultTTL = 14400;
		$this->validator = new Validator();
	}
	
	/**
	 * Return true if $domain is valid domain name
	 * @var string $domain Domain name
	 * @return bool
	 */
	function isDomain($domain)
	{
		return ($domain == "*") || $this->validator->isDomain($domain);
	}
	
	
	
	/**
	* Reverses IP address string for PTR record creation needs
	*
	* @param string $ip Ip address string
	* @return string Reversed IP
	* @access public
	*/
	public function reverseIP($ip)
	{
		$chunks = explode(".", $ip);
		$chunksr = array_reverse($chunks);
		$retval = implode(".", $chunksr);
		
		return ($retval);
	}
	
	
	
	/**
	* Convert a BIND-style time(1D, 2H, 15M) to seconds.
	*
	* @param string  $time Time to convert.
	* @return int    time in seconds on success, PEAR error on failure.
	*/
	function parseTimeToSeconds($time)
	{
		
		if (is_numeric($time)) 
			return $time;
		else 
		{
			
			// TODO: Add support for multiple \d\s
			$split = preg_split("/([0-9]+)([a-zA-Z]+)/", $time, -1, PREG_SPLIT_DELIM_CAPTURE | PREG_SPLIT_NO_EMPTY);
			
			if (count($split) != 2)
				throw new Scalr_Net_Dns_Exception(sprintf(_("Unable to parse time. %d"), $time));
			
			list($num, $what) = $split;
			
			switch (strtoupper($what))
			{
				case 'S': //Seconds
					$times = 1; 
					break;
					
				case 'M': //Minute
					$times = 1 * 60; 
					break;
					
				case 'H': //Hour
					$times = 1 * 60 * 60; 
					break;
					
				case 'D': //Day
					$times = 1 * 60 * 60 * 24; 
					break;
					
				case 'W': //Week
					$times = 1 * 60 * 60 * 24 * 7; 
					break;
					
				default:
					throw new Scalr_Net_Dns_Exception(sprintf(_("Unable to parse time. %d"), $time));
					break;
			}
			$time = $num * $times;
			return $time;
		}
	}
    
	
	/**
	* Append dot to the end of FQDN
	* @access public
	* @param string $domain Domain name
	* @return void
	*/ 
	public function dottify($value)
	{
		$retval = $this->unDottify($value);
		$retval .= ".";
		return $retval;
	}
	
	
	/**
	* Remove leading dot
	* @access public
	* @param string $domain Domain name
	* @return void
	*/ 
	public function unDottify($domain)
	{
		$retval = rtrim($domain, ".");
		return $retval;
	}
}
	
?>
