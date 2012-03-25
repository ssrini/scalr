<?php

class Scalr_Messaging_Msg_BlockDeviceAttached extends Scalr_Messaging_Msg {
	public $volumeId;	
	public $deviceName;
	public $behaviour;
	public $localIp;
	public $remoteIp;
	public $roleName;
	
	function __construct ($behaviour=null, $roleName=null, $localIp=null, $remoteIp=null, $volumeId=null, $deviceName=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->roleName = $roleName;
		$this->localIp = $localIp;
		$this->remoteIp = $remoteIp;
		$this->volumeId = $volumeId;
		$this->deviceName = $deviceName;
	}	
}