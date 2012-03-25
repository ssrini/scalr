<?php
	
	class DBDNSZone
	{			
		public 
			$id,
			$clientId,
			$envId,
			$farmId,
			$farmRoleId,
			$zoneName,
			$status,
			$soaOwner,
			$soaTtl,
			$soaParent,
			$soaSerial,
			$soaRefresh,
			$soaRetry,
			$soaExpire,
			$soaMinTtl,
			$dateLastModified,
			$axfrAllowedHosts,
			$allowedAccounts,
			$allowManageSystemRecords,
			$isOnNsServer,
			$isZoneConfigModified;
		
		private $db;
		private $records;
		private $updateRecords = false;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'id',
			'farm_roleid'	=> 'farmRoleId',
			'farm_id'		=> 'farmId',
			'client_id'		=> 'clientId',
			'env_id'		=> 'envId',		
			'zone_name'		=> 'zoneName',
			'status' 		=> 'status',
			'soa_owner'		=> 'soaOwner',
			'soa_ttl'		=> 'soaTtl',
			'soa_parent'	=> 'soaParent',
			'soa_serial'	=> 'soaSerial',
			'soa_refresh'	=> 'soaRefresh',
			'soa_retry'		=> 'soaRetry',
			'soa_expire'	=> 'soaExpire',
			'soa_min_ttl'	=> 'soaMinTtl',
			'dtlastmodified'=> 'dateLastModified',
			'axfr_allowed_hosts'	=> 'axfrAllowedHosts',
			'allow_manage_system_records'	=> 'allowManageSystemRecords',
			'isonnsserver'	=> 'isOnNsServer',
			'iszoneconfigmodified'	=> 'isZoneConfigModified',
			'allowed_accounts' => 'allowedAccounts'
		);
		
		function __construct($id = null)
		{
			$this->id = $id;
			$this->db = Core::GetDBInstance();
		}
		
		/**
		 * @return array
		 * @param integer $farm_id
		 */
		public static function loadByFarmId($farm_id)
		{
			$db = Core::GetDBInstance();
			$zones = $db->GetAll("SELECT id FROM dns_zones WHERE farm_id=?", array($farm_id));
			$retval = array();
			foreach ($zones as $zone)
				$retval[] = DBDNSZone::loadById($zone['id']);
			
			return $retval;
		}
		
		/**
		 * 
		 * @param integer $id
		 * @return DBDNSZone
		 */
		public static function loadById($id)
		{
			$db = Core::GetDBInstance();
			
			$zoneinfo = $db->GetRow("SELECT * FROM dns_zones WHERE id=?", array($id));
			if (!$zoneinfo)
				throw new Exception(sprintf(_("DNS zone ID#%s not found in database"), $id));
				
			$DBDNSZone = new DBDNSZone($id);
			
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if (isset($zoneinfo[$k]))
					$DBDNSZone->{$v} = $zoneinfo[$k];
			}
			
			return $DBDNSZone;
		}
		
		/**
		 * 
		 * @param unknown_type $zoneName
		 * @param unknown_type $soaRefresh
		 * @param unknown_type $soaExpire
		 * @return DBDNSZone
		 */
		public static function create($zoneName, $soaRefresh, $soaExpire, $soaOwner, $soaRetry = 7200)
		{
			$zone = new DBDNSZone();
			$zone->zoneName = $zoneName;
			$zone->soaRefresh = $soaRefresh;
			$zone->soaExpire = $soaExpire;
			$zone->status = DNS_ZONE_STATUS::PENDING_CREATE;
			
			$zone->soaOwner = $soaOwner;
			$zone->soaTtl = 14400;
			$zone->soaParent = "ns1.scalr.net";
			$zone->soaSerial = date("Ymd")."01";
			$zone->soaRetry = $soaRetry ? $soaRetry : 7200;
			$zone->soaMinTtl = 300;
			
			return $zone;
		}
		
		public function getContents($config_contents = false)
		{
			$this->loadRecords();
			
			$this->soaSerial = Scalr_Net_Dns_SOARecord::raiseSerial($this->soaSerial);
			$this->save();
			
			$soaRecord = new Scalr_Net_Dns_SOARecord(
				$this->zoneName, 
				$this->soaParent, 
				$this->soaOwner,
				$this->soaTtl, 
				$this->soaSerial, 
				$this->soaRefresh,
				$this->soaRetry,
				$this->soaExpire,
				$this->soaMinTtl
			);
			
			$zone = new Scalr_Net_Dns_Zone();
			$zone->addRecord($soaRecord);
			
			if (!$config_contents)
			{
				$rCache = array();
				foreach ($this->records as $record)
				{
					if (!$rCache[$record['type']])
					{
						$r = new ReflectionClass("Scalr_Net_Dns_{$record['type']}Record");
						
						$params = array();
						foreach ($r->getConstructor()->getParameters() as $p)
							$params[] = $p->name;
						
						$rCache[$record['type']] = array(
							'reflect'	=> $r,
							'params'	=> $params
						);
					}
						
					$args = array();
					foreach ($rCache[$record['type']]['params'] as $p)
						$args[$p] = $record[$p];
						
					try
					{
						$r = $rCache[$record['type']]['reflect']->newInstanceArgs($args);
						$zone->addRecord($r);
					}
					catch(Exception $e){}
				}
			}
			
			return $zone->generate($this->axfrAllowedHosts, $config_contents);
		}
		
		private function getBehaviorsRecords(DBServer $dbServer)
		{
			$records = array();
			if ($dbServer->farmRoleId != 0) {
				foreach (Scalr_Role_Behavior::getListForFarmRole($dbServer->GetFarmRoleObject()) as $behavior) {
					$records = array_merge($records, (array)$behavior->getDnsRecords($dbServer));
				}
			}
			
			return $records;
		}
		
		private function getDbRecords(DBServer $dbServer) 
		{
			if ($dbServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL))
				$dbType = ROLE_BEHAVIORS::POSTGRESQL;
			elseif ($dbServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS))
				$dbType = ROLE_BEHAVIORS::REDIS;
			else 
				return array();
		
			$records = array();
				
			array_push($records, array(
				"name" 		=> "int-{$dbType}",
				"value"		=> $dbServer->localIp,
				"type"		=> "A",
				"ttl"		=> 90,
				"server_id"	=> $dbServer->serverId,
				"issystem"	=> '1'
			));
			
			array_push($records, array(
				"name" 		=> "ext-{$dbType}",
				"value"		=> $dbServer->remoteIp,
				"type"		=> "A",
				"ttl"		=> 90,
				"server_id"	=> $dbServer->serverId,
				"issystem"	=> '1'
			));
			
			if ($dbServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) == 1)
			{
				array_push($records, array(
					"name" 		=> "int-{$dbType}-master",
					"value"		=> $dbServer->localIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $dbServer->serverId,
					"issystem"	=> '1'
				));
				
				array_push($records, array(
					"name" 		=> "ext-{$dbType}-master",
					"value"		=> $dbServer->remoteIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $dbServer->serverId,
					"issystem"	=> '1'
				));
			}
			
			if ($dbServer->GetFarmRoleObject()->GetRunningInstancesCount() == 1 || !$dbServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER))
			{
				array_push($records, array(
					"name" 		=> "int-{$dbType}-slave",
					"value"		=> $dbServer->localIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $dbServer->serverId,
					"issystem"	=> '1'
				));
				
				array_push($records, array(
					"name" 		=> "ext-{$dbType}-slave",
					"value"		=> $dbServer->remoteIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $dbServer->serverId,
					"issystem"	=> '1'
				));
			}

			return $records;
		}
		
		private function getServerDNSRecords(DBServer $DBServer)
		{
			if ($DBServer->status != SERVER_STATUS::RUNNING)
				return array();
			
			if ($DBServer->GetProperty(SERVER_PROPERTIES::EXCLUDE_FROM_DNS))
				return array();
			
			$DBFarmRole = $DBServer->GetFarmRoleObject();
			if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_EXCLUDE_FROM_DNS))
				return array();
				
			if ($DBServer->platform == SERVER_PLATFORMS::RDS)
			{
				$records = array(
					array(
						"name" 		=> 'int-mysql',
						"value"		=> $DBServer->GetProperty(RDS_SERVER_PROPERTIES::ENDPOINT_HOST),
						"type"		=> "CNAME",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					),
					array(
						"name" 		=> 'ext-mysql',
						"value"		=> $DBServer->GetProperty(RDS_SERVER_PROPERTIES::ENDPOINT_HOST),
						"type"		=> "CNAME",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					),
				);
				
				return $records;
			}
				
			$int_record_alias = $DBFarmRole->GetSetting(DBFarmRole::SETTING_DNS_INT_RECORD_ALIAS);
			$int_record = ($int_record_alias) ? $int_record_alias : "int-{$DBFarmRole->GetRoleObject()->name}";
			
			$ext_record_alias = $DBFarmRole->GetSetting(DBFarmRole::SETTING_DNS_EXT_RECORD_ALIAS);
			$ext_record = ($ext_record_alias) ? $ext_record_alias : "ext-{$DBFarmRole->GetRoleObject()->name}";
			
			$records = array(
				array(
					"name" 		=> $int_record,
					"value"		=> $DBServer->localIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $DBServer->serverId,
					"issystem"	=> '1'
				),
				array(
					"name" 		=> $ext_record,
					"value"		=> $DBServer->remoteIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $DBServer->serverId,
					"issystem"	=> '1'
				),
			);
			
			if ($DBFarmRole->ID == $this->farmRoleId)
			{
				array_push($records, array(
					"name" 		=> "@",
					"value"		=> $DBServer->remoteIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $DBServer->serverId,
					"issystem"	=> '1'
				));
			}
			
			$records = array_merge($records, (array)$this->getDbRecords($DBServer));
			$records = array_merge($records, (array)$this->getBehaviorsRecords($DBServer));
			
			if ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL))
			{
				array_push($records, array(
					"name" 		=> "int-mysql",
					"value"		=> $DBServer->localIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $DBServer->serverId,
					"issystem"	=> '1'
				));
				
				array_push($records, array(
					"name" 		=> "ext-mysql",
					"value"		=> $DBServer->remoteIp,
					"type"		=> "A",
					"ttl"		=> 90,
					"server_id"	=> $DBServer->serverId,
					"issystem"	=> '1'
				));
				
				if ($DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER))
				{
					array_push($records, array(
						"name" 		=> "int-mysql-master",
						"value"		=> $DBServer->localIp,
						"type"		=> "A",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					));
					
					array_push($records, array(
						"name" 		=> "ext-mysql-master",
						"value"		=> $DBServer->remoteIp,
						"type"		=> "A",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					));
					
					array_push($records, array(
						"name" 		=> "{$int_record}-master",
						"value"		=> $DBServer->localIp,
						"type"		=> "A",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					));
					
					array_push($records, array(
						"name" 		=> "{$ext_record}-master",
						"value"		=> $DBServer->remoteIp,
						"type"		=> "A",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					));
				}
				
				if ($DBFarmRole->GetRunningInstancesCount() == 1 || !$DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER))
				{
					array_push($records, array(
						"name" 		=> "int-mysql-slave",
						"value"		=> $DBServer->localIp,
						"type"		=> "A",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					));
					
					array_push($records, array(
						"name" 		=> "ext-mysql-slave",
						"value"		=> $DBServer->remoteIp,
						"type"		=> "A",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					));
					
					array_push($records, array(
						"name" 		=> "{$int_record}-slave",
						"value"		=> $DBServer->localIp,
						"type"		=> "A",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					));
					
					array_push($records, array(
						"name" 		=> "{$ext_record}-slave",
						"value"		=> $DBServer->remoteIp,
						"type"		=> "A",
						"ttl"		=> 90,
						"server_id"	=> $DBServer->serverId,
						"issystem"	=> '1'
					));
				}
			}
			
			return $records;
		}
		
		public function updateSystemRecords($server_id = null)
		{
			if (!$server_id) {
				$this->db->Execute("DELETE FROM dns_zone_records WHERE zone_id=? AND issystem='1' AND server_id != ''", array($this->id));
				
				if ($this->farmId) {
					$system_records = array();
					try
					{
						$DBFarm = DBFarm::LoadByID($this->farmId);
						$DBServers = $DBFarm->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));
						
						foreach ($DBServers as $DBServer)
							$system_records = array_merge($this->getServerDNSRecords($DBServer), $system_records);
					}
					catch(Exception $e)
					{
						//
					}
				}
			}
			else
			{
				$this->db->Execute("DELETE FROM dns_zone_records WHERE zone_id=? AND issystem='1' AND server_id=?", array($this->id, $server_id));
				$system_records = $this->getServerDNSRecords(DBServer::LoadByID($server_id));
			}
			
			
			if ($system_records)
			{
				foreach ($system_records as $record)
				{
					$this->db->Execute("REPLACE INTO dns_zone_records SET
						`zone_id`	= ?,
						`type`		= ?,
						`ttl`		= ?,
						`priority`	= ?,
						`value`		= ?,
						`name`		= ?,
						`issystem`	= '1',
						`weight`	= ?,
						`port`		= ?,
						`server_id`	= ?
					", array(
						$this->id,
						$record['type'],
						(int)$record['ttl'],
						(int)$record['priority'],
						$record['value'],
						$record['name'],
						(int)$record['weight'],
						(int)$record['port'],
						$record['server_id']
					));
				}
			}
			
			if ($this->status == DNS_ZONE_STATUS::ACTIVE)
				$this->status = DNS_ZONE_STATUS::PENDING_UPDATE;
		}
		
		private function loadRecords()
		{
			$this->records = $this->db->GetAll("SELECT * FROM dns_zone_records WHERE zone_id=?", array($this->id));
		}
		
		public function getRecords($includeSystem = true)
		{
			if (!$this->records)
				$this->loadRecords();
				
			if ($includeSystem)
				return $this->records;
			
			$retval = array();
			foreach ($this->records as $record)
				if (!$record['issystem'])
					$retval[] = $record;
				
			return $retval;
		}
		
		public function setRecords($records)
		{
			$this->records = $records;
			$this->updateRecords = true;
			
			if ($this->status == DNS_ZONE_STATUS::ACTIVE)
				$this->status = DNS_ZONE_STATUS::PENDING_UPDATE;
		}
		
		public function remove()
		{
			$this->db->Execute("DELETE FROM dns_zones WHERE id=?", array($this->id));
			$this->db->Execute("DELETE FROM dns_zone_records WHERE zone_id=?", array($this->id));
		}
		
		private function unBind () {
			$row = array();
			foreach (self::$FieldPropertyMap as $field => $property) {
				$row[$field] = $this->{$property};
			}
			
			return $row;		
		}
		
		public function save ($update_system_records = false) {
				
			$row = $this->unBind();
			unset($row['id']);
			unset($row['dtlastmodified']);
			
			$this->db->BeginTrans();
			
			// Prepare SQL statement
			$set = array();
			$bind = array();
			foreach ($row as $field => $value) {
				$set[] = "`$field` = ?";
				$bind[] = $value;
			}
			$set = join(', ', $set);
	
			try	{
				
				//Save zone;
				
				if ($this->id) {
					
					if ($update_system_records)
						$this->updateSystemRecords();
					
					// Perform Update
					$bind[] = $this->id;
					$this->db->Execute("UPDATE dns_zones SET $set, `dtlastmodified` = NOW() WHERE id = ?", $bind);	
					
					//TODO:
					if ($update_system_records) {
						$this->db->Execute("UPDATE dns_zones SET status=?, `dtlastmodified` = NOW() WHERE id = ?", 
							array($this->status, $this->id)
						);
					}
				}
				else {
					// Perform Insert
					$this->db->Execute("INSERT INTO dns_zones SET $set", $bind);
					$this->id = $this->db->Insert_ID();
					
					if ($update_system_records) {
						$this->updateSystemRecords();
						$this->db->Execute("UPDATE dns_zones SET status=?, `dtlastmodified` = NOW() WHERE id = ?", 
							array($this->status, $this->id)
						);
					}
				}
				
				if ($this->updateRecords)
				{
					$this->db->Execute("DELETE FROM dns_zone_records WHERE zone_id=? AND issystem='0'", array($this->id));
					
					foreach ($this->records as $record)
					{
						$this->db->Execute("REPLACE INTO dns_zone_records SET
							`zone_id`	= ?,
							`type`		= ?,
							`ttl`		= ?,
							`priority`	= ?,
							`value`		= ?,
							`name`		= ?,
							`issystem`	= '0',
							`weight`	= ?,
							`port`		= ?
						", array(
							$this->id,
							$record['type'],
							(int)$record['ttl'],
							(int)$record['priority'],
							$record['value'],
							$record['name'],
							(int)$record['weight'],
							(int)$record['port']
						));
					}
				}
			} catch (Exception $e) {
				
				$this->db->RollbackTrans();
				throw new Exception ("Cannot save DBDNS zone. Error: " . $e->getMessage(), $e->getCode());			
			}
			
			$this->db->CommitTrans();
		}
	}
?>