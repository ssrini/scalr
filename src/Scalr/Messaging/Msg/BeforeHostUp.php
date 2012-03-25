<?php

class Scalr_Messaging_Msg_BeforeHostUp extends Scalr_Messaging_Msg {
	public $behaviour;
	public $localIp;
	public $remoteIp;
	public $roleName;
	
	function __construct ($behaviour=null, $roleName=null, $localIp=null, $remoteIp=null, $serverId=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->roleName = $roleName;
		$this->localIp = $localIp;
		$this->remoteIp = $remoteIp;
		$this->serverId = $serverId;
	}	
}