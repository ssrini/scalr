<?php
	
/*
	require "manager.php";
	
	$worker = new Scalr_Service_Gearman_Worker();
	
	$worker->mainLoop();


	exit();
	
	*/
	
	require (dirname(__FILE__)."/../src/prepend.inc.php");
	
	//Init gearman
	$gmWorker= new GearmanWorker();
	$gmWorker->addServer();
	//$gmWorker->addServer("10.17.54.2", 4730);
	//$gmWorker->addServer("10.48.76.130", 4730);
	//$gmWorker->addServer("10.41.241.2", 4730);

	$gmWorker->addFunction("Scalr_Service_Gearman_Jobs_MessagesSender", "testJob");
	
	//$gmWorker->addFunction("Scalr_Service_Gearman_Jobs_MessagesSender", "sendMessage");
	
	
	
	while ($gmWorker->work());
	 
	function testJob($job)
	{
	  	sleep(5);
		var_dump($job->workload());
		return true;
	}
	
	function sendMessage($job)
	{
		
	}