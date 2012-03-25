<?php

class Scalr_Messaging_Msg_HostInit extends Scalr_Messaging_Msg {
	public $behaviour;
	public $localIp;
	public $remoteIp;
	public $roleName;
	public $cryptoKey;
	public $sshPubKey;
	public $snmpPort;
	public $snmpCommunityName;
	
	public $serverIndex;
	
	function __construct ($behaviour=null, $roleName=null, $localIp=null, $remoteIp=null, 
			$cryptoKey=null, $sshPubKey=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->roleName = $roleName;
		$this->localIp = $localIp;
		$this->remoteIp = $remoteIp;
		$this->cryptoKey = $cryptoKey;
		$this->sshPubKey = 	$sshPubKey;
	}	
}