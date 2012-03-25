<?php

class Scalr_Messaging_Msg_Mysql_NewMasterUp extends Scalr_Messaging_Msg {
	public $behaviour;
	public $localIp;
	public $remoteIp;
	public $roleName;
	public $snapPlacement;
	public $rootPassword;
	public $replPassword;
	
	function __construct ($behaviour=null, $roleName=null, $localIp=null, $remoteIp=null, $snapPlacement=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->roleName = $roleName;
		$this->localIp = $localIp;
		$this->remoteIp = $remoteIp;
		$this->snapPlacement = $snapPlacement;
	}	
}