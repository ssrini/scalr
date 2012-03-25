<?php

class Scalr_System_Cronjob_Distributed extends Scalr_System_Cronjob_MultiProcess {
	
	const ENV_CONFIG_FILE_PROPERTY 	= "scalr.system.dcron.configFile";
	const ENV_NONE_NAME_PROPERTY 	= "scalr.system.dcron.nodeName";

	const GETOPT_CONFIG_FILE = "distributed-ini";
	const GETOPT_NONE_NAME = "node-name";
	
	const REGKEY_MAIN_PROCESS_PID = "main.pid";
	const REGKEY_COORDINATOR_PROCESS_PID = "coord.pid";	

	private $logger;	
	
	protected $zookeeper;

	protected $jobZPath;	
	
	protected $nodeName;	

	private $globalWorkQueue;
	
	/**
	 * @var Scalr_System_Cronjob_Distributed_NodeRegistry
	 */
	private $nodeRegistry;
	
	protected $quorum;

	protected $isLeader;
	
	private $leaderTimeout = 60000; // 1 minute
	
	private $leaderElection;

	protected $electionTimeout;

	protected $coordinatorSlippageLimit = 10;
	
	private $leaderMtime;
	
	private $returnedNodesQueue;
	
	private $coordinatorPid;
	
	private $coordinatorLoop;
	
	/**
	 * @var Scalr_System_Cronjob_MultiProcess_Worker
	 */
	protected $worker;
	
	/**
	 * @var Scalr_System_Cronjob_Distributed_Elector
	 */
	protected $elector;
	
	static function getConfig () {
		return Scalr_Util_Arrays::mergeReplaceRecursive(parent::getConfig(), array(
			"getoptRules" => array(
				self::GETOPT_CONFIG_FILE."=s" => "Distributed cronjob configuration file. Local file or URL is accepted",
				self::GETOPT_NONE_NAME."=s" => "Computing node name. Ex: node-1" 
			)
		));
		
		return $ret;
	}

	function startForking ($workQueue) {
		$this->logger->debug("Start forking");		
		
		//$this->processPool->on("signal", array($this, "onSignal"));
		//$this->processPool->on("shutdown", array($this, "onShutdown"));
		
		$this->worker->startForking($this->processPool->workQueue);
		
		if (!$this->nodeRegistry->nodesCapacity()) {
			$this->logger->info("Job is not running. Intiate job and begin leader election");
			
			// Create job znode
			$this->zookeeper->setOrCreate($this->jobZPath, null, false);
			
			// Create leader znode
			$this->zookeeper->setOrCreate("{$this->jobZPath}/leader", null, false);

			// Register node
			$this->nodeRegistry->set(self::REGKEY_MAIN_PROCESS_PID, posix_getpid());
			
			try {
				$this->leaderElection->initiate();
			} catch (Scalr_Service_Zookeeper_InterruptedException $ignore) {
			}
			
			$this->doLeaderElection();
			
			if ($this->isLeader) {
				$this->worker->enqueueWork($this->globalWorkQueue);
			}
		} else {
			$this->logger->info("Job is already running. Put myself into returned nodes queue");
			
			$this->nodeRegistry->set(self::REGKEY_MAIN_PROCESS_PID, posix_getpid());
			$this->returnedNodesQueue->put($this->elector->getElectionData());
		}

		$this->forkCoordinator();
		
		//return $this->processPool->workQueue;
	}
	
	function endForking () {
		$this->logger->info("End forking. Perform cleanup");
		
        try {
        	if ($this->zookeeper) {
	            $this->logger->debug("Delete node from node registry");
	            $this->nodeRegistry->deleteNode();
        	}
        } catch (Exception $ignore) {}
        
        parent::endForking();
	}
	
	function run ($options=null) {
		$this->init($options);
		
		// Check that process pool is running
		try {
			$poolPid = $this->nodeRegistry->get(self::REGKEY_MAIN_PROCESS_PID);
		} catch (Exception $e) {
			$this->logger->warn(sprintf("Caught: <%s> %s. Let the process pool is not started", 
					get_class($e), $e->getMessage()));
			$poolPid = 0;
		}		
		
		if ($this->poolIsRunning($poolPid)) {
			// and i'm a leader node ...
			if ($this->nodeName == $this->zookeeper->getData("{$this->jobZPath}/leader")) {
				if (!$this->checkMemoryLimit()) {
					return;
				}
				
				// Enqueue work
				$this->worker->enqueueWork($this->globalWorkQueue);
			}
			return;
		}
		
		$this->processPool->start();
	}
	
	protected function init ($options=null) {
		$this->logger = Logger::getLogger(__CLASS__);

		// Merge configurations. this config, ini config
		
		// Get configuration filename from ENV, CLI options, own static config, default: "dcron.ini"
		$configFileName = $_ENV[self::ENV_CONFIG_FILE_PROPERTY];
		if (!$configFileName) {
			if ($options && $options["getopt"]) {
				$configFileName = $options["getopt"]->getOption(self::GETOPT_CONFIG_FILE);
			}
		}
		if (!$configFileName) {
			if ($this->config["iniFile"]) {
				$configFileName = $this->config["iniFile"];
			}
		}
		if (!$configFileName) {
			$configFileName = "dcron.ini";
		}
		
		// Load configuration	
		$configString = @file_get_contents($configFileName);
		if (!$configString) {
			throw new Scalr_System_Cronjob_Exception(sprintf("Cannot load configuration file '%s'", $configFileName));
		}
		$iniConfig = Scalr_Util_Compat::parseIniString($configString, true);
		if (!$iniConfig) {
			throw new Scalr_System_Cronjob_Exception(sprintf("Cannot parse configuration file '%s'", $configFileName));
		}
		
		// XXX Temporary hack
		if ($iniConfig["remoteConfigUrl"]) {
			$this->logger->debug(sprintf("Fetch configuration from '%s'", $iniConfig["remoteConfigUrl"]));
			$configString = @file_get_contents($iniConfig["remoteConfigUrl"]);
			if (!$configString) {
				throw new Scalr_System_Cronjob_Exception(sprintf("Cannot load configuration file '%s'", $iniConfig["remoteConfigUrl"]));
			}
			$iniConfig = Scalr_Util_Compat::parseIniString($configString, true);
			if (!$iniConfig) {
				throw new Scalr_System_Cronjob_Exception(sprintf("Cannot parse configuration file '%s'", $iniConfig["remoteConfigUrl"]));
			}
		}
		
		// Apply configuration. Worker configuration is already applied
		$this->config = Scalr_Util_Arrays::mergeReplaceRecursive($iniConfig, $this->config);
		foreach ($this->config as $k => $v) {
			if (property_exists($this, $k)) {
				$this->{$k} = $v;
			}
		}
		
		// Get nodeName from ENV, CLI options, UNIX hostname command output 
		$nodeName = $_ENV[self::ENV_NONE_NAME_PROPERTY];
		if (!$nodeName) {
			if ($options && $options["getopt"]) {
				$nodeName = $options["getopt"]->getOption(self::GETOPT_NONE_NAME);
			}
		}
		if (!$nodeName) {
			$shell = new Scalr_System_Shell();
			$nodeName = php_uname("n");
		}
		if (!$nodeName) {
			throw new Scalr_System_Cronjob_Exception('Cannot detect current nodeName. '
					. 'Use $_ENV or CLI options to setup nodeName');
		}
		$this->nodeName = $nodeName;
		
		$this->logger->info(sprintf("Initialize distributed cronjob (nodeName: %s, quorum: %d, distributedConfig: %s)", 
				$this->nodeName, $this->config["quorum"], $configFileName));
		
		// Create elector
		$electorCls = $this->config["electorCls"];
		if (!$electorCls) {
			$electorCls = "Scalr_System_Cronjob_Distributed_DefaultElector";
		}
		$this->logger->info("Set elector: {$electorCls}");
		$this->elector = new $electorCls ($this->nodeName, $this->config);
		
		// ZOO
       	$this->jobZPath = "{$this->config["jobsZPath"]}/{$this->jobName}";		 
       	$this->zookeeper = new Scalr_Service_Zookeeper($this->config["zookeeper"]);

       	$this->nodeRegistry = new Scalr_System_Cronjob_Distributed_NodeRegistry(array(
       		"zookeeper" => $this->zookeeper,
       		"path" => "{$this->jobZPath}/nodes",
       		"node" => $this->nodeName
       	));
       			
       	$this->leaderElection = new Scalr_Service_Zookeeper_Election(array(
       		"zookeeper" => $this->zookeeper,
       		"path" => "{$this->jobZPath}/election",
       		"timeout" => $this->electionTimeout,
       		"quorum" => $this->quorum
       	));
       	
       	$this->returnedNodesQueue = new Scalr_Service_Zookeeper_Queue(array(
       		"zookeeper" => $this->zookeeper,
       		"path" => "{$this->jobZPath}/returned-queue"
       	));
       			
       	// Work queue
		$this->globalWorkQueue = new Scalr_Service_Zookeeper_Queue(array(
			"zookeeper" => $this->zookeeper,
			"path" => $this->jobZPath . "/work-queue"
		));

		// Local queue
		$this->config["processPool"]["workQueue"] = new Scalr_System_Ipc_ShmQueue(array(
			"name" => "scalr.system.cronjob.multiprocess.workQueue-" . posix_getpid(),		
			"blocking" => true,
			"autoInit" => true
		));
		
		// Call parent initialization
		parent::init($options);
	}
	
	private function doLeaderElection () {
		$this->logger->info("Do leader election");
		try {
			$this->logger->info("Sending my vote");
			$this->leaderElection->vote($this->elector->getElectionData());
		} catch (Scalr_Util_TimeoutException $e) {
			$this->logger->error(sprintf("Timeout exceed (%s) while waiting for election complete", 
					$this->leaderElection->timeout->format()));
		}				
		
		$this->checkElectionResults($this->leaderElection->getVotes(), true);	
	}

	private function checkElectionResults ($votes, $updateQuorum=true) {
		$this->logger->debug("Check election results");
		if (!is_array($votes)) {
			throw new Scalr_Service_Zookeeper_Exception("Argument '\$votes' must be array");
		}
		
   		$leaderPath = "{$this->jobZPath}/leader";		
		$leaderNode = $this->elector->determineLeaderNode($votes);
		if (!$leaderNode) {
			$leaderTimeout = new Scalr_Util_Timeout($this->leaderTimeout);
			$this->logger->warn(sprintf("Elector cannot determine a leader node. "
					. "Cronjob will wait %s and do another election", $leaderTimeout->format()));
		}
		
		$oldIsLeader = $this->isLeader;
		$this->isLeader = $leaderNode == $this->nodeName;
       	if ($this->isLeader || $oldIsLeader) {
       		$this->zookeeper->setOrCreate($leaderPath, "{$leaderNode}");
       		$this->leaderMtime = $this->zookeeper->get($leaderPath)->mtime;
       	}
       	$this->logger->info($this->isLeader ? "I'm a leader!" : "O-ho-ho... I'm slave :(");
	}
	

	private function forkCoordinator () {
		$this->logger->info("Forking coordinator process");
		
		$pid = pcntl_fork();
		if ($pid > 0) {
			$this->coordinatorPid = $pid;
		} else if ($pid == 0) {
			$this->coordinatorLoop = true;
			$this->coordinatorPid = posix_getpid();			
			$ppid = posix_getppid();
			
			$this->nodeRegistry->set(self::REGKEY_COORDINATOR_PROCESS_PID, posix_getpid());
			
			$leaderPath = "{$this->jobZPath}/leader";
			$leaderTimeout = new Scalr_Util_Timeout($this->leaderTimeout);
			$zombyTimeout = new Scalr_Util_Timeout((int)$this->config["tickTime"]*10);
			$heartbeatTimeout = new Scalr_Util_Timeout((int)$this->config["tickTime"]);
			
			// Track mtime from self node
			$lastMtime = $this->zookeeper->get("{$this->nodeRegistry->path}/{$this->nodeRegistry->node}")->mtime;
			
			while ($this->coordinatorLoop) {
				$leaderTimeout->reset();
				try {
					$exceptionCounter = 0;
					while (!$leaderTimeout->reached() && $this->coordinatorLoop) {
						try {
							// Terminate myself if parent was killed
							if (!posix_kill($ppid, 0)) {
								$this->coordinatorLoop = false;
								break 2;
							}
							
							// Leader election maybe initiated
							if ($this->leaderElection->isInitiated()) {
								$this->logger->info("[coordinator] Someone has initiated leader election");
								$this->doLeaderElection();
							}
							
							// Leader may changed
							$leaderNodeName = $this->zookeeper->getData($leaderPath);
							$oldIsLeader = $this->isLeader;							
							$this->isLeader = $leaderNodeName == $this->nodeName;
							if (!$this->isLeader && $oldIsLeader) {
								$this->logger->info("[coordinator] I am not longer a leader ('$this->nodeName'). "
										. "Leader is '$leaderNodeName'");
							}
							
							// Check leader znode mtime
							$leaderStat = $this->zookeeper->get($leaderPath);
							if ($leaderStat->mtime != $this->leaderMtime) {
								// Leader had updated it's state
								$leaderTimeout->reset();															
								$this->logger->info("[coordinator] Leader is the same");
								$this->leaderMtime = $leaderStat->mtime;
							}
							
							if ($this->isLeader) {
								// Process returned nodes. 
								// Administrator's configured leader may be here
								if ($c = $this->returnedNodesQueue->capacity()) {
									$this->logger->info(sprintf("%d node(s) have returned back online", $c));
									
									$votes = array($this->elector->getElectionData());
									while ($vote = $this->returnedNodesQueue->peek()) {
										$votes[] = $vote;
									}
									
									$this->checkElectionResults($votes, false);
								}
								
								// Check zomby nodes
								if ($zombyTimeout->reached(false)) {
									$childData = $this->zookeeper->getChildren($this->nodeRegistry->path);
									foreach ($childData->children as $childName) {
										$childStat = $this->zookeeper->get("{$this->nodeRegistry->path}/{$childName}");
										if ($childStat->mtime < $lastMtime) {
											// Zomby detected
											$this->logger->info(sprintf("[coordinator] Cleanup zomby node '%s'", $childName));
											$this->zookeeper->deleteRecursive("{$this->nodeRegistry->path}/{$childName}");
										}
									}
									
									$zombyTimeout->reset();
									$lastMtime = $this->zookeeper->get("{$this->nodeRegistry->path}/{$this->nodeRegistry->node}")->mtime;
								}
							}
							
							// Node heart beat
							if ($heartbeatTimeout->reached(false)) {
								$this->logger->debug(sprintf("[coordinator] '%s' heartbeat", $this->nodeName));
								$this->nodeRegistry->touchNode();
								$heartbeatTimeout->reset();								
							}
							
							// Poll work queue
							while ($message = $this->globalWorkQueue->peek()) {
								$this->logger->info("[coordinator] Put received message into local queue");
								$this->processPool->workQueue->put($message);
							}
							
							Scalr_Util_Timeout::sleep(1000);
							
						} catch (Exception $e) {
							$this->logger->error(sprintf("[coordinator] Caught in message loop <%s> %s", 
									get_class($e), $e->getMessage()));
							if (++$exceptionCounter > $this->coordinatorSlippageLimit) {
								$this->logger->fatal("[coordinator] Got too many consistent exceptions in main loop. "
										. "Slippage limit: {$this->coordinatorSlippageLimit} exceed");
								posix_kill(posix_getppid(), SIGTERM);
								exit();
							}
						}
					}
				} catch (Scalr_Util_TimeoutException $e) {
					$this->logger->warn("[coordinator] Caught leader timeout exception ({$leaderTimeout->format()})");
					$this->logger->info("[coordinator] Start new leader election procedure");
					try {
						$this->leaderElection->initiate($this->nodeRegistry->nodesCapacity());
					} catch (Exception $e) {
							$this->logger->error(sprintf("[coordinator] Caught in leader election <%s> %s", 
									get_class($e), $e->getMessage()));
					}
				}
			}
			$this->logger->info("[coordinator] Done");
			
			exit();
		} else if ($pid == -1) {
			throw new Scalr_System_Cronjob_Exception("Cannot fork coordinator process");
		}
	}
	
	// ProcessPool shutdown event handler
	function onShutdown ($pool) {
		if ($this->coordinatorPid) {
			$this->logger->info("Send SIGTERM -> coordinator (pid: {$this->coordinatorPid})");
			posix_kill($this->coordinatorPid, SIGTERM);
		}
	}
	
	// ProcessPool signal event handler	
	function onSignal ($pool, $signal) {
		parent::onSignal($pool, $signal);
		
		switch ($signal) {
			case SIGTERM:
				if (posix_getpid() == $this->coordinatorPid) {
					$this->logger->info("Handle SIGTERM in coordinator");
					$this->coordinatorLoop = false;
				}
				break;
		}
	}
}

