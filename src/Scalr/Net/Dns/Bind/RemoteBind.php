<?
	class Scalr_Net_Dns_Bind_RemoteBind
	{
		/**
		 * 
		 * @var Scalr_Net_Dns_Bind_Transport
		 */
		private $transport;
		private $logger;
		
		private $namedConf = false;
		private $zonesConfig = array();
		
		function __construct()
		{
			$this->logger = Logger::getLogger(__CLASS__);
		}
		
		function setTransport(Scalr_Net_Dns_Bind_Transport $transport)
		{
			$this->transport = $transport;
			
			$this->listZones();
		}
		
		function listZones()
		{
			if (count($this->zonesConfig) == 0)
			{
				$contents = $this->transport->getNamedConf();
				preg_match_all("/\/\/(.*?)-BEGIN(.*?)\/\/\\1-END/sm", $contents, $matches);
				foreach ($matches[1] as $index=>$domain_name)
					$this->zonesConfig[$domain_name] = $matches[0][$index];
			}
			
			return array_keys($this->zonesConfig); 
		}
		
		function addZoneToNamedConf($zoneName, $content)
		{
			$this->zonesConfig[$zoneName] = $content;
		}
		
		function removeZoneFromNamedConf($zoneName)
		{
			unset($this->zonesConfig[$zoneName]);
		}
		
		function addZoneDbFile($zoneName, $content)
		{
			$this->transport->uploadZoneDbFile($zoneName, $content);
		}
		
		function removeZoneDbFile($zoneName)
		{
			$this->transport->removeZoneDbFile($zoneName);
		}
		
		function saveNamedConf()
		{
			$this->transport->setNamedConf(implode("\n", $this->zonesConfig));
			$this->reloadBind();
		}
		
		function reloadBind()
		{
			$this->transport->rndcReload();
		}
	}
?>