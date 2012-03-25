<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20110323();
	$ScalrUpdate->Run();
	
	class Update20110323
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->Execute("UPDATE client_environment_properties SET `group`='rs-ORD1' WHERE `name` LIKE 'rackspace%' AND `name` != 'rackspace.is_enabled'");
			$db->Execute("CREATE TABLE `ui_debug_log`(     `id` INT(11) NOT NULL AUTO_INCREMENT ,     `ipaddress` VARCHAR(15) ,     `dtadded` DATETIME ,     `url` VARCHAR(255) ,     `request` TEXT ,     `response` TEXT ,     `env_id` INT(11) ,     `client_id` INT(11) ,     PRIMARY KEY (`id`)  );");
			
			
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