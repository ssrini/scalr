<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20110423();
	$ScalrUpdate->Run();
	
	class Update20110423
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$farm_roles = $db->Execute("SELECT id FROM farm_roles");
			while ($farm_role = $farm_roles->FetchRow()) {
				$db->Execute("INSERT INTO farm_role_settings SET `name` =?, `farm_roleid` =?, `value`='1'", array(
					'scaling.enabled', $farm_role['id']
				));
			}
			
			$db->Execute("ALTER TABLE  `farm_roles` ADD  `cloud_location` VARCHAR( 50 ) NULL");
			
			
			$farmRoles = $db->Execute("SELECT id FROM farm_roles");
			while ($farmRole = $farmRoles->FetchRow()) {
				$location = $db->GetOne("SELECT value FROM farm_role_settings WHERE farm_roleid=? and `name`=?", array($farmRole['id'], 'cloud.location'));
				$db->Execute("UPDATE farm_roles SET cloud_location=? WHERE id=?", array($location, $farmRole['id']));
			}
			
			
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