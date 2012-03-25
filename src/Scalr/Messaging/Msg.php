<?php

class Scalr_Messaging_Msg {
	
	public $messageId;

	private $messageName;	
	
	public $meta = array();
	
	public $forecastVars = array();
	
	function __construct () {
		$this->messageId = Scalr::GenerateUID();
		$this->meta[Scalr_Messaging_MsgMeta::SCALR_VERSION] = SCALR_VERSION;
	}
	
	function getName () {
		if ($this->messageName === null) {
			$this->messageName = substr(get_class($this), strlen(__CLASS__) + 1);
		}
		return $this->messageName;
	}
	
	function getTimestamp() {
		return strtotime($this->meta[Scalr_Messaging_MsgMeta::TIMESTAMP]);
	}
	
	function getServerId () {
		return $this->meta[Scalr_Messaging_MsgMeta::SERVER_ID];
	}
	
	function setServerId ($serverId) {
		$this->meta[Scalr_Messaging_MsgMeta::SERVER_ID] = $serverId;
	}
	
	static function getClassForName ($name) {
		return __CLASS__ . "_" . $name;
	}
}