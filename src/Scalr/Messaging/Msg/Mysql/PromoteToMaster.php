<?php

class Scalr_Messaging_Msg_Mysql_PromoteToMaster extends Scalr_Messaging_Msg {
	public $rootPassword;
	public $replPassword;
	public $statPassword;
	
	/**
		@deprecated
	 */
	public $volumeId;
	
	public $volumeConfig;
	
	function __construct ($rootPassword=null, $replPassword=null, $statPassword=null, $volumeConfig=null) {
		parent::__construct();
		$this->rootPassword = $rootPassword;
		$this->replPassword = $replPassword;
		$this->statPassword = $statPassword;
		
		$this->volumeConfig = $volumeConfig;
	}
}