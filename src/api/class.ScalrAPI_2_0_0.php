<?php
	
	class ScalrAPI_2_0_0 extends ScalrAPICore
	{
		/**********************************
		 * DNS Functions
		 */
		public function DNSZonesList()
		{
			$response = $this->CreateInitialResponse();
			$response->DNSZoneSet = new stdClass();
			$response->DNSZoneSet->Item = array();
			
			$rows = $this->DB->Execute("SELECT * FROM dns_zones WHERE env_id=?", array($this->Environment->id));
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->{"ZoneName"} = $row['zone_name'];
				$itm->{"FarmID"} = $row['farm_id'];
				$itm->{"FarmRoleID"} = $row['farm_roleid'];
				$itm->{"Status"} = $row['status'];
				$itm->{"LastModifiedAt"} = $row['dtlastmodified'];
				$itm->{"IPSet"} = new stdClass();
				$itm->{"IPSet"}->Item = array();
				if ($row['status'] == DNS_ZONE_STATUS::ACTIVE)
				{
					$ips = $this->DB->GetAll("SELECT value FROM dns_zone_records WHERE zone_id=? AND `type`=? AND `name` IN ('', '@', '{$row['zone_name']}.')",
						array($row['id'], 'A')
					);
					
					foreach ($ips as $ip)
					{
						$itm_ip = new stdClass();
						$itm_ip->IPAddress = $ip['value'];
						$itm->{"IPSet"}->Item[] = $itm_ip;
					}
				}
				
				$response->DNSZoneSet->Item[] = $itm; 	
		    }
		    
		    return $response;
		}
		
		public function DNSZoneRecordAdd($ZoneName, $Type, $TTL, $Name, $Value, $Priority = 0, $Weight = 0, $Port = 0)
		{
			$zoneinfo = $this->DB->GetRow("SELECT id, env_id FROM dns_zones WHERE zone_name=?", array($ZoneName));
			if (!$zoneinfo || $zoneinfo['env_id'] != $this->Environment->id)
				throw new Exception (sprintf("Zone '%s' not found in database", $ZoneName));
				
			if (!in_array($Type, array("A", "MX", "CNAME", "NS", "TXT", "SRV")))
				throw new Exception (sprintf("Unknown record type '%s'", $Type));	

			$record = array(
				'name'		=> $Name,
				'value'		=> $Value,
				'type'		=> $Type,
				'ttl'		=> $TTL,
				'priority'	=> $Priority,
				'weight'	=> $Weight,
				'port'		=> $Port
			);
				
			$recordsValidation = Scalr_Net_Dns_Zone::validateRecords(array(
				$record
			));
			if ($recordsValidation === true)
			{
				$DBDNSZone = DBDNSZone::loadById($zoneinfo['id']);
				
				$records = $DBDNSZone->getRecords(false);
				array_push($records, $record);
				
				$DBDNSZone->setRecords($records);
				
				$DBDNSZone->save(false);
			}
			else
				throw new Exception($recordsValidation[0]);

			$response = $this->CreateInitialResponse();
			$response->Result = 1;
			
			return $response;
		}
		
		public function DNSZoneRecordRemove($ZoneName, $RecordID)
		{
			$zoneinfo = $this->DB->GetRow("SELECT id, env_id, allow_manage_system_records FROM dns_zones WHERE zone_name=?", array($ZoneName));
			if (!$zoneinfo || $zoneinfo['env_id'] != $this->Environment->id)
				throw new Exception (sprintf("Zone '%s' not found in database", $ZoneName));
				
			$record_info = $this->DB->GetRow("SELECT * FROM dns_zone_records WHERE zone_id=? AND id=?", array($zoneinfo['id'], $RecordID)); 
			if (!$record_info)
				throw new Exception (sprintf("Record ID '%s' for zone '%s' not found in database", $RecordID, $ZoneName));
			
			if ($record_info['issystem'] == 1 && !$zoneinfo['allow_manage_system_records'])
				throw new Exception (sprintf("Record ID '%s' is system record and cannot be removed"));
				
			$response = $this->CreateInitialResponse();
				
			$this->DB->Execute("DELETE FROM dns_zone_records WHERE id=?", array($RecordID));
			
			$response->Result = 1;
			
			return $response;
		}
		
		public function DNSZoneRecordsList($ZoneName)
		{
			$zoneinfo = $this->DB->GetRow("SELECT id, env_id FROM dns_zones WHERE zone_name=?", array($ZoneName));
			if (!$zoneinfo || $zoneinfo['env_id'] != $this->Environment->id)
				throw new Exception (sprintf("Zone '%s' not found in database", $ZoneName));
			
			$response = $this->CreateInitialResponse();
				
			$response->ZoneRecordSet = new stdClass();
			$response->ZoneRecordSet->Item = array();
			
			$records = $this->DB->GetAll("SELECT * FROM dns_zone_records WHERE zone_id=?", array($zoneinfo['id']));
			foreach ($records as $record)
			{
				$itm = new stdClass();
				$itm->{"ID"} = $record['id'];
				$itm->{"Type"} = $record['type'];
				$itm->{"TTL"} = $record['ttl'];
				$itm->{"Priority"} = $record['priority'];
				$itm->{"Name"} = $record['name'];
				$itm->{"Value"} = $record['value'];
				$itm->{"Weight"} = $record['weight'];
				$itm->{"Port"} = $record['port'];
				$itm->{"IsSystem"} = $record['issystem'];
				
				$response->ZoneRecordSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		public function DNSZoneCreate($DomainName, $FarmID = null, $FarmRoleID = null)
		{
			$DomainName = trim($DomainName);
			
			$Validator = new Validator();
			if (!$Validator->IsDomain($DomainName))
				throw new Exception(_("Invalid domain name"));
				
			$domain_chunks = explode(".", $DomainName);				
			$chk_dmn = '';
			while (count($domain_chunks) > 0)
			{
				$chk_dmn = trim(array_pop($domain_chunks).".{$chk_dmn}", ".");
				if ($this->DB->GetOne("SELECT id FROM dns_zones WHERE zone_name=? AND client_id != ?", array($chk_dmn, $this->user->getAccountId())))
				{
					if ($chk_dmn == $DomainName)
						throw new Exception(sprintf(_("%s already exists on scalr nameservers"), $DomainName));
					else
						throw new Exception(sprintf(_("You cannot use %s domain name because top level domain %s does not belong to you"), $DomainName, $chk_dmn));
				}
			}
			
			if ($FarmID)
			{
				$DBFarm = DBFarm::LoadByID($FarmID);
				if ($DBFarm->EnvID != $this->Environment->id)
					throw new Exception(sprintf("Farm #%s not found", $FarmID));
			}
			
			if ($FarmRoleID)
			{
				$DBFarmRole = DBFarmRole::LoadByID($FarmRoleID);
				if ($DBFarm->ID != $DBFarmRole->FarmID)
					throw new Exception(sprintf("FarmRole #%s not found on Farm #%s", $FarmRoleID, $FarmID));
			}
			
			$response = $this->CreateInitialResponse();
			
			$DBDNSZone = DBDNSZone::create(
				$DomainName, 
				14400, 
				86400,
				str_replace('@', '.', $this->user->getEmail())
			);
			
			$DBDNSZone->farmRoleId = (int)$FarmRoleID;
			$DBDNSZone->farmId = (int)$FarmID;
			$DBDNSZone->clientId = $this->user->getAccountId();
			$DBDNSZone->envId = $this->Environment->id;
			
			$def_records = $this->DB->GetAll("SELECT * FROM default_records WHERE clientid=?", array($this->user->getAccountId()));
            foreach ($def_records as $record)
            {
                $record["name"] = str_replace("%hostname%", "{$DomainName}.", $record["name"]);
                $record["value"] = str_replace("%hostname%", "{$DomainName}.", $record["value"]);
            	$records[] = $record;
            }
            
            $nss = $this->DB->GetAll("SELECT * FROM nameservers WHERE isbackup='0'");
            foreach ($nss as $ns)
            	$records[] = array("id" => "c".rand(10000, 999999), "type" => "NS", "ttl" => 14400, "value" => "{$ns["host"]}.", "name" => "{$DomainName}.", "issystem" => 0);
			
			$DBDNSZone->setRecords($records);
			
			$DBDNSZone->save(true);
			
			$response->Result = 1;
			
			return $response;
		}
		
		/**********************************
		 * FARM Functions
		 */
		
		public function FarmTerminate($FarmID, $KeepEBS, $KeepEIP, $KeepDNSZone)
		{
			$response = $this->CreateInitialResponse();
			
			try
			{
				$DBFarm = DBFarm::LoadByID($FarmID);
				if ($DBFarm->EnvID != $this->Environment->id)
					throw new Exception("N");
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			}
							
			if ($DBFarm->Status != FARM_STATUS::RUNNING)
				throw new Exception(sprintf("Farm already terminated", $FarmID));
			
			$event = new FarmTerminatedEvent(
				(($KeepDNSZone) ? 0 : 1), 
				(($KeepEIP) ? 1 : 0),
				true,
				(($KeepEBS) ? 1 : 0)
			);
			Scalr::FireEvent($FarmID, $event);
			
			$response->Result = true;
			return $response;
		}
		
		public function FarmLaunch($FarmID)
		{
			$response = $this->CreateInitialResponse();
			
			try
			{
				$DBFarm = DBFarm::LoadByID($FarmID);
				if ($DBFarm->EnvID != $this->Environment->id)
					throw new Exception("N");
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			}
							
			if ($DBFarm->Status == FARM_STATUS::RUNNING)
				throw new Exception(sprintf("Farm already running", $FarmID));
			
			Scalr::FireEvent($FarmID, new FarmLaunchedEvent(true));
			
			$response->Result = true;
			return $response;
		}

		public function FarmGetStats($FarmID, $Date = null)
		{
			$response = $this->CreateInitialResponse();
			$response->StatisticsSet = new stdClass();
			$response->StatisticsSet->Item = array();
			
			preg_match("/([0-9]{2})\-([0-9]{4})/", $Date, $m);
			if ($m[1] && $m[2])
				$filter_sql = " AND month='".(int)$m[1]."' AND year='".(int)$m[2]."'";
			
			try
			{
				$DBFarm = DBFarm::LoadByID($FarmID);
				if ($DBFarm->EnvID != $this->Environment->id)
					throw new Exception("N");
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			}
				
			$rows = $this->DB->Execute("SELECT SUM(`usage`) AS `usage`, instance_type, cloud_location FROM servers_stats WHERE farm_id=? {$filter_sql} GROUP BY instance_type, month, year, cloud_location", 
				array($FarmID)
			);
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->Month = $row['month'];
				$itm->Year = $row['year'];
				$itm->Statistics = new stdClass();
				$itm->Statistics->{"Hours"} = round($row["usage"]/60, 2);
				$itm->Statistics->{"InstanceType"} = str_replace(".","_", $row["instance_type"]/60);
				$itm->Statistics->{"CloudLocation"} = $row["cloud_location"];
				
				$response->StatisticsSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		public function FarmsList()
		{
			$response = $this->CreateInitialResponse();
			$response->FarmSet = new stdClass();
			$response->FarmSet->Item = array();
			
			$farms = $this->DB->Execute("SELECT id, name, status, comments FROM farms WHERE env_id = ?", array($this->Environment->id));
			while ($farm = $farms->FetchRow())
			{
				$itm = new stdClass();
				$itm->{"ID"} = $farm['id'];
				$itm->{"Name"} = $farm['name'];
				$itm->{"Region"} = ""; // FIXME: Remove this from farms
				$itm->{"Status"} = $farm['status'];
				$itm->{"Comments"} = $farm['comments'];
				
				$response->FarmSet->Item[] = $itm; 
			}
			
			return $response;
		}
		
		public function FarmGetDetails($FarmID)
		{
			$response = $this->CreateInitialResponse();
			
			try
			{
				$DBFarm = DBFarm::LoadByID($FarmID);
				if ($DBFarm->EnvID != $this->Environment->id)
					throw new Exception("N");
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			}
				
			$response->FarmRoleSet = new stdClass();
			$response->FarmRoleSet->Item = array();
				
			foreach ($DBFarm->GetFarmRoles() as $DBFarmRole)
			{
				$itm = new stdClass();
				$itm->{"ID"} = $DBFarmRole->ID;
				$itm->{"RoleID"} = $DBFarmRole->RoleID;
				$itm->{"Name"} = $DBFarmRole->GetRoleObject()->name;
				$itm->{"Platform"} = $DBFarmRole->Platform;
				$itm->{"Category"} = ROLE_GROUPS::GetNameByBehavior($DBFarmRole->GetRoleObject()->getBehaviors());
				$itm->{"ScalingProperties"} = new stdClass();
					$itm->{"ScalingProperties"}->{"MinInstances"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
					$itm->{"ScalingProperties"}->{"MaxInstances"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);
				
				//TODO:
				if ($DBFarmRole->Platform == SERVER_PLATFORMS::EC2)
				{
					$itm->{"PlatformProperties"} = new stdClass();
					$itm->{"PlatformProperties"}->{"InstanceType"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_INSTANCE_TYPE);
					$itm->{"PlatformProperties"}->{"AvailabilityZone"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_AWS_AVAIL_ZONE);
				}
				elseif ($DBFarmRole->Platform == SERVER_PLATFORMS::RDS)
				{
					$itm->{"PlatformProperties"} = new stdClass();
					$itm->{"PlatformProperties"}->{"InstanceClass"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_RDS_INSTANCE_CLASS);
					$itm->{"PlatformProperties"}->{"AvailabilityZone"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_RDS_AVAIL_ZONE);
					$itm->{"PlatformProperties"}->{"InstanceEngine"} = $DBFarmRole->GetSetting(DBFarmRole::SETTING_RDS_INSTANCE_ENGINE);
				}
				
				$itm->{"ServerSet"} = new stdClass();
				$itm->{"ServerSet"}->Item = array();
				foreach ($DBFarmRole->GetServersByFilter() as $DBServer)
				{
					$iitm = new stdClass();
					$iitm->{"ServerID"} = $DBServer->serverId;
					$iitm->{"ExternalIP"} = $DBServer->remoteIp;
					$iitm->{"InternalIP"} = $DBServer->localIp;
					$iitm->{"Status"} = $DBServer->status;
					$iitm->{"ScalarizrVersion"} = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_VESION);
					$iitm->{"Uptime"} = round((time()-strtotime($DBServer->dateAdded))/60, 2); //seconds -> minutes
					
					$iitm->{"IsDbMaster"} = ($DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) == 1 || $DBServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) == 1) ? '1' : '0'; 
					
					if ($DBFarmRole->Platform == SERVER_PLATFORMS::EC2)
					{
						$iitm->{"PlatformProperties"} = new stdClass();
						$iitm->{"PlatformProperties"}->{"InstanceType"} = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_TYPE);
						$iitm->{"PlatformProperties"}->{"AvailabilityZone"} = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
						$iitm->{"PlatformProperties"}->{"AMIID"} = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::AMIID);
						$iitm->{"PlatformProperties"}->{"InstanceID"} = $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
					}
					elseif ($DBFarmRole->Platform == SERVER_PLATFORMS::RDS)
					{
						$iitm->{"PlatformProperties"} = new stdClass();
						$iitm->{"PlatformProperties"}->{"InstanceClass"} = $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_CLASS);
						$iitm->{"PlatformProperties"}->{"AvailabilityZone"} = $DBServer->GetProperty(RDS_SERVER_PROPERTIES::AVAIL_ZONE);
						$iitm->{"PlatformProperties"}->{"InstanceEngine"} = $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ENGINE);
						$iitm->{"PlatformProperties"}->{"InstanceID"} = $DBServer->GetProperty(RDS_SERVER_PROPERTIES::INSTANCE_ID);
					}
					
					$itm->{"ServerSet"}->Item[] = $iitm;				
				}		 
				
				$response->FarmRoleSet->Item[] = $itm; 
			}
			
			return $response;
		}
		
		
		/**********************************
		 * OTHER Functions
		 */
		
		public function EventsList($FarmID, $StartFrom = 0, $RecordsLimit = 20)
		{
			$farminfo = $this->DB->GetRow("SELECT id FROM farms WHERE id=? AND env_id=?",
				array($FarmID, $this->Environment->id)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
				
			$sql = "SELECT * FROM events WHERE farmid='{$FarmID}'";
				
			$total = $this->DB->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
		
			$sql .= " ORDER BY id DESC";
			
			$start = $StartFrom ? (int) $StartFrom : 0;
			$limit = $RecordsLimit ? (int) $RecordsLimit : 20;
			$sql .= " LIMIT {$start}, {$limit}";
			
			$response = $this->CreateInitialResponse();
			$response->TotalRecords = $total;
			$response->StartFrom = $start;
			$response->RecordsLimit = $limit;
			$response->EventSet = new stdClass();
			$response->EventSet->Item = array();
			
			$rows = $this->DB->Execute($sql);
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->ID = $row['event_id'];
				$itm->Type = $row['type'];
				$itm->Timestamp = strtotime($row['dtadded']);
				$itm->Message = $row['short_message'];
				
				$response->EventSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		public function LogsList($FarmID, $ServerID = null, $StartFrom = 0, $RecordsLimit = 20)
		{
			$farminfo = $this->DB->GetRow("SELECT clientid FROM farms WHERE id=? AND env_id=?",
				array($FarmID, $this->Environment->id)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
				
			$sql = "SELECT * FROM logentries WHERE farmid='{$FarmID}'";
			if ($ServerID)
				$sql .= " AND serverid=".$this->DB->qstr($ServerID);
				
			$total = $this->DB->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
		
			$sql .= " ORDER BY id DESC";
			
			$start = $StartFrom ? (int) $StartFrom : 0;
			$limit = $RecordsLimit ? (int) $RecordsLimit : 20;
			$sql .= " LIMIT {$start}, {$limit}";
			
			$response = $this->CreateInitialResponse();
			$response->TotalRecords = $total;
			$response->StartFrom = $start;
			$response->RecordsLimit = $limit;
			$response->LogSet = new stdClass();
			$response->LogSet->Item = array();
			
			$rows = $this->DB->Execute($sql);
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->ServerID = $row['serverid'];
				$itm->Message = $row['message'];
				$itm->Severity = $row['severity'];
				$itm->Timestamp = $row['time'];
				$itm->Source = $row['source'];
				
				$response->LogSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		public function ScriptGetDetails($ScriptID)
		{
			$script_info = $this->DB->GetRow("SELECT * FROM scripts WHERE id=?", array($ScriptID));
			if (!$script_info)
				throw new Exception(sprintf("Script ID: %s not found in our database (1)", $ScriptID));
				
			if ($script_info['origin'] == SCRIPT_ORIGIN_TYPE::CUSTOM && $this->user->getAccountId() != $script_info['clientid'])
				throw new Exception(sprintf("Script ID: %s not found in our database (2)", $ScriptID));
				
			if ($script_info['origin'] == SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED && $script_info['approval_state'] != APPROVAL_STATE::APPROVED)
				throw new Exception(sprintf("Script ID: %s not found in our database (3)", $ScriptID));
			
			$response = $this->CreateInitialResponse();
				
			$response->ScriptID = $ScriptID;
			$response->ScriptRevisionSet = new stdClass();
			$response->ScriptRevisionSet->Item = array();
			
			$revisions = $this->DB->GetAll("SELECT * FROM script_revisions WHERE scriptid=?", array($ScriptID));
			foreach ($revisions as $revision)
			{
				$itm = new stdClass();
				$itm->{"Revision"} = $revision['revision'];
				$itm->{"Date"} = $revision['dtcreated'];
				$itm->{"ConfigVariables"} = new stdClass();
				$itm->{"ConfigVariables"}->Item = array();
				
				$text = preg_replace('/(\\\%)/si', '$$scalr$$', $revision['script']);
				preg_match_all("/\%([^\%\s]+)\%/si", $text, $matches);
				$vars = $matches[1];
				$data = array();
			    foreach ($vars as $var)
			    {
			    	if (!in_array($var, array_keys(CONFIG::getScriptingBuiltinVariables())))
			    	{
			    		$ditm = new stdClass;
			    		$ditm->Name = $var;
			    		$itm->{"ConfigVariables"}->Item[] = $ditm;
			    	}
			    }
				
				$response->ScriptRevisionSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		public function ScriptExecute($ScriptID, $Timeout, $Async, $FarmID, $FarmRoleID = null, $ServerID = null, $Revision = null, array $ConfigVariables = null)
		{			
			$response = $this->CreateInitialResponse();
			
			$farminfo = $this->DB->GetRow("SELECT * FROM farms WHERE id=? AND env_id=?",
				array($FarmID, $this->Environment->id)
			);
			
			if (!$farminfo)
				throw new Exception(sprintf("Farm #%s not found", $FarmID));
			
			
			if ($FarmRoleID)
			{
				$dbFarmRole = DBFarmRole::LoadByID($FarmRoleID);
				if ($dbFarmRole->FarmID != $FarmID)
					throw new Exception (sprintf("FarmRole #%s not found on farm #%s", $FarmRoleID, $FarmID));
			}
		
				
			if (!$Revision)
				$Revision = 'latest';
				
			/* * */			
			if ($ServerID && !$FarmRoleID)
			{
				$DBServer = DBServer::LoadByID($ServerID);
				$FarmRoleID = $DBServer->farmRoleId;
			}
			
			$config = $ConfigVariables;
			$scriptid = (int)$ScriptID;
			if ($ServerID)
				$target = SCRIPTING_TARGET::INSTANCE;
			else if ($FarmRoleID)
				$target = SCRIPTING_TARGET::ROLE;
			else
				$target = SCRIPTING_TARGET::FARM;
			$event_name = 'APIEvent-'.date("YmdHi").'-'.rand(1000,9999);
			$version = $Revision;
			$farmid = (int)$FarmID;
			$timeout = (int)$Timeout;
			$issync = ($Async == 1) ? 0 : 1;
			
			$this->DB->Execute("INSERT INTO farm_role_scripts SET
				scriptid	= ?,
				farmid		= ?,
				farm_roleid	= ?,
				params		= ?,
				event_name	= ?,
				target		= ?,
				version		= ?,
				timeout		= ?,
				issync		= ?,
				ismenuitem	= ?
			", array(
				$scriptid, $farmid, $FarmRoleID, serialize($config), $event_name, $target, $version, $timeout, $issync, 0
			));
			
			$farm_rolescript_id = $this->DB->Insert_ID();
			
			switch($target)
			{
				case SCRIPTING_TARGET::FARM:
					
					$servers = $this->DB->GetAll("SELECT server_id FROM servers WHERE status IN (?,?) AND farm_id=?",
						array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $farmid)
					);
					
					break;
					
				case SCRIPTING_TARGET::ROLE:
					
					$servers = $this->DB->GetAll("SELECT server_id FROM servers WHERE status IN (?,?) AND farm_roleid=?",
						array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $FarmRoleID)
					);
					
					break;
					
				case SCRIPTING_TARGET::INSTANCE:
					
					$servers = $this->DB->GetAll("SELECT server_id FROM servers WHERE status IN (?,?) AND server_id=? AND farm_id=?",
						array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $DBServer->serverId, $farmid)
					);
					
					break;
			}
			
			//
			// Send Trap
			//
			if (count($servers) > 0)
			{			
				foreach ($servers as $server)
				{
					$DBServer = DBServer::LoadByID($server['server_id']);
					
					$msg = new Scalr_Messaging_Msg_ExecScript($event_name);
					$msg->meta[Scalr_Messaging_MsgMeta::EVENT_ID] = "FRSID-{$farm_rolescript_id}";
					
					$isEventNotice = !$DBServer->IsSupported("0.5");
					
					$DBServer->SendMessage($msg, $isEventNotice);
				}
			}
			
			
			$response->Result = true;
			return $response;
		}

		public function RolesList($Platform = null, $ImageID = null, $Name = null, $Prefix = null)
		{
			$response = $this->CreateInitialResponse();
			$response->RoleSet = new stdClass();
			$response->RoleSet->Item = array();
			
			$sql = "SELECT * FROM roles WHERE (env_id='0' OR env_id='{$this->Environment->id}')";
			
			if ($ImageID)
				$sql .= " AND id IN (SELECT role_id FROM role_images WHERE image_id = {$this->DB->qstr($ImageID)})";
				
			if ($Name)
				$sql .= " AND name = {$this->DB->qstr($Name)}";
			
			if ($Prefix)
				$sql .= " AND name LIKE{$this->DB->qstr("%{$Prefix}%")}";

			if ($Platform)
				$sql .= " AND id IN (SELECT role_id FROM role_images WHERE platform = {$this->DB->qstr($Platform)})";
				
			$rows = $this->DB->Execute($sql);
			while ($row = $rows->FetchRow())
			{
				if ($row['client_id'] == 0)
					$row["client_name"] = "Scalr";
				else
					$row["client_name"] = $this->DB->GetOne("SELECT fullname FROM clients WHERE id='{$row['client_id']}'");
					
				if (!$row["client_name"])
					$row["client_name"] = "";
					
				//TODO:
				/*
				$itm->{"ImageID"} = 
				$itm->{"BuildDate"} = 
				$itm->{"Category"} = 
				$itm->{"Platform"} = 
				 */
					
				$itm = new stdClass();
				$itm->{"Name"} = $row['name'];
				$itm->{"Owner"} = $row["client_name"];
				$itm->{"Architecture"} = $row['architecture'];
				
				//TODO:
				if ($row['platform'] == SERVER_PLATFORMS::EC2 || $row['platform'] == SERVER_PLATFORMS::RDS)
				{
					$itm->{"PlatformProperties"} = new stdClass();
					$itm->{"PlatformProperties"}->{"Region"} = $row['region'];
				}
				
				$response->RoleSet->Item[] = $itm; 
			}
			
			return $response;
		}
		
		public function ScriptsList()
		{
			$response = $this->CreateInitialResponse();
			$response->ScriptSet = new stdClass();
			$response->ScriptSet->Item = array();
			
			$filter_sql .= " AND ("; 
				// Show shared roles
				$filter_sql .= " origin='".SCRIPT_ORIGIN_TYPE::SHARED."'";
			
				// Show custom roles
				$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::CUSTOM."' AND clientid='{$this->user->getAccountId()}')";
				
				//Show approved contributed roles
				$filter_sql .= " OR (origin='".SCRIPT_ORIGIN_TYPE::USER_CONTRIBUTED."' AND (scripts.approval_state='".APPROVAL_STATE::APPROVED."' OR clientid='{$this->user->getAccountId()}'))";
			$filter_sql .= ")";
			
		    $sql = "SELECT 
		    			scripts.id, 
		    			scripts.name, 
		    			scripts.description, 
		    			scripts.origin,
		    			scripts.clientid,
		    			scripts.approval_state,
		    			MAX(script_revisions.dtcreated) as dtupdated, MAX(script_revisions.revision) AS version FROM scripts 
		    		INNER JOIN script_revisions ON script_revisions.scriptid = scripts.id 
		    		WHERE 1=1 {$filter_sql}";

		    $sql .= " GROUP BY script_revisions.scriptid";
		    
		    $rows = $this->DB->Execute($sql);
		    
		    while ($row = $rows->FetchRow())
		    {
		    	$itm = new stdClass();
				$itm->{"ID"} = $row['id'];
				$itm->{"Name"} = $row['name'];
				$itm->{"Description"} = $row['description'];
				$itm->{"LatestRevision"} = $row['version'];
				
				$response->ScriptSet->Item[] = $itm; 	
		    }
		    
		    return $response;
		}
			
		public function Hello()
		{
			$response = $this->CreateInitialResponse();	
			$response->Result = 1;
			return $response;
		}
		
		private $validObjectTypes = array('role','server','farm');
		private $validWatcherNames = array('CPU','MEM','LA','NET');
		private $validGraphTypes = array('daily','weekly','monthly','yearly');
		
		public function StatisticsGetGraphURL($ObjectType, $ObjectID, $WatcherName, $GraphType)
		{
			if (!in_array($ObjectType, $this->validObjectTypes))
				throw new Exception('Incorrect value of object type. Valid values are: role, instance and farm');

			if (!in_array($WatcherName, $this->validWatcherNames))
				throw new Exception('Incorrect value of watcher name. Valid values are: CPU, MEM, LA and NET');

			if (!in_array($GraphType, $this->validGraphTypes))
				throw new Exception('Incorrect value of graph type. Valid values are: daily, weekly, monthly and yearly');
				
			try
			{
				switch($ObjectType)
				{
					case 'role':
						
						$DBFarmRole = DBFarmRole::LoadByID($ObjectID);
						$DBFarm = $DBFarmRole->GetFarmObject();
						$role = "FR_{$DBFarmRole->ID}";
						
						break;
						
					case 'server':
						
						$DBServer = DBServer::LoadByID($ObjectID);
						$DBFarm = $DBServer->GetFarmObject();
						$role = "INSTANCE_{$DBServer->farmRoleId}_{$DBServer->index}";
						
						break;
						
					case 'farm':
						
						$DBFarm = DBFarm::LoadByID($ObjectID);
						$role = 'FARM';
						
						break;
				}
			}
			catch(Exception $e)
			{
				throw new Exception("Object #{$ObjectID} not found in database");
			}
			
			if ($DBFarm->EnvID != $this->Environment->id)
				throw new Exception("Object #{$ObjectID} not found in database");
				
			$response = $this->CreateInitialResponse();
				
			if (CONFIG::$MONITORING_TYPE == MONITORING_TYPE::REMOTE)
			{
				$_REQUEST['role_name'] = $_REQUEST['role'];

				$data = array(
					'task'			=> 'get_stats_image_url',
					'farmid'		=> $DBFarm->ID,
					'watchername'	=> "{$WatcherName}SNMP",
					'graph_type'	=> $GraphType,
					'role_name'		=> $role
				);
			
				$content = @file_get_contents(CONFIG::$MONITORING_SERVER_URL."/server/statistics.php?".http_build_query($data));
				$r = @json_decode($content);
				if ($r->type == 'ok')
					$response->GraphURL = $r->msg;
				else
				{
					if ($r->msg)
						throw new Exception($r->msg);
					else
						throw new Exception("Internal API error");
				}
			}
			else
			{
				//TODO:
				throw new Exception("This API method not implemented for Local monitoring type");
			}
			
			return $response;
		}
		
		/***********************************
		 * SERVER Functions
		 */
		
		public function BundleTaskGetStatus($BundleTaskID)
		{
			$BundleTask = BundleTask::LoadById($BundleTaskID);
			if ($BundleTask->envId != $this->Environment->id)
				throw new Exception(sprintf("Bundle task #%s not found", $BundleTaskID));
				
			$response = $this->CreateInitialResponse();	
            $response->BundleTaskStatus = $BundleTask->status;
            if ($BundleTask->status == SERVER_SNAPSHOT_CREATION_STATUS::FAILED)
            	$response->FailureReason = $BundleTask->failureReason;
            
            return $response;
		}
		
		public function ServerImageCreate($ServerID, $RoleName)
		{
			$DBServer = DBServer::LoadByID($ServerID);
    		
    		// Validate client and server
    		if ($DBServer->envId != $this->Environment->id)
    			throw new Exception(sprintf("Server ID #%s not found", $ServerID));
    			
    		//Check for already running bundle on selected instance
            $chk = $this->DB->GetOne("SELECT id FROM bundle_tasks WHERE server_id=? AND status NOT IN ('success', 'failed')", 
            	array($ServerID)
            );
            
            if ($chk)
            	throw new Exception(sprintf(_("Server '%s' is already synchonizing."), $ServerID));
            
            //Check is role already synchronizing...
            $chk = $this->DB->GetOne("SELECT server_id FROM bundle_tasks WHERE prototype_role_id=? AND status NOT IN ('success', 'failed')", array(
            	$DBServer->roleId
            ));
            if ($chk && $chk != $DBServer->serverId)
            {
            	try
            	{
            		$bDBServer = DBServer::LoadByID($chk);
	            	if ($bDBServer->farmId == $DBServer->farmId)
	            		throw new Exception(sprintf(_("Role '%s' is already synchonizing."), $DBServer->GetFarmRoleObject()->GetRoleObject()->name));
            	}
            	catch(Exception $e) {}
            }
            
            try {
            	$DBRole = DBRole::loadByFilter(array("name" => $RoleName, "env_id" => $DBServer->envId));
            }
            catch(Exception $e)
            {
            	
            }
            
            if (!$DBRole)
            {
            	$ServerSnapshotCreateInfo = new ServerSnapshotCreateInfo($DBServer, $RoleName, SERVER_REPLACEMENT_TYPE::NO_REPLACE, false, 'Bundled via API');
            	$BundleTask = BundleTask::Create($ServerSnapshotCreateInfo);
            	
            	$response = $this->CreateInitialResponse();
            	
            	$response->BundleTaskID = $BundleTask->id;
            	
            	return $response;
            }
            else
            	throw new Exception(_("Specified role name is already used by another role"));
		}
		
		public function ServerReboot($ServerID)
		{
			$DBServer = DBServer::LoadByID($ServerID);
			if ($DBServer->envId != $this->Environment->id)
				throw new Exception(sprintf("Server ID #%s not found", $ServerID));
			
			$response = $this->CreateInitialResponse();
				
			PlatformFactory::NewPlatform($DBServer->platform)->RebootServer($DBServer);
    		
    		$response->Result = true;
			return $response;
		}
		
		public function ServerLaunch($FarmRoleID, $IncreaseMaxInstances = false)
		{
			try
			{
				$DBFarmRole = DBFarmRole::LoadByID($FarmRoleID);
				$DBFarm = DBFarm::LoadByID($DBFarmRole->FarmID);
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf("Farm Role ID #%s not found", $FarmRoleID));
			}
			
			if ($DBFarm->EnvID != $this->Environment->id)
				throw new Exception(sprintf("Farm Role ID #%s not found", $FarmRoleID));

			//TODO: Remove this limitation
			/*
			$isSzr = $dbFarmRole->GetRoleObject()->isSupported("0.5");
				
			if ($retval == Scalr_Scaling_Decision::UPSCALE && ($dbFarmRole->GetPendingInstancesCount() > 5 || !$isSzr))
			 */
			$isSzr = $DBFarmRole->GetRoleObject()->isSupported("0.5");	
				
			$n = $DBFarmRole->GetPendingInstancesCount(); 
			if ($n >= 5 && !$isSzr)
				throw new Exception("There are {$n} pending instances. You cannot launch new instances while you have 5 pending ones.");
			
			$response = $this->CreateInitialResponse();
				
			$max_instances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES);
			$min_instances = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES);
			
			if ($IncreaseMaxInstances) {
        		if ($max_instances < $min_instances+1)
        			$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $max_instances+1);
			}
			
			if ($DBFarmRole->GetRunningInstancesCount()+$DBFarmRole->GetPendingInstancesCount() >= $max_instances)
			{
				if ($IncreaseMaxInstances)
					$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $max_instances+1);
				else
					throw new Exception("Max instances limit reached. Use 'IncreaseMaxInstances' parameter or increase max isntances settings in UI");
			}
	
        	if ($DBFarmRole->GetRunningInstancesCount() < $min_instances || $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES) < $min_instances)
        		$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, $min_instances+1);
        	        	
			$ServerCreateInfo = new ServerCreateInfo($DBFarmRole->Platform, $DBFarmRole);
			try {
				$DBServer = Scalr::LaunchServer($ServerCreateInfo);
											
				Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, sprintf("Starting new instance (API). ServerID = %s.", $DBServer->serverId)));
			}
			catch(Exception $e){
				Logger::getLogger(LOG_CATEGORY::API)->error($e->getMessage());
			}
            	
            $response->ServerID = $DBServer->serverId;
            
			return $response;
		}
		
		public function ServerTerminate($ServerID, $DecreaseMinInstancesSetting = false)
		{			
			$DBServer = DBServer::LoadByID($ServerID);
			if ($DBServer->envId != $this->Environment->id)
				throw new Exception(sprintf("Server ID #%s not found", $ServerID));
			
				
			$response = $this->CreateInitialResponse();

			PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
    		
			$this->DB->Execute("UPDATE servers_history SET
				dtterminated	= NOW(),
				terminate_reason	= ?
				WHERE server_id = ?
			", array(
				sprintf("Terminated via API. TransactionID: %s", $response->TransactionID),
				$DBServer->serverId
			));
			
    		if ($DecreaseMinInstancesSetting)
    		{
    			$DBFarmRole = $DBServer->GetFarmRoleObject();
    			
    			if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES) > 1)
    			{
	    			$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, 
	    				$DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES)-1
	    			);
    			}
    		}
    		
    		$response->Result = true;
			return $response;
		}

	}
?>