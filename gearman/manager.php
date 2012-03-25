<?php
	declare(ticks = 1);

	require dirname(__FILE__).'/../src/prepend.inc.php';
	
	$getoptRules = array(
		'client-check' => "Check Gearman client process",
		'client-stop'  => "Stop gearman client process",
		'client-kill'  => "Kill gearman client process",
		//'client-reconfigure' => "Reconfigure gearman client process",
	
		'worker-check' => "Check Gearman worker process",
		'worker-stop'  => "Stop gearman worker process",
		'worker-kill'  => "Kill gearman worker process",
		//'worker-reconfigure' => "Reconfigure gearman worker process",
	);
	
	$getopt = new Zend_Console_Getopt($getoptRules, $_SERVER["argv"]);
	try {
		$getopt->parse();
		$options = $getopt->getOptions();
	} catch (Zend_Console_Getopt_Exception $e) {
		print "{$e->getMessage()}\n\n";
		die($getopt->getUsageMessage());
	}
	
	if (!$options[0] || $getopt->getOption("help")) {
		die($getopt->getUsageMessage());			
	}

	$servers = array(
		array('host' => '10.17.54.2', 'port' => 4730),
		array('host' => '10.48.76.130', 'port' => 4730),
		array('host' => '10.41.241.2', 'port' => 4730)
	);
	
	$manager = new Scalr_Service_Gearman_Manager($servers);
	switch ($options[0]) {
		case "client-check":
			$manager->checkProcess(Scalr_Service_Gearman_Manager::GEARMAN_CLIENT);
			break;
			
		case "client-stop":
			$manager->terminateProcess(Scalr_Service_Gearman_Manager::GEARMAN_CLIENT);
			break;
			
		case "client-kill":
			$manager->terminateProcess(Scalr_Service_Gearman_Manager::GEARMAN_CLIENT, true);
			break;
			
		case "worker-check":
			$manager->checkProcess(Scalr_Service_Gearman_Manager::GEARMAN_WORKER);
			break;
			
		case "worker-client-stop":
			$manager->terminateProcess(Scalr_Service_Gearman_Manager::GEARMAN_WORKER);
			break;
			
		case "worker-client-kill":
			$manager->terminateProcess(Scalr_Service_Gearman_Manager::GEARMAN_WORKER, true);
			break;
	}

	exit();