<?php
	class Scalr_Service_Gearman
	{
		protected $poolPid;
		protected $logger;
		protected $jobDir;
		protected $clsNamespace;
		protected $workers;
		protected $jobs;
		protected $terminating = false;
		protected $servers;
		
		public function __construct()
		{
			$this->jobDir = dirname(__FILE__)."/Gearman/Jobs";
			$this->clsNamespace = "Scalr_Service_Gearman_Jobs";
			$this->workers = array();
		}
		
		protected function loadJobs()
		{
			$jobClassnames = @glob($this->jobDir."/*.php");
			
			foreach ($jobClassnames as $filename) {
				$basename = basename($filename);
				if (in_array($basename{0}, array("_", "."))) {
					continue;
				}
				$fname = pathinfo($filename, PATHINFO_FILENAME);

				$className = ($this->clsNamespace ? "{$this->clsNamespace}_" : "") . $fname;
				require_once("{$this->jobDir}/{$basename}");
				if (class_exists($className)) {
					$this->jobs[$className] = array('jobName' => $className, 'pids' => array());
				}
			}
		}
		
		public function mainLoop()
		{
			$this->loadJobs();
			$this->poolPid = posix_getpid();
			$this->initSignalHandler();
		
			while (true) {
				
				$this->logger->info("MainLoop");
				
				if (!$this->terminating) {
					foreach ($this->jobs as $job => $settings) {
						
						$startWorker = false;
						if (count($settings['pids']) < $this->processesPerJob) {
							$startWorker = true;
							$this->logger->error("No process for job: {$job}. Starting...");
						}
						else {
							foreach ($settings['pids'] as $pid) {
								if (!posix_kill($pid, 0)) {
									$startWorker = true;
									$this->logger->error("Process for job: {$job}. Not running. Starting...");
									break;
								}
							}
						}
						
						if ($startWorker)
							$this->forkChild($job);
					}
				}
				
				$pid = pcntl_wait($status, WNOHANG);
				if ($pid > 0) {
					$this->logger->info(sprintf("wait() from child %s. Status: %d", $pid, $status));
					foreach ($this->jobs as $job=>$settings)
					{
						foreach ($settings['pids'] as $k => $wpid) {
							if ($wpid && $wpid == $pid) {
								$this->logger->warn("{$job} client worker died");
								unset($settings['pids'][$k]);
								$this->jobs[$job] = $settings;
								break;
							}
						}
					}
				}
				
				if ($this->terminating) {
					$pids = 0;
					foreach ($this->jobs as $settings) {
						$pids += count($settings['pids']);
					}
					
					if ($pids == 0)
						exit();
				}
				
				sleep(10);
			}
		}
		
		protected function forkChild($jobName)
		{
			$this->logger->info("Fork child process");
			
			$pid = pcntl_fork();
			if ($pid == -1) {
				// Cannot fork child				
				throw new Scalr_System_Ipc_Exception("Cannot fork child process");
				
			} else if ($pid) {
				// Current process
				$this->logger->info(sprintf("Child PID: %s was forked", $pid));
				$this->jobs[$jobName]['pids'][] = $pid;
				
			} else {
				
				//$this->childProcess($jobName);
				while (true)
				{
					print "1";
					sleep(20);
				}
				
				exit();
			}
		}
		
		protected static $signames = array(
			SIGHUP => "SIGHUP",
			SIGINT => "SIGINT",
			SIGQUIT => "SIGQUIT",
			SIGILL => "SIGILL",
			SIGTRAP => "SIGTRAP",
			SIGABRT => "SIGABRT",
			SIGBUS => "SIGBUS",
			SIGFPE => "SIGFPE",
			SIGKILL => "SIGKILL",
			SIGUSR1 => "SIGUSR1",
			SIGSEGV => "SIGSEGV",
			SIGUSR2 => "SIGUSR2",
			SIGPIPE => "SIGPIPE",
			SIGALRM => "SIGALRM",
			SIGTERM => "SIGTERM",
			SIGSTKFLT => "SIGSTKFLT",
			SIGCHLD => "SIGCHLD",
			SIGCONT => "SIGCONT",
			SIGSTOP => "SIGSTOP",
			SIGTSTP => "SIGTSTP",
			SIGTTIN => "SIGTTIN",
			SIGTTOU => "SIGTTOU",
			SIGURG => "SIGURG",
			SIGXCPU => "SIGXCPU",
			SIGXFSZ => "SIGXFSZ",
			SIGVTALRM => "SIGVTALRM",
			SIGPROF => "SIGPROF",
			SIGWINCH => "SIGWINCH",
			SIGPOLL => "SIGPOLL",
			SIGIO => "SIGIO",
			SIGPWR => "SIGPWR",
			SIGSYS => "SIGSYS",
			SIGBABY => "SIGBABY"
		);
		
		protected function initSignalHandler () {
			$fn = array($this, "signalHandler");
			$signals = array(SIGCHLD, SIGTERM, SIGABRT, SIGALRM, SIGUSR1, SIGUSR2, SIGHUP);
			foreach ($signals as $sig) {
				if (!pcntl_signal($sig, $fn)) {
					$this->logger->warn(sprintf("Cannot install signal handler on signal %s in process %d", 
							self::$signames[$sig], posix_getpid()));
				}
			}
			
			$this->logger->info("initSignalHandler");
		}
		
		protected function onSIGCHLD($pid, $status)
		{
			foreach ($this->jobs as $job=>$settings)
			{
				foreach ($settings['pids'] as $k => $wpid) {
					if ($wpid == $pid) {
						$this->logger->warn("{$job} worker died");
						unset($settings['pids'][$k]);
						$this->jobs[$job] = $settings;
						break;
					}
				}
			}
		}
		
		protected function shutdown()
		{	
			if (!$this->terminating) {
				$this->terminating = true;
				$this->killWorkers();
			}
		}
		
		protected function killWorkers()
		{
			foreach ($this->jobs as $job => $settings)
				@posix_kill($settings['pid'], SIGTERM);
		}
		
		public function signalHandler ($sig) {
			$mypid = posix_getpid();
			
			switch ($sig) {
				// Child terminated
				case SIGCHLD: 
					// In parent
					$pid = pcntl_waitpid(0, $status, WNOHANG);	
					if ($pid != -1) {			
						$this->logger->info(sprintf("Received %s from %d. Status: %d", 
								self::$signames[$sig], $pid, $status));	
						$this->onSIGCHLD($pid, $status);
					}
					break;
					
				// Startup barrier ready
				case SIGUSR2: 					
				case SIGUSR1:
					
					//TODO:
					
					break;
					
					
				// Terminate process
				case SIGTERM:
				case SIGABRT:
					
					if ($mypid == $this->poolPid) {
						// In parent
						$this->logger->info(sprintf("Received %s in parent", self::$signames[$sig]));
						$this->shutdown();
						
						return;					 		
					} else {
						// In child
						$this->logger->info(sprintf("Worker process %d terminated", $mypid));

						// Sometimes (in our tests when daemonize=true) parent process does'nt receive SIGCHLD
						// Sending kill signal will force SIGCHLD
						// TODO: Consider it deeper  
						posix_kill($mypid, SIGKILL);
						exit();					
					}
					break;
				
				default:
					$this->logger->info(sprintf("Received %s", self::$signames[$sig]));
					break;
			}
		}
	}