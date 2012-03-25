<?php
	class Scalr_Net_Snmp_Client 
	{
		/**
		 * Default SNMP port
		 *
		 */
		const DEFAULT_PORT = 161;
		
		/**
		 * Default SNMPTrap port
		 *
		 */
		const DEFAULT_TRAP_PORT = 162;
		
		/**
		 * Connection timeout in milliseconds
		 *
		 */
		const DEFAULT_TIMEOUT = 5;
		
		/**
		 * Connection retries
		 *
		 */
		const DEFAULT_RETRIES = 3;
		
		/**
		* SNMP Connection Timeout
		* @var integer
		*/
		private $timeout;
		
		private $community;
		
		private $host;
		private $port;
		
		/**
		 * Path to snmptrap binary
		 *
		 * @todo: move to config
		 * @var string
		 */
		private static $snmpTrapPath = "/usr/bin/snmptrap";
		private static $snmpGetPath = "/usr/bin/snmpget";
		
		
		/**
		 * Set path to SNMPtrap binary
		 *
		 * @param string $path
		 */
		public static function setSNMPTrapPath($path)
		{
			self::$snmpTrapPath = $path;
		}
		
		/**
		 * Define connection target
		 *
		 * @param string $host
		 * @param int $port
		 * @param string $community
		 */
		public function connect($host, $port = 161, $community = "public", $timeout = 2, $retries = 2, $SNMP_VALUE_PLAIN = false)
		{
			if (is_null($port))
				$port = self::DEFAULT_PORT ;
			
			$this->community = $community;
			$this->host = $host;
			$this->port = $port;
			
			$this->timeout = (!$timeout) ? self::DEFAULT_TIMEOUT : $timeout;
			$this->timeout = $this->timeout*100000;
			
			$this->retries = $retries ? $retries : self::DEFAULT_RETRIES;
			
			$this->snmpValuePlain = $SNMP_VALUE_PLAIN;
			
			if ($SNMP_VALUE_PLAIN == true)
				@snmp_set_valueretrieval(SNMP_VALUE_PLAIN);
			else 
				@snmp_set_valueretrieval(SNMP_VALUE_LIBRARY);
		}		
		
		private function snmpRequest($request)
		{
			if (!$this->snmpValuePlain)
				$view_option = "-Ov -Oq";
			else
				$view_option = "-On";
			
			// -t {timeout}
			$args = "-r {$this->retries} {$view_option} -v 2c -c {$this->community} {$this->host}:{$this->port}";
			
			$cmd = self::$snmpGetPath." {$args} {$request} 2>/dev/null";
			
			$cmd = str_replace(array("\r", "\n"), "", $cmd);
			
			@exec($cmd, $retval);
			
			$retval = implode("\n", $retval);
			
			return $retval;
		}
		
		/**
		 * Get object with OID $OID
		 *
		 * @param string $OID
		 * @return string Object value
		 */
		public function get($oid)
		{
			if (is_array($oid))
				$oid = implode(' ', $oid);

			$retval = $this->snmpRequest("{$oid}");			
			return $retval;
		}
		
		public function sendTrap($trap)
		{
			@exec(self::$snmpTrapPath . ' -v 2c -c '.$this->community.' '.$this->host.':162 "" '.$trap, $retval);
			
			$retval = implode("\n", $retval);
			
			return $retval;
		}
		
		/**
		 * Do snmpwalk
		 *
		 * @param unknown_type $rootOID
		 * @return array Array of values
		 */
		public function getTree($rootOID = null)
		{
			$retval = @snmpwalk("{$this->host}:{$this->port}", $this->community, $rootOID, $this->timeout);	
			return $retval;
		}
		
		/**
		 * Do snmpwalkoid
		 *
		 * @param unknown_type $rootOID
		 * @return array Array of values
		 */
		public function getFullTree($rootOID = null)
		{
			$retval = @snmpwalkoid("{$this->host}:{$this->port}", $this->community, $rootOID, $this->timeout);
			return $retval;
		}
	}
?>