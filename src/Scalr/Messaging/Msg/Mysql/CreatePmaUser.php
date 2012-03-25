<?php

class Scalr_Messaging_Msg_Mysql_CreatePmaUser extends Scalr_Messaging_Msg {

	public $farmRoleId;
	public $pmaServerIp;
	
	function __construct ($farmRoleId=null, $pmaServerIp=null) {
		parent::__construct();
		$this->farmRoleId = $farmRoleId;
		$this->pmaServerIp = $pmaServerIp;		
	}	
}