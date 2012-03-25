<?php
	class Scalr_Cronjob_MessagingQueue extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
    {
    	static function getConfig () {
    		return array(
    			"description" => "Message queue processeor",
    			"processPool" => array(
					"daemonize" => false,
    				"workerMemoryLimit" => 40000,   		
    				"size" => 20,
    				"startupTimeout" => 10000 // 10 seconds
    			),
    			"waitPrevComplete" => true,
    			"fileName" => __FILE__,
    			"memoryLimit" => 500000
    		);
    	}
    	
        private $logger;
        private $db;
        
        public function __construct() {
        	$this->logger = Logger::getLogger(__CLASS__);
        	
        	$this->timeLogger = Logger::getLogger('time');
        	
        	$this->db = Core::GetDBInstance();
        }
        
        function startForking ($workQueue) {
        	// Reopen DB connection after daemonizing
        	$this->db = Core::GetDBInstance(null, true);
        }
        
        function startChild () {
        	// Reopen DB connection in child
        	$this->db = Core::GetDBInstance(null, true);
        	$this->messageSerializer = new Scalr_Messaging_XmlSerializer();
        }        
        
        function enqueueWork ($workQueue) {
            $this->logger->info("Fetching pending messages...");
            
            $rows = $this->db->GetAll("SELECT id FROM messages 
            	WHERE `type`='out' AND status=? AND UNIX_TIMESTAMP(dtlasthandleattempt)+handle_attempts*120 < UNIX_TIMESTAMP(NOW()) ORDER BY id DESC LIMIT 0,3000", 
            	array(MESSAGE_STATUS::PENDING)
            );
            	
            $this->logger->info("Found ".count($rows)." pending messages");            
            
            foreach ($rows as $row) {
            	$workQueue->put($row["id"]);
            }
        }
                
        function handleWork ($msgId) {
        	
        	$message = $this->db->GetRow("SELECT server_id, message, id, handle_attempts FROM messages WHERE id=?", array($msgId));
            
        	try {
				if ($message['handle_attempts'] >= 3) {
					$this->db->Execute("UPDATE messages SET status=? WHERE id=?", array(MESSAGE_STATUS::FAILED, $message['id']));
				}
				else {
					
					try {
						$DBServer = DBServer::LoadByID($message['server_id']);
					}
					catch (Exception $e) {
						$this->db->Execute("UPDATE messages SET status=? WHERE id=?", array(MESSAGE_STATUS::FAILED, $message['id']));
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
							$msg = $this->messageSerializer->unserialize($message['message']);
							$DBServer->SendMessage($msg);
						}
						else {
							$this->db->Execute("UPDATE messages SET status=? WHERE id=?", array(MESSAGE_STATUS::UNSUPPORTED, $message['id']));
						}
					}
					elseif (in_array($DBServer->status, array(SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_TERMINATE))) {
						$this->db->Execute("UPDATE messages SET status=? WHERE id=?", array(MESSAGE_STATUS::FAILED, $message['id']));
					}
				}
			}
			catch(Exception $e) {
				//var_dump($e->getMessage());
			}    	
        }
    }
