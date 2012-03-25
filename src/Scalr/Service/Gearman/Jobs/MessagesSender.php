<?php
	class Scalr_Service_Gearman_Jobs_MessagesSender
	{
		public static function getTasksList()
		{
			$db = Core::GetDBInstance(null, true);
			
			$rows = $db->GetAll("SELECT id FROM messages WHERE `type`='out' AND status=? AND UNIX_TIMESTAMP(dtlasthandleattempt)+handle_attempts*120 < UNIX_TIMESTAMP(NOW()) ORDER BY id DESC LIMIT 0,3000", 
		    	array(MESSAGE_STATUS::PENDING)
			);
			return $rows;
		}
		
		public static function doJob($job)
		{
			$db = Core::GetDBInstance(null, true);
		
			$messageSerializer = new Scalr_Messaging_XmlSerializer();
			
			$message = $db->GetRow("SELECT server_id, message, id, handle_attempts FROM messages WHERE id=?", array($job->workload()));            
	        try {
				if ($message['handle_attempts'] >= 3) {
					$db->Execute("UPDATE messages SET status=? WHERE id=?", array(MESSAGE_STATUS::FAILED, $message['id']));
				}
				else {
					try {
						$DBServer = DBServer::LoadByID($message['server_id']);
					}
					catch (Exception $e) {
						$db->Execute("UPDATE messages SET status=? WHERE id=?", array(MESSAGE_STATUS::FAILED, $message['id']));
						return;	
					}
					
					if ($DBServer->status == SERVER_STATUS::RUNNING || 
						$DBServer->status == SERVER_STATUS::INIT || 
						$DBServer->status == SERVER_STATUS::IMPORTING || 
						$DBServer->status == SERVER_STATUS::TEMPORARY ||
						$DBServer->status == SERVER_STATUS::PENDING_TERMINATE)
					{						
						// Only 0.2-68 or greater version support this feature.
						if ($DBServer->IsSupported("0.2-68")) {					
							$msg = $messageSerializer->unserialize($message['message']);
							$DBServer->SendMessage($msg);
						}
						else {
							$db->Execute("UPDATE messages SET status=? WHERE id=?", array(MESSAGE_STATUS::UNSUPPORTED, $message['id']));						}
					}
					elseif (in_array($DBServer->status, array(SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_TERMINATE))) {
						$db->Execute("UPDATE messages SET status=? WHERE id=?", array(MESSAGE_STATUS::FAILED, $message['id']));
					}
				}
			}
			catch(Exception $e) {
				//var_dump($e->getMessage());
			}
		}
	}