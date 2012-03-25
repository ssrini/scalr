<?php

class Scalr_System_Cronjob_MultiProcess_DefaultWorker implements 
		Scalr_System_Cronjob_MultiProcess_Worker {

	static function getConfig () {
		return Scalr_System_Cronjob_MultiProcess::getConfig();
	}
	
	/**
	 * 
	 * @param Scalr_Util_Queue $workQueue
	 * @return void
	 */
	function enqueueWork ($workQueue) {
	}	
	
	/**
	 * 
	 * @param string $message
	 * @return void
	 */
	function handleWork ($message) {
	}
	
	/**
	 * Called in parent
	 * @param Scalr_Util_Queue $workQueue
	 * @return Scalr_Util_Queue
	 */
	function startForking ($workQueue) {
	}
	
	/**
	 * Called in parent
	 * @param $pid
	 * @return unknown_type
	 */
	function childForked ($pid) {
	}

	/**
	 * Called in parent
	 * @return 
	 */
	function endForking () {
	
	}

	/**
	 * Called in child
	 */
	function startChild () {
	}
	
	/**
	 * Called in child
	 */
	function endChild () {
	}
	
	/**
	 * Called in child when SIGTERM received
	 */
	function terminate () {
	}
}