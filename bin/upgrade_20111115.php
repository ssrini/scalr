<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20111103();
	$ScalrUpdate->Run();
	
	class Update20111103
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `services_mongodb_snapshots_map` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `farm_roleid` int(11) NOT NULL,
				  `shard_index` int(11) NOT NULL,
				  `snapshot_id` varchar(25) NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
			");
			
			$db->Execute("CREATE TABLE IF NOT EXISTS `services_mongodb_volumes_map` (
				  `id` int(11) NOT NULL AUTO_INCREMENT,
				  `farm_roleid` int(11) NOT NULL,
				  `replica_set_index` int(11) NOT NULL,
				  `shard_index` int(11) NOT NULL,
				  `volume_id` varchar(25) NOT NULL,
				  PRIMARY KEY (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;
			");
			
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