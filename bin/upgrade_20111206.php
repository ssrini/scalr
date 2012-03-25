<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20111206();
	$ScalrUpdate->Run();
	
	class Update20111206
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->Execute("ALTER TABLE  `scalr`.`services_mongodb_volumes_map` ADD UNIQUE  `main` (  `farm_roleid` ,  `replica_set_index` ,  `shard_index` )");
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>