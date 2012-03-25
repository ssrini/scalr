<?php

class Scalr_Messaging_Msg_MongoDb_SetupControlPanelResult extends Scalr_Messaging_Msg_MongoDb {

	public $cpanelUrl;
	public $lastError;
	public $status;
	
	function __construct () {
		parent::__construct();	
	}	
}