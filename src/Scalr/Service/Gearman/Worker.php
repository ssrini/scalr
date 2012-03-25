<?php
	class Scalr_Service_Gearman_Worker extends Scalr_Service_Gearman
	{
		private $gmClient;
		
		protected $processesPerJob = 4;
		
		public function __construct(array $serversList)
		{
			parent::__construct();
			$this->logger = Logger::getLogger("Scalr_Service_Gearman_Worker");
			$this->servers = $serversList;
		}
		
		public function initGearman()
		{
			$this->gmWorker= new GearmanWorker();
			
			//$this->gmWorker->addServer();

			foreach ($this->servers as $server)
				$this->gmWorker->addServer($server['host'], $server['port']);
			
			foreach ($this->jobs as $job => $settings) {
				$this->gmWorker->addFunction($job, array($job, "doJob"));
			}
		}
	
		protected function childProcess($jobName)
		{
			$this->initGearman();				
			while ($this->gmWorker->work());
		}
	}