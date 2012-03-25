<?php
	class Scalr_Service_Gearman_Manager
	{
		const GEARMAN_CLIENT = 1;
		const GEARMAN_WORKER = 2;
		
		protected $servers;
		
		public function __construct($servers)
		{
			$this->logger = Logger::getLogger("Gearman_Manager");
			$this->shm = new Scalr_System_Ipc_Shm(array(
				"name" => "scalr.system.gearman.shm-manager"
			));
			
			$this->servers = $servers;
		}
		
		protected function forkChild ($type) {
			$this->logger->error("Fork child process");
			
			$pid = pcntl_fork();
			if ($pid == -1) {
				// Cannot fork child				
				throw new Scalr_System_Ipc_Exception("Cannot fork child process");
				
			} else if ($pid) {
				// Current process
				$this->shm->put($type, $pid);
				$this->logger->error(sprintf("Child PID: %s was forked", $pid));
			} else {
				
				if (posix_getpid() == $this->shm->get($type)) {
					$this->logger->error("Detaching process from terminatal");
					if (posix_setsid() == -1) {
						throw new Scalr_System_Ipc_Exception("Cannot detach process from terminal");
					}
				}
				
				if ($type == self::GEARMAN_CLIENT) {
					$client = new Scalr_Service_Gearman_Client($this->servers);
					$client->mainLoop();
				} elseif ($type == self::GEARMAN_WORKER) {
					$worker = new Scalr_Service_Gearman_Worker($this->servers);
					$worker->mainLoop();
				}
				exit();
			}
		}
		
		public function launchProcess($type)
		{
			$this->forkChild($type);
		}
		
		protected function getNameByType($type)
		{
			if ($type == self::GEARMAN_CLIENT)
				return 'Client';
			else
				return 'Worker';
		}
		
		public function checkProcess($type)
		{
			$this->logger->error("{$this->getNameByType($type)}Check");
			$clientPid = $this->shm->get($type);
			if ($clientPid){
				$this->logger->error("{$this->getNameByType($type)}MainProcessFound: {$clientPid}");
				print "Found: {$clientPid}\n";
				if (!posix_kill($clientPid, 0)) {
					$this->logger->error("{$this->getNameByType($type)}MainProcess not running. Starting new one...");	
				} else {
					$this->logger->error("{$this->getNameByType($type)}MainProcess is up and running. Exiting...");
					exit();
				}
			} else {
				$this->logger->error("{$this->getNameByType($type)}MainProcessNotFound");
			}
			
			$this->launchProcess($type);
		}
		
		public function terminateProcess($type, $kill = false)
		{
			$clientPid = $this->shm->get($type);
			posix_kill($clientPid, ($kill) ? SIGKILL :SIGTERM);
		}
	}