<?php

interface Scalr_System_Cronjob_MultiProcess_Worker extends Scalr_System_Ipc_Worker {

	/**
	 * @return array
	 * @key string [description] required*
	 * @key array [getoptRules] (optional) @see Zend_Console_GetOpt rules syntax
	 * @key array [processPool] required* @see Scalr_System_Ipc_ProcessPool configuration options
	 * @key int [memoryLimit] (optional) Memory limit for cronjob process group. 
	 * If limit exceeded cronjob will be restarted. Works only with [processPool][daemonize] = true
	 * @key int [memoryLimitTick] (optional) Tick time for process group memory limit check   
	 * @key string [fileName] (optional) Cronjob filename. Control process will check each second 
	 * for local file modifications and restart cronjob if file was changed.  
	 * @key bool [waitPrevComplete] Wait for previous tasks completion
	 * @key array [distributed] (optional) 
	 * @key string [iniFile] required with distributed* Path or URL to distributed cronjob configuration
	 * @key string [electorCls] (optional with distributed) Leader elector class name @see Scalr_System_Cronjob_Distributed_Elector
	 * @key int [leaderTimeout] required with distributed* Must be moreeq 2 cronjob execution intervals
	 */
	static function getConfig ();
	
	/**
	 * 
	 * @param Scalr_Util_Queue $workQueue
	 * @return void
	 */
	function enqueueWork ($workQueue);	
}