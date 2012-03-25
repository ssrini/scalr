<?
	class UsageStatsPollerProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Farm usage stats poller";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = Logger::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            $this->Logger->info("Fetching running farms...");
            
            $this->ThreadArgs = $db->GetAll("SELECT farms.id as id FROM farms 
            	INNER JOIN clients ON clients.id = farms.clientid WHERE clients.status='Active' AND farms.status=?",
            	array(FARM_STATUS::RUNNING)
            );
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." farms.");
        }
        
        public function OnEndForking()
        {

        }
        
        public function StartThread($farminfo)
        {
            $db = Core::GetDBInstance();
            $snmpClient = new Scalr_Net_Snmp_Client();
            
            $DBFarm = DBFarm::LoadByID($farminfo['id']);

            foreach ($DBFarm->GetFarmRoles() as $DBFarmRole)
            {
                foreach ($DBFarmRole->GetServersByFilter(array(), array('status' => array(SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_LAUNCH, SERVER_STATUS::TEMPORARY, SERVER_STATUS::IMPORTING))) as $DBServer)
                {                	
                    $launchTime = strtotime($DBServer->dateAdded);
                    $lastCheckTime = (int)$DBServer->GetProperty(SERVER_PROPERTIES::STATISTICS_LAST_CHECK_TS);
                    if (!$lastCheckTime)
                    	$lastCheckTime = $launchTime;
                    
                    $period = round((time()-$lastCheckTime) / 60);
                    
                    $maxMinutes = (date("j")*24*60) - (date("H")*60);
                    if ($period > $maxMinutes)
                    	$period = $maxMinutes;
                    
                    $serverType = $DBServer->GetFlavor();
                    
                    if (!$serverType)
                    	continue;
                    
                    $db->Execute("INSERT INTO servers_stats SET
                    	`usage` = ?,
                    	`instance_type` = ?,
                    	`env_id` = ?,
                    	`month` = ?,
                    	`year` = ?,
                    	`farm_id` = ?,
                    	`cloud_location` = ?
                    ON DUPLICATE KEY UPDATE `usage` = `usage` + ?             
                    ", array(
                    	$period,
                    	$serverType,
                    	$DBServer->envId,
                    	date("m"),
                    	date("Y"),
                    	$DBServer->farmId,
                    	$DBServer->GetCloudLocation(),
                    	$period
                    ));
                    
                    $DBServer->SetProperty(SERVER_PROPERTIES::STATISTICS_LAST_CHECK_TS, time());
                        
                    /*
                    if (!$DBServer->IsRebooting())
                    {
						if (!$DBServer->remoteIp)
							continue;
                    	
						$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
						if (!$port)
							$port = 161;
							
                    	$snmpClient->connect($DBServer->remoteIp, $port, $DBFarm->Hash, null, null, true);
                        $res = $snmpClient->get(".1.3.6.1.4.1.2021.10.1.3.3");
                        if ($res)
                        {                                	
							preg_match_all("/[0-9]+/si", $snmpClient->get(".1.3.6.1.2.1.2.2.1.10.2"), $matches);
							$bw_in = $matches[0][0];
						                        
							preg_match_all("/[0-9]+/si", $snmpClient->get(".1.3.6.1.2.1.2.2.1.16.2"), $matches);
							$bw_out = $matches[0][0];
						            
							$c_bw_in = (int)$DBServer->GetProperty(SERVER_PROPERTIES::STATISTICS_BW_IN);
							$c_bw_out = (int)$DBServer->GetProperty(SERVER_PROPERTIES::STATISTICS_BW_OUT);
							
				            if ($bw_in > $c_bw_in && ($bw_in-(int)$c_bw_in) > 0)
				            	$bw_in_used[] = round(((int)$bw_in-(int)$c_bw_in)/1024, 2);
				            else
				            	$bw_in_used[] = $bw_in/1024;
						            	
				            if ($bw_out > $c_bw_out && ($bw_out-(int)$c_bw_out) > 0)
				            	$bw_out_used[] = round(((int)$bw_out-(int)$c_bw_out)/1024, 2);
				            else
				            	$bw_out_used[] = $bw_out/1024;

				            	
				            $DBServer->SetProperties(array(
				            	SERVER_PROPERTIES::STATISTICS_BW_IN 	=> $bw_in,
				            	SERVER_PROPERTIES::STATISTICS_BW_OUT 	=> $bw_out
				            ));
						}
					}
					*/
                } //for each items
            }
        }
    }
?>