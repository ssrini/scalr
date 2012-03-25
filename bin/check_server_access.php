<?php
	require_once('../src/prepend.inc.php');
	
	$servers = $db->Execute("SELECT server_id FROM servers");
	while ($server = $servers->FetchRow())
	{
		$dbServer = DBServer::LoadByID($server['server_id']);
		if ($dbServer->IsSupported("0.5")) {
			if (!$dbServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT))
				$dbServer->SetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT, 8014);
		}
	}
?>