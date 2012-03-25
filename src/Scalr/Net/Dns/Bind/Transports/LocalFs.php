<?php
	class Scalr_Net_Dns_Bind_Transports_LocalFs implements Scalr_Net_Dns_Bind_Transport
	{
		/**
		 * 
		 * @var Scalr_Net_Ssh2_Client
		 */
		private $rndcPath;
		private $zonesPath;
		
		public $host;
		
		public function __construct($rndcPath, $zonesPath)
		{
			$this->logger = Logger::getLogger(__CLASS__);
			
			$this->shell = new Scalr_System_Shell();
			
			$this->rndcPath = $rndcPath;
			$this->zonesPath = $zonesPath;
					
			// COunt initial number of zones	
			$this->zonesCount = $this->rndcStatus();
			if (!$this->zonesCount)
				throw new Exception(sprintf(_("Cannot fetch RNDC status on local server")));
		}
		
		public function getNamedConf()
		{
			$retval = @file_get_contents("{$this->zonesPath}/zones.include");
			if (!$retval)
				throw new Exception("Cannot load zones.include file");
			else
				return $retval;
		}
		
		public function setNamedConf($content)
		{
			return file_put_contents("{$this->zonesPath}/zones.include", $content);
		}
		
		public function uploadZoneDbFile($zone_name, $content)
		{
			return file_put_contents("{$this->zonesPath}/{$zone_name}.db", $content);
		}
		
		public function removeZoneDbFile($zone_name)
		{
			return unlink("{$this->zonesPath}/{$zone_name}.db");
		}
		
		public function rndcReload($zone_name = "")
		{
			$retval = $this->shell->queryRaw("{$this->rndcPath} reload {$zone_name}", true);
			
			$this->logger->info("Execute rndc reload: {$retval}");
		}
		
		public function rndcStatus()
		{
			$retval = $this->shell->queryRaw("{$this->rndcPath} status", true);
			
			$this->logger->info("Execute rndc status: {$retval}");
			
			preg_match_all("/number of zones:[^0-9]([0-9]+)/", $retval, $matches);
		
			if ($matches[1][0] > 0)
				return $matches[1][0];
			else
				return false;
		}
	}