<?php

abstract class Scalr_System_Cronjob_Distributed_Elector {
	
	protected $nodeName;
	
	protected $jobConfig;
	
	function __construct ($nodeName, $jobConfig) {
		$this->nodeName = $nodeName;
		$this->jobConfig = $jobConfig;
	}
	
	abstract function getElectionData ();
	
	abstract function determineLeaderNode ($votes);
}