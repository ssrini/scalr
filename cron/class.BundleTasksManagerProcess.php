<?
	class BundleTasksManagerProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Bundle tasks manager";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = Logger::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            $this->ThreadArgs = $db->GetAll("SELECT id FROM bundle_tasks WHERE status NOT IN (?,?,?)", array(
            	SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS,
            	SERVER_SNAPSHOT_CREATION_STATUS::FAILED,
            	SERVER_SNAPSHOT_CREATION_STATUS::CANCELLED
            ));
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." bundle tasks.");
        }
        
        public function OnEndForking()
        {
			
        }
        
        public function StartThread($bundle_task_info)
        {
         	$db = Core::GetDBInstance();
         	
         	// Reconfigure observers;
        	Scalr::ReconfigureObservers();
         	
         	$BundleTask = BundleTask::LoadById($bundle_task_info['id']);
         	
        	try
         	{
         		$DBServer = DBServer::LoadByID($BundleTask->serverId);
         	}
         	catch (ServerNotFoundException $e)
         	{
         		if (!$BundleTask->snapshotId)
         		{
         			$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::FAILED;
         			$BundleTask->setDate('finished');
         			$BundleTask->failureReason = sprintf(_("Server '%s' was terminated during snapshot creation process"), $BundleTask->serverId);
         			$BundleTask->Save();
         			return;
         		}
         	}
         	catch (Exception $e)
         	{
         		//$this->Logger->error($e->getMessage());
         	}
         	
         	switch($BundleTask->status)
         	{
         		case SERVER_SNAPSHOT_CREATION_STATUS::STARING_SERVER:
         		case SERVER_SNAPSHOT_CREATION_STATUS::PREPARING_ENV:
         		case SERVER_SNAPSHOT_CREATION_STATUS::INTALLING_SOFTWARE:
         			
         			if (!PlatformFactory::NewPlatform($DBServer->platform)->IsServerExists($DBServer)) {
         				
         				$DBServer->status = SERVER_STATUS::TERMINATED;
         				$DBServer->save();
         				$BundleTask->SnapshotCreationFailed("Server was terminated and no longer available in cloud.");
         				exit();
         			}
         			
         			// IF server is in pensing state
         			$status = PlatformFactory::NewPlatform($DBServer->platform)->GetServerRealStatus($DBServer);
         			$BundleTask->Log(sprintf(_("Server status: %s"), $status->getName()));
         			if ($status->isPending()) {
         				$BundleTask->Log(sprintf(_("Waiting for running state."), $status->getName()));
         				exit();
         			}
         			elseif ($status->isTerminated()) {
         				$DBServer->status = SERVER_STATUS::TERMINATED;
         				$DBServer->save();
         				$BundleTask->SnapshotCreationFailed("Server was terminated and no longer available in cloud.");
         				exit();
         			}
         			
         		break;
         	}
         	
         	switch($BundleTask->status)
         	{
         		case SERVER_SNAPSHOT_CREATION_STATUS::STARING_SERVER:
         			
         			$ips = PlatformFactory::NewPlatform($DBServer->platform)->GetServerIPAddresses($DBServer);
         			
         			$DBServer->remoteIp = $ips['remoteIp'];
         			$DBServer->localIp = $ips['locateIp'];
         			$DBServer->save();
         			
         			$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::PREPARING_ENV;
         			$BundleTask->save();
         			
         			$BundleTask->Log(sprintf(_("Bundle task status: %s"), $BundleTask->status));
         			
         			break;
         			
         		case SERVER_SNAPSHOT_CREATION_STATUS::PREPARING_ENV:
         			
         			$BundleTask->Log(sprintf(_("Initializing SSH2 session to the server")));
         			
         			try {
	         			$ssh2Client = $DBServer->GetSsh2Client();
	         			$ssh2Client->connect($DBServer->remoteIp, 22);
         			} catch(Exception $e) {
         				$BundleTask->Log(sprintf(_("Scalr unable to establish SSH connection with server on %:%. Error: %s"),
         					$DBServer->remoteIp,
         					22,
         					$e->getMessage()
         				));
         				
         				//TODO: Set status of bundle log to failed
         				
         				exit();
         			}
         			
         			//Prepare script
         			$BundleTask->Log(sprintf(_("Uploading builder scripts...")));
         			
         			$behaviors = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_BEHAVIOR);
         			
         			try {
         				
	         			$options = array(
							'server-id' 	=> $DBServer->serverId,
							'role-name' 	=> $BundleTask->roleName,
							'crypto-key' 	=> $DBServer->GetProperty(SERVER_PROPERTIES::SZR_KEY),
							'platform' 		=> $DBServer->platform,
							'behaviour' 	=> trim(str_replace("base", "", $behaviors)),
							'queryenv-url' 	=> CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/query-env",
							'messaging-p2p.producer-url' => CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/messaging"
						);
		
						$command = 'scalarizr --import -y';
						foreach ($options as $k => $v) {
							$command .= sprintf(' -o %s=%s', $k, $v);
						}
         				
						if ($DBServer->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_MYSQL_SERVER_TYPE) == 'percona')
							$recipes = 'mysql=percona';
						else
							$recipes = '';
						
						$scalarizrBranch = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_DEV_SCALARIZR_BRANCH);
						$scriptContents = @file_get_contents(APPPATH."/templates/services/role_builder/chef_import.tpl");
						$scriptContents = str_replace(array(
							"%PLATFORM%",
							"%BEHAVIOURS%",
							"%SZR_IMPORT_STRING%",
							"%DEV%",
							"%SCALARIZR_BRANCH%",
							"%RECIPES%",
							"%BUILD_ONLY%",
							"%CHEF_SERVER_URL%", 
							"%CHEF_VALIDATOR_NAME%", 
							"%CHEF_VALIDATOR_KEY%", 
							"%CHEF_ENVIRONMENT%", 
							"%CHEF_ROLE%", 
							"%CHEF_NODE_NAME%",
							"\r\n"
						), array(
							$DBServer->platform,
							trim(str_replace("base", "", str_replace(",", " ", $behaviors))),
							$command,
							$scalarizrBranch ? '1' : '0',
							$scalarizrBranch,
							$recipes,
							'0',
							'',
							'',
							'',
							'',
							'',
							'',
							"\n"
						), $scriptContents);
						
         				if (!$ssh2Client->sendFile('/tmp/scalr-builder.sh', $scriptContents, "w+", false))
							throw new Exception("Cannot upload script");
							
						$BundleTask->Log(sprintf(_("Uploading chef recipes...")));
							
						if (!$ssh2Client->sendFile('/tmp/recipes.tar.gz', APPPATH . '/www/storage/chef/recipes.tar.gz'))
							throw new Exception("Cannot upload chef recipes");
							
         			} catch(Exception $e) {
         				$BundleTask->Log(sprintf(_("Scripts upload failed: %s"), $e->getMessage()));
         				
         				//TODO: Set status of bundle log to failed
         				
         				exit();
         			}
         			
         			$BundleTask->Log("Launching role builder routines on server");
         			
         			$ssh2Client->exec("chmod 0777 /tmp/scalr-builder.sh");
         			$ssh2Client->exec("setsid /tmp/scalr-builder.sh > /var/log/role-builder-output.log 2>&1 &");        			
         			
         			$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::INTALLING_SOFTWARE;
         			$BundleTask->save();
         			
         			break;
         		
         		case SERVER_SNAPSHOT_CREATION_STATUS::INTALLING_SOFTWARE:
         			
         			try {
	         			$ssh2Client = $DBServer->GetSsh2Client();
	         			$ssh2Client->connect($DBServer->remoteIp, 22);
         			} catch(Exception $e) {
         				$BundleTask->Log(sprintf(_("Scalr unable to establish SSH connection with server on %:%. Error: %s"),
         					$DBServer->remoteIp,
         					22,
         					$e->getMessage()
         				));
         				//TODO: Set status of bundle log to failed
         				
         				exit();
         			}
         			
         			$log = $ssh2Client->getFile('/var/log/role-builder-output.log');
         			$log_lines = explode("\r\n", $log);
         			$last_msg = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_LAST_LOG_MESSAGE);
					while ($msg = trim(array_shift($log_lines))) {
						
						if (substr($msg, -1, 1) != ']')
							continue;
						
						if ($last_msg) {
							if ($msg != $last_msg)
								continue;
							elseif ($msg == $last_msg) {
								$last_msg = null;
								continue;
							}
						}
						
						if (stristr($msg, '[ Failed ]')) {
							$BundleTask->SnapshotCreationFailed($msg);
							
							/** Terminate server **/
							PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
							$DBServer->status = SERVER_STATUS::PENDING_TERMINATE;
							$DBServer->save();
							
							$BundleTask->Log(sprintf("Temporary server '%s' (%s) has been terminated", $DBServer->serverId, $DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID)));
						}
						else {
							$BundleTask->Log($msg);
							$DBServer->SetProperty(SERVER_PROPERTIES::SZR_IMPORTING_LAST_LOG_MESSAGE, $msg);
						}
					}
         			
         			//Read /var/log/role-builder-output.log
         			
         			break;
	
         		case SERVER_SNAPSHOT_CREATION_STATUS::PENDING:
         			
	         		try {
						$platformModule = PlatformFactory::NewPlatform($BundleTask->platform);
						$platformModule->CreateServerSnapshot($BundleTask);
					}
					catch(Exception $e) {	
						Logger::getLogger(LOG_CATEGORY::BUNDLE)->error($e->getMessage());
						$BundleTask->SnapshotCreationFailed($e->getMessage());
					}
         			
         			break;
         			
         		case SERVER_SNAPSHOT_CREATION_STATUS::PREPARING:
         			
         			$addedTime = strtotime($BundleTask->dateAdded);
         			if ($addedTime+3600 < time())
         				$BundleTask->SnapshotCreationFailed("Server didn't send PrepareBundleResult message in time.");
         			
         			break;
         			
         		case SERVER_SNAPSHOT_CREATION_STATUS::IN_PROGRESS:
         			
         			PlatformFactory::NewPlatform($BundleTask->platform)->CheckServerSnapshotStatus($BundleTask);
         			
         			break;
         			
         		case SERVER_SNAPSHOT_CREATION_STATUS::REPLACING_SERVERS:
         			
         			$r_farm_roles = array();
         			
         			$BundleTask->Log(sprintf("Bundle task replacement type: %s", $BundleTask->replaceType));
         			
         			try {
         				$DBFarm = DBFarm::LoadByID($BundleTask->farmId);
         			} catch (Exception $e)
         			{
         				if (stristr($e->getMessage(), "not found in database")) {
         					$BundleTask->SnapshotCreationFailed("Farm was removed before task was finished");
         				}
         				
         				return;
         			}
         			
         			if ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::REPLACE_FARM)
         			{			         		
         				try {
         					$r_farm_roles[] = $DBFarm->GetFarmRoleByRoleID($BundleTask->prototypeRoleId);	
         				} catch (Exception $e) {
         				}
         				
         			}
         			elseif ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::REPLACE_ALL)
         			{
         				$farm_roles = $db->GetAll("SELECT id FROM farm_roles WHERE role_id=? AND new_role_id=? AND farmid IN (SELECT id FROM farms WHERE env_id=?)", array(
         					$BundleTask->prototypeRoleId,
         					$BundleTask->roleId,
         					$BundleTask->envId
         				));
         				foreach ($farm_roles as $farm_role)
         				{
         					try
         					{
         						$r_farm_roles[] = DBFarmRole::LoadByID($farm_role['id']);
         					}
         					catch(Exception $e){}
         				}
         			}
         			
         			$update_farm_dns_zones = array();
         			$completed_roles = 0;
         			foreach ($r_farm_roles as $DBFarmRole)
         			{
	         			if ($DBFarmRole->CloudLocation != $BundleTask->cloudLocation) {
	         				$BundleTask->Log(sprintf("Role '%s' (ID: %s), farm '%s' (ID: %s) using the same role but in abother cloud location. Skiping it.", 
			         			$DBFarmRole->GetRoleObject()->name,
			         			$DBFarmRole->ID,
			         			$DBFarmRole->GetFarmObject()->Name,
			         			$DBFarmRole->FarmID
			         		));
			         		
			         		$completed_roles++;
	         			}
         				else {
	         				$servers = $db->GetAll("SELECT server_id FROM servers WHERE farm_roleid = ? AND role_id=? AND status NOT IN (?,?)", array(
			         			$DBFarmRole->ID, $DBFarmRole->RoleID, SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_TERMINATE
			         		));
			         		
			         		$BundleTask->Log(sprintf("Found %s servers that need to be replaced with new ones. Role '%s' (ID: %s), farm '%s' (ID: %s)", 
			         			count($servers), 
			         			$DBFarmRole->GetRoleObject()->name,
			         			$DBFarmRole->ID,
			         			$DBFarmRole->GetFarmObject()->Name,
			         			$DBFarmRole->FarmID
			         		));
			         		
			         		if (count($servers) == 0)
			         		{
			         			$DBFarmRole->RoleID = $DBFarmRole->NewRoleID;
			         			$DBFarmRole->NewRoleID = null;
			         			$DBFarmRole->Save();
			         					         			
			         			$update_farm_dns_zones[$DBFarmRole->FarmID] = 1;
			         			
			         			$completed_roles++;
			         		}
			         		else
			         		{		         			
			         			$metaData = $BundleTask->getSnapshotDetails();
			         			
			         			foreach ($servers as $server)
			         			{
			         				try
			         				{
			         					$DBServer = DBServer::LoadByID($server['server_id']);
			         				}
			         				catch(Exception $e)
			         				{
			         					//TODO:
			         					continue;
			         				}
			         				
			         				
			         				
			         				if ($DBServer->serverId == $BundleTask->serverId || $metaData['noServersReplace'])
			         				{
			         					$DBServer->roleId = $BundleTask->roleId;
			         					$DBServer->Save();
			         					
			         					if ($metaData['noServersReplace']) {
			         						$BundleTask->Log(sprintf("'Do not replace servers' option was checked. Server '%s' won't be replaced to new image.", 
						         				$DBServer->serverId
						         			));	
			         					} else {
			         						$BundleTask->Log(sprintf("Server '%s', on which snapshot has been taken, already has all modifications. No need to replace it.", 
						         				$DBServer->serverId
						         			));
			         					}
			         					
			         					if ($DBServer->GetFarmObject()->Status == FARM_STATUS::SYNCHRONIZING)
			         					{
			         						PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
			         						
			         						$db->Execute("UPDATE servers_history SET
												dtterminated	= NOW(),
												terminate_reason	= ?
												WHERE server_id = ?
											", array(
												sprintf("Farm was in 'Synchronizing' state. Server terminated when bundling was completed. Bundle task #%s", $BundleTask->id),
												$DBServer->serverId
											));
			         					}
			         				}
			         				else
			         				{
				         				if (!$db->GetOne("SELECT server_id FROM servers WHERE replace_server_id=? AND status NOT IN (?,?)", 
				         					array($DBServer->serverId, SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_TERMINATE)
				         				)) {
				         					$ServerCreateInfo = new ServerCreateInfo($DBFarmRole->Platform, $DBFarmRole, $DBServer->index, $DBFarmRole->NewRoleID);
											$nDBServer = Scalr::LaunchServer($ServerCreateInfo);
											$nDBServer->replaceServerID = $DBServer->serverId;
											
											$nDBServer->Save();
											
											$BundleTask->Log(sprintf(_("Started new server %s to replace server %s"), 
					         					$nDBServer->serverId,
					         					$DBServer->serverId
					         				));
				         				}
			         				} // if serverid != bundletask->serverID
			         			} // foreach server
			         		} // count($servers)
         				}
         			}
         			

         			
         			if ($completed_roles == count($r_farm_roles))
         			{
         				$BundleTask->Log(sprintf(_("No servers with old role. Replacement complete. Bundle task complete."), 
         					SERVER_REPLACEMENT_TYPE::NO_REPLACE, SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS
         				));
         				
         				$BundleTask->setDate('finished');
	         			$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS;
	         			$BundleTask->Save();
         			}
         			
         			try {
	         			if (count($update_farm_dns_zones) != 0)
	         			{
	         				foreach ($update_farm_dns_zones as $farm_id => $v)
	         				{
	         					$dnsZones = DBDNSZone::loadByFarmId($farm_id);
	         					foreach ($dnsZones as $dnsZone)
	         					{
	         						if ($dnsZone->status != DNS_ZONE_STATUS::INACTIVE && $dnsZone->status != DNS_ZONE_STATUS::PENDING_DELETE)
	         						{
		         						$dnsZone->updateSystemRecords();
		         						$dnsZone->save();
	         						}
	         					}
	         				}
	         			}
         			}
         			catch(Exception $e)
         			{
         				$this->Logger->fatal("DNS ZONE: {$e->getMessage()}");
         			}
         			
         			break;
         			
         		case SERVER_SNAPSHOT_CREATION_STATUS::CREATING_ROLE:
         			
         			try
         			{
         				if ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::REPLACE_ALL)
         				{
	         				$saveOldRole = false;
         					try {
	         					$dbRole = DBRole::loadById($DBServer->roleId);
	         					if ($dbRole->name == $BundleTask->roleName && $dbRole->envId == $BundleTask->envId)
	         						$saveOldRole = true;
	         				}
	         				catch(Exception $e){
	         					//NO OLD ROLE
	         				}
	         				
	         				if ($dbRole && $saveOldRole)
	         				{
	         					if ($DBServer)
	         						$new_role_name = BundleTask::GenerateRoleName($DBServer->GetFarmRoleObject(), $DBServer);
	         					else
	         						$new_role_name = $BundleTask->roleName."-".rand(1000, 9999);
	         					
	         					
	         					$dbRole->name = $new_role_name;
	         					
	         					$BundleTask->Log(sprintf(_("Old role '%s' (ID: %s) renamed to '%s'"), 
		         					$BundleTask->roleName, $dbRole->id, $new_role_name
		         				));
		         				
		         				$dbRole->save();
	         				}
	         				else
	         				{
	         					//TODO:
	         					//$this->Logger->error("dbRole->replace->fail({$BundleTask->roleName}, {$BundleTask->envId})");
	         				}
         				}
         				
         				try {
         					$DBRole = DBRole::createFromBundleTask($BundleTask);
         				} catch (Exception $e)
         				{
         					$BundleTask->SnapshotCreationFailed("Role creation failed due to internal error ({$e->getMessage()}). Please try again.");
         					return;
         				}
	         			
	         			if ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::NO_REPLACE)
	         			{
	         				$BundleTask->setDate('finished');
	         				$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS;
	         				
	         				$BundleTask->Log(sprintf(_("Replacement type: %s. Bundle task status: %s"), 
	         					SERVER_REPLACEMENT_TYPE::NO_REPLACE, SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS
	         				));
	         				
	         				try
	         				{
		         				$DBServer = DBServer::LoadByID($BundleTask->serverId);
		         				if ($DBServer->status == SERVER_STATUS::IMPORTING)
		         				{
		         					if ($DBServer->farmId)
		         					{
		         						// Create DBFarmRole object
		         						// TODO: create DBFarm role
		         					}
		         					
		         					//$DBServer->Delete();
		         				}
	         				}
	         				catch(Exception $e)
	         				{
	         					
	         				}
	         			}
	         			else
	         			{
		         			try
	         				{
	         					$BundleTask->Log(sprintf(_("Replacement type: %s. Bundle task status: %s"), 
		         					$BundleTask->replaceType, SERVER_SNAPSHOT_CREATION_STATUS::REPLACING_SERVERS
		         				));
	         					
	         					if ($BundleTask->replaceType == SERVER_REPLACEMENT_TYPE::REPLACE_FARM)
         						{
	         						$DBFarm = DBFarm::LoadByID($BundleTask->farmId);
	         						$DBFarmRole = $DBFarm->GetFarmRoleByRoleID($BundleTask->prototypeRoleId);
	         						
	         						$DBFarmRole->NewRoleID = $BundleTask->roleId;
	         						       						
	         						$DBFarmRole->Save();
         						}
         						else
         						{
         							$farm_roles = $db->GetAll("SELECT id FROM farm_roles WHERE role_id=? AND farmid IN (SELECT id FROM farms WHERE env_id=?)", array(
         								$BundleTask->prototypeRoleId,
         								$BundleTask->envId
         							));
         							foreach ($farm_roles as $farm_role)
         							{
         								$DBFarmRole = DBFarmRole::LoadByID($farm_role['id']);
         								$DBFarmRole->NewRoleID = $BundleTask->roleId;
		         						
		         						$DBFarmRole->Save();
         							}
         						}
	         					
	         					
	         					$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::REPLACING_SERVERS;
	         				}
	         				catch(Exception $e)
	         				{
	         					$this->Logger->error($e->getMessage());
	         					
	         					$BundleTask->Log(sprintf(_("Server replacement failed: %s"), 
		         					$e->getMessage()
		         				));
	         					
		         				$BundleTask->setDate('finished');
	         					$BundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS;
	         				}
	         			}
	         			
	         			$BundleTask->Save();
         			}
         			catch(Exception $e)
         			{
         				$this->Logger->error($e->getMessage());
         			}
         			
         			break;
         	}
        }
    }
?>
