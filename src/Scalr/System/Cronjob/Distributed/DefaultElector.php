<?php

class Scalr_System_Cronjob_Distributed_DefaultElector 
		extends Scalr_System_Cronjob_Distributed_Elector {
		
	private $logger;
	
	function __construct ($nodeName, $jobConfig) {
		parent::__construct($nodeName, $jobConfig);
		if (!$jobConfig["leader"]) {
			throw new Scalr_System_Cronjob_Exception("Configuration array must have a key for 'leader' "
				. "that names the leader node apointed by administrator");
		}
		$this->logger = Logger::getLogger(__CLASS__);
	}
	
	function getElectionData () {
		return array("node" => $this->nodeName);	
	}
	
	function determineLeaderNode ($votes) {
       	// Search for a node appointed as leader by administrator
		foreach ($votes as $vote) {
			if ($vote["node"] == $this->jobConfig["leader"]) {
				return $vote["node"];
			}
		}
		
		$this->logger->warn("God's seal znode not found. "
				. "First znode in the election sequence will be a leader");
				
		return $votes[0]["node"];
	}
}