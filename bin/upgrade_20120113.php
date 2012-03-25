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
			
			
			//TODO:
			/*
			$db->Execute("CREATE TABLE `sys_dns_zones` (
			  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
			  `farm_id` int(11) DEFAULT NULL,
			  `account_id` int(11) DEFAULT NULL,
			  `env_id` int(11) DEFAULT NULL,
			  `zone_name` varchar(255) DEFAULT NULL,
			  `status` varchar(255) DEFAULT NULL,
			  `isonnsserver` tinyint(1) DEFAULT '0',
			  `iszoneconfigmodified` tinyint(1) DEFAULT '0',
			  PRIMARY KEY (`id`),
			  UNIQUE KEY `zones_index3945` (`zone_name`),
			  KEY `env_id` (`env_id`)
			) ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
			");
			*/
			
			
			$db->Execute("ALTER TABLE  `services_chef_runlists` ADD  `chef_environment` VARCHAR( 255 ) NULL");
			$db->Execute("ALTER TABLE  `services_chef_servers` ADD  `v_username` VARCHAR( 255 ) NULL , ADD  `v_auth_key` TEXT NULL");
			
			$db->Execute("CREATE TABLE `servers_stats` (
				  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
				  `usage` int(11) DEFAULT NULL,
				  `instance_type` varchar(15) DEFAULT NULL,
				  `env_id` int(11) DEFAULT NULL,
				  `month` int(2) DEFAULT NULL,
				  `year` int(4) DEFAULT NULL,
				  `farm_id` int(11) DEFAULT NULL,
				  `cloud_location` varchar(25) DEFAULT NULL,
				  PRIMARY KEY (`id`),
				  UNIQUE KEY `main` (`instance_type`,`cloud_location`,`farm_id`,`env_id`,`month`,`year`),
				  KEY `envid` (`env_id`),
				  KEY `farm_id` (`farm_id`)
				) ENGINE=MyISAM AUTO_INCREMENT=1 DEFAULT CHARSET=latin1;
			");
			
			$db->Execute("ALTER TABLE  `script_revisions` ADD  `variables` TEXT NULL");
			
			$dbVersions = $db->Execute("SELECT * FROM script_revisions WHERE `variables` IS NULL");
			while ($version = $dbVersions->FetchRow()) {
				$data = array();
				foreach ((array)Scalr_UI_Controller_Scripts::getCustomVariables($version["script"]) as $var) {
					if (! in_array($var, array_keys(CONFIG::getScriptingBuiltinVariables())))
						$data[$var] = ucwords(str_replace("_", " ", $var));
				}
				
				$db->Execute("UPDATE script_revisions SET `variables` = ? WHERE id = ?", array(serialize($data), $version['id']));
			}
			
			$db->Execute("ALTER TABLE  `scalr`.`servers_stats` ADD INDEX  `year` (  `year` )");
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>