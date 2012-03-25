<?php

class Scalr_System_Cronjob_Distributed_DataCenterElector 
		extends Scalr_System_Cronjob_Distributed_DefaultElector {

	private $logger;
	
	private $dataCenter;
	
	private $leaderDataCenter;
	
	function __construct ($nodeName, $jobConfig) {
		parent::__construct($nodeName, $jobConfig);
		$this->logger = Logger::getLogger(__CLASS__);
		
		if (!key_exists("datacenter", $jobConfig)) {
			throw new Scalr_System_Cronjob_Exception("Configuration array must have a key for 'datacenter'");
		}
		
		$dcConfig = $jobConfig["datacenter"];
		if (!$dcConfig["centers"]) {
			throw new Scalr_System_Cronjob_Exception("No datacenters defined in configuration");
		}
		if (!$dcConfig["leader"]) {
			throw new Scalr_System_Cronjob_Exception("Configuration array must have a key for 'datacenter'.'leader' "
					. "that names the leader datacenter");
		}
		
		
		$centers = array_map("trim", explode(",", $dcConfig["centers"]));
		$nodeCenterMap = array();
		foreach ($centers as $center) {
			$nodes = array_map("trim", explode(",", $dcConfig[$center]));
			foreach ($nodes as $node) {
				$nodeCenterMap[$node] = $center;
			} 
		}

		$this->dataCenter = key_exists($this->nodeName, $nodeCenterMap) ? 
				$nodeCenterMap[$this->nodeName] : $dcConfig["default"];
		$this->leaderDataCenter = $dcConfig["leader"];
	}
	
	function getElectionData () {
		// Find node datacenter
		return array(
			"node" => $this->nodeName,
			"dataCenter" => $this->dataCenter
		);
	}
	
	function determineLeaderNode ($votes) {
       	// Search for a node appointed as leader by administrator
		foreach ($votes as $vote) {
			if ($vote["node"] == $this->jobConfig["leader"]) {
				return $vote["node"];
			}
		}
		
		$this->logger->warn(sprintf("God's seal znode not found. "
				. "First znode from datacenter '%s' will be a leader", $this->leaderDataCenter));
				
		// Search for a node in a suitable datacenter
		foreach ($votes as $vote) {
			if ($vote["dataCenter"] == $this->leaderDataCenter) {
				return $vote["node"];
			}
		}
		
		$this->logger->warn(sprintf("Cannot find a running node in a datacenter '%s'", 
				$this->leaderDataCenter));
				
		return null;
	}
}