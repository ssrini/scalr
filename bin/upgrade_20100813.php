<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20100813();
	$ScalrUpdate->Run();
	
	class Update20100813
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			$db->BeginTrans();
			
			$db->Execute("create table `servers_history`(     `id` int(11) NOT NULL AUTO_INCREMENT ,     `client_id` int(11) ,     `server_id` varchar(36) ,     `cloud_server_id` varchar(50) ,     `dtlaunched` datetime ,     `dtterminated` datetime ,     `dtterminated_scalr` datetime ,     `terminate_reason` varchar(255) ,     PRIMARY KEY (`id`)  );");
			$db->Execute("alter table `servers_history` add index `client_id` (`client_id`);");
			$db->Execute("alter table `servers_history` add index `server_id` (`server_id`);");
			$db->Execute("alter table `servers_history`     add column `platform` varchar(20) NULL after `terminate_reason`;");
			
			//$db->RollbackTrans();
			$db->CommitTrans();
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>