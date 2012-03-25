<?php

	require(dirname(__FILE__)."/../../src/prepend.inc.php");  
    @session_destroy();

    $logger = Logger::getLogger("Messaging");
	$logger->info("Messaging server received request");
    
    try
    {
	    $service = new Scalr_Messaging_Service();
	    $service->addQueueHandler(new Scalr_Messaging_Service_ControlQueueHandler());
	    $service->addQueueHandler(new Scalr_Messaging_Service_LogQueueHandler());
	    //list($http_code, $status_text) = $Service->Handle("control", $payload);
	    list($http_code, $status_text) = $service->handle($_REQUEST["queue"], @file_get_contents("php://input"));
	    $logger->info("Respond with {$http_code} {$status_text}");
	    header("HTTP/1.0 {$http_code} {$status_text}");
    }
    catch(Exception $e)
    {
    	$logger->error("Respond with 500 {$e->getMessage()}");
    	header("HTTP/1.0 500 {$e->getMessage()}");
    }
    	
    exit();