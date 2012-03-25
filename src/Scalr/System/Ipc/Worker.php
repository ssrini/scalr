<?php

interface Scalr_System_Ipc_Worker {
	
	/**
	 * Called in parent
	 * @param Scalr_Util_Queue $workQueue 
	 * @return Scalr_Util_Queue
	 */
	function startForking ($workQueue);
	
	/**
	 * Called in parent
	 * @param $pid
	 * @return unknown_type
	 */
	function childForked ($pid);

	/**
	 * Called in parent
	 * @return 
	 */
	function endForking ();

	/**
	 * Called in child
	 */
	function startChild ();

	/**
	 * Called in child
	 */
	function handleWork ($message);

	/**
	 * Called in child
	 */
	function endChild ();
	
	/**
	 * Called in child when SIGTERM received
	 */
	function terminate ();
}