<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
	
	/*
		$remoteBind = new Scalr_Net_Dns_Bind_RemoteBind();
        $transport = new Scalr_Net_Dns_Bind_Transports_LocalFs('/usr/sbin/rndc', '/var/named/etc/namedb/client_zones');
        $remoteBind->setTransport($transport);
	
	$zones = $db->GetAll("SELECT * FROM dns_zones WHERE status=?", array(DNS_ZONE_STATUS::ACTIVE));
	foreach ($zones as $zone)
	{
		$DBDNSZone = DBDNSZone::loadById($zone['id']);
	            	
		$remoteBind->addZoneToNamedConf($DBDNSZone->zoneName, $DBDNSZone->getContents(true));
	}
	
	$remoteBind->saveNamedConf();
	$remoteBind->reloadBind();
	*/
	
	$farms = $db->Execute("SELECT * FROM farms WHERE status=?", array(FARM_STATUS::RUNNING));
	while ($farm = $farms->FetchRow()) {
		
		$dId = $db->GetOne("SELECT id FROM powerdns.domains WHERE scalr_farm_id=?", array($farm['id']));
		$hash = $farm['hash'];
		$new = false;
		if (!$dId) {
			$db->Execute("INSERT INTO `powerdns`.`domains` SET `name`=?, `type`=?, `scalr_farm_id`=?", array("{$hash}.scalr-dns.net",'NATIVE', $farm['id']));
			$domainId = $db->Insert_ID();
			$new = true;
		} else {
			//
			$domainId = $dId;
		}
		
		///////
		//UPDATE RECORDS ONLY FOR SERVER
		$domainName = "{$hash}.scalr-dns.net";
		
		$servers = $db->Execute("SELECT server_id FROM servers WHERE farm_id = ?", array($farm['id']));
		while ($ss = $servers->FetchRow())
		{
			try {
				$server = DBServer::LoadByID($ss['server_id']);
				$dbRole = DBRole::loadById($server->roleId);
			} catch (Exception $e) { continue; }
			
			if (!$new) {
				if ($db->GetOne("SELECT COUNT(*) FROM powerdns.records WHERE server_id = ?", array($server->serverId)) > 0)
					continue;
					
				$records[] = array("int.{$server->index}.{$server->farmRoleId}", $server->localIp, $server->serverId);
				$records[] = array("ext.{$server->index}.{$server->farmRoleId}", $server->remoteIp, $server->serverId);
			}
			else {
				// Set index records
				if ($server && $server->status == SERVER_STATUS::RUNNING) {
					$records[] = array("int.{$server->index}.{$server->farmRoleId}", $server->localIp, $server->serverId);
					$records[] = array("ext.{$server->index}.{$server->farmRoleId}", $server->remoteIp, $server->serverId);
					
					$isMysql = $dbRole->hasBehavior(ROLE_BEHAVIORS::MYSQL);
					$dbmsr = $dbRole->hasBehavior(ROLE_BEHAVIORS::REDIS) ? 'redis' : false;
					$dbmsr = $dbRole->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL) ? 'postgresql' : false;
					
					if ($isMysql) {
						// Clear records
						$db->Execute("DELETE FROM powerdns.records WHERE `service` = ? AND domain_id = ?", array('mysql', $domainId));
						$mysqlMasterServer = null;
						$mysqlSlaves = 0;
						
						$servers = $db->Execute("SELECT server_id, local_ip, remote_ip FROM servers WHERE `farm_roleid` = ? and `status`=?", array($server->farmRoleId, SERVER_STATUS::RUNNING));
						while ($s = $servers->FetchRow()) {
							if ($db->GetOne("SELECT value FROM server_properties WHERE server_id = ? AND name = ?", array($s['server_id'], SERVER_PROPERTIES::DB_MYSQL_MASTER)) == 1) {
								$records[] = array("int.master.mysql", $s['local_ip'], $s['server_id'], 'mysql');
								$records[] = array("ext.master.mysql", $s['remote_ip'], $s['server_id'], 'mysql');
								$mysqlMasterServer = $s;	
							} else {
								$records[] = array("int.slave.mysql", $s['local_ip'], $s['server_id'], 'mysql');
								$records[] = array("ext.slave.mysql", $s['remote_ip'], $s['server_id'], 'mysql');
								$mysqlSlaves++;
							}
						}
					
						if ($mysqlSlaves == 0 && $mysqlMasterServer) {
							$records[] = array("int.slave.mysql", $mysqlMasterServer['local_ip'], $mysqlMasterServer['server_id'], 'mysql');
							$records[] = array("ext.slave.mysql", $mysqlMasterServer['remote_ip'], $mysqlMasterServer['server_id'], 'mysql');
						}
					}
					
					if ($dbmsr) {
						// Clear records
						$db->Execute("DELETE FROM powerdns.records WHERE `service` = ? AND domain_id = ?", array($dbmsr, $domainId));
						$mysqlMasterServer = null;
						$mysqlSlaves = 0;
						
						$servers = $db->Execute("SELECT server_id, local_ip, remote_ip FROM servers WHERE `farm_roleid` = ? and `status`=?", array($server->farmRoleId, SERVER_STATUS::RUNNING));
						while ($s = $servers->FetchRow()) {
							if ($db->GetOne("SELECT value FROM server_properties WHERE server_id = ? AND name = ?", array($s['server_id'], Scalr_Db_Msr::REPLICATION_MASTER)) == 1) {
								$records[] = array("int.master.{$dbmsr}", $s['local_ip'], $s['server_id'], $dbmsr);
								$records[] = array("ext.master.{$dbmsr}", $s['remote_ip'], $s['server_id'], $dbmsr);
								$mysqlMasterServer = $s;	
							} else {
								$records[] = array("int.slave.{$dbmsr}", $s['local_ip'], $s['server_id'], $dbmsr);
								$records[] = array("ext.slave.{$dbmsr}", $s['remote_ip'], $s['server_id'], $dbmsr);
								$mysqlSlaves++;
							}
						}
					
						if ($mysqlSlaves == 0 && $mysqlMasterServer) {
							$records[] = array("int.slave.{$dbmsr}", $mysqlMasterServer['local_ip'], $mysqlMasterServer['server_id'], $dbmsr);
							$records[] = array("ext.slave.{$dbmsr}", $mysqlMasterServer['remote_ip'], $mysqlMasterServer['server_id'], $dbmsr);
						}
					}
				}
			}
					
			foreach ($records as $r) {
				$db->Execute("INSERT INTO powerdns.records SET 
					`domain_id`=?, `name`=?, `type`=?, `content`=?, `ttl`=?, `server_id`=?, `service`=?
				", 
				array($domainId, "$r[0].{$domainName}", "A", "{$r[1]}", 20, $r[2], $r[3]));
			}
			$records = array();
		}
			// Update service records
	}
?>