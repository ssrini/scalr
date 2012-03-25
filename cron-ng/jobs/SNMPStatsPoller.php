<?php

	Core::Load("Data/RRD");
	
	require_once(APPPATH . "/cron/watchers/class.SNMPWatcher.php");
	require_once(APPPATH . "/cron/watchers/class.CPUSNMPWatcher.php");
	require_once(APPPATH . "/cron/watchers/class.LASNMPWatcher.php");
	require_once(APPPATH . "/cron/watchers/class.MEMSNMPWatcher.php");
	require_once(APPPATH . "/cron/watchers/class.NETSNMPWatcher.php");
	
	class Scalr_Cronjob_SNMPStatsPoller extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
    {
    	static function getConfig () {
    		return array(
    			"description" => "SNMP stats poller",
    			"processPool" => array(
					"daemonize" => false,
    				"workerMemoryLimit" => 40000,   		
    				"size" => 12,
    				"startupTimeout" => 10000 // 10 seconds
    			),
    			"waitPrevComplete" => true,
    			"fileName" => __FILE__,
    			"memoryLimit" => 500000
    		);
    	}
    	
        private $logger;
        private $watchers;
        private $snmpWatcher;
        private $db;
        
        public function __construct() {
        	$this->logger = Logger::getLogger(__CLASS__);
        	
        	$this->timeLogger = Logger::getLogger('time');
        	
        	$this->db = Core::GetDBInstance();
        	
        	// key = watcher_name, value = use average value for varm and role
        	$this->watchers = array("CPUSNMP" => true, "MEMSNMP" => true, "LASNMP" => true, "NETSNMP" => false);
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
        	
        	$this->snmpWatcher = new SNMPWatcher();
        	foreach (array_keys($this->watchers) as $watcher_name)
            	$this->snmpWatcher->SetOIDs($watcher_name);
        }        
        
        function enqueueWork ($workQueue) {
            $this->logger->info("Fetching active farms...");
            
            $rows = $this->db->GetAll("SELECT farms.*, clients.isactive FROM farms 
            	INNER JOIN clients ON clients.id = farms.clientid 
            	WHERE farms.status='1' AND clients.isactive='1'");
            $this->logger->info("Found ".count($rows)." farms");            
            
            foreach ($rows as $row) {
            	$workQueue->put($row["id"]);
            }
        }
                
        function handleWork ($farmId) {
        	
        	$farminfo = $this->db->GetRow("SELECT hash, name FROM farms WHERE id=?", array($farmId));
            
        	
            $GLOBALS["SUB_TRANSACTIONID"] = abs(crc32(posix_getpid().$farmId));
            $GLOBALS["LOGGER_FARMID"] = $farmId;
            
            $this->logger->info("[{$GLOBALS["SUB_TRANSACTIONID"]}] Begin polling farm (ID: {$farmId}, Name: {$farminfo['name']})");
            
            //
            // Check farm status
            //
            
            if ($this->db->GetOne("SELECT status FROM farms WHERE id=?", array($farmId)) != FARM_STATUS::RUNNING)
            {
            	$this->logger->warn("[FarmID: {$farmId}] Farm terminated by client.");
            	return;
            }
            
            
            //
            // Collect information from database
            //			
                        
            // Check data folder for farm
			$farm_rrddb_dir = CONFIG::$RRD_DB_DIR."/{$farmId}";
			
            if (!file_exists($farm_rrddb_dir))
            {
            	mkdir($farm_rrddb_dir, 0777);
            	chmod($farm_rrddb_dir, 0777);
            }
            
           	// SNMP Watcher config
            $this->snmpWatcher->SetConfig($farminfo["hash"], $farm_rrddb_dir);
            	
            $farm_data = array();
            
            // Get all farm roles
            $farm_roles = $this->db->GetAll("SELECT id, role_id FROM farm_roles WHERE farmid=?", array($farmId));
            
            $this->logger->info("[{$GLOBALS["SUB_TRANSACTIONID"]}] Found".count($farm_roles)." roles...");
            
            foreach ($farm_roles as $farm_role)
            {
            	$role_data = array();
            	$role_icnt = 0;
            	
            	$DBFarmRole = DBFarmRole::LoadByID($farm_role['id']);
            	
            	$servers = $DBFarmRole->GetServersByFilter(array(), array('status' => array(SERVER_STATUS::PENDING_TERMINATE, SERVER_STATUS::TERMINATED)));
            	
            	$this->logger->info("[{$GLOBALS["SUB_TRANSACTIONID"]}] Found ".count($servers)." servers...");
            	
            	// Watch SNMP values fro each instance
            	foreach ($servers as $DBServer)
            	{
            		if ($DBServer->status == SERVER_STATUS::PENDING_TERMINATE || $DBServer->status == SERVER_STATUS::TERMINATED)
            			continue;
            			
            		if (!$DBServer->remoteIp)
            			continue;
            		
            		$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
            		if (!$port)
            			$port = 161;
            			
            		// Connect to SNMP
            		$this->snmpWatcher->Connect($DBServer->remoteIp, true, $port);
            		
            		$this->snmpWatcher->ResetData();
            		
            		foreach (array_keys($this->watchers) as $watcher_name)
            		{            			
            			// Get data
            			$data = $this->snmpWatcher->GetDataByWatcher($watcher_name);
            			
            			if ($data[0] === '' || $data[0] === false || $data[0] === null)
            			{
            				$this->logger->info('break (Line: 142)');
            				break;
            			}
            			
            			// Collect data
            			foreach($data as $k=>$v)
            			{
            				$role_data[$watcher_name][$k] += (float)$v;
            				$farm_data[$watcher_name][$k] += (float)$v;
            			}
            			
            			$this->snmpWatcher->UpdateRRDDatabase($watcher_name, $data, "INSTANCE_{$farm_role["id"]}_{$DBServer->index}");
            		}
            		
            		$role_icnt++;
            		$farm_icnt++;
            	}
            	
            	//Update data and build graphic for role
            	foreach ($role_data as $watcher_name => $data)
            	{
            		// if true count average value value
            		if ($this->watchers[$watcher_name])
            		{
            			foreach ($data as &$ditem)
            				$ditem = round($ditem/$role_icnt, 2);
            		}
            			
             		if ($data[0] === '' || $data[0] === false || $data[0] === null)
             		{
            			$this->logger->info('break 1 (Line: 172)');
             			break 1;
             		}
            		
            		try
            		{
            			// Update RRD database for role
	            		$this->snmpWatcher->UpdateRRDDatabase($watcher_name, $data, "FR_{$farm_role["id"]}");
            		}
            		catch(Exception $e)
            		{
            			$this->logger->error("RRD Update for {$watcher_name} on role #{$farm_role["id"]} failed. {$e->getMessage()}");
            		}
            	}
            }
            
            // Update data and build graphic for farm
        	foreach ($farm_data as $watcher_name => $data)
            {
            	// if true count average value value
            	if ($this->watchers[$watcher_name])
            	{
            		foreach ($data as &$ditem)
            			$ditem = round($ditem/$farm_icnt, 2);
            	}
            	
            	if ($data[0] === '' || $data[0] === false || $data[0] === null)
            	{
            		$this->logger->info('continue (Line: 200)');
            		continue;
            	}
            	
            	try
            	{
            		// Update farm RRD database
	            	$this->snmpWatcher->UpdateRRDDatabase($watcher_name, $data, "FARM");
            	}
            	catch(Exception $e)
            	{
            		$this->logger->error("RRD Update for farm failed. {$e->getMessage()}");
            	}
            }
        }
    }
