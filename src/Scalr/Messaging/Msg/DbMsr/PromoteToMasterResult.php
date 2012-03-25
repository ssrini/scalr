<?php

class Scalr_Messaging_Msg_DbMsr_PromoteToMasterResult extends Scalr_Messaging_Msg_DbMsr {
	

	const STATUS_OK = "ok";
	const STATUS_FAILED = "error";	
	
	public $status;
	public $lastError;
	
	public $postgresql;
	public $redis;
	public $mysql;
	
	function __construct () {
		parent::__construct();
	}	
}