<?php
	
	require "manager.php";

	$client = new Scalr_Service_Gearman_Client();
	$client->mainLoop();

	exit();
	require (dirname(__FILE__)."/../src/prepend.inc.php");
	
	//Init gearman
	$gmClient= new GearmanClient();
	$gmClient->addServer("10.17.54.2", 4730);
	$gmClient->addServer("10.48.76.130", 4730);
	$gmClient->addServer("10.41.241.2", 4730);
	
	while (true) {
		
		// Send messages...
		$rows = $db->GetAll("SELECT id FROM messages WHERE `type`='out' AND status=? AND UNIX_TIMESTAMP(dtlasthandleattempt)+handle_attempts*120 < UNIX_TIMESTAMP(NOW()) ORDER BY id DESC LIMIT 0,3000", 
	    	array(MESSAGE_STATUS::PENDING)
		);	
		foreach ($rows as $row) {
			$gmClient->doBackground("sendMessage", $row['id'], $row['id']);
		}
		sleep(5);
	}