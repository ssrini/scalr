<?
	try
	{
		require(dirname(__FILE__)."/../src/prepend.inc.php");
		
		if ($req_FarmID && $req_Hash)
		{
			$chunks = explode(";", $req_Data);
			foreach ($chunks as $chunk)
			{
				$dt = explode(":", $chunk);
				$data[$dt[0]] = trim($dt[1]);
			}
						
			// prepare GET params
			$farm_id = (int)$req_FarmID;
			$hash = preg_replace("/[^A-Za-z0-9]+/", "", $req_Hash);
		
			$pkg_ver = $req_PkgVer;
		
			//TODO:
			//Logger::getLogger('EVENT_HANDLER')->info(new FarmLogMessage($farm_id, http_build_query($_REQUEST)));
			
			
			//Logger::getLogger('EVENT_HANDLER')->info(http_build_query($_REQUEST));
			
			// Add log infomation about event received from instance
			//$Logger->info("Event data: {$req_Data}");
			//$Logger->info("Scalarizr version: {$req_PkgVer}");
			
			// Get farminfo and instanceinfo from database
			$farminfo = $db->GetRow("SELECT id FROM farms WHERE id=? AND hash=?", array($farm_id, $hash));
			$DBFarm = DBFarm::LoadByID($farminfo['id']);
			
			$DBServer = DBServer::LoadByPropertyValue(EC2_SERVER_PROPERTIES::INSTANCE_ID, $req_InstanceID);
			if ($DBServer->farmId != $DBFarm->ID)
				throw new Exception("Server not found");
				
			if ($DBServer->GetProperty(SERVER_PROPERTIES::SZR_VESION) != $pkg_ver)
				$DBServer->SetProperty(SERVER_PROPERTIES::SZR_VESION, $pkg_ver);
						
			/**
			 * Deserialize data from instance
			 */
			if (!$DBServer->localIp)
				$DBServer->localIp = $data["localip"];
			
			if ($DBServer->localIp == $_SERVER['REMOTE_ADDR'] && $DBServer->platform == SERVER_PLATFORMS::EC2)
			{
				if (!$DBServer->remoteIp)
				{
					try
					{
						$ips = PlatformFactory::NewPlatform($DBServer->platform)->GetServerIPAddresses($DBServer);
				    	
				    	$_SERVER['REMOTE_ADDR'] = $ips['remoteIp'];
				    	
				    	$Logger->info(sprintf("Instance external ip = '%s'", $_SERVER['REMOTE_ADDR']));
					}
					catch(Exception $e)
					{
						$Logger->fatal(sprintf(_("Cannot determine external IP for instance %s: %s"),
							$req_InstanceID, $e->getMessage()
						));
						exit();
					}
				}
				else
					$_SERVER['REMOTE_ADDR'] = $DBServer->remoteIp;
			}
						
			//************************//
			
			if ($DBFarm && $DBServer)
			{								
				$instance_events = array(
            		"hostInit" 			=> "HostInit",
            		"hostUp" 			=> "HostUp",
            		"rebootFinish" 		=> "RebootComplete",
            		"IPAddressChanged" 	=> "IPAddressChanged",
            		"newMysqlMaster"	=> "NewMysqlMasterUp"
            	);
						
				switch ($req_EventType)
				{
					case "go2Halt": break;
		
					case "hostDown":
							$event = new HostDownEvent($DBServer);
						break;
						
					case "rebootFinish":
							$event = new RebootCompleteEvent($DBServer);
						break;
						
					case "rebootStart":
							$event = new RebootBeginEvent($DBServer);
						break;
						
					case "ebsVolumeAttached":
						
							$event = new EBSVolumeAttachedEvent(
								$DBServer,
								$data["device_name"],
								$data["volume_id"]
							);
						
						break;
						
					case "hostUp":
						
						switch ($DBServer->status)
						{
							case SERVER_STATUS::INIT:
									$event = new HostUpEvent($DBServer, $data['ReplUserPass']);
								break;
		
							default:
									$Logger->error("Strange situation. Received hostUp event from instance '{$DBServer->serverId}' ('{$_SERVER['REMOTE_ADDR']}') with state {$DBServer->status}!");
								break;
						}
						
						break;
											 
					case "newMysqlMaster":
						
							$servers = $DBFarm->GetMySQLInstances(true);
							
							$event = new NewMysqlMasterUpEvent($DBServer, $data['snapurl'], $servers[0]);
						break;
		
					case "hostInit":
						
							switch ($DBServer->status)
							{
								case SERVER_STATUS::PENDING:
										$event = new HostInitEvent(
											$DBServer, 
											$data["localip"], 
											$_SERVER['REMOTE_ADDR'], 
											base64_decode($data["based64_pubkey"])
										);
									break;
			
								default:
										$Logger->error("Strange situation. Received hostInit event from instance '{$DBServer->serverId}' ('{$_SERVER['REMOTE_ADDR']}') with state {$DBServer->status}!");
									break;
							}
						
							
						break;
		
					case "rebundleFail":
							$event = new RebundleFailedEvent($DBServer, $data['bundletaskid']);							
						break;
		
					case "mysqlBckComplete":
							$event = new MysqlBackupCompleteEvent($DBServer, $data["operation"], $data['snapinfo']);
						break;
		
					case "mysqlBckFail":
							$event = new MysqlBackupFailEvent($DBServer, $data["operation"]);
						break;
						
					case "newAMI":	

							if (!$data['bundletaskid'])
							{
								$data['bundletaskid'] = $db->GetOne("SELECT id FROM bundle_tasks WHERE status=? AND server_id=?", array(
									SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS, $DBServer->serverId
								));
							}
						
							$event = new RebundleCompleteEvent($DBServer, $data["amiid"], $data['bundletaskid']);
						break;
											
				    //********************
				    //* LOG Events
				    //********************		
					case "mountResult":
						
						if ($data['isarray'] == 1)
						{
							// EBS array
							$db->Execute("UPDATE ebs_arrays SET status=?, isfscreated='1' WHERE name=? AND serevr_id=?",
								array(EBS_ARRAY_STATUS::IN_USE, $data['name'], $DBServer->serverId)
							);
						}
						else
						{
							// Single volume
							$ebsinfo = $db->GetRow("SELECT * FROM ec2_ebs WHERE volume_id=?", array($data['name']));
							if ($ebsinfo)
								$db->Execute("UPDATE ec2_ebs SET mount_status=?, isfsexist='1' WHERE id=?", array(EC2_EBS_MOUNT_STATUS::MOUNTED, $ebsinfo['id']));
						}
						
						if ($data['mountpoint'] && $data['success'] == 1)
							Scalr::FireEvent($req_FarmID, new EBSVolumeMountedEvent($DBServer, $data['mountpoint'], $data['name']));
						
						break;
					
					case "basicEvent":
						
						if ($data['MessageName'] == BASIC_MESSAGE_NAMES::MYSQL_PMA_CREDENTIALS)
						{
							$DBFarmRole = DBFarmRole::LoadByID($data['Arg1']);
							if ($data['PmaUser'] && $data['PmaPass'])
							{
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_USER, $data['PmaUser']);
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_PASS, $data['PmaPass']);
							}
							else
							{
								if (!$data['Error'])
									$data['Error'] = 'Cannot create mysql user/password for PMA.';
								else
									$data['Error'] = base64_decode($data['Error']);
									
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME, "");
								$DBFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR, $data['Error']);
							}
						}
						
						break;
						
					case "trapACK": 
					
						$db->Execute("UPDATE messages SET status=? WHERE messageid=?", array(MESSAGE_STATUS::HANDLED, $data['trap_id']));
						
						break;
						
					case "scriptingLog":
												
	            		$event_name = ($instance_events[$data['eventName']]) ? $instance_events[$data['eventName']] : $data['eventName'];  
	            		
						$Logger->info(new ScriptingLogMessage(
							$req_FarmID, 
							$event_name,
							$DBServer->serverId,
							base64_decode($data['msg'])
						));
						
						break;
						
					case "execResult":
							
							$event_name = ($instance_events[$data['eventName']]) ? $instance_events[$data['eventName']] : $data['eventName'];
						
							$stderr = base64_decode($data['stderr']);
							if (trim($stderr))
								$stderr = "\n stderr: {$stderr}";
								
							$stdout = base64_decode($data['stdout']);
							if (trim($stdout))
								$stdout = "\n stdout: {$stdout}";
						
							if (!$stderr && !$stdout)
								$stdout = _("Script executed without any output.");
								
							$Logger->warn(new ScriptingLogMessage(
								$req_FarmID, 
								$event_name,
								$DBServer->serverId,
								sprintf(_("Script '%s' execution result (Execution time: %s seconds). %s %s"), 
									$data['script_path'], $data['time_elapsed'], $stderr, $stdout
								)
							));
						
						break;
												
					case "rebundleStatus":
					
						if ($data['bundletaskid'])
						{
							try
							{
								$BundleTask = BundleTask::LoadById($data['bundletaskid']);
								if ($BundleTask->serverId == $DBServer->serverId)
									$BundleTask->Log($data['message']);
							}
							catch(Exception $e){}
						}
						
						break;
					
					case "logEvent":
	
						$message = base64_decode($data["msg"]);
						$db->Execute("INSERT DELAYED INTO logentries SET serverid=?, message=?, time=?, severity=?, source=?, farmid=?", 
							array($DBServer->serverId, $message, time(), $data["severity"], $data["source"], $req_FarmID)
						);
	
						break;
				}
			}
		}
		
		if ($event)
		{
			Scalr::FireEvent($DBFarm->ID, $event);
		}
		
		exit();
	}
    catch(Exception $e)
    {
    	header("HTTP/1.0 500 Internal Server Error");
    	die($e->getMessage());
    }
?>
