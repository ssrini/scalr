<?php

class Scalr_Messaging_Msg_MongoDb_SetupControlPanel extends Scalr_Messaging_Msg_MongoDb {

	public $username;
	public $password;
	
	function __construct ($username=null, $password=null) {
		parent::__construct();
		$this->username = $username;
		$this->password = $password;		
	}	
}