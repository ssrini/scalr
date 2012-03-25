<?php
	class DNSEventObserver extends EventObserver
	{
		public $ObserverName = 'DNS';
		
		function __construct()
		{
			parent::__construct();
		}
	
		public function OnRebootComplete(RebootCompleteEvent $event)
		{
			
		}
		
		/**
		 * @deprecated
		 */
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{			
			$this->updateZoneServerRecords($event->DBServer->serverId, $event->DBServer->farmId, true);
		}
		
		public function OnNewDbMsrMasterUp(NewDbMsrMasterUpEvent $event) 
		{
			$this->updateZoneServerRecords($event->DBServer->serverId, $event->DBServer->farmId, true);
		}
	
		/**
		 * Public IP address for instance changed
		 *
		 * @param array $instanceinfo
		 * @param string $new_ip_address
		 */
		public function OnIPAddressChanged(IPAddressChangedEvent $event)
		{
			$this->updateZoneServerRecords($event->DBServer->serverId, $event->DBServer->farmId);
		}
		
		/**
		 * Farm launched
		 *
		 * @param bool $mark_instances_as_active
		 */
		public function OnFarmLaunched(FarmLaunchedEvent $event)
		{
			//SYSTEM DNS RECORD
			if (CONFIG::$SYSDNS_SYSTEM) {
				try {
					$hash = DBFarm::LoadByID($event->GetFarmID())->Hash;
					$this->DB->Execute("INSERT INTO `powerdns`.`domains` SET `name`=?, `type`=?, `scalr_farm_id`=?", array("{$hash}.scalr-dns.net",'NATIVE', $event->GetFarmID()));
				} catch (Exception $e) {}
			}
			
			$zones = DBDNSZone::loadByFarmId($event->GetFarmID());
			if (count($zones) == 0)
				return;
				
			foreach ($zones as $zone)
			{
				if ($zone->status == DNS_ZONE_STATUS::INACTIVE)
				{
					$zone->status = DNS_ZONE_STATUS::PENDING_CREATE;
					$zone->save();
				}
			}
		}
		/**
		 * Farm terminated
		 *
		 * @param bool $remove_zone_from_DNS
		 * @param bool $keep_elastic_ips
		 */
		public function OnFarmTerminated(FarmTerminatedEvent $event)
		{
			//SYSTEM DNS ZONES
			if (CONFIG::$SYSDNS_SYSTEM) {
				$this->DB->Execute("DELETE FROM `powerdns`.`domains` WHERE scalr_farm_id = ?", array($event->GetFarmID()));
			}
			
			if (!$event->RemoveZoneFromDNS)
				return;
				
			$zones = DBDNSZone::loadByFarmId($event->GetFarmID());
			if (count($zones) == 0)
				return;
				
			foreach ($zones as $zone)
			{
				if ($zone->status != DNS_ZONE_STATUS::PENDING_DELETE)
				{
					$zone->status = DNS_ZONE_STATUS::INACTIVE;
					$zone->save();
				}
			}
		}
		
		/**
		 * Instance sent hostUp event
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostUp(HostUpEvent $event)
		{
			$update_all = $event->DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL) ? true : false;
			$this->updateZoneServerRecords($event->DBServer->serverId, $event->DBServer->farmId, $update_all);
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{
			$update_all = false;
			try {
				$update_all = $event->DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL) ? true : false;
			}
			catch(Exception $e){}
			
			$this->updateZoneServerRecords($event->DBServer->serverId, $event->DBServer->farmId, $update_all);
		}
		
		/**
		 * Instance terminated
		 *
		 * @param array $instanceinfo
		 */
		public function OnHostDown(HostDownEvent $event)
		{
			$update_all = false;
			try {
				$update_all = $event->DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL) ? true : false;
			}
			catch(Exception $e){}
			
			$this->updateZoneServerRecords($event->DBServer->serverId, $event->DBServer->farmId, $update_all, true);
		}
		
		private function updateZoneServerRecords($server_id, $farm_id, $reset_all_system_records = false, $skip_status_check = false)
		{
			$zones = DBDNSZone::loadByFarmId($farm_id);
			foreach ($zones as $DBDNSZone)
			{
				if (!$skip_status_check && ($DBDNSZone->status == DNS_ZONE_STATUS::PENDING_DELETE || $DBDNSZone->status == DNS_ZONE_STATUS::INACTIVE))
					continue;
				
				if (!$reset_all_system_records)
				{
					$DBDNSZone->updateSystemRecords($server_id);
					$DBDNSZone->save();
				}
				else
				{
					$DBDNSZone->save(true);
				}
			}
			
			//UPDATE SYSTEM records
			try {
				$this->updateSystemZone($server_id, $farm_id, $reset_all_system_records, $skip_status_check);
			} catch (Exception $e) {}
		}
		
		private function updateSystemZone($server_id, $farm_id, $reset_all_system_records = false, $skip_status_check = false)
		{
			//UPDATE RECORDS ONLY FOR SERVER
			if (!CONFIG::$SYSDNS_SYSTEM)
				return true;
			
			$this->DB->Execute("DELETE FROM powerdns.records WHERE server_id = ?", array($server_id));
			
			try {
				$server = DBServer::LoadByID($server_id);
				$dbRole = DBRole::loadById($server->roleId);
			} catch (Exception $e) {}
			
			$domain = $this->DB->GetRow("SELECT id, name FROM powerdns.domains WHERE scalr_farm_id = ?", array($server->farmId));
			$domainId = $domain['id'];
			$domainName = $domain['name'];
			if (!$domainId)
				return;
			
			// Set index records
			if ($server && $server->status == SERVER_STATUS::RUNNING) {
				$records[] = array("int.{$server->index}.{$server->farmRoleId}", $server->localIp, $server->serverId);
				$records[] = array("ext.{$server->index}.{$server->farmRoleId}", $server->remoteIp, $server->serverId);
				
				if ($server->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_IS_ROUTER)) {
					$records[] = array("int.mongo", $server->localIp, $server->serverId, 'mongodb');
					$records[] = array("ext.mongo", $server->remoteIp, $server->serverId, 'mongodb');
				}
				
				if ($dbRole->hasBehavior(ROLE_BEHAVIORS::NGINX)) {
					$records[] = array("api.cloudfoundry", $server->localIp, $server->serverId, 'cloudfoundry');
					$records[] = array("api.cloudfoundry", $server->remoteIp, $server->serverId, 'cloudfoundry');
				}
				
				$isMysql = $dbRole->hasBehavior(ROLE_BEHAVIORS::MYSQL);
				$dbmsr = $dbRole->hasBehavior(ROLE_BEHAVIORS::REDIS) ? 'redis' : false;
				
				if (!$dbmsr)
					$dbmsr = $dbRole->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL) ? 'postgresql' : false;
				
				if ($isMysql) {
					// Clear records
					$this->DB->Execute("DELETE FROM powerdns.records WHERE `service` = ? AND domain_id = ?", array('mysql', $domainId));
					$mysqlMasterServer = null;
					$mysqlSlaves = 0;
					
					$servers = $this->DB->Execute("SELECT server_id, local_ip, remote_ip FROM servers WHERE `farm_roleid` = ? and `status`=?", array($server->farmRoleId, SERVER_STATUS::RUNNING));
					while ($s = $servers->FetchRow()) {
						if ($this->DB->GetOne("SELECT value FROM server_properties WHERE server_id = ? AND name = ?", array($s['server_id'], SERVER_PROPERTIES::DB_MYSQL_MASTER)) == 1) {
							$records[] = array("int.master.mysql", $s['local_ip'], $s['server_id'], 'mysql');
							$records[] = array("ext.master.mysql", $s['remote_ip'], $s['server_id'], 'mysql');
							$mysqlMasterServer = $s;	
						} else {
							$records[] = array("int.slave.mysql", $s['local_ip'], $s['server_id'], 'mysql');
							$records[] = array("ext.slave.mysql", $s['remote_ip'], $s['server_id'], 'mysql');
							$mysqlSlaves++;
						}
						
						$records[] = array("int.mysql", $s['local_ip'], $s['server_id'], 'mysql');
						$records[] = array("ext.mysql", $s['remote_ip'], $s['server_id'], 'mysql');
					}
				
					if ($mysqlSlaves == 0 && $mysqlMasterServer) {
						$records[] = array("int.slave.mysql", $mysqlMasterServer['local_ip'], $mysqlMasterServer['server_id'], 'mysql');
						$records[] = array("ext.slave.mysql", $mysqlMasterServer['remote_ip'], $mysqlMasterServer['server_id'], 'mysql');
					}
				}
				
				if ($dbmsr) {
					// Clear records
					$this->DB->Execute("DELETE FROM powerdns.records WHERE `service` = ? AND domain_id = ?", array($dbmsr, $domainId));
					$mysqlMasterServer = null;
					$mysqlSlaves = 0;
					
					$servers = $this->DB->Execute("SELECT server_id, local_ip, remote_ip FROM servers WHERE `farm_roleid` = ? and `status`=?", array($server->farmRoleId, SERVER_STATUS::RUNNING));
					while ($s = $servers->FetchRow()) {
						if ($this->DB->GetOne("SELECT value FROM server_properties WHERE server_id = ? AND name = ?", array($s['server_id'], Scalr_Db_Msr::REPLICATION_MASTER)) == 1) {
							$records[] = array("int.master.{$dbmsr}", $s['local_ip'], $s['server_id'], $dbmsr);
							$records[] = array("ext.master.{$dbmsr}", $s['remote_ip'], $s['server_id'], $dbmsr);
							$mysqlMasterServer = $s;	
						} else {
							$records[] = array("int.slave.{$dbmsr}", $s['local_ip'], $s['server_id'], $dbmsr);
							$records[] = array("ext.slave.{$dbmsr}", $s['remote_ip'], $s['server_id'], $dbmsr);
							$mysqlSlaves++;
						}
						
						$records[] = array("int.{$dbmsr}", $s['local_ip'], $s['server_id'], $dbmsr);
						$records[] = array("ext.{$dbmsr}", $s['remote_ip'], $s['server_id'], $dbmsr);
					}
				
					if ($mysqlSlaves == 0 && $mysqlMasterServer) {
						$records[] = array("int.slave.{$dbmsr}", $mysqlMasterServer['local_ip'], $mysqlMasterServer['server_id'], $dbmsr);
						$records[] = array("ext.slave.{$dbmsr}", $mysqlMasterServer['remote_ip'], $mysqlMasterServer['server_id'], $dbmsr);
					}
				}
				
				
				
				foreach ($records as $r) {
					$this->DB->Execute("INSERT INTO powerdns.records SET 
						`domain_id`=?, `name`=?, `type`=?, `content`=?, `ttl`=?, `server_id`=?, `service`=?
					", 
					array($domainId, "$r[0].{$domainName}", "A", "{$r[1]}", 20, $r[2], $r[3]));
				}
			}
			
			// Update service records
		}
	}
?>