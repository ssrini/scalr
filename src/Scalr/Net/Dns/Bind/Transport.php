<?php
	interface Scalr_Net_Dns_Bind_Transport
	{
		public function rndcStatus();
		
		public function rndcReload();
		
		public function getNamedConf();
		
		public function setNamedConf($content);
		
		public function uploadZoneDbFile($zone_name, $content);
		
		public function removeZoneDbFile($zone_name);	
	}