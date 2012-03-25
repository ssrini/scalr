<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$msg = new Scalr_Messaging_Msg_ScalarizrUpdateAvailable();
	$servers = $db->Execute("SELECT * FROM servers WHERE status IN (?,?) AND farm_id='2607'", array(SERVER_STATUS::RUNNING, SERVER_STATUS::INIT));
	while ($server = $servers->FetchRow())
	{		
		try
		{
			printf("Sending message to server %s (server_id: %s) ", $server["remote_ip"], $server["server_id"]);
			$DBServer = DBServer::LoadByID($server["server_id"]);
			$DBServer->SendMessage($msg);
		}
		catch(Exception $e)
		{
			print "Failed\n";
			continue;
		}
		
		/*
		try
		{
			$DBServer = DBServer::LoadByID($server['server_id']);
			
			if (!$DBServer->IsSupported("0.5"))
				continue;
			
			$db->Execute("INSERT INTO farm_role_scripts SET
				scriptid	= ?,
				farmid		= ?,
				farm_roleid	= ?,
				params		= ?,
				event_name	= ?,
				target		= ?,
				version		= ?,
				timeout		= ?,
				issync		= ?,
				ismenuitem	= ?
			", array(
				'2102',
				$server['farm_id'],
				$server['farm_roleid'],
				serialize(array()),
				'CustomEvent-'.date("YmdHi").'-'.rand(1000,9999),
				'instance',
				'1',
				'600',
				'1',
				'0'
			));
			
			$farmScriptId = $db->Insert_ID();
			
			printf("Sending message to server %s (server_id: %s) ", $server["remote_ip"], $server["server_id"]);
			
			$message = new Scalr_Messaging_Msg_ExecScript($eventName);
			$message->meta[Scalr_Messaging_MsgMeta::EVENT_ID] = "FRSID-{$farmScriptId}";
			$DBServer->SendMessage($message);
		}
		catch(Exception $e)
		{
			print "Failed\n";
			continue;
		}
		*/
		
		print "Success\n";
		flush();
	}
?>