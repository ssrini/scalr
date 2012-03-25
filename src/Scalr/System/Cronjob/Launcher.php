<?php

class Scalr_System_Cronjob_Launcher {
	
	public $jobDir;
	
	public $clsNamespace = "";
	
	public $clsSuffix = "";
	
	public $oldSyntax = true;
	
	private $logger;
	
	/**
	 * @param $config
	 * @key string $jobDir
	 * @key string $clsSuffix
	 * @key string $clsNamespace
	 * @key bool $oldSyntax (default true)
	 * TODO: add $getopt key
	 * TODO: support new syntax: php -q cron.php [options] task 
	 */
	function __construct ($config) {
		foreach ($config as $k => $v) {
			$this->{$k} = $v;
		}
		$this->logger = Logger::getLogger(__CLASS__);
	}
	
	function launch ($args=null) {
		$args = $args ? $args : $_SERVER["argv"];
		
		$getoptRules = array();
		
		$jobClassnames = glob("{$this->jobDir}/*{$this->clsSuffix}.php");
		$proceed = 0;
		if ($jobClassnames) {
			//$this->logger->debug("Found " . count($jobClassnames) . " job classes");
			foreach ($jobClassnames as $filename) {
				$basename = basename($filename);
				if (in_array($basename{0}, array("_", "."))) {
					continue;
				}
				$fname = pathinfo($filename, PATHINFO_FILENAME);

				$className = ($this->clsNamespace ? "{$this->clsNamespace}_" : "") . $fname;
				require_once("{$this->jobDir}/{$basename}");
				
				if (class_exists($className, false)) {
					if (method_exists($className, "getConfig")) {
						// Extract job name
						$jobName = str_replace($this->clsSuffix, "", $fname);

						// Call static getConfig from job class						
						$jobConfig = $this->computeConfig($className);
						
						// Add job to cron CLI options
						$getoptRules[$jobName] = $jobConfig["description"];
						
						// Process job own CLI options
						if ($jobConfig["getoptRules"]) {
							foreach ($jobConfig["getoptRules"] as $rule => $description) {
								// If such rule already exists, append cronjob name to With --JobName  
								$description = key_exists($rule, $getoptRules) ?
									preg_replace('/\)$/', ", --{$jobName})", $getoptRules[$rule]) :
									"{$description} (With --{$jobName})";
								
								$getoptRules[$rule] = $description;
							}
						}
						
						$proceed++;
					} else {
						$this->logger->error("Class {$className} has no static method getConfig");
					}
				} else {
					$this->logger->error("No {$className} was found in {$filename}");
				}
			}
		}
		if (!$jobClassnames || !$proceed) {
			throw new Scalr_System_Cronjob_Exception(sprintf("No job classes found in '%s'", $this->jobDir));
		}
            
		$getoptRules["help"] = "Print this help";
            
		// Parse command line options
		$getopt = new Zend_Console_Getopt($getoptRules, $args);
		try {
			$getopt->parse();
		} catch (Zend_Console_Getopt_Exception $e) {
			print "{$e->getMessage()}\n\n";
			die($getopt->getUsageMessage());
		}
		
		
		// Instantiate cronjob class
		$cronjobName = $this->oldSyntax ? substr($args[1], 2) : $args[count($args)-1];
		if (!$cronjobName || $getopt->getOption("help")) {
			die($getopt->getUsageMessage());			
		}
		
		$filename = "{$this->jobDir}/{$cronjobName}{$this->clsSuffix}.php";
		$jobClassName =  ($this->clsNamespace ? "{$this->clsNamespace}_" : "")
				. pathinfo($filename, PATHINFO_FILENAME);
		
		require_once($filename);

		// Compute cronjob configuration
		$jobConfig = $this->computeConfig($jobClassName);
		$jobConfig["jobName"] = $cronjobName;
		
		if (is_subclass_of($jobClassName, "Scalr_System_Cronjob_MultiProcess_Worker")) {
			$jobConfig["worker"] = new $jobClassName;
			
			if ($jobConfig["distributed"]) {
				$jobClassName = "Scalr_System_Cronjob_Distributed";
			} else {
				$jobClassName = "Scalr_System_Cronjob_MultiProcess";
			}
		}

		
		$this->logger->info("Starting {$cronjobName} ...");
		$cronjob = new $jobClassName($jobConfig);		
		$cronjob->run(array(
			"getopt" => $getopt
		));		
	}
	
	private function computeConfig ($jobClassName) {
		$jobConfig = call_user_func(array($jobClassName, "getConfig"));
		
		if (is_subclass_of($jobClassName, "Scalr_System_Cronjob_MultiProcess_Worker")) {
			if ($jobConfig["distributed"]) {
				$inheritConfig = Scalr_System_Cronjob_Distributed::getConfig();
			} else {
				$inheritConfig = Scalr_System_Cronjob_MultiProcess::getConfig();
			}
		} else {
			$inheritConfig = Scalr_System_Cronjob::getConfig();	
		}
		
		return Scalr_Util_Arrays::mergeReplaceRecursive($inheritConfig, $jobConfig);
	}
}