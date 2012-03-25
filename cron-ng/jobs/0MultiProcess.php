<?php

class Scalr_Cronjob_0MultiProcess extends Scalr_System_Cronjob_MultiProcess_DefaultWorker {
	
	private $logger;
	
	function __construct () {
		$this->logger = Logger::getLogger(__CLASS__);
	}
	 
	static function getConfig () {
		return Scalr_Util_Arrays::mergeReplaceRecursive(parent::getConfig(), array(
			"description" => "MultiProcess cronjob. Do Network, I/O and CPU jobs in a 5 workers pool",
			"processPool" => array(
				"size" => 5,
				//"startupTimeout" => 10000,
				"daemonize" => true,
				"preventParalleling" => true,
				"workerMemoryLimit" => 32000,
				"workerMemoryLimitTick" => 1000,
				"termTimeout" => 2000
			),
			//"memoryLimit" => 128000
			//"pendingWorkCoef" => 3,
			"fileName" => __FILE__ 
		));
	}

	/*
	function endForking () {
		throw new Exception("error in endForking");
	}
	*/
	
	/*
	function startChild () {
		$t = 0;
		for ($i=0; $i<10; $i++) {
			$this->logger->info("Do some useful things");
			Scalr_Util_Timeout::sleep(500);
			$t += 500;
			if ($t > 5000) {
				//throw new Exception("Something error in netware heheh");
				die();
			}
			
		}
		
		while (true) {
			$this->logger->info("Do some useful things");
			Scalr_Util_Timeout::sleep(500);
			$t += 500;
			if ($t > 5000) {
				//throw new Exception("Something error in netware heheh");
				die();
			}
		}
	}      
	*/ 
	    
	
	function enqueueWork ($workQueue) {
		foreach (range(1, 200) as $i) {
			$workQueue->put($i);
		}
	}
	
	function handleWork ($message) {
		if ($message % 3 == 0) {
			// Network
			$urls = array("http://google.com", "http://habrahabr.ru", "http://yahoo.com", "http://bing.com");
			$url = $urls[array_rand($urls, 1)];
			$this->logger->info("Fetching $url");
			$t1 = microtime(true);
			file_get_contents($url);
			$t2 = microtime(true);
			$this->logger->info(sprintf("Fetched %s. Time elapsed: %.2f", $url, $t2-$t1));
			
		} elseif ($message % 3 == 1) {
			// I/O
			$fsize = 1*1024*1024; // 1 MB
			$tmpname = tempnam("/tmp", "cronng");
			$this->logger->info(sprintf("Copy %d bytes from /dev/random to %s", $fsize, $tmpname));
			$t1 = microtime(true);		
			$rfp = fopen("/dev/zero", "r");
			$wfp = fopen($tmpname, "w+");
			while ($written < $fsize) {
				$buf = fread($rfp, 4096);
				fwrite($wfp, $buf);
				$written += 4096; 
			}
			$t2 = microtime(true);
			$this->logger->info(sprintf("Copied %d bytes from /dev/random to %s. Time elapsed: %.2f", $fsize, $tmpname, $t2-$t1));
			unlink($tmpname);
			
		} elseif ($message % 3 == 2) {
			// CPU
			// TODO:
		}
		
		//sleep(1);
		
		/*
		if ($message == 5) {
			// Allocate memory
			$fp = fopen("/dev/urandom", "r");
			$s = "";
			while (strlen($s) < 52428800) {
				$s .= fread($fp, 4096);
			}
			fclose($fp);
		}
		*/
//		$this->logger->info("[".posix_getpid()."] proceed " . $message);
		//Scalr_Util_Timeout::sleep(rand(950, 1050));		
	}
}
