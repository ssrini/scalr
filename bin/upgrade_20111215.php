<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20111215();
	$ScalrUpdate->Run();
	
	class Update20111215
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->Execute("CREATE TABLE `services_chef_runlists` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `env_id` int(11) DEFAULT NULL,
				  `chef_server_id` int(11) DEFAULT NULL,
				  `name` varchar(30) NOT NULL,
				  `description` varchar(255) NOT NULL,
				  `runlist` text,
				  `attributes` text,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
			");
			
			$db->Execute("CREATE TABLE `services_chef_servers` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `env_id` int(11) DEFAULT NULL,
				  `url` varchar(255) DEFAULT NULL,
				  `username` varchar(50) DEFAULT NULL,
				  `auth_key` text,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
			");
			
			$db->Execute("DROP TABLE IF EXISTS `services_mongodb_cluster_log`");
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `services_mongodb_cluster_log` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `farm_roleid` int(11) DEFAULT NULL,
				  `severity` enum('INFO','WARNING','ERROR') DEFAULT NULL,
				  `dtadded` datetime DEFAULT NULL,
				  `message` text,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;
			");
			
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>