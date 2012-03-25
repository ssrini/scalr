<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20101026();
	$ScalrUpdate->Run();
	
	class Update20101026
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			$db->BeginTrans();
			
			$tasks = $db->Execute("SELECT id,env_id FROM scheduler_tasks");
			while ($task = $tasks->FetchRow())
			{
				$timezone = $db->GetOne("SELECT value FROM client_environment_properties WHERE env_id=? AND `name`='timezone'", array($task['env_id']));
				if (!$timezone)
					$timezone = 'America/Los_Angeles';
				
				$db->Execute("UPDATE scheduler_tasks SET `timezone`=? WHERE id=?", array($timezone, $task['id']));
			}
			
			
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