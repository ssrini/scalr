<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$environments = $db->Execute("SELECT id FROM client_environments");
	$i = 0;
	while ($env = $environments->FetchRow())
	{
		$keys = Scalr::GenerateAPIKeys();
		
		$secretKey = $db->GetOne("SELECT `value` FROM client_environment_properties WHERE env_id=? AND `name` = ?", array($env['id'], 'api.access_key'));
		if (!$secretKey) {
			$db->Execute("REPLACE INTO client_environment_properties SET env_id=?, `name` =?, `value` =?", array(
				$env['id'], 'api.access_key', $keys['key']
			));
			
			$db->Execute("REPLACE INTO client_environment_properties SET env_id=?, `name` =?, `value` =?", array(
				$env['id'], 'api.keyid', $keys['id']
			));
			$i++;
		}
	}
	
	print "Fixed {$i} environmetns";
?>