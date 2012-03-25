<?php
class Scalr_Messaging_Msg_ExecScript extends Scalr_Messaging_Msg {
	public $eventName;
	
	function __construct ($eventName=null) {
		parent::__construct();
		$this->eventName = $eventName;
	}	
}