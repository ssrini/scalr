<?php

class Scalr_Messaging_Msg_UpdateSshAuthorizedKeys extends Scalr_Messaging_Msg {
	public $add;
	public $remove;
	
	function __construct (array $add=array(), array $remove=array()) {
		parent::__construct();
		$this->add = $add;
		$this->remove = $remove;
	}		
}