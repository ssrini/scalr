<?php

class Scalr_Messaging_Msg_UpdateServiceConfigurationResult extends Scalr_Messaging_Msg {
	public $status;
	public $lastError;
	public $preset;
	public $behavior;
	
	
	function __construct ($status, $lastError, $preset, $behaviour) {
		parent::__construct();
		$this->status = $status;
		$this->lastError = $lastError;
		$this->preset = $preset;
		$this->behavior = $behaviour;
	}	
}