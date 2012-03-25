<?php

class Scalr_Messaging_Msg_ScalarizrUpdateAvailable extends Scalr_Messaging_Msg {
	public $version;
	
	function __construct ($version=null) {
		parent::__construct();
		$this->version = $version;
	}	
}