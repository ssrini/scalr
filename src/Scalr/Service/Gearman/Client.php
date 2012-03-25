<?php
	class Scalr_Service_Gearman_Client extends Scalr_Service_Gearman
	{
		private $gmClient;
		
		protected $processesPerJob = 1;
		
		public function __construct(array $serversList)
		{
			parent::__construct();
			$this->logger = Logger::getLogger("Scalr_Service_Gearman_Client");
			$this->servers = $serversList;
		}
		
		public function initGearman()
		{
			$this->gmClient= new GearmanClient();
			
			foreach ($this->servers as $server)
				$this->gmClient->addServer($server['host'], $server['port']);
		}
	
		protected function childProcess($jobName)
		{
			$this->initGearman();				
			while (true) {
				foreach ($jobName::getTasksList() as $job) {
					$this->gmClient->doBackground($jobName, $job['id'], $job['id']);
				}
				
				$this->logger->info("[".posix_getpid()."] {$jobName}: sleeping 5 seconds");
				sleep(5);
			}
		}
	}