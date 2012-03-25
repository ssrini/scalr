<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20110914();
	$ScalrUpdate->Run();
	
	class Update20110914
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `account_user_settings` (
			  	`id` int(11) NOT NULL AUTO_INCREMENT,
			  	`user_id` int(11) DEFAULT NULL,
			  	`name` varchar(255) DEFAULT NULL,
			  	`value` varchar(255) DEFAULT NULL,
			  	PRIMARY KEY (`id`),
			  	UNIQUE KEY `userid_name` (`user_id`,`name`)
				) ENGINE=InnoDB DEFAULT CHARSET=latin1;
			");
			
			//$db->Execute("ALTER TABLE  `account_groups` CHANGE  `isactive`  `is_active` TINYINT( 4 ) NULL DEFAULT  '1'");
			$db->Execute("ALTER TABLE  `storage_snapshots` ADD  `service` VARCHAR( 50 ) NULL");
			
			$account_users = $db->Execute("SELECT id FROM account_users");
			while ($u = $account_users->FetchRow()) {
				$user = Scalr_Account_User::init()->loadById($u['id']);
				if (!$user->getSetting(Scalr_Account_User::SETTING_API_ACCESS_KEY))
				{
					$environmentId = $db->GetOne("SELECT id FROM client_environments WHERE client_id=?", array($user->getAccountId()));
					if ($environmentId)
					{
						$ScalrKeyID = $db->GetOne("SELECT value FROM client_environment_properties WHERE env_id=? AND `name` =?", array($environmentId, 'api.keyid'));
						$ScalrKey = $db->GetOne("SELECT value FROM client_environment_properties WHERE env_id=? AND `name` =?", array($environmentId, 'api.access_key'));
						
						$keys = array("id" => $ScalrKeyID, "key" => $ScalrKey);
						
						$isEnabled = $db->GetOne("SELECT value FROM client_environment_properties WHERE env_id=? AND `name` =?", array($environmentId, 'api.enabled'));
						$ips = $db->GetOne("SELECT value FROM client_environment_properties WHERE env_id=? AND `name` =?", array($environmentId, 'api.allowed_ips'));
						
						$user->setSetting(Scalr_Account_User::SETTING_API_ENABLED, $isEnabled);
						$user->setSetting(Scalr_Account_User::SETTING_API_SECRET_KEY, $ips);
					}
					else
					{
						$keys = Scalr::GenerateAPIKeys();
					}
					
					$user->setSetting(Scalr_Account_User::SETTING_API_ACCESS_KEY, $keys['id']);
					$user->setSetting(Scalr_Account_User::SETTING_API_SECRET_KEY, $keys['key']);
				}
				
				if ($user->getAccountId() != 0)
				{
					$rssLogin = $user->getAccount()->getSetting(CLIENT_SETTINGS::RSS_LOGIN);
					$rssPass = $user->getAccount()->getSetting(CLIENT_SETTINGS::RSS_PASSWORD);
					
					$user->setSetting(Scalr_Account_User::SETTING_RSS_LOGIN, $rssLogin);
					$user->setSetting(Scalr_Account_User::SETTING_RSS_PASSWORD, $rssPass);
				}
			}
			
			
			//$db->RollbackTrans();
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>