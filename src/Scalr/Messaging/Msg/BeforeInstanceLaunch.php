<?php

class Scalr_Messaging_Msg_BeforeInstanceLaunch extends Scalr_Messaging_Msg {
	public $behaviour;
	public $roleName;
	
	function __construct ($behaviour=null, $roleName=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->roleName = $roleName;
	}
	
}