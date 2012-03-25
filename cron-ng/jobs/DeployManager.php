<?php

	class Scalr_Cronjob_DeployManager extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
    {
    	static function getConfig () {
    		return array(
    			"description" => "Deploy manager",
    			"processPool" => array(
					"daemonize" => false,
    				"workerMemoryLimit" => 40000,   		
    				"size" => 12,
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
        	// Reconfigure observers;
        	Scalr::ReconfigureObservers();
        }        
        
        function enqueueWork ($workQueue) {
            $this->logger->info("Fetching active farms...");
            
            $rows = $this->db->GetAll("SELECT id FROM dm_deployment_tasks WHERE status IN ('pending','deploying')");
            $this->logger->info("Found ".count($rows)." deployment tasks");            
            
            foreach ($rows as $row) {
            	$workQueue->put($row["id"]);
            }
        }
                
        function handleWork ($deploymentTaskId) {
        	
        	try {
        		$deploymentTask = Scalr_Model::init(Scalr_Model::DM_DEPLOYMENT_TASK)->loadById($deploymentTaskId);
        		
        		try {
        			$dbServer = DBServer::LoadByID($deploymentTask->serverId);
        		} catch (Exception $e) {
        			$deploymentTask->status = Scalr_Dm_DeploymentTask::STATUS_ARCHIVED;
        			return;
        		}
        		
        		switch ($deploymentTask->status) {
        			case Scalr_Dm_DeploymentTask::STATUS_PENDING:
        				
        				$deploymentTask->deploy();
        				
        				break;
        				
        			case Scalr_Dm_DeploymentTask::STATUS_DEPLOYING:
        				
        				//TODO:
        				
        				break;
        		}
        		
        	}
        	catch(Exception $e) {
        		var_dump($e->getMessage());
        	}
        	
        }
    }
