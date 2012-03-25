<?php

class Scalr_Messaging_Msg_HostInitResponse extends Scalr_Messaging_Msg {
	public $farmCryptoKey;
		
	function __construct ($farmCryptoKey, $serverIndex) {
		parent::__construct();
		$this->farmCryptoKey = $farmCryptoKey;
		$this->serverIndex = $serverIndex;
	}
	
	function addDbMsrInfo(Scalr_Db_Msr_Info $msrInfo)
	{
		$this->dbType = $msrInfo->databaseType;
		$this->{$msrInfo->databaseType} = $msrInfo->getMessageProperties();
	}
}