<?php

class Scalr_Messaging_Msg_Win_PrepareBundleResult extends Scalr_Messaging_Msg {
	
	public $bundleTaskId;
	public $status;
	public $lastError;
	public $os;
	
	function __construct () {
		parent::__construct();
	}	
}