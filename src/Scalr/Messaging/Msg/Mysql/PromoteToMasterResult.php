<?php

class Scalr_Messaging_Msg_Mysql_PromoteToMasterResult extends Scalr_Messaging_Msg {
	const STATUS_OK = "ok";
	const STATUS_FAILED = "error";	
	
	/**
	 * @var string ok|error 
	 */
	public $status;
	
	public $volumeId;
	
	public $lastError;
	
	function __construct () {
		parent::__construct();
	}	
}