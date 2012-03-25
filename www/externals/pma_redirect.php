<?php

	require(dirname(__FILE__)."/../src/prepend.inc.php");
	
	if (!Scalr_Session::getInstance()->getAuthToken()->hasAccess(Scalr_AuthToken::ACCOUNT_USER)) {
		$errmsg = _("You have no permissions for viewing requested page");
		UI::Redirect("/#/dashboard");
	}

	if ($req_c) {
		switch ($req_c) {
			case 1:
				$errmsg = _("Scalr unable to create PMA session with your MySQL server. Please re-setup PMA access once again.");				
				break;
			case 2:
				$errmsg = _("Scalr unable to create PMA session with your MySQL server. Please try again.");
				break;
		}
		
		if ($req_f)
			$url = "/farm_mysql_info.php?farmid={$req_f}";
		else
			$url = "/#/dashboard";
			
		UI::Redirect($url);
	}
	
	if ($req_farmid) {
		$DBFarm = DBFarm::LoadByID($req_farmid);
		
		if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBFarm->EnvID))
			UI::Redirect("/#/dashboard");
	}
	else
		UI::Redirect("/#/dashboard");
		
	$Crypto = Core::GetInstance("Crypto", "123");
		
	$servers = $DBFarm->GetMySQLInstances(true);
	$DBServer = $servers[0];	
	
	if ($DBServer) {
		$DBFarmRole = $DBServer->GetFarmRoleObject();
		
		if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_USER))
		{
			$r = array();
			define('PMA_KEY', '!80uy98hH&)#0gsg695^39gsvt7s853r%#dfscvJKGSG67gVB@');
			$r['s'] = md5(mt_rand());
			$key = substr($r['s'], 5).PMA_KEY;
			$r['r'] = $Crypto->encrypt(serialize(array(
				'user' => $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_USER), 
				'password' => $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_PASS), 
				'host' => $DBServer->remoteIp
			)), $key);
			$r['h'] = md5($r['r'].$r['s'].PMA_KEY);
			$r['f'] = $DBFarm->ID;
			
			$query = http_build_query($r);
						
			UI::Redirect("http://phpmyadmin.scalr.net/auth/pma_sso.php?{$query}");
		}
		else
		{
			$okmsg = _("There is no MySQL access credentials for PMA");
			UI::Redirect("/farm_mysql_info.php?farmid={$req_farmid}");
		}
	}
	else
	{
		$errmsg = _("There is no running MySQL master. Please wait until master starting up.");
		UI::Redirect("/farm_mysql_info.php?farmid={$req_farmid}");
	}
?>