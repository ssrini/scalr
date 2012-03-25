<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20110922();
	$ScalrUpdate->Run();
	
	class Update20110922
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			//$db->Execute("ALTER TABLE  `client_environments` ADD  `color` VARCHAR( 6 ) NULL");
			//$db->Execute("ALTER TABLE  `account_teams` ADD  `description` VARCHAR( 255 ) NULL");
			$db->Execute("ALTER TABLE  `account_team_users` CHANGE  `is_owner`  `permissions` VARCHAR( 10 ) NULL");
			
			$db->Execute("UPDATE account_team_users SET permissions = 'owner'");
			
			
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