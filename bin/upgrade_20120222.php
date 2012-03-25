<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20120222();
	$ScalrUpdate->Run();
	
	class Update20120222
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `account_user_dashboard` (
				  `user_id` int(11) NOT NULL,
				  `env_id` int(11) NOT NULL,
				  `value` text NOT NULL,
				   UNIQUE (`user_id`, `env_id`),
				   FOREIGN KEY (`user_id`) REFERENCES  `account_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
				   FOREIGN KEY (`env_id`) REFERENCES  `client_environments` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION
				) ENGINE=InnoDB;
				");

			$db->Execute("ALTER TABLE  `services_mongodb_snapshots_map` ADD UNIQUE  `main` (  `farm_roleid` ,  `shard_index` )");
			$db->Execute("ALTER TABLE  `storage_snapshots` ADD  `cloud_location` VARCHAR( 20 ) NULL");
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `server_operations` (
			  `id` varchar(36) NOT NULL DEFAULT '',
			  `server_id` varchar(36) NOT NULL DEFAULT '',
			  `name` varchar(50) DEFAULT NULL,
			  `phases` text,
			  UNIQUE KEY `id` (`id`),
			  UNIQUE KEY `server_id` (`server_id`,`name`(20))
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;
			");
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `server_operation_progress` (
			  `operation_id` varchar(36) NOT NULL,
			  `timestamp` int(11) DEFAULT NULL,
			  `phase` varchar(100) NOT NULL,
			  `step` varchar(100) NOT NULL,
			  `status` varchar(15) NOT NULL,
			  `progress` int(11) DEFAULT NULL,
			  `stepno` int(11) DEFAULT NULL,
			  `message` text,
			  `trace` text,
			  `handler` varchar(255) DEFAULT NULL,
			  UNIQUE KEY `unique` (`operation_id`,`phase`,`step`),
			  KEY `operation_id` (`operation_id`),
			  KEY `timestamp` (`timestamp`)
			) ENGINE=InnoDB DEFAULT CHARSET=latin1;
			");
			
			$db->Execute("ALTER TABLE `server_operations` ADD CONSTRAINT `server_operations_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`server_id`) ON DELETE CASCADE ON UPDATE NO ACTION;");
			$db->Execute("ALTER TABLE `server_operation_progress` ADD CONSTRAINT `server_operation_progress_ibfk_1` FOREIGN KEY (`operation_id`) REFERENCES `server_operations` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;");
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>