<?php

class Scalr_Messaging_Msg_RebootStart extends Scalr_Messaging_Msg {
	public $behaviour;
	public $localIp;
	public $remoteIp;
	public $roleName;
	
	function __construct ($behaviour=null, $roleName=null, $localIp=null, $remoteIp=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->roleName = $roleName;
		$this->localIp = $localIp;
		$this->remoteIp = $remoteIp;
	}		
}