<?php

class Scalr_Integration_ZohoCrm_DeletedClient {

	public $ID;
	public $Fullname;
	private $settings;
	
	function SetSettingValue ($name, $value) {
		$this->settings[$name] = $value;
	}
	
	function GetSettingValue ($name) {
		return $this->settings[$name];
	}
	
	function ClearSettings ($filter) {
	}
}