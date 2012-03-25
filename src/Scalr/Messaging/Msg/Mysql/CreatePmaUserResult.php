<?php

class Scalr_Messaging_Msg_Mysql_CreatePmaUserResult extends Scalr_Messaging_Msg {
	/**
	 * @var string ok|error
	 */
	public $status;
	
	public $lastError;
	
	public $pmaUser;
	
	public $pmaPassword;	
	
	public $farmRoleId;
	
	function __construct ($farmRoleId=null, $pmaUser=null, $pmaPassword=null, $lastError=null, $status=null) {
		parent::__construct();
		$this->status = $status;
		$this->lastError = $lastError;
		$this->pmaUser = $pmaUser;
		$this->pmaPassword = $pmaPassword;
		$this->farmRoleId = $farmRoleId;
	}
}