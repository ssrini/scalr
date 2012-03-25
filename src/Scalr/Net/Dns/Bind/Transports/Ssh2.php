<?php
	class Scalr_Net_Dns_Bind_Transports_Ssh2 implements Scalr_Net_Dns_Bind_Transport
	{
		/**
		 * 
		 * @var Scalr_Net_Ssh2_Client
		 */
		private $ssh2Client;
		private $rndcPath;
		private $zonesPath;
		
		public $host;
		
		public function __construct(Scalr_Net_Dns_Bind_Transports_Ssh2_AuthInfo $authInfo, $host, $port, $rndcPath, $zonesPath)
		{
			$this->ssh2Client = new Scalr_Net_Ssh2_Client();
			$this->logger = Logger::getLogger(__CLASS__);
			
			$this->rndcPath = $rndcPath;
			$this->zonesPath = $zonesPath;
			$this->host = $host;
			
			switch($authInfo->getType())
			{
				case Scalr_Net_Dns_Bind_Transports_Ssh2_AuthInfo::TYPE_PASSWORD:
					$this->ssh2Client->addPassword($authInfo->login, $authInfo->password);
					break;
					
				case Scalr_Net_Dns_Bind_Transports_Ssh2_AuthInfo::TYPE_PUBKEY:
					$this->ssh2Client->addPubkey($authInfo->login, $authInfo->pubKeyPath, $authInfo->privKeyPath, $authInfo->keyPassword);
					break;
			}
			
			try
			{
				$this->ssh2Client->connect($host, $port);
			}
			catch(Scalr_Net_Ssh2_Exception $e)
			{
				throw new Exception("Unable to initialize SSH2 Transport: {$e->getMessage()}");
			}
					
			// COunt initial number of zones	
			$this->zonesCount = $this->rndcStatus();
			if (!$this->zonesCount)
				throw new Exception(sprintf(_("Cannot fetch RNDC status on %s"), $host));
		}
		
		public function getNamedConf()
		{
			$retval = $this->ssh2Client->getFile("{$this->zonesPath}/zones.include");
			if (!$retval)
				throw new Exception("Cannot load zones.include file");
			else
				return $retval;
		}
		
		public function setNamedConf($content)
		{
			return $this->ssh2Client->sendFile("{$this->zonesPath}/zones.include", $content, "w+", false);
		}
		
		public function uploadZoneDbFile($zone_name, $content)
		{
			return $this->ssh2Client->sendFile("{$this->zonesPath}/{$zone_name}.db", $content, "w+", false);
		}
		
		public function removeZoneDbFile($zone_name)
		{
			return $this->ssh2Client->exec("rm -f {$this->zonesPath}/{$zone_name}.db");
		}
		
		public function rndcReload()
		{
			$retval = $this->ssh2Client->exec("{$this->rndcPath} reload");
			
			$this->logger->info("Execute rndc reload: {$retval}");
		}
		
		public function rndcStatus()
		{
			$retval = $this->ssh2Client->exec("{$this->rndcPath} status");
				
			$this->logger->info("Execute rndc status: {$retval}");
			
			preg_match_all("/number of zones:[^0-9]([0-9]+)/", $retval, $matches);
		
			if ($matches[1][0] > 0)
				return $matches[1][0];
			else
				return false;
		}
	}