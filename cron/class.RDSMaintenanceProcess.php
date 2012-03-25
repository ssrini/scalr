<?
	class RDSMaintenanceProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "RDS Maintenance (RDS Auto snapshots)";
        public $Logger;
                
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = Logger::getLogger(__CLASS__);
        }
                
        // Auto - snapshoting 
        public function OnStartForking()
        {         
       	 	$db = Core::GetDBInstance(null, true);
			
        	// selects rows where the snapshot's time has come to create new snapshot.
			$resultset = $db->Execute("SELECT * FROM autosnap_settings 
						WHERE (UNIX_TIMESTAMP(DATE_ADD(dtlastsnapshot, INTERVAL period HOUR)) < UNIX_TIMESTAMP(NOW())
						OR dtlastsnapshot IS NULL)
						AND objectid 	!= '0' 
						AND object_type = ?",
				array(AUTOSNAPSHOT_TYPE::RDSSnap)
			);
					
			while ($snapshotsSettings = $resultset->FetchRow())
			{		
				try
				{ 	
					$environment = Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadById($snapshotsSettings['env_id']);
					$AmazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
						$environment->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
						$environment->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
						$snapshotsSettings['region']
					); 
					
					// Check instance. If instance wasn't found then delete current recrod from settings table				 	
					try
					{					
						$AmazonRDSClient->DescribeDBInstances($snapshotsSettings['objectid']);	
					}
					catch(Exception $e)
					{
						
						if(stristr($e->getMessage(), "not found") || stristr($e->getMessage(), "not a valid") || stristr($e->getMessage(), "security token included in the request is invalid"))				
							$db->Execute("DELETE FROM autosnap_settings WHERE id = ?", 
								array($snapshotsSettings['id'])
							);
							
						$this->Logger->error(sprintf(
								_("RDS instance %s was not found. Auto-snapshot settings for this 
								instance will be deleted. %s."),												
							$snapshotsSettings['objectid'],
							$e->getMessage()												
							));									
						throw $e;
					}
					
					// snapshot random unique name
					$snapshotId = "scalr-auto-".dechex(microtime(true)*10000).rand(0,9); 										
					
					try {
						// Create new snapshot
						$AmazonRDSClient->CreateDBSnapshot($snapshotId,$snapshotsSettings['objectid']);					
						
						// update last snapshot creation date in settings
						$db->Execute("UPDATE autosnap_settings SET last_snapshotid=?, dtlastsnapshot=NOW() WHERE id=?", 
							array($snapshotId, $snapshotsSettings['id']
						));
						
						// create new snapshot record in DB
						$db->Execute("INSERT INTO rds_snaps_info SET 
							snapid		= ?,
							comment		= ?,
							dtcreated	= NOW(),
							region		= ?,
							autosnapshotid = ?", 
						array( 
							$snapshotId,
						 	_("Auto snapshot"),
			 				$snapshotsSettings['region'],
			 				$snapshotsSettings['id'])
			 			);
					}
					catch(Exception $e) 
					{
						$this->Logger->warn(sprintf(
							_("Cannot create RDS snapshot: %s."),												
							$e->getMessage()												
						));
					}
					
					// Remove old snapshots
					if ($snapshotsSettings['rotate'] != 0)
					{  
						$oldSnapshots = $db->GetAll("SELECT * FROM rds_snaps_info WHERE autosnapshotid = ? ORDER BY id ASC",
							array($snapshotsSettings['id'])
						);
						
						if (count($oldSnapshots) > $snapshotsSettings['rotate'])
						{							
							while (count($oldSnapshots) > $snapshotsSettings['rotate'])
							{   
								// takes the oldest snapshot ...
								$deletingSnapshot = array_shift($oldSnapshots);
								
								try
								{  	// and deletes it from amazon and from DB																																		
									$AmazonRDSClient->DeleteDBSnapshot($deletingSnapshot['snapid']);																					
									$db->Execute("DELETE FROM rds_snaps_info WHERE id = ?", 
										array($deletingSnapshot['id']
									));																
								}
								catch(Exception $e)
								{							
									if(stristr($e->getMessage(), "not found") || 
										stristr($e->getMessage(), "not a valid"))
									{									
										$db->Execute("DELETE FROM rds_snaps_info WHERE id = ?",
											array($deletingSnapshot['id'])
										);
									}
										
									$this->Logger->error(sprintf(
											_("DBsnapshot %s for RDS instance %s was not found 
												and cannot be deleted . %s."),
										$deletingSnapshot['snapid'],
										$snapshotsSettings['objectid'],
										$e->getMessage()
									));					
								}								
							}							
						}
					}
				}
				catch(Exception $e)
				{ 		
					$this->Logger->warn(sprintf(
						_("Cannot create snapshot for RDS Instance %s. %s"),
						$snapshotsSettings['objectid'],
						$e->getMessage()
					));					
				}
			}
           
        }
        
        public function OnEndForking()
        {
			
        }
        
        public function StartThread($queue_name)
        {        	
        	
        }
    }
?>