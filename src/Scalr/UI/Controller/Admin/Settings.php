<?php

class Scalr_UI_Controller_Admin_Settings extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		return $this->user && ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN);
	}
	
	public function coreAction()
	{
		$config = array();
		foreach ($this->db->GetAll("select * from config") as $cfg)
			$config[$cfg["key"]] = $cfg["value"];
		$this->response->page('ui/admin/settings/core.js', array('config'=>$config));
	}
	
	public function xSaveAction()
	{
		$result = $this->db->GetAll("select * from config");
		foreach ($result as $cfg) {
			$keyValue = $this->getParam($cfg["key"]);
			if($keyValue !== NULL)
				if(
			  		(empty($keyValue) && !empty($cfg["value"])) ||
				  	(!empty($keyValue) && empty($cfg["value"])) ||
				  	($keyValue != $cfg["value"] && !empty($cfg["value"]) && !empty($keyValue))
			  	)
					$this->db->Execute('UPDATE config SET `value` = ? WHERE `key`=?', array($keyValue, $cfg["key"]));
		}
		$this->response->success('Settings successfully saved');
	}
}
