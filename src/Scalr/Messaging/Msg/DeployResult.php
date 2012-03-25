<?php

class Scalr_Messaging_Msg_DeployResult extends Scalr_Messaging_Msg {
	
	/**
	 * [ok|error]
	 * @var string
	 */
	public $status;
    public $deployTaskId;
    public $lastError;
    
	function __construct ($deployTaskId, $status, $lastError=null) {
		parent::__construct();
		$this->deployTaskId= $deployTaskId;
		$this->status = $status;
		$this->lastError = $lastError;
	}
}