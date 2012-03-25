<?php

class Scalr_Messaging_Msg_UpdateServiceConfiguration extends Scalr_Messaging_Msg {
	public $behaviour;
	public $resetToDefaults;
	public $restartService;
	
	function __construct ($behaviour=null, $resetToDefaults=null, $restartService=null) {
		parent::__construct();
		$this->behaviour = $behaviour;
		$this->resetToDefaults = $resetToDefaults;
		$this->restartService = $restartService;
	}	
}