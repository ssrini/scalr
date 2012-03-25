<?php

class Scalr_Cronjob_0Distributed extends Scalr_System_Cronjob_MultiProcess_DefaultWorker {
	
	static function getConfig () {
		return array(
			"description" => "Me is a zookeeper-ed distribut-ed",
			"processPool" => array(
				"daemonize" => true
			),
			"distributed" => true,
			"iniFile" => dirname(dirname(__FILE__)) . "/distributed.ini",
			"electorCls" => "Scalr_System_Cronjob_Distributed_DataCenterElector",
			"fileName" => __FILE__,
			"memoryLimit" => 128000
		);
	}
	
	/**
	 * @var Logger
	 */
	private $logger;
	
	function __construct () {
		$this->logger = LoggerManager::getLogger(__CLASS__);
	}           
	
	function enqueueWork ($workQueue) {
		foreach (range(0, 5) as $j) {
			foreach (range(1, 2) as $i) {
				$workQueue->put($i);
			}
		}
		
		//foreach (range(1, 15) as $farmId) {
		//	$workQueue->put($farmId);
		//}
	}
	
	function handleWork ($farmId) {
		$this->logger->error("Proceed farm '{$farmId}'");
		Scalr_Util_Timeout::sleep(1000*rand(0, 1));
	}
}