<?php

/**
 * Process pool for executing work queue
 * 
 * @author Marat Komarov
 */

class Scalr_System_Ipc_ProcessPool extends Scalr_Util_Observable {
	
	public $name;
	
	public $size;
	
	public $workQueue;
	
	public $worker;
	
	public $daemonize;
	
	public $workTimeout;
	
	public $workerTimeout;
	
	public $workerMemoryLimit;
	
	public $workerMemoryLimitTick = 10000; // 10 seconds
	
	public $startupTimeout = 5000; // 5 seconds
	
	public $termTimeout = 5000; // 5 seconds
	
	protected $poolPid;
	
	protected $childPid;
	
	protected $childs = array();
	
	protected $isChild = false;
	
	protected $childEventQueue;	

	protected $ready = false;	
	
	private static $termExitCode = 9;
	
	private $logger;
	
	private $timeLogger;
	
	private $stopForking = false;
	
	private $timeoutFly;
	
	private $inWaitLoop;
	
	private $cleanupComplete = false;
	
	/**
	 * @var Scalr_System_Ipc_Shm
	 */
	private $shm;
	
	/**
	 * @var Scalr_System_Ipc_ShmArray
	 */
	private $workersStat;
	
	const SHM_STARTUP_BARRIER = 0;
	
	/**
	 * 
	 * @param array $config
	 * @key int [size]*
	 * @key Scalr_DataQueue [workQueue]* Work queue. Must be multi-process safe (ex impl: Scalr_System_Ipc_ShmQueue)
	 * @key Scalr_System_Ipc_DefaultWorker [worker]*
	 * @key string [name] Pool name. Will be used instead of posix_getpid() as a ipc resources suffix
	 * @key int [startupTimeout] Time to wait when 'start' event will be received from all childs 
	 * @key int [workTimeout] Max execution time for $worker->handleWork() (default infinity)
	 * @key int [workerTimeout] Max execution time for worker process (default infinity)
	 * @key int [termTimeout] Time to wait after sending SIGTERM to worker process (default 5 seconds)
	 * @key bool [daemonize] daemonize process pool (default false)
	 * @key int [workerMemoryLimit] Memory limit for worker process
	 * @key int [workerMemoryLimitTick] Tick time for worker memory limit check   
	 * 
	 * @event ready
	 * @event shutdown
	 * @event signal
	 * 
	 * @return Scalr_System_Ipc_ProcessPool
	 */
	function __construct ($config) {
		// Check system requirements
        if (substr(PHP_OS, 0, 3) === 'WIN') {
            throw new Scalr_System_Ipc_Exception('Cannot run on windows');
        } else if (!in_array(substr(PHP_SAPI, 0, 3), array('cli', 'cgi'))) {
        	throw new Scalr_System_Ipc_Exception('Can only run on CLI or CGI enviroment');
        } else if (!function_exists('pcntl_fork')) {
        	throw new Scalr_System_Ipc_Exception('pcntl_* functions are required');
        } else if (!function_exists('posix_kill')) {
        	throw new Scalr_System_Ipc_Exception('posix_* functions are required');
        }

        // Apply configuration
        foreach ($config as $k => $v) {
        	if (property_exists($this, $k)) {
        		$this->{$k} = $v;
        	}
		}
		if ($this->size < 1) {
			throw new Scalr_System_Ipc_Exception(sprintf(
					"'size' must be more then 1. '%s' is given", $this->size));
		}
		
		if ($this->workQueue && !is_object($this->workQueue)) {
			$this->workQueue = new Scalr_System_Ipc_ShmQueue($this->workQueue);
		}
		
		$this->shm = new Scalr_System_Ipc_Shm(array(
			"name" => "scalr.ipc.processPool.shm-" . ($this->name ? $this->name : posix_getpid())
		));
		
		$this->defineEvents(array(
			/**
			 * Fires when pool process received Unix signal
			 * @event signal
			 * @param Scalr_System_Ipc_ProcessPool $ppool
			 * @param int $signal
			 */
			"signal",
		
			/**
			 * Fires each 100 millis
			 * @event tick
			 * @param Scalr_System_Ipc_ProcessPool $ppool
			 * @param int $tick
			 */
			"tick",
		
			/**
			 * Fires when pool ready for processing tasks
			 * @event ready
			 * @param Scalr_System_Ipc_ProcessPool $ppool
			 */
			"ready",
		
			/**
			 * Fires when pool is going terminate 
			 * @event shutdown
			 * @param Scalr_System_Ipc_ProcessPool $ppool
			 */
			"shutdown"
		));
		
		$this->logger = Logger::getLogger(__CLASS__);
		$this->timeLogger = Logger::getLogger("time");
		register_shutdown_function(array($this, "_cleanup"));
	}
	
	function start () {
		$t1 = microtime(true);
		$msg = "Starting process pool (size: {$this->size}";
		if ($this->daemonize) $msg .= ", daemonize: true";
		$msg .= ")";
        $this->logger->info($msg);
		
		// @see http://www.php.net/manual/en/function.pcntl-fork.php#41150
        @ob_end_flush();		
		
		if ($this->daemonize) {
			$this->logger->info("Going to daemonize process pool. Fork child process");
			$pid = pcntl_fork();
			if ($pid == -1) {
				throw new Scalr_System_Ipc_Exception("Cannot daemonize process pool: cannot fork process");
			} else if ($pid == 0) {
				// Child
				$this->logger->info("Detaching process from terminal");
				if (posix_setsid() == -1) {
					throw new Scalr_System_Ipc_Exception("Cannot detach process from terminal");
				}
				$this->sleepMillis(200);
			} else {
				// Parent process
				die();
			}
		}
		
		
		$this->poolPid = posix_getpid();
		$this->initSignalHandler();
		
		/*
		$this->workersStat = new Scalr_System_Ipc_ShmArray(array(
			"name" => "scalr.ipc.processPool.workersStat.{$this->poolPid}" 
		));
		*/
		
		$this->shm->put(self::SHM_STARTUP_BARRIER, 0);
		
		$this->childEventQueue = new Scalr_System_Ipc_ShmQueue(array(
			// Important! suffix must be pool pid
			"name" => "scalr.ipc.processPool.ev-" . $this->poolPid,
			"autoInit" => true
		));
		
		$this->timeoutFly = new Scalr_Util_Timeout(0);
		
		// Start forking
		try {
			$userWorkQueue = $this->worker->startForking($this->workQueue);
			if ($userWorkQueue) {
				$this->workQueue = $userWorkQueue;
			}
		} catch (Exception $e) {
			$this->logger->error("Exception in worker->startForking(). "
					. "Caught: <".get_class($e)."> {$e->getMessage()}");
			$this->shutdown();
			throw $e;
		} 

		// Fork childs		
		for ($i=0; $i<$this->size; $i++) {
			try {
				$this->forkChild();
			} catch (Exception $e) {
				$this->logger->error("Exception during fork childs. "
						. "Caught: <".get_class($e)."> {$e->getMessage()}");
				$this->shutdown();
				throw $e;
			}
		}

		
		// Wait when all childs enter startup barrier

		if (!pcntl_setpriority(-10)) {
			$this->logger->warn("Cannot set higher priority for main process");
		}
	
		try {
			$timeout = new Scalr_Util_Timeout($this->startupTimeout);
			while (!$timeout->reached()) {
				//if (count($this->workersStat) == $this->size) {
				//	break;
				//}
				
				$this->handleChildEvents();	
				$this->logger->info("Barrier capacity: " . $this->shm->get(self::SHM_STARTUP_BARRIER));
				if ($this->shm->get(self::SHM_STARTUP_BARRIER) == $this->size) {
					break;
				}
				$timeout->sleep(10);
			}
		} catch (Scalr_Util_TimeoutException $e) {
			$this->logger->error("Caught timeout exception");
			
			$this->shutdown();			
			
			throw new Scalr_System_Ipc_Exception(sprintf("Timeout exceed (%d millis) "
					. "while waiting when all childs enter startup barrier", 
					$this->startupTimeout));
		}
		$this->logger->debug("All children (".count($this->childs).") have entered startup barrier");
		//$this->timeLogger->info("startup;" . (microtime(true) - $t1) . ";;;");
		
		// Send to all childs SIGUSR2
		$this->logger->debug("Send SIGUSR2 to all workers");
		foreach ($this->childs as $i => $childInfo) {
			$this->kill($childInfo["pid"], SIGUSR2); // Wakeup
			$this->childs[$i]["startTime"] = microtime(true);
		}
		
		$this->logger->debug("Process pool is ready");
		$this->ready = true;
		$this->fireEvent("ready", $this);
		
		$this->wait();		
	}
	
	function shutdown () {
		$this->logger->info("Shutdown...");
		$this->fireEvent("shutdown", $this);
		
		$this->stopForking = true;
		foreach ($this->childs as $childInfo) {
			$this->terminateChild($childInfo["pid"]);
		}
		$this->wait();
	}
	
	function _cleanup () {
		if (!$this->cleanupComplete && posix_getpid() == $this->poolPid) {
			try {
				$this->shm->delete();
			} catch (Exception $ignore) {
			}

			if ($this->childEventQueue) {			
				try {
					$this->childEventQueue->delete();
				} catch (Exception $ignore) {
				}
			}

			if ($this->workersStat) {	
				try {
					$this->workersStat->delete();
				} catch (Exception $ignore) {
				}
			}
			
			$this->cleanupComplete = true;

		}
	}
	
	protected function postShutdown () {
		$this->_cleanup();
		$this->worker->endForking();		
	}
	
	protected function wait () {
		if ($this->inWaitLoop) {
			return;
		}
		$this->inWaitLoop = true;
		
		$ticks = 0;
		while ($this->childs) {
			// Handle children events
			$this->handleChildEvents(50);			
			
			// Handle children timeouts
			foreach ($this->childs as $childInfo) {
				if ($childInfo["termStartTime"]) {
					// Kill maybe
					if ($this->timeoutReached($this->termTimeout, $childInfo["termStartTime"])) {
						$this->logger->info(sprintf("Child %d reached termination timeout and will be KILLED", 
								$childInfo["pid"]));
						$this->kill($childInfo["pid"], SIGKILL);
						$this->logger->info(sprintf("Kill hanged child PID: %d (Was notified with SIGTERM at %d, now: %d)", 
								$childInfo["pid"], $childInfo["termStartTime"], time()));
					}
					
				} else {
					// Terminate maybe	
					$term = $this->workTimeout && $childInfo["workStartTime"] &&
							$this->timeoutReached($this->workTimeout, $childInfo["workStartTime"]);
					if ($term) {
						$this->logger->info(sprintf("Child PID: %d reached WORK max execution time", 
								$childInfo["pid"]));
					} else {
						$term = $this->workerTimeout && $childInfo["startTime"] &&
							$this->timeoutReached($this->workerTimeout, $childInfo["startTime"]);
						if ($term) {
							$this->logger->info(sprintf("Child %d reached WORKER max execution time", 
									$childInfo["pid"]));
						}
					}
					
					if ($term) {
						$this->terminateChild($childInfo["pid"]);
					}
				}
			}

			// When child dies, this gets rid of the zombies
			$pid = pcntl_wait($status, WNOHANG);
			if ($pid > 0) {
				$this->logger->info(sprintf("wait() from child %s. Status: %d", $pid, $status));
				$this->onSIGCHLD($pid, $status);
			}
			
			// Check for zombies each 20 iterations (~ 1 second)
			if ($ticks % 20 == 0) {
				$this->checkZombies();
			} 
			
			$this->sleepMillis(50);
			$ticks++;
		}
		$this->logger->info("Leave wait loop");
		$this->inWaitLoop = false;
		
		$this->postShutdown();
	}

	protected function forkChild ($useBarrier=true) {
		$this->logger->info("Fork child process");
		$pid = pcntl_fork();
		
		if ($pid == -1) {
			// Cannot fork child				
			throw new Scalr_System_Ipc_Exception("Cannot fork child process");
			
		} else if ($pid) {
			// Current process
			$this->logger->info(sprintf("Child PID: %s was forked", $pid));
			$this->childs[$pid] = array("pid" => $pid);
			$this->worker->childForked($pid);
			
		} else {
			// Child process
			try {
				$this->isChild = true;
				$this->childPid = posix_getpid();
				$this->logger->info("Starting...");
				$this->fireChildEvent("start");
				
				if (!$useBarrier) {
					$this->ready = true;
				} else {
					/*
					$stat = new Scalr_System_Ipc_WorkerStat();
					$stat->pid = $this->childPid;
					$stat->startTime = microtime(true);
					$stat->ready = true;
					$this->workersStat[$this->childPid] = $stat;
					*/
					
					// Wait for SIGUSR2				
					while (!$this->ready) {
						$this->sleepMillis(10);
					}
				}
				
				$this->worker->startChild();
				
				if ($this->workQueue) {
					$memoryTick = new Scalr_Util_Timeout($this->workerMemoryLimitTick);
					$os = Scalr_System_OS::getInstance();
					while ($message = $this->workQueue->peek()) {
						$t1 = microtime(true);
						$this->logger->info("Peek message from work queue");

						// Notify parent before message handler	
						/*					
						$stat = $this->workersStat[$this->childPid];
						$stat->message = $message;
						$stat->workStartTime = microtime(true);
						$stat->workEndTime = null;
						$this->workersStat[$this->childPid] = $stat;
						*/
						
						$this->fireChildEvent("beforeHandleWork", array(
							"microtime" => microtime(true),
							"message" => $message
						));
						
						//$this->timeLogger->info("before handle work ($message);; " . (microtime(true) - $t1) . ";;");
						
						//$t1 = microtime(true);
						$this->worker->handleWork($message);
						
						//$this->timeLogger->info("handle work ($message);;; " . (microtime(true) - $t1) . ";");
						
						//$t1 = microtime(true);
						
						// Notify parent after message handler
						$this->fireChildEvent("afterHandleWork", array(
							"message" => $message
						));
						if ($this->workerMemoryLimit && $memoryTick->reached(false)) {
							$this->fireChildEvent("memoryUsage", array(
								"memory" => $os->getMemoryUsage(posix_getpid(), Scalr_System_OS::MEM_RES)
							));
							$memoryTick->reset();
						}
						
						
						/*
						$stat = $this->workersStat[$this->childPid];
						$stat->workEndTime = microtime(true);
						
						if ($this->workerMemoryLimit && $memoryTick->reached(false)) {
							$stat->memoryUsage = $os->getMemoryUsage($this->childPid, Scalr_System_OS::MEM_RES);
							$stat->memoryUsageTime = microtime(true);
							$memoryTick->reset();
						}
						
						$this->workersStat[$this->childPid] = $stat;
						*/
						
						//$this->timeLogger->info("after handle work ($message);;;; " . (microtime(true) - $t1));
						//$this->logger->info("TIME after handleWork : " . round(microtime(true) - $t1, 4) . " sec");
					}
				}
				
				$this->worker->endChild();
				$this->logger->info("Done");
				
			} catch (Exception $e) {
				// Raise fatal error
				$this->logger->info(sprintf("Unhandled exception in worker process: <%s> '%s'", 
						get_class($e), $e->getMessage()));
				$this->logger->info(sprintf("Worker process %d terminated (exit code: %d)", 
						$this->childPid, self::$termExitCode));
						
				// Sometimes (in our tests when daemonize=true) parent process doesn't receive SIGCHLD
				// Sending kill signal will force SIGCHLD
				// TODO: Consider it deeper				   
				posix_kill($this->childPid, SIGKILL);
				
				exit(self::$termExitCode);
			}
			
			exit();
		}
	}
	
	private function checkZombies() {
		// Check zomby child processes
		$this->logger->debug('Check zombies');
		foreach (array_keys($this->childs) as $pid) {
			if (!posix_kill($pid, 0)) {
				unset($this->childs[$pid]);
			} else {
				$this->logger->debug(sprintf("process %d is alive", $pid));
			}
		}
	}
	
	private function timeoutReached ($timeout, $startTime) {
		$this->timeoutFly->start = $startTime;
		$this->timeoutFly->setTimeout($timeout);
		return $this->timeoutFly->reached(false);
	}
	
	private function sleepMillis ($millis) {
		Scalr_Util_Timeout::sleep($millis);
	}
	
	protected function kill ($pid, $signal, $logPrefix="") {
		$this->logger->info(sprintf("%sSend %s -> %s", $logPrefix, self::$signames[$signal], $pid));
		return posix_kill($pid, $signal);
	}
	
	protected function terminateChild ($pid) {
		if (key_exists($pid, $this->childs)) {
			$this->kill($pid, SIGTERM);
			$this->childs[$pid]["termStartTime"] = microtime(true);
		}
	}
	
	protected function fireChildEvent ($evName, $evData=array()) {
		//$t1 = microtime(true);
		$evData["type"] = $evName;
		$evData["pid"] = posix_getpid();
		$this->childEventQueue->put($evData);
		//$this->logger->info("TIME put '$evName' event: " . (round(microtime(true) - $t1, 4)) . " sec");
		/*
		if (!$this->kill($this->poolPid, SIGUSR1, "[".posix_getpid()."] ")) {
			$this->logger->fatal("Cannot send signal to parent process");
			posix_kill(posix_getpid(), SIGKILL);
		}
		*/
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
		$signals = array(SIGCHLD, SIGTERM, SIGABRT, SIGALRM, SIGUSR1, SIGUSR2);
		if ($this->daemonize) {
			$signals[] = SIGHUP;
		}
		foreach ($signals as $sig) {
			$this->logger->debug("Install ".self::$signames[$sig]." handler");
			if (!pcntl_signal($sig, $fn)) {
				$this->logger->warn(sprintf("Cannot install signal handler on signal %s in process %d", 
						self::$signames[$sig], posix_getpid()));
			}
		}
	}
	
	function signalHandler ($sig) {
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
				// In child
				$this->logger->debug(sprintf("Received %s", self::$signames[$sig]));
				$this->ready = true;
				break;
				
			case SIGUSR1:
				// XXX: SIGHUP doesn't works
				$this->logger->info(sprintf("Received %s", self::$signames[$sig]));

				$num = $this->size - count($this->childs);
				if ($num) {
					$this->logger->info(sprintf("HUP: Need to fork %d workers", $num));
					for ($i=0; $i<$num; $i++) {
						$this->forkChild(false);
					}
				}
				break;
				
				
			// Terminate process
			case SIGTERM:
			case SIGABRT:
				
				if ($mypid == $this->poolPid) {
					// In parent
					$this->logger->info(sprintf("Received %s in parent", self::$signames[$sig]));
					if (!$this->terminating) {
						$this->terminating = true;
						$this->fireEvent("signal", $this, $sig);
						$this->shutdown();
					} else {
						$this->logger->info(sprintf("Termination is already initiated"));
					}
					return;					 		
				} else {
					// In child
					$this->logger->info(sprintf("Received %s in child", self::$signames[$sig]));					
					if ($this->isChild) {
						$this->logger->debug("Worker terminating...");
						try {
							$this->worker->terminate();
						} catch (Exception $e) {
							$this->logger->error("Exception in worker->terminate(). Caught: {$e->getMessage()}");
						}
						$this->logger->info(sprintf("Worker process %d terminated (exit code: %d)", 
								$mypid, self::$termExitCode));

						// Sometimes (in our tests when daemonize=true) parent process does'nt receive SIGCHLD
						// Sending kill signal will force SIGCHLD
						// TODO: Consider it deeper  
						posix_kill($mypid, SIGKILL);
						exit(self::$termExitCode);					
					}
				}
				break;
			
			default:
				$this->logger->info(sprintf("Received %s", self::$signames[$sig]));
				break;
		}
		
		$this->fireEvent("signal", $this, $sig);
	}
	
	protected function onSIGCHLD ($pid, $status) {
		if ($pid <= 0) {
			$this->logger->warn(sprintf('process PID is negative (pid: %s)', $pid)); 
			return;
		}
		
		$this->logger->info(sprintf("Childs: (%s)", join(", ", array_keys($this->childs))));
		if (key_exists($pid, $this->childs)) {
			unset($this->childs[$pid]);
			
			$this->logger->debug(sprintf("Child termination options. "
					. "wifexited: %d, wifsignaled: %d, wifstopped: %d, stopForking: %d", 
					pcntl_wifexited($status), pcntl_wifsignaled($status), 
					pcntl_wifstopped($status), $this->stopForking));
			
			// In case of unnormal exit fork new child process
			// 1. status=65280 when child process died with fatal error (set by PHP)
			// 2. exit=9 when child was terminated by parent or by unhandled exception (set by ProcessPool)
			// 3. stopeed by signal
			$crashed = (pcntl_wifexited($status) && $status == 65280) ||
				(pcntl_wifexited($status) && pcntl_wexitstatus($status) == self::$termExitCode) ||
				(pcntl_wifsignaled($status));
			
					
			if ($crashed) {
				$this->logger->info(sprintf("Child PID: %s crashed (status: %s, wifexited: %s, wifsignaled: %s)",
						$pid, $status, pcntl_wifexited($status), pcntl_wifsignaled($status)));
			} else {
				$this->logger->info(sprintf("Child PID: %s seems normally terminated (status: %s, wifexited: %s, wifsignaled: %s)",
						$pid, $status, pcntl_wifexited($status), pcntl_wifsignaled($status)));
			}
			$numTasks = $this->workQueue ? $this->workQueue->capacity() : 0;
			if ($numTasks) {
				$this->logger->info(sprintf("Work queue still has %d unhandled tasks", $numTasks));
			}
			
			if ($crashed || $numTasks) {
				try {
					if (!$this->stopForking) {
						$this->logger->info("Forking new worker process");
						$this->forkChild(false);
					} else {
						$this->logger->info("Flag stopForking=1 prevents new process forking");
					}
				} catch (Scalr_System_Ipc_Exception $e) {
					$this->logger->error(sprintf("Cannot fork child. Caught: <%s> %s", 
							get_class($e), $e->getMessage()));
				}
			}
		} else {
			$this->logger->info(sprintf("Child PID: %s is unknown. Known childs: (%s)", 
					$pid, join(",", array_keys($this->childs))));
		}
	}

	protected function handleChildEvents ($nmess=null) {
		$i = 0;
		while ($message = $this->childEventQueue->peek()) {
			$t1 = microtime(true);
			$this->logger->debug(sprintf("Peeked '%s' from event queue", $message["type"]));
			
			switch ($message["type"]) {
				case "beforeHandleWork":
					$this->childs[$message["pid"]]["workStartTime"] = $message["microtime"];
					$this->childs[$message["pid"]]["message"] = $message["message"];
					break;
					
				case "afterHandleWork":
					unset($this->childs[$message["pid"]]["workStartTime"]);
					unset($this->childs[$message["pid"]]["message"]);
					break;
					
				case "start":
					if (!$this->ready) {
						$this->shm->put(self::SHM_STARTUP_BARRIER, $this->shm->get(self::SHM_STARTUP_BARRIER) + 1);
					} else {
						$this->childs[$message["pid"]]["startTime"] = microtime(true);
					}
					break;
				
				case "memoryUsage":
					if ($this->workerMemoryLimit && $message["memory"] > $this->workerMemoryLimit) {
						$this->logger->info(sprintf(
								"Worker %d allocates %d Kb. Maximum %d Kb is allowed by configuration", 
								$message["pid"], $message["memory"], $this->workerMemoryLimit));
						$this->terminateChild($message["pid"]);
					}
					break;
					
				default:
					$this->logger->warn("Peeked unknown message from child event queue. "
							. "Serialized message: {$message0}");
			}
			
			$this->logger->info("Child message handle: " . round(microtime(true) - $t1, 4) . " sec");
			
			$i++;
			if ($nmess && $i >= $nmess) {
				break;
			}
		}
	}
	
	function getPid () {
		return $this->poolPid;
	}
}

/*
class Scalr_System_Ipc_WorkerStat {
	public 
		// Worker process pid
		$pid,
		// Worker start time
		$startTime, 
		// Ready flag
		$ready,
		// Message working on
		$message,
		// Current work start time
		$workStartTime,
		// Current work end time
		$workEndTime,
		// Memory usage
		$memoryUsage,
		// Memory usage probe time
		$memoryUsageTime;
}
*/