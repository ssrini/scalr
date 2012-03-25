<?php

class Scalr_Messaging_Msg_Win_PrepareBundle extends Scalr_Messaging_Msg {
	
	public $bundleTaskId;
	
	function __construct ($bundleTaskId) {
		parent::__construct();
		
		$this->bundleTaskId = $bundleTaskId;
	}	
}