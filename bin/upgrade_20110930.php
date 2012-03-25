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
			
			$db->Execute("ALTER TABLE  `messages` ADD  `message_name` VARCHAR( 30 ) NULL , ADD  `message_version` INT( 2 ) NULL");
			$db->Execute("ALTER TABLE  `storage_volumes` ADD  `farm_roleid` INT( 11 ) NULL , ADD  `server_index` INT( 11 ) NULL");
			$db->Execute("ALTER TABLE  `storage_volumes` ADD  `purpose` VARCHAR( 20 ) NULL");
			
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