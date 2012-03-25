<?php
	require_once('../src/prepend.inc.php');
	
	print "Checking apache_vhosts...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM apache_vhosts WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	if ($c > 0) {
		print "{$c} with zomby FarmRoleID\n";
		$db->Execute("DELETE 	      FROM apache_vhosts WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	}
		
	$c = $db->GetOne("SELECT COUNT(*) FROM apache_vhosts WHERE farm_id NOT IN (SELECT id FROM farms)");
	if ($c > 0) {
		print "{$c} with zomby FarmID\n";
		$db->Execute("DELETE 		  FROM apache_vhosts WHERE farm_id NOT IN (SELECT id FROM farms)");
	}
		
	$c = $db->GetOne("SELECT COUNT(*) FROM apache_vhosts WHERE client_id NOT IN (SELECT id FROM clients)");
	if ($c > 0) {
		print "{$c} with zomby ClientID\n";
		$db->Execute("DELETE 		  FROM apache_vhosts WHERE client_id NOT IN (SELECT id FROM clients)");
	}
		
	print "------------------------\n";
	
	
	print "Checking dns_zones...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE farm_roleid != '0' AND farm_roleid NOT IN (SELECT id FROM farm_roles)");
	if ($c > 0) {
		print "{$c} with zomby FarmRoleID\n";
		$db->Execute("DELETE 		  FROM dns_zones WHERE farm_roleid != '0' AND farm_roleid NOT IN (SELECT id FROM farm_roles)");
	}
		
	$c = $db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE farm_id != '0' AND farm_id NOT IN (SELECT id FROM farms)");
	if ($c > 0) {
		print "{$c} with zomby FarmID\n";
		$db->Execute("DELETE 		  FROM dns_zones WHERE farm_id != '0' AND farm_id NOT IN (SELECT id FROM farms)");
	}
		
	$c = $db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE client_id NOT IN (SELECT id FROM clients)");
	if ($c > 0) {
		print "{$c} with zomby ClientID\n";
		$db->Execute("DELETE 		  FROM dns_zones WHERE client_id NOT IN (SELECT id FROM clients)");
	}
		
	print "------------------------\n";
	
	
	
	print "Checking dns_zone_records...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM dns_zone_records WHERE zone_id NOT IN (SELECT id FROM dns_zones)");
	if ($c > 0) {
		print "{$c} with zomby ZoneID\n";
		$db->Execute("DELETE 		  FROM dns_zone_records WHERE zone_id NOT IN (SELECT id FROM dns_zones)");
	}
		
	$c = $db->GetOne("SELECT COUNT(*) FROM dns_zone_records WHERE server_id != '' AND server_id IS NOT NULL AND server_id NOT IN (SELECT server_id FROM servers)");
	if ($c > 0) {
		print "{$c} with zomby ServerID\n";
		$db->Execute("DELETE 		  FROM dns_zone_records WHERE server_id != '' AND server_id IS NOT NULL AND server_id NOT IN (SELECT server_id FROM servers)");
	}
		
	print "------------------------\n";
	
	
	print "Checking server_properties...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM server_properties WHERE server_id NOT IN (SELECT server_id FROM servers)");
	if ($c > 0) {
		print "{$c} with zomby ServerID\n";
		$db->Execute("DELETE 		  FROM server_properties WHERE server_id NOT IN (SELECT server_id FROM servers)");
	}
		
	print "------------------------\n";	
	
	
	print "Checking farm_role_options...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM farm_role_options WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	if ($c > 0) {
		print "{$c} with zomby FarmRoleID\n";
		$db->Execute("DELETE 		  FROM farm_role_options WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	}
		
	print "------------------------\n";
	
	print "Checking farm_role_scaling_metrics...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM farm_role_scaling_metrics WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	if ($c > 0) {
		print "{$c} with zomby FarmRoleID\n";
		$db->Execute("DELETE 		  FROM farm_role_scaling_metrics WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	}
		
	print "------------------------\n";
	
	print "Checking farm_role_scripts...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM farm_role_scripts WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	if ($c > 0) {
		print "{$c} with zomby FarmRoleID\n";
		$db->Execute("DELETE FROM farm_role_scripts WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	}
		
	print "------------------------\n";
	
	print "Checking farm_role_settings...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM farm_role_settings WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	if ($c > 0) {
		print "{$c} with zomby FarmRoleID\n";
		$db->Execute("DELETE 		  FROM farm_role_settings WHERE farm_roleid NOT IN (SELECT id FROM farm_roles)");
	}
		
	print "------------------------\n";
	
	
	
	print "Checking farm_settings...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM farm_settings WHERE farmid NOT IN (SELECT id FROM farms)");
	if ($c > 0) {
		print "{$c} with zomby FarmID\n";
		$db->Execute("DELETE 		  FROM farm_settings WHERE farmid NOT IN (SELECT id FROM farms)");
	}
		
	print "------------------------\n";
	
	print "Checking events...\n";
	$c = $db->GetOne("SELECT COUNT(*) FROM events WHERE farmid NOT IN (SELECT id FROM farms)");
	if ($c > 0)
		print "{$c} with zomby FarmID\n";
		
	print "------------------------\n";
?>