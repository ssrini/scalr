<?php

class Scalr_Messaging_Msg_Log extends Scalr_Messaging_Msg {
	public $entries;
	
	function __construct ($entries=null) {
		parent::__construct();
		$this->entries = $entries;
	}	
}
