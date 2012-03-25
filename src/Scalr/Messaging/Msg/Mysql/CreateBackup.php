<?php

class Scalr_Messaging_Msg_Mysql_CreateBackup extends Scalr_Messaging_Msg {
	
	public $rootPassword;
	
	function __construct ($rootPassword) {
		parent::__construct();		
		$this->rootPassword = $rootPassword;
	}
}