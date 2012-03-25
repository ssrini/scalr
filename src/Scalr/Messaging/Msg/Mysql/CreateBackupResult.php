<?php

class Scalr_Messaging_Msg_Mysql_CreateBackupResult extends Scalr_Messaging_Msg {
	
	public $status;
	public $lastError;
	public $backupUrls;
	
	function __construct () {
		parent::__construct();		
	}
}