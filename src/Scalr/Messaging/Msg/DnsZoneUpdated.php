<?php
class Scalr_Messaging_Msg_DnsZoneUpdated extends Scalr_Messaging_Msg {
	public $zoneName;
	
	function __construct ($zoneName=null) {
		parent::__construct();
		$this->zoneName = $zoneName;
	}
}