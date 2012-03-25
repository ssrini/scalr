<?
	class SNMPTree2
	{
		public function Connect($host, $port=161, $community="public", $timeout = 2, $retries = 0, $SNMP_VALUE_PLAIN = false)
		{
			$this->Host = $host;
			$this->Port = $port;
			$this->Community = $community;
			$this->Shell = ShellFactory::GetShellInstance();
			$this->Timeout = $timeout;
			$this->Retries = $retries;
			$this->SNMPValuePlain = $SNMP_VALUE_PLAIN;
			
			$this->logger = Logger::getLogger(__CLASS__);
		}
		
		public function Get($OID)
		{
			$s1 = microtime(true);
			
			if (is_array($OID))
				$OID = implode(' ', $OID);

			if (!$this->SNMPValuePlain)
				$view_option = "-Ov -Oq";
			else
				$view_option = "-On";

			//print "/usr/bin/snmpget -r {$this->Retries} -t {$this->Timeout} {$view_option} -v 2c -c {$this->Community} {$this->Host}:{$this->Port} {$OID}\n";
				
			$retval = $this->Shell->QueryRaw("/usr/bin/snmpget -r {$this->Retries} -t {$this->Timeout} {$view_option} -v 2c -c {$this->Community} {$this->Host}:{$this->Port} {$OID}");
			
			//$this->logger->info("TIME(snmpget:{$this->Host}) = " . (microtime(true) - $s1));
			
			return $retval;
		}
	}    

	class SNMPWatcher
    {
        /**
         * ADODB Instance
         *
         * @var ADODBConnection
         */
    	protected $DB;
    	
    	/**
    	 * SNMPTree
    	 *
    	 * @var SNMPTree
    	 */
        protected $SNMPTree;
        
        protected $Community;
        
        protected $WatchersCache = array();
        
        /**
         * Constructor
         *
         */
        function __construct()
        {
            $this->DB = Core::GetDBInstance();
            $this->SNMPTree = new SNMPTree2();
        }
        
        public function SetConfig($community, $path)
        {
        	$this->Community = $community;
            $this->DataPath = $path;
        }
        
        public function Connect($host, $SNMP_VALUE_PLAIN = false, $port = 161)
        {
        	$this->SNMPTree->Connect($host, $port, $this->Community, 1, 0, $SNMP_VALUE_PLAIN);
        }
        
        /*
         *  //
         *  // BEGIN 
         *  //
         */
        private $OIDs = array();
        private $wOIDs = array();
        private $Data = array();
        
        public function ResetData()
        {
        	$this->Data = array();
        }
        
        public function GetDataByWatcher($watcher_name)
        {
        	if (empty($this->Data))
        		$this->RequestData();
        		
        	return $this->Data[$watcher_name];
        }
        
        public function RequestData()
        {
        	$result = $this->SNMPTree->Get($this->OIDs);
        	preg_match_all("/([^=]+)=([^\n]+)/", $result, $matches);
        	$result = array();
        	foreach ($matches[1] as $k=>$v)
        	{
        		preg_match("/:[^0-9]+([0-9\.]+)/", $matches[2][$k], $m);
        		$OID = trim($v);
        		$result[$OID] = $m[1];
        	}        	
        	
        	$retval = array();
        	foreach ($this->wOIDs as $wn => $oids)
        	{
        		//$retval[$wn] = array();
        		$w_data = array();
        		foreach($oids as $oid)
        			$w_data[] = $result[$oid];
        			
        		$this->Data[$wn] = $w_data;
        	}
        }
        
        public function SetOIDs($watcher_name)
        {
        	if (!$this->WatchersCache[$watcher_name])
        		$this->WatchersCache[$watcher_name] = new ReflectionClass("{$watcher_name}Watcher");
        		
        	$Watcher = $this->WatchersCache[$watcher_name]->newInstance($this->SNMPTree);
        	
        	$this->wOIDs[$watcher_name] = $Watcher->GetOIDs();
        	$this->OIDs = array_merge($this->OIDs, $Watcher->GetOIDs());
        }
        
        /*
         *  //
         *  // END 
         *  //
         */
        
        public function RetreiveData($watcher_name)
        {
        	if (!$this->WatchersCache[$watcher_name])
        		$this->WatchersCache[$watcher_name] = new ReflectionClass("{$watcher_name}Watcher");
        		
        	$Watcher = $this->WatchersCache[$watcher_name]->newInstance($this->SNMPTree);
        	
        	return $Watcher->RetreiveData();
        }
        
        public function UpdateRRDDatabase($watcher_name, $data, $name)
        {
        	if (!$this->WatchersCache[$watcher_name])
        		$this->WatchersCache[$watcher_name] = new ReflectionClass("{$watcher_name}Watcher");
        		
        	$Watcher = $this->WatchersCache[$watcher_name]->newInstance(null, $this->DataPath);
        	
        	return $Watcher->UpdateRRDDatabase($name, $data);
        }
        
        public function PlotGraphic($watcher_name, $name)
        {
        	if (!$this->WatchersCache[$watcher_name])
        		$this->WatchersCache[$watcher_name] = new ReflectionClass("{$watcher_name}Watcher");
        		
        	$Watcher = $this->WatchersCache[$watcher_name]->newInstance(null, $this->DataPath);
        	
        	return $Watcher->PlotGraphic($name);
        }
    }
?>