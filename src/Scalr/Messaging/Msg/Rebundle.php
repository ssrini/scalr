<?php

class Scalr_Messaging_Msg_Rebundle extends Scalr_Messaging_Msg {
	public $roleName;
	public $bundleTaskId;
	public $excludes;
	
	function __construct ($bundleTaskId=null, $roleName=null, $excludes=array()) {
		parent::__construct();
		$this->bundleTaskId = $bundleTaskId;
		$this->roleName = $roleName;
		$this->excludes = $excludes;
	}		
}