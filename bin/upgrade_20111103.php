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
			
			$images = $db->Execute("SELECT COUNT( * ) as cnt, id, image_id, role_id FROM  `role_images` GROUP BY image_id, role_id ORDER BY COUNT( * ) DESC");
			while ($image = $images->FetchRow())
			{
				if ($image['cnt'] > 1) {
					$db->Execute("DELETE FROM role_images WHERE image_id = ? AND role_id = ? AND id != ?", array(
						$image['image_id'],
						$image['role_id'],
						$image['id']
					));
				}
			}
			
			$db->Execute("ALTER TABLE  `scalr`.`role_images` ADD UNIQUE  `unique` (  `role_id` ,  `image_id` ,  `cloud_location` )");
			
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