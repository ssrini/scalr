<?php

class Scalr_Messaging_Msg_IpAddressChanged extends Scalr_Messaging_Msg {
	public $behaviour;
	public $localIp;
	public $remoteIp;
	public $roleName;
	public $newRemoteIp;

	function __construct ($behaviour=null, $roleName=null, $localIp=null, $remoteIp=null, $newRemoteIp=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->roleName = $roleName;
		$this->localIp = $localIp;
		$this->remoteIp = $remoteIp;
		$this->newRemoteIp = $newRemoteIp;
	}		
}