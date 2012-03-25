<?
	class HealthCheckProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "HealthCheck process";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = Logger::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            $this->Logger->info("Fetching completed farms...");
            
            $this->ThreadArgs = $db->GetAll("SELECT server_id FROM servers WHERE status=?",
            	array(SERVER_STATUS::RUNNING)
            );
                        
            $this->Logger->info("Found ".count($this->ThreadArgs)." servers.");
            
            $this->snmpClient = new Scalr_Net_Snmp_Client();
        }
        
        public function OnEndForking()
        {
			//$db = Core::GetDBInstance(null, true);
        }
        
        public function StartThread($serverinfo)
        {
            $DBServer = DBServer::LoadByID($serverinfo['server_id']);
            
   			$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
   			if (!$port)
   				$port = 161;
   			
   			$this->snmpClient->connect($DBServer->remoteIp, $port, $DBServer->GetFarmObject()->Hash, 3, 1);
   			$r = $this->snmpClient->get(".1.3.6.1.4.1.2021.10.1.3.1");
   			if (!$r)
   			{
   				$DBServer->SetProperty(SERVER_PROPERTIES::HEALTHCHECK_FAILED, 1);
   				$DBServer->SetProperty(SERVER_PROPERTIES::HEALTHCHECK_TIME, time());
   			}
   			else
   			{
   				$DBServer->SetProperty(SERVER_PROPERTIES::HEALTHCHECK_FAILED, 0);
   				$DBServer->SetProperty(SERVER_PROPERTIES::HEALTHCHECK_TIME, time());
   			}
        }
    }
?>