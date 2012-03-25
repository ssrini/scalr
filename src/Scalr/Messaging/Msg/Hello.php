<?php

class Scalr_Messaging_Msg_Hello extends Scalr_Messaging_Msg {
	public $cryptoKey;
	public $architecture;
	
	function __construct ($cryptoKey = null) {
		parent::__construct();
		$this->cryptoKey = $cryptoKey;
	}
}