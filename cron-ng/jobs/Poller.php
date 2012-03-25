<?php

	class Scalr_Cronjob_Poller extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
	{
		static function getConfig () {
	        return array(
	        	"description" => "Main poller",        	
	        	"processPool" => array(
					"daemonize" => false,
	        		"workerMemoryLimit" => 40000,   // 40Mb       	
	        		"startupTimeout" => 10000, 		// 10 seconds
	        		"workTimeout" => 120000,		// 120 seconds
	        		"size" => 7						// 7 workers
	        	),
	    		"waitPrevComplete" => true,        		
				"fileName" => __FILE__,
	        );
		}
	    
        private $cleanupInterval = 120000; // 2 minutes    	
    	
    	private $logger;
        
        private $db;

        private $lastCleanup;
        
        private $cleanupSem;
	        
	    function __construct() {
	        $this->logger = Logger::getLogger(__CLASS__);
	        $this->db = Core::GetDBInstance();
        	$this->lastCleanup = new Scalr_System_Ipc_Shm(
        		array("name" => "scalr.cronjob.poller.lastCleanup")
        	);
        	$this->cleanupSem = sem_get(
        		Scalr_System_OS::getInstance()->tok("scalr.cronjob.poller.cleanupSem")
        	);
		}
	        
	    function startForking ($workQueue) {
	        // Reopen DB connection after daemonizing
	        $this->db = Core::GetDBInstance(null, true);
		}        

		function endForking () {
			$this->lastCleanup->delete();
        	sem_remove($this->cleanupSem);
        }		
		
	    function startChild () {
			// Reopen DB connection in child
			$this->db = Core::GetDBInstance(null, true);
	        // Reconfigure observers;
	        Scalr::ReconfigureObservers();					
		}        
	        
		function enqueueWork ($workQueue) {
			$this->logger->info("Fetching completed farms...");
	            
			$rows = $this->db->GetAll("SELECT farms.id FROM farms 
            	INNER JOIN clients ON clients.id = farms.clientid WHERE clients.status='Active'"
            );
			foreach ($rows as $row) {
				if ($this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id=?", array($row['id'])) != 0)
					$workQueue->put($row['id']);
			}
	                      
			$this->logger->info(sprintf("Found %d farms.", count($rows)));
		}
		
		function handleWork ($farmId) {
			$this->cleanup();			
			
			$DBFarm = DBFarm::LoadByID($farmId);
            
            $GLOBALS["SUB_TRANSACTIONID"] = abs(crc32(posix_getpid().$farmId));
            $GLOBALS["LOGGER_FARMID"] = $farmId;
                        
            $this->logger->info("[". $GLOBALS["SUB_TRANSACTIONID"]."] Begin polling farm (ID: {$DBFarm->ID}, Name: {$DBFarm->Name}, Status: {$DBFarm->Status})");

            //
            // Collect information from database
            //
            $servers_count = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id = ? AND status != ?", 
            	array($DBFarm->ID, SERVER_STATUS::TERMINATED)
            );
            $this->logger->info("[FarmID: {$DBFarm->ID}] Found {$servers_count} farm instances in database");

            if ($DBFarm->Status == FARM_STATUS::TERMINATED && $servers_count == 0)
            	return;
            	
            foreach ($DBFarm->GetServersByFilter(array(), array('status' => SERVER_STATUS::PENDING_LAUNCH)) as $DBServer)
            {
            	try {
            		if ($DBServer->status != SERVER_STATUS::PENDING) {
            			$p = PlatformFactory::NewPlatform($DBServer->platform);
		            	if (!$p->IsServerExists($DBServer))
		                {
		                	if ($DBServer->status != SERVER_STATUS::TERMINATED && $DBServer->status != SERVER_STATUS::PENDING_TERMINATE)
		                	{
			                	$DBServer->SetProperty(SERVER_PROPERTIES::REBOOTING, 0);
		                		
			                	if ($DBServer->platform == SERVER_PLATFORMS::RACKSPACE) {
			                		
			                		try {
				                		$info = array(
				                			'exists' => $p->IsServerExists($DBServer),
				                			'status' => $p->GetServerRealStatus($DBServer),
				                			'list'	 => $p->GetServersList($DBServer->GetEnvironmentObject(), $DBServer->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER), true),
				                			'raw'	 => $p->_tmpVar 
				                		);
			                		} catch (Exception $e) {
			                			$info = $e->getMessage();
			                		}
			                		
			                		$this->db->Execute("INSERT INTO debug_rackspace SET server_id = ? AND info = ?", array(
			                			$DBServer->serverId,
			                			json_encode($info)
			                		));
			                		
			                		continue;
			                	}
			                	
		                		// Add entry to farm log
			                    Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, 
			                    	sprintf("Server '%s' found in database but not found on {$DBServer->platform}. Crashed.", $DBServer->serverId)
			                    ));
			                	Scalr::FireEvent($DBFarm->ID, new HostCrashEvent($DBServer));
			                	continue;
		                	}
		                }
            		}
            	}
            	catch(Exception $e)
            	{
            		if (stristr($e->getMessage(), "AWS was not able to validate the provided access credentials"))
            			continue;
            			
            		if (stristr($e->getMessage(), "Could not connect to host"))
            			continue;
            			
            		print "[Farm: {$farmId}] {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}\n\n";
            		continue;
            	}
	                
	            try {
	                if ($DBServer->status != SERVER_STATUS::TERMINATED && $DBServer->GetRealStatus()->isTerminated())
	                {
	                    if ($DBServer->status != SERVER_STATUS::PENDING_TERMINATE) {
		                	Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, 
		                    	sprintf("Server '%s' (Platform: %s) not running (Real state: %s).", $DBServer->serverId, $DBServer->platform, $DBServer->GetRealStatus()->getName())
		                    ));
	                    }
	                     
	                    $DBServer->SetProperty(SERVER_PROPERTIES::REBOOTING, 0);
	                    
	                	Scalr::FireEvent($DBFarm->ID, new HostDownEvent($DBServer));
	                	continue;
	                }
	                elseif ($DBServer->GetRealStatus()->IsRunning() && $DBServer->status != SERVER_STATUS::RUNNING)
	                {
	                	if ($DBServer->status != SERVER_STATUS::TERMINATED)
	                	{
		                	if ($DBServer->platform == SERVER_PLATFORMS::RDS)
		                	{
		                		//TODO: timeouts
		                		
		                		if ($DBServer->status == SERVER_STATUS::PENDING)
		                		{
		                			$info = PlatformFactory::NewPlatform($DBServer->platform)->GetServerIPAddresses($DBServer);
		                			$event = new HostInitEvent(
										$DBServer, 
										$info['localIp'],
										$info['remoteIp'],
										''
									);	
		                		}
		                		elseif ($DBServer->status == SERVER_STATUS::INIT)
		                		{
		                			$event = new HostUpEvent($DBServer, ""); // TODO: add mysql replication password
		                		}
		                		
		                		if ($event)
		                			Scalr::FireEvent($DBServer->farmId, $event);
		                		else
		                		{
		                			//TODO: Log
		                		}
		                	}
		                	else {
		                		
		                		if ($DBServer->platform == SERVER_PLATFORMS::NIMBULA)
		                		{
		                			if (!$DBServer->GetProperty(NIMBULA_SERVER_PROPERTIES::USER_DATA_INJECTED))
		                			{
		                				$dbRole = $DBServer->GetFarmRoleObject()->GetRoleObject();
		                				
		                				$ssh2Client = new Scalr_Net_Ssh2_Client();
		                				$ssh2Client->addPassword(
											$dbRole->getProperty(DBRole::PROPERTY_NIMBULA_INIT_ROOT_USER), 
											$dbRole->getProperty(DBRole::PROPERTY_NIMBULA_INIT_ROOT_PASS)
										);
										
										$info = PlatformFactory::NewPlatform($DBServer->platform)->GetServerIPAddresses($DBServer);
										
										$port = $dbRole->getProperty(DBRole::PROPERTY_SSH_PORT);
										if (!$port) $port = 22;
										
										try {
											$ssh2Client->connect($info['remoteIp'], $port);
											
											foreach ($DBServer->GetCloudUserData() as $k=>$v)
								        		$u_data .= "{$k}={$v};";
								        	
								        	$u_data = trim($u_data, ";");
											$ssh2Client->sendFile('/etc/scalr/private.d/.user-data', $u_data, "w+", false);
											
											$DBServer->SetProperty(NIMBULA_SERVER_PROPERTIES::USER_DATA_INJECTED, 1);
										}
										catch(Exception $e) {
											Logger::getLogger(LOG_CATEGORY::FARM)->error(new FarmLogMessage($DBFarm->ID, $e->getMessage()));
										}
		                			}
		                		}
		                		
		                		try {
				                	$dtadded = strtotime($DBServer->dateAdded);
				                	$DBFarmRole = $DBServer->GetFarmRoleObject();
									$launch_timeout = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SYSTEM_LAUNCH_TIMEOUT) > 0 ? $DBFarmRole->GetSetting(DBFarmRole::SETTING_SYSTEM_LAUNCH_TIMEOUT) : 300;
		                		} catch (Exception $e) {
		                			if (stristr($e->getMessage(), "not found")) {
		                				PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
		                				$DBServer->status = SERVER_STATUS::TERMINATED;
		                				$DBServer->Save();
		                			}
		                		}

		                		$scripting_event = false;
		                		if ($DBServer->status == SERVER_STATUS::PENDING) {
									$event = "hostInit";
									$scripting_event = EVENT_TYPE::HOST_INIT;
								}
								elseif ($DBServer->status == SERVER_STATUS::INIT) { 
									$event = "hostUp";
									$scripting_event = EVENT_TYPE::HOST_UP;
								}

								if ($scripting_event) {
									$scripting_timeout = (int)$this->db->GetOne("SELECT sum(timeout) FROM farm_role_scripts  
										WHERE event_name=? AND 
										farm_roleid=? AND issync='1'",
										array($scripting_event, $DBServer->farmRoleId)
									);

									if ($scripting_timeout)
										$launch_timeout = $launch_timeout+$scripting_timeout;
										
								    if ($dtadded+$launch_timeout < time()) {
			                            //Add entry to farm log
			                    		Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, "Server '{$DBServer->serverId}' did not send '{$event}' event in {$launch_timeout} seconds after launch (Try increasing timeouts in role settings). Considering it broken. Terminating instance."));
			                                
			                    		try {
			                            	Scalr::FireEvent($DBFarm->ID, new BeforeHostTerminateEvent($DBServer, false));
			                            }
			                            catch (Exception $err) {
											$this->logger->fatal($err->getMessage());
			                            }
									}
			                    }
		                	}
	                	}
	                }
	                elseif ($DBServer->GetRealStatus()->isRunning() && $DBServer->status == SERVER_STATUS::RUNNING)
	                {
	                	if (!$DBServer->IsRebooting()) 
						{
							$ipaddresses = PlatformFactory::NewPlatform($DBServer->platform)->GetServerIPAddresses($DBServer);
							
							if ($ipaddresses['remoteIp'] && $DBServer->remoteIp != $ipaddresses['remoteIp'])
							{
								Scalr::FireEvent(
	                            	$DBServer->farmId,
	                                new IPAddressChangedEvent($DBServer, $ipaddresses['remoteIp']) 
	                            );
							}
							
							//TODO: Check health:
						}
						else
						{
							//TODO: Check reboot timeout
						}
	                }
	                
	                if ($DBServer->status == SERVER_STATUS::PENDING_TERMINATE || $DBServer->status == SERVER_STATUS::TERMINATED)
	                {
	                	if ($DBServer->status == SERVER_STATUS::TERMINATED || !$DBServer->dateShutdownScheduled || ($DBServer->dateShutdownScheduled && strtotime($DBServer->dateShutdownScheduled)+60*3 < time()))
	                	{                		
	                		try {
		                		if (!$DBServer->GetRealStatus()->isTerminated())
								{
		                			try {
										if ($DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::RABBITMQ)) {
			                				$serversCount = count($DBServer->GetFarmRoleObject()->GetServersByFilter(array(), array('status' => SERVER_STATUS::TERMINATED)));
			                				if ($DBServer->index == 1 && $serversCount > 1) {
			                					Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, sprintf("RabbitMQ role. Main DISK node should be terminated after all other nodes. Waiting... (Platform: %s) (Poller).", 
					                				$DBServer->serverId, $DBServer->platform
					                			)));
					                			continue;
			                				}
			                			}
		                			} catch (Exception $e) {}

		                			Logger::getLogger(LOG_CATEGORY::FARM)->warn(new FarmLogMessage($DBFarm->ID, sprintf("Terminating server '%s' (Platform: %s) (Poller).", 
		                				$DBServer->serverId, $DBServer->platform
		                			)));
		                			
		                			PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
		                			
		                			$this->db->Execute("UPDATE servers_history SET
										dtterminated	= NOW(),
										terminate_reason	= ?
										WHERE server_id = ?
									", array(
										sprintf("Server is running on Amazon but has terminated status on Scalr"),
										$DBServer->serverId
									));
								}
	                		} catch (Exception $e) {
	                			if (stristr($e->getMessage(), "not found")) {
	                				
	                				$this->db->Execute("UPDATE servers_history SET
										dtterminated	= NOW(),
										terminate_reason	= ?
										WHERE server_id = ?
									", array(
										sprintf("Role was removed from farm"),
										$DBServer->serverId
									));
	                				
	                				$DBServer->Remove();
	                			} elseif (stristr($e->getMessage(), "disableApiTermination")) {
	                				continue;
	                			} else
	                				throw $e;
	                		}
	                		
	                	}
	                }
	            } catch (Exception $e) {
	  				if (stristr($e->getMessage(), "not found"))
	  					var_dump($e);
	  				else 
	  					print "[Farm: {$farmId}] {$e->getMessage()} at {$e->getFile()}:{$e->getLine()}\n\n";
	  			}
			}
		}
		
		
        private function cleanup () {
        	// Check that time has come to cleanup dead servers
        	$doCleanup = false;
        	sem_acquire($this->cleanupSem);
        	try {
	       		if (time() - (int)$this->lastCleanup->get(0) >= $this->cleanupInterval) {
        			$doCleanup = true;
        			$this->lastCleanup->put(0, time());
        		}
        	} catch (Exception $e) {
        		sem_release($this->cleanupSem);
        	}
        	sem_release($this->cleanupSem);
        	
        	if ($doCleanup) {
        		$this->logger->info("Cleanup dead servers");
        		
   				try
				{	      
		            $terminated_servers = $this->db->GetAll("SELECT server_id FROM servers WHERE status=? AND (UNIX_TIMESTAMP(dtshutdownscheduled)+3600 < UNIX_TIMESTAMP(NOW()) OR dtshutdownscheduled IS NULL)", 
		            	array(SERVER_STATUS::TERMINATED)
		            );
		            foreach ($terminated_servers as $ts)
		            	DBServer::LoadByID($ts['server_id'])->Remove();
		            	
		            $importing_servers = $this->db->GetAll("SELECT server_id FROM servers WHERE status IN(?,?) AND UNIX_TIMESTAMP(dtadded)+86400 < UNIX_TIMESTAMP(NOW())", 
		            	array(SERVER_STATUS::IMPORTING, SERVER_STATUS::TEMPORARY)
		            );	
		            foreach ($importing_servers as $ts)
		            	DBServer::LoadByID($ts['server_id'])->Remove();
		            
		            $pending_launch_servers = $this->db->GetAll("SELECT server_id FROM servers WHERE status=?", array(SERVER_STATUS::PENDING_LAUNCH));
		            try
					{
			            foreach ($pending_launch_servers as $ts)
			            {
			            	$DBServer = DBServer::LoadByID($ts['server_id']);
			            	$account = Scalr_Account::init()->loadById($DBServer->clientId);
			            	if ($account->status == Scalr_Account::STATUS_ACTIVE) {
								Scalr::LaunchServer(null, $DBServer);
			            	}
			            }
			        }
					catch(Exception $e)
					{
						Logger::getLogger(LOG_CATEGORY::FARM)->error(sprintf("Can't load server with ID #'%s'", 
		                	$ts['server_id'],
		                	$e->getMessage()
		                ));
					}
				}
				catch (Exception $e)
				{
					$this->logger->fatal("Poller::cleanup failed: {$e->getMessage()}");
				}        		
        	}
        }
		
	}
