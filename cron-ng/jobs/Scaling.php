<?php

	class Scalr_Cronjob_Scaling extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
	{
		static function getConfig () {
	        return array(
	        	"description" => "Roles scaling",        	
	        	"processPool" => array(
					"daemonize" => false,
	        		"workerMemoryLimit" => 40000,   // 40Mb       	
	        		"startupTimeout" => 10000, 		// 10 seconds
	        		"workTimeout" => 120000,		// 120 seconds
	        		"size" => 7						// 7 workers
	        	),
	    		"waitPrevComplete" => true,        		
				"fileName" => __FILE__,
        		"getoptRules" => array(
        			'farm-id-s' => 'Affect only this farm'
        		)
	        );
		}
	    
		private $logger;
	        
		private $db;
	        
	    function __construct() {
	        $this->logger = Logger::getLogger(__CLASS__);
	        $this->db = Core::GetDBInstance();
		}
	        
	    function startForking ($workQueue) {
	        // Reopen DB connection after daemonizing
	        $this->db = Core::GetDBInstance(null, true);
		}        
	        
	    function startChild () {
			// Reopen DB connection in child
			$this->db = Core::GetDBInstance(null, true);
	        // Reconfigure observers;
	        Scalr::ReconfigureObservers();					
		}        
	        
		function enqueueWork ($workQueue) {
			$this->logger->info("Fetching completed farms...");
			$farmid = $this->runOptions['getopt']->getOption('farm-id');

			if ($farmid) {
				$rows = $this->db->GetAll("SELECT farms.id FROM farms 
		            INNER JOIN clients ON clients.id = farms.clientid 
		            WHERE clients.status='Active' AND farms.status=? AND farms.id=?",
		            array(FARM_STATUS::RUNNING, $farmid)
				);
			} else {
				$rows = $this->db->GetAll("SELECT farms.id FROM farms 
		            INNER JOIN clients ON clients.id = farms.clientid 
		            WHERE clients.status='Active' AND farms.status=?",
		            array(FARM_STATUS::RUNNING)
				);
			}
			foreach ($rows as $row) {
				$workQueue->put($row['id']);
			}
	                      
			$this->logger->info(sprintf("Found %d farms.", count($rows)));
		}
		
		function handleWork ($farmId) {
        	$DBFarm = DBFarm::LoadByID($farmId);
        	
            $GLOBALS["SUB_TRANSACTIONID"] = abs(crc32(posix_getpid().$farmId));
            $GLOBALS["LOGGER_FARMID"] = $farmId;        	
                        
            if ($DBFarm->Status != FARM_STATUS::RUNNING)
            {
            	$this->logger->warn("[FarmID: {$DBFarm->ID}] Farm terminated. There is no need to scale it.");
            	return;
            }
            
            foreach ($DBFarm->GetFarmRoles() as $DBFarmRole)
            {            	
            	for ($i = 0; $i < 10; $i++) {
            		
	            	if ($DBFarmRole->NewRoleID != '')
	            	{
	            		$this->logger->warn("[FarmID: {$DBFarm->ID}] Role '{$DBFarmRole->GetRoleObject()->name}' being synchronized. This role will not be scalled.");
	            		continue 2;
	            	}
	            	
	            	if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_ENABLED) == '0' && !$DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MONGODB))
	            	{
	            		$this->logger->info("[FarmID: {$DBFarm->ID}] Scaling disabled for role '{$DBFarmRole->GetRoleObject()->name}'. Skipping...");
	            		continue 2;
	            	}
	            	
	            	// Get polling interval in seconds
	            	$polling_interval = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_POLLING_INTERVAL)*60;
	            	$dt_last_polling = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME);
	            	if ($dt_last_polling && $dt_last_polling+$polling_interval > time() && $i == 0)
	            	{
	            		$this->logger->info("Polling interval: every {$polling_interval} seconds");
	            		//continue;
	            	}
	            	
	            	// Set Last polling time
	            	$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_LAST_POLLING_TIME, time());
	
	            	// Get current count of running and pending instances.
	            	$this->logger->info(sprintf("Processing role '%s'", $DBFarmRole->GetRoleObject()->name));
	            	
	            	
	            	$scalingManager = new Scalr_Scaling_Manager($DBFarmRole);
	            	$scalingDecision = $scalingManager->makeScalingDecition();
		            	
	            	if ($scalingDecision == Scalr_Scaling_Decision::STOP_SCALING)
	            	{
	            		return;
	            	}
	            	if ($scalingDecision == Scalr_Scaling_Decision::NOOP)
	            	{
	            		continue 2;
	            	}
	            	elseif ($scalingDecision == Scalr_Scaling_Decision::DOWNSCALE)
	            	{	     
						/*
						 Timeout instance's count decrease. Decreases instance�s count after scaling 
						 resolution the spare instances are running�g for selected timeout interval
						 from scaling EditOptions							
						*/    
							
						// We have to check timeout limits before new scaling (downscaling) process will be initiated
						if($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_TIMEOUT_ENABLED))
						{   // if the farm timeout is exceeded
							// checking timeout interval.
							
							$last_down_scale_data_time =  $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_DATETIME); 							
							$timeout_interval = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_TIMEOUT);
							
							// check the time interval to continue scaling or cancel it...
							if(time() - $last_down_scale_data_time < $timeout_interval*60)
							{
								// if the launch time is too small to terminate smth in this role -> go to the next role in foreach()							
								Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, 
									sprintf("Waiting for downscaling timeout on farm %s, role %s",
										$DBFarm->Name,
										$DBFarmRole->GetRoleObject()->name
									)
								));
								continue 2;
							}
						} // end Timeout instance's count decrease         			
	            		$sort = ($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_KEEP_OLDEST) == 1) ? 'DESC' : 'ASC';
		            		
		            	$servers = $this->db->GetAll("SELECT server_id FROM servers WHERE status = ? AND farm_roleid=? ORDER BY dtadded {$sort}",
			            	array(SERVER_STATUS::RUNNING, $DBFarmRole->ID)
			            );
			            	
		            	$got_valid_instance = false;
			            	
	                    // Select instance that will be terminated
	                    //
	                    // * Instances ordered by uptime (oldest wil be choosen)
	                    // * Instance cannot be mysql master
	                    // * Choose the one that was rebundled recently
	                    while (!$got_valid_instance && count($servers) > 0)
	                    {
	                    	$item = array_shift($servers);
		                    $DBServer = DBServer::LoadByID($item['server_id']);
	                        
		                    if ($DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::RABBITMQ)) {
		                    	$serversCount = count($DBServer->GetFarmRoleObject()->GetServersByFilter(array(), array('status' => SERVER_STATUS::TERMINATED)));
		                    	if ($DBServer->index == 1 && $serversCount > 1)
		                    		continue;
		                    }
		                    
	                        // Exclude db master
	                        if ($DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) != 1 && $DBServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) != 1)
	                        {
	                        	/* 
	                        	 * We do not want to delete the most recently synced instance. Because of LA fluctuation. 
	                        	 * I.e. LA may skyrocket during sync and drop dramatically after sync.
	                        	 */
	
	                        	if ($DBServer->dateLastSync != 0)
	                        	{
	                        		$chk_sync_time = $this->db->GetOne("SELECT server_id FROM servers 
	                        		WHERE dtlastsync > {$DBServer->dateLastSync} 
		                        	AND farm_roleid='{$DBServer->farmRoleId}' AND status != '".SERVER_STATUS::TERMINATED."'");
	                        		if ($chk_sync_time)
	                        			$got_valid_instance = true;
	                        	}
	                        	else
	                        		$got_valid_instance = true;
		                    }
						}
	                        
	                    if ($DBServer && $got_valid_instance)
	                    {
							$this->logger->info(sprintf("Server '%s' selected for termination...", $DBServer->serverId));
	                       	$allow_terminate = false;
	
	                       	if ($DBServer->platform == SERVER_PLATFORMS::EC2)
	                       	{
		                        $AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
									$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION),
									$DBServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
									$DBServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
								);
									
	                        	// Shutdown an instance just before a full hour running 
			                    $response = $AmazonEC2Client->DescribeInstances($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID));
			                    if ($response && $response->reservationSet->item)
			                    {
	                        		$launch_time = strtotime($response->reservationSet->item->instancesSet->item->launchTime);
	                        		$time = 3600 - (time() - $launch_time) % 3600;
		                        		
	                        		// Terminate instance in < 10 minutes for full hour. 
	                        		if ($time <= 600)
	                        			$allow_terminate = true;
	                        		else
	                        		{
	                        			$timeout = round(($time - 600) / 60, 1);
										
	                        			Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, sprintf("Farm %s, role %s scaling down. Server '%s' will be terminated in %s minutes. Launch time: %s",
	                        				$DBFarm->Name,
	                        				$DBServer->GetFarmRoleObject()->GetRoleObject()->name,
	                        				$DBServer->serverId,
	                        				$timeout,
	                        				$response->reservationSet->item->instancesSet->item->launchTime
	                        			)));
	                        			
	                        		}
		                        }
			                        //
	                        }
	                        else
	                        	$allow_terminate = true;
	                        	
	                        if ($allow_terminate)
	                        {                       
		                        //Check safe shutdown
		                        if ($DBServer->GetFarmRoleObject()->GetSetting(DBFarmRole::SETTING_SCALING_SAFE_SHUTDOWN) == 1)
		                        {
		                        	$snmpClient = new Scalr_Net_Snmp_Client();
		                        	$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
		                        	$snmpClient->connect($DBServer->remoteIp, $port ? $port : 161, $DBFarm->Hash, null, null, false);
		                        	$res = $snmpClient->get('1.3.6.1.4.1.36632.6.1');
						            if ($res != '1')
						            {
						            	Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, sprintf("Safe shutdown enabled. Server '%s'. Script return '%s', server won't be terminated while return value not '1'",
		                        			$DBServer->serverId,
						            		$res
		                        		)));
						            }
		                        }
	                        	
	                        	try
		                        {		                            
							    	Scalr::FireEvent($DBFarm->ID, new BeforeHostTerminateEvent($DBServer, false));
							            
							    	$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_DOWNSCALE_DATETIME, time());
							    	
							        Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, sprintf("Farm %s, role %s scaling down. Server '%s' marked as 'Pending terminate' and will be fully terminated in 3 minutes.",
	                        			$DBFarm->Name,
	                        			$DBServer->GetFarmRoleObject()->GetRoleObject()->name,
	                        			$DBServer->serverId
	                        		)));
								}
		                        catch (Exception $e)
		                        {
		                            $this->logger->fatal(sprintf("Cannot terminate %s: %s",
		                            	$DBFarm->ID,
		                            	$DBServer->serverId,
		                            	$e->getMessage()
		                            ));
		                        }
	                        }
						}
	                    else
							$this->logger->warn(sprintf("[FarmID: {$DBFarm->ID}] Scalr unable to determine what instance it should terminate (FarmRoleID: {$DBFarmRole->ID}). Skipping..."));
		                        
						break;
		            }
	            	elseif ($scalingDecision == Scalr_Scaling_Decision::UPSCALE)
		            {
						/*
						Timeout instance's count increase. Increases  instance's count after 
						scaling resolution �need more instances� for selected timeout interval
						from scaling EditOptions						
						*/	            		
						if($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_TIMEOUT_ENABLED))
						{ 
							// if the farm timeout is exceeded
							// checking timeout interval.
							$last_up_scale_data_time =  $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_DATETIME); 								
							$timeout_interval = $DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_TIMEOUT);						
							
							// check the time interval to continue scaling or cancel it...
							if(time() - $last_up_scale_data_time < $timeout_interval*60)
							{
								// if the launch time is too small to terminate smth in this role -> go to the next role in foreach()							
								Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, 
									sprintf("Waiting for upscaling timeout on farm %s, role %s",
										$DBFarm->Name,
										$DBFarmRole->GetRoleObject()->name
									)
								));
								continue 2;									
							}
						}// end Timeout instance's count increase 
							
						if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_ONE_BY_ONE) == 1)
						{
							$pendingInstances = $DBFarmRole->GetPendingInstancesCount();
							if ($pendingInstances > 0) {
								Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, 
									sprintf("There are %s pending intances of %s role on % farm. Waiting...",
										$pendingInstances,
										$DBFarmRole->GetRoleObject()->name,
										$DBFarm->Name
									)
								));
								continue 2;	
							}
						}
						
						$fstatus = $this->db->GetOne("SELECT status FROM farms WHERE id=?", array($DBFarm->ID));
			            if ($fstatus != FARM_STATUS::RUNNING)
			            {
			            	$this->logger->warn("[FarmID: {$DBFarm->ID}] Farm terminated. There is no need to scale it.");
			            	return;
			            }
		      			            
			            $ServerCreateInfo = new ServerCreateInfo($DBFarmRole->Platform, $DBFarmRole);
						try {
							$DBServer = Scalr::LaunchServer($ServerCreateInfo);
	
							$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_UPSCALE_DATETIME, time());
							
							Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($DBFarm->ID, sprintf("Farm %s, role %s scaling up. Starting new instance. ServerID = %s.", 
								$DBFarm->Name,
	                        	$DBServer->GetFarmRoleObject()->GetRoleObject()->name,
	                        	$DBServer->serverId
							)));
						}
						catch(Exception $e){
							Logger::getLogger(LOG_CATEGORY::SCALING)->error($e->getMessage());
						}
	            	}
            	}
            }
		}
	}
