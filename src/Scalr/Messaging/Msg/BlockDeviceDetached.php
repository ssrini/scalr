<?php

class Scalr_Messaging_Msg_BlockDeviceDetached extends Scalr_Messaging_Msg {
	public $deviceName;
	public $behaviour;
	public $localIp;
	public $remoteIp;
	public $roleName;
	
	function __construct ($behaviour=null, $roleName=null, $localIp=null, $remoteIp=null, $deviceName=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->roleName = $roleName;
		$this->localIp = $localIp;
		$this->remoteIp = $remoteIp;
		$this->deviceName = $deviceName;		
	}	
}