<? 
	require("src/prepend.inc.php"); 
	$display['load_extjs'] = true;
	
	try {
		$DBServer = DBServer::LoadByID($req_server_id);
		if (!Scalr_Session::getInstance()->getAuthToken()->hasAccessEnvironment($DBServer->envId))
			UI::Redirect("/#/servers/view");
	}
	catch(Exception $e) {
		UI::Redirect("/#/servers/view");
	}
	
    $AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
		$DBServer->GetProperty(EC2_SERVER_PROPERTIES::REGION),
		$DBServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
		$DBServer->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
	);

	$MonitorInstanceType = new MonitorInstancesType();
    $MonitorInstanceType->AddInstance($DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID));
	
    if ($req_action == "Disable")
    {	
    	$res = $AmazonEC2Client->UnmonitorInstances($MonitorInstanceType);    	
    	$okmsg = "Disabling Cloudwatch monitoring... It could take a few minutes.";	
    }
    elseif ($req_action == "Enable")
    {
    	$AmazonEC2Client->MonitorInstances($MonitorInstanceType);
    	$okmsg = "Enabling Cloudwatch monitoring... It could take a few minutes.";
    }
	
    UI::Redirect("/#/servers/{$DBServer->serverId}/extendedInfo");
    
	require("src/append.inc.php"); 
?>