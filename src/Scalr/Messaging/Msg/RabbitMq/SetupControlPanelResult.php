<?php

class Scalr_Messaging_Msg_RabbitMq_SetupControlPanelResult extends Scalr_Messaging_Msg {

	public $username;
	public $password;
	public $cpanelUrl;
	public $lastError;
	public $status;
	
	function __construct () {
		parent::__construct();	
	}	
}