<?php

	class Scalr_Cronjob_ScalarizrMessaging extends Scalr_System_Cronjob_MultiProcess_DefaultWorker
    {
        static function getConfig () {
        	return array(
        		"description" => "Process ingoing Scalarizr messages",        	
        		"processPool" => array(
					"daemonize" => false,
        			"workerMemoryLimit" => 40000,   // 40Mb       	
        			"startupTimeout" => 10000, 		// 10 seconds
        			"size" => 3						// 3 workers
        		),
    			"waitPrevComplete" => true,
				"fileName" => __FILE__,
        		"getoptRules" => array(
        			'farm-id-s' => 'Affect only this farm'
        		)
        	);
        }
    	
        private $logger;
        
        private $db;
        
        private $serializer;
        
    	function __construct() {
        	$this->logger = Logger::getLogger(__CLASS__);
        	$this->serializer = new Scalr_Messaging_XmlSerializer();
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
        	$this->logger->info("Fetching servers...");
        	$farmid = $this->runOptions['getopt']->getOption('farm-id');
        	
        	if ($farmid) {
        		$rows = $this->db->GetAll("SELECT distinct(m.server_id) FROM messages m 
	        			INNER JOIN servers s ON m.server_id = s.server_id
		        		WHERE m.type = ? AND m.status = ? AND m.isszr = ? AND s.farm_id = ?",
        				array("in", MESSAGE_STATUS::PENDING, 1, $farmid));
        	} else {
	        	$rows = $this->db->GetAll("SELECT distinct(server_id) FROM messages 
	        			WHERE type = ? AND status = ? AND isszr = ?",
	        			array("in", MESSAGE_STATUS::PENDING, 1));
        	}
        	
        	$this->logger->info("Found ".count($rows)." servers");
        	foreach ($rows as $row) {
        		$workQueue->put($row["server_id"]);
        	}
        }
        
        function handleWork ($serverId)
        {
            try {
            	$dbserver = DBServer::LoadByID($serverId);
            	
            	if ($dbserver->farmId) {
	            	if ($dbserver->GetFarmObject()->Status == FARM_STATUS::TERMINATED)
	            		throw new ServerNotFoundException("");
            	}
            		
            } catch (Exception $e) {
            	$this->db->Execute("DELETE FROM messages WHERE server_id=? AND `type`='in'", array($serverId));
            	return;
            }
            
            $rs = $this->db->Execute("SELECT * FROM messages 
            		WHERE server_id = ? AND type = ? AND status = ? 
            		ORDER BY id ASC", 
            		array($serverId, "in", MESSAGE_STATUS::PENDING));
            
       		while ($row = $rs->FetchRow()) {
       			try {
       				$message = $this->serializer->unserialize($row["message"]);
       				$event = null;
       				
       				// Update scalarizr package version
					if ($message->meta[Scalr_Messaging_MsgMeta::SZR_VERSION]) {
						$dbserver->SetProperty(SERVER_PROPERTIES::SZR_VESION, 
								$message->meta[Scalr_Messaging_MsgMeta::SZR_VERSION]);
					}
       				
       				try {
       					if ($message instanceof Scalr_Messaging_Msg_OperationResult) {
       						if ($message->status == 'error') {
       							if ($message->name == 'Initialization')	{
       								$dbserver->SetProperty(SERVER_PROPERTIES::SZR_IS_INIT_FAILED, 1);
       								
       								if ($message->error) {
										$msg = $message->error->message;
										$trace = $message->error->trace;
										$handler = $message->error->handler;
									}
       								
       								$this->db->Execute("INSERT INTO server_operation_progress SET 
										`operation_id` = ?,
										`timestamp` = ?,
										`phase` = ?,
										`step` = ?,
										`status` = ?,
										`message`= ?,
										`trace` = ?,
										`handler` = ?,
										`progress` = ?,
										`stepno` = ? 
										ON DUPLICATE KEY UPDATE status = ?, progress = ?, trace = ?, handler = ?, message = ?
									", array(
										$message->id,
										$message->getTimestamp(),
										$message->phase,
										$message->step,
										$message->status,
										$msg,
										$trace,
										$handler,
										$message->progress,
										$message->stepno,
										//
										$message->status,
										$message->progress,
										$trace,
										$handler,
										$msg
									));
       							}
       						}
       					} elseif ($message instanceof Scalr_Messaging_Msg_Win_HostDown) {
       						$status = PlatformFactory::NewPlatform($dbserver->platform)->GetServerRealStatus($dbserver);
       						if ($status->isRunning()) {
       							$event = new RebootBeginEvent($dbserver);
       						} else {
       							$event = new HostDownEvent($dbserver);
       						}
       						
       					} elseif ($message instanceof  Scalr_Messaging_Msg_Win_PrepareBundleResult) {
       						
       						try {
       							$bundleTask = BundleTask::LoadById($message->bundleTaskId);
       						} catch (Exception $e) {}
       						
       						if ($bundleTask) {
	       						if ($message->status == 'ok') {
	       							$metaData = array(
	       								'szr_version' => $message->meta[Scalr_Messaging_MsgMeta::SZR_VERSION], 
	       								'os' => $message->os, 
	       								'software' => $message->software, 
	       							);
	       							
	       							$bundleTask->setMetaData($metaData);
	       							$bundleTask->Save();
	       							
	       							PlatformFactory::NewPlatform($bundleTask->platform)->CreateServerSnapshot($bundleTask);
	       						} else {
	       							$bundleTask->SnapshotCreationFailed("PrepareBundle procedure failed: {$message->lastError}");	
	       						}
       						}
       						
       					} elseif ($message instanceof Scalr_Messaging_Msg_DeployResult) {
       						
       						try {
       							$deploymentTask = Scalr_Model::init(Scalr_Model::DM_DEPLOYMENT_TASK)->loadById($message->deployTaskId);
       						} catch (Exception $e) {}
       						
       						if ($deploymentTask) {
	       						if ($message->status == 'error') {
	       							$deploymentTask->status = Scalr_Dm_DeploymentTask::STATUS_FAILED;
	       							$deploymentTask->lastError = $message->lastError;
	       						} else {
	       							$deploymentTask->status = Scalr_Dm_DeploymentTask::STATUS_DEPLOYED;
	       							$deploymentTask->dtDeployed = date("Y-m-d H:i:s");
	       						}
	       						
	       						$deploymentTask->save();
       						}
       					
       					} elseif ($message instanceof Scalr_Messaging_Msg_Hello) {
	       					$event = $this->onHello($message, $dbserver);
	       					
	       				}
	       				
	       				/********* MONGODB *********/
	       				elseif ($message instanceof Scalr_Messaging_Msg_MongoDb) {
	       					
	       					try {
	       						$dbFarmRole = $dbserver->GetFarmRoleObject();
	       					} catch (Exception $e) {}
	       					
	       					if ($dbFarmRole instanceof DBFarmRole) {
	       						foreach (Scalr_Role_Behavior::getListForFarmRole($dbFarmRole) as $behavior)
									$behavior->handleMessage($message, $dbserver);
	       					}
	       				}
	       				/**************************/
	       				
	       				/********* DBMSR *********/
	       				elseif ($message instanceof Scalr_Messaging_Msg_DbMsr) {
	       					try {
	       						$dbFarmRole = $dbserver->GetFarmRoleObject();
	       					} catch (Exception $e) {}
	       					
	       					if ($dbFarmRole instanceof DBFarmRole) {
	       						foreach (Scalr_Role_Behavior::getListForFarmRole($dbFarmRole) as $behavior)
									$behavior->handleMessage($message, $dbserver);
	       					}
	       				}
	       				/**************************/
	       				
	       				elseif ($message instanceof Scalr_Messaging_Msg_HostInit) {
							$event = $this->onHostInit($message, $dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_HostUp) {
	       					$event = $this->onHostUp($message, $dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_HostDown) {
	       					$event = new HostDownEvent($dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_RebootStart) {
	       					$event = new RebootBeginEvent($dbserver);
	       				
	       				} elseif ($message instanceof Scalr_Messaging_Msg_RebootFinish) {
	       					$event = new RebootCompleteEvent($dbserver);

	       				} elseif ($message instanceof Scalr_Messaging_Msg_BeforeHostUp) {
	       					$event = new BeforeHostUpEvent($dbserver);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_BlockDeviceAttached) {
	       					if ($dbserver->platform == SERVER_PLATFORMS::EC2) {								
								$ec2Client = Scalr_Service_Cloud_Aws::newEc2(
									$dbserver->GetProperty(EC2_SERVER_PROPERTIES::REGION),
									$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
									$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
								);
	
			    				$instanceId = $dbserver->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
			    				$volumes = $ec2Client->DescribeVolumes()->volumeSet->item;
			    				if (!is_array($volumes)) {
			    					$volumes = array($volumes);
			    				}
			    				foreach ($volumes as $volume) {
			    					if ($volume->status == AMAZON_EBS_STATE::IN_USE && 
			    						$volume->attachmentSet->item->instanceId == $instanceId && 
			    						$volume->attachmentSet->item->device == $message->deviceName) {
			    						$message->volumeId = $volume->volumeId;
			    					}
			    				}
	       					}
	       					
							$event = new EBSVolumeAttachedEvent(
								$dbserver,
								$message->deviceName,
								$message->volumeId
							);
		
	       				} elseif ($message instanceof Scalr_Messaging_Msg_BlockDeviceMounted) {

							// Single volume
							$ebsinfo = $this->db->GetRow("SELECT * FROM ec2_ebs WHERE volume_id=?", array($message->volumeId));
							if ($ebsinfo)
								$this->db->Execute("UPDATE ec2_ebs SET mount_status=?, isfsexist='1' WHERE id=?", array(EC2_EBS_MOUNT_STATUS::MOUNTED, $ebsinfo['id']));

	       					$event = new EBSVolumeMountedEvent(
	       						$dbserver, 
	       						$message->mountpoint, 
	       						$message->volumeId, 
	       						$message->deviceName
	       					);
	       					
	       				} elseif ($message instanceof Scalr_Messaging_Msg_RebundleResult) {
	       					if ($message->status == Scalr_Messaging_Msg_RebundleResult::STATUS_OK) {
	       						
	       						$metaData = array(
       								'szr_version' => $message->meta[Scalr_Messaging_MsgMeta::SZR_VERSION], 
       								'os' => $message->os, 
       								'software' => $message->software, 
       							);
	       						
	       						if ($dbserver->platform == SERVER_PLATFORMS::EC2) {
		       						if ($message->aws) {
		       							if ($message->aws->root-device-type == 'ebs')
		       								$tags[] = ROLE_TAGS::EC2_EBS;
		       								
		       							if ($message->aws->virtualization-type == 'hvm')
		       								$tags[] = ROLE_TAGS::EC2_HVM;
		       						}
		       						else {
		       							$ec2Client = Scalr_Service_Cloud_Aws::newEc2(
											$dbserver->GetProperty(EC2_SERVER_PROPERTIES::REGION),
											$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
											$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
										);
										try {
			       							$DescribeImagesType = new DescribeImagesType(null, array(), null);
			       							$DescribeImagesType->imagesSet = new stdClass();
											$DescribeImagesType->imagesSet->item = array();
											$DescribeImagesType->imagesSet->item[] = array('imageId' => $dbserver->GetProperty(EC2_SERVER_PROPERTIES::AMIID));
											
											$info = $ec2Client->DescribeImages($DescribeImagesType);
											
											if ($info->imagesSet->item->rootDeviceType == 'ebs')
												$tags[] = ROLE_TAGS::EC2_EBS;
											else {
												try {
													$bundleTask = BundleTask::LoadById($message->bundleTaskId);
													if ($bundleTask->bundleType == SERVER_SNAPSHOT_CREATION_TYPE::EC2_EBS)
														$tags[] = ROLE_TAGS::EC2_EBS;
												} catch (Exception $e) {}
											}
											
											if ($info->imagesSet->item->virtualizationType == 'hvm')
												$tags[] = ROLE_TAGS::EC2_HVM;
												
										} catch (Exception $e) {
											$metaData['tagsError'] = $e->getMessage();
										}
		       						}
	       						} elseif ($dbserver->platform == SERVER_PLATFORMS::NIMBULA) {
	       							$metaData['init_root_user'] = $message->sshUser;
	       							$metaData['init_root_pass'] = $message->sshPassword;
	       						}
	       						
	       						$metaData['tags'] = $tags;
	       						
	       						$event = new RebundleCompleteEvent(
	       							$dbserver, 
	       							$message->snapshotId, 
	       							$message->bundleTaskId,
									$metaData
	       						);
	       					} else if ($message->status == Scalr_Messaging_Msg_RebundleResult::STATUS_FAILED) {
	       						$event = new RebundleFailedEvent($dbserver, $message->bundleTaskId, $message->lastError);
	       					}
	       					
	       				}
	       				
	       				
	       				/*******/
	       				elseif ($message instanceof Scalr_Messaging_Msg_Mysql_CreateDataBundleResult) {
	       					if ($message->status == "ok") {
	       						$event = new MysqlBackupCompleteEvent($dbserver, MYSQL_BACKUP_TYPE::BUNDLE, array(
	       							'snapshotConfig'	=> $message->snapshotConfig,
	       							'logFile'			=> $message->logFile,
	       							'logPos'			=> $message->logPos,
	       							'dataBundleSize'	=> $message->dataBundleSize,
	       						
	       							/* @deprecated */
	       							'snapshotId'		=> $message->snapshotId
	       						));
	       					} else {
	       						$event = new MysqlBackupFailEvent($dbserver, MYSQL_BACKUP_TYPE::BUNDLE);
	       						$event->lastError = $message->lastError;
	       					}
	       				} elseif ($message instanceof Scalr_Messaging_Msg_Mysql_CreateBackupResult) {
	       					if ($message->status == "ok") {
	       						$event = new MysqlBackupCompleteEvent($dbserver, MYSQL_BACKUP_TYPE::DUMP);
	       					} else {
	       						$event = new MysqlBackupFailEvent($dbserver, MYSQL_BACKUP_TYPE::DUMP);
	       						$event->lastError = $message->lastError;
	       					}
	       				} elseif ($message instanceof Scalr_Messaging_Msg_Mysql_PromoteToMasterResult) {
	       					$event = $this->onMysql_PromoteToMasterResult($message, $dbserver);
	       				} elseif ($message instanceof Scalr_Messaging_Msg_Mysql_CreatePmaUserResult) {
       						$farmRole = DBFarmRole::LoadByID($message->farmRoleId);
       						if ($message->status == "ok") {
	       						$farmRole->SetSetting(DbFarmRole::SETTING_MYSQL_PMA_USER, $message->pmaUser);
	       						$farmRole->SetSetting(DbFarmRole::SETTING_MYSQL_PMA_PASS, $message->pmaPassword);
       						} else {
       							$farmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME, "");
       							$farmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR, $message->lastError);
       						}
	       				} 
	       				/*******/
	       				elseif ($message instanceof Scalr_Messaging_Msg_RabbitMq_SetupControlPanelResult) {
       						$farmRole = $dbserver->GetFarmRoleObject();
       						if ($message->status == "ok") {
	       						$farmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_SERVER_ID, $dbserver->serverId);
	       						$farmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_URL, $message->cpanelUrl);
	       						$farmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUEST_TIME, "");
       						} else {
       							$farmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_SERVER_ID, "");
       							$farmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUEST_TIME, "");
       							$farmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_ERROR_MSG, $message->lastError);
       						}
	       				} 
	       				
	       				elseif ($message instanceof Scalr_Messaging_Msg_AmiScriptsMigrationResult) {
	       					
	       					try {
		       					//Open security group:
		       					if ($dbserver->platform == SERVER_PLATFORMS::EC2)
		       					{
		       						$info = PlatformFactory::NewPlatform($dbserver->platform)->GetServerExtendedInformation($dbserver);
		       						$sg = explode(", ", $info['Security groups']);
		       						
		       						foreach ($sg as $sgroup) {
		       							if ($sgroup != 'default') {
		       								
		       								$ipPermissionSet = new IpPermissionSetType();
						
									    	$group_rules = array(
												array('rule' => 'tcp:8013:8013:0.0.0.0/0'), // For Scalarizr
												array('rule' => 'udp:8014:8014:0.0.0.0/0'), // For Scalarizr
											); 
											
											foreach ($group_rules as $rule) {
								            	$group_rule = explode(":", $rule["rule"]);
								                $ipPermissionSet->AddItem($group_rule[0], $group_rule[1], $group_rule[2], null, array($group_rule[3]));
								            }
								            
											$ec2Client = Scalr_Service_Cloud_Aws::newEc2(
												$dbserver->GetProperty(EC2_SERVER_PROPERTIES::REGION),
												$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
												$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
											);
								            
								            // Create security group
								            $ec2Client->AuthorizeSecurityGroupIngress(
								            	$dbserver->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID), 
								            	$sgroup, 
								            	$ipPermissionSet
								            );
		       								
		       								break;
		       							}
		       						}
		       					}
	       					} catch (Exception $e) {
	       						$this->logger->fatal($e->getMessage());
	       					}
	       					
	       					$dbserver->SetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT, 8014);
	       					$dbserver->SetProperty(SERVER_PROPERTIES::SZR_VESION, "0.7.171");
	       					
	       					if ($message->mysql) {
	       						$event = $this->onHostUp($message, $dbserver, true);
	       					}
	       					
	       				}
	       				
	       				$handle_status = MESSAGE_STATUS::HANDLED;
       				} catch (Exception $e) {
       					$handle_status = MESSAGE_STATUS::FAILED;
       					
       					$this->logger->error(sprintf("Cannot handle message '%s' (message_id: %s) "
       							. "from server '%s' (server_id: %s). %s", 
       							$message->getName(), $message->messageId, 
       							$dbserver->remoteIp ? $dbserver->remoteIp : '*no-ip*', 
       							$dbserver->serverId, 
       							$e->getMessage()."({$e->getFile()}:{$e->getLine()})")
       					);
       				}
       				
       				$this->db->Execute("UPDATE messages SET status = ? WHERE messageid = ?",
       						array($handle_status, $message->messageId));
       				
       				if ($event) {
       					Scalr::FireEvent($dbserver->farmId, $event);
       				}
       				
       			} catch (Exception $e) {
       				$this->logger->error($e->getMessage(), $e);
       			}
       		}
        }
        
        private function onHello($message, DBServer $dbserver) {
        	if ($dbserver->status == SERVER_STATUS::TEMPORARY) {
        	
        		$bundleTask = BundleTask::LoadById($dbserver->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_BUNDLE_TASK_ID));
        		$bundleTask->status = SERVER_SNAPSHOT_CREATION_STATUS::PENDING;
        		
        		$bundleTask->Log("Received Hello message from scalarizr on server. Creating image");
        		
        		$bundleTask->save();
        		
        	}
       		if ($dbserver->status == SERVER_STATUS::IMPORTING) {
       			
       			switch ($dbserver->platform) {
       				case SERVER_PLATFORMS::EC2:
       					$dbserver->SetProperties(array(
		       				EC2_SERVER_PROPERTIES::AMIID => $message->awsAmiId,
		       				EC2_SERVER_PROPERTIES::INSTANCE_ID => $message->awsInstanceId,
		       				EC2_SERVER_PROPERTIES::INSTANCE_TYPE => $message->awsInstanceType,
		       				EC2_SERVER_PROPERTIES::AVAIL_ZONE => $message->awsAvailZone,
		       				EC2_SERVER_PROPERTIES::REGION => substr($message->awsAvailZone, 0, -1),
		       				SERVER_PROPERTIES::ARCHITECTURE => $message->architecture
		       			));
       					break;
       				case SERVER_PLATFORMS::EUCALYPTUS:
       					$dbserver->SetProperties(array(
	       					EUCA_SERVER_PROPERTIES::EMIID => $message->awsAmiId,
		       				EUCA_SERVER_PROPERTIES::INSTANCE_ID => $message->awsInstanceId,
		       				EUCA_SERVER_PROPERTIES::INSTANCE_TYPE => $message->awsInstanceType,
		       				EUCA_SERVER_PROPERTIES::AVAIL_ZONE => $message->awsAvailZone,
		       				SERVER_PROPERTIES::ARCHITECTURE => $message->architecture
	       				));
       					break;
       				case SERVER_PLATFORMS::NIMBULA:
       					$dbserver->SetProperties(array(
	       					NIMBULA_SERVER_PROPERTIES::NAME => $message->serverName,
		       				SERVER_PROPERTIES::ARCHITECTURE => $message->architecture
	       				));
       					break;
       				case SERVER_PLATFORMS::CLOUDSTACK:
       					$dbserver->SetProperties(array(
	       					CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID => $message->cloudstack->instanceId,
		       				CLOUDSTACK_SERVER_PROPERTIES::CLOUD_LOCATION => $message->cloudstack->availZone,
		       				SERVER_PROPERTIES::ARCHITECTURE => $message->architecture,
	       				));
       					break;
       				case SERVER_PLATFORMS::RACKSPACE:
       					$env = $dbserver->GetEnvironmentObject();
				       	$cs = Scalr_Service_Cloud_Rackspace::newRackspaceCS(
				       		$env->getPlatformConfigValue(Modules_Platforms_Rackspace::USERNAME, true, $dbserver->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER)),
				       		$env->getPlatformConfigValue(Modules_Platforms_Rackspace::API_KEY, true, $dbserver->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER)),
				       		$dbserver->GetProperty(RACKSPACE_SERVER_PROPERTIES::DATACENTER)
				       	);
				       	
				       	$csServer = null;
				       	$list = $cs->listServers(true);
				       	if ($list) {
					       	foreach ($list->servers as $_tmp) {
					       		if ($_tmp->addresses->public && 
					       				in_array($message->remoteIp, $_tmp->addresses->public)) {
					       			$csServer = $_tmp;
					       		}
					       	}
				       		
				       	}
				       	if (!$csServer) {
				       		$this->logger->error(sprintf("Server not found on CloudServers (server_id: %s, remote_ip: %s, local_ip: %s)",
				       				$dbserver->serverId, $message->remoteIp, $message->localIp));
				       		return;
				       	}
	       				
	       				
	       				$dbserver->SetProperties(array(
	       					RACKSPACE_SERVER_PROPERTIES::SERVER_ID => $csServer->id,
	       					RACKSPACE_SERVER_PROPERTIES::NAME => $csServer->name,
	       					RACKSPACE_SERVER_PROPERTIES::IMAGE_ID => $csServer->imageId,
	       					RACKSPACE_SERVER_PROPERTIES::FLAVOR_ID => $csServer->flavorId,
	       					RACKSPACE_SERVER_PROPERTIES::HOST_ID => $csServer->hostId,
	       					SERVER_PROPERTIES::ARCHITECTURE => $message->architecture,
	       				));
       					break;
       				case SERVER_PLATFORMS::OPENSTACK:
       					$env = $dbserver->GetEnvironmentObject();
				       	$os = Scalr_Service_Cloud_Openstack::newNovaCC(
				       		$env->getPlatformConfigValue(Modules_Platforms_Openstack::API_URL, true, $dbserver->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION)),
							$env->getPlatformConfigValue(Modules_Platforms_Openstack::USERNAME, true, $dbserver->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION)),
							$env->getPlatformConfigValue(Modules_Platforms_Openstack::API_KEY, true, $dbserver->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION)),
							$env->getPlatformConfigValue(Modules_Platforms_Openstack::PROJECT_NAME, true, $dbserver->GetProperty(OPENSTACK_SERVER_PROPERTIES::CLOUD_LOCATION))
				       	);
				       	
				       	$csServer = null;
				       	$list = $os->serversList();
				       	if ($list) {
					       	foreach ($list->servers as $_tmp) {
					       		if ($_tmp->addresses->public) {
						       		$ipaddresses = array();
						       		foreach ($_tmp->addresses->public as $addr)
						       			if ($addr->version == 4)
						       				array_push($ipaddresses, $addr->addr);
						       		
						       		if (in_array($message->remoteIp, $ipaddresses))
						       			$osServer = $_tmp;
					       		}
					       	}
				       		
				       	}
				       	if (!$osServer) {
				       		$this->logger->error(sprintf("Server not found on Openstack (server_id: %s, remote_ip: %s, local_ip: %s)",
				       				$dbserver->serverId, $message->remoteIp, $message->localIp));
				       		return;
				       	}
	       				
	       				
	       				$dbserver->SetProperties(array(
	       					OPENSTACK_SERVER_PROPERTIES::SERVER_ID => $osServer->id,
	       					OPENSTACK_SERVER_PROPERTIES::NAME => $osServer->name,
	       					OPENSTACK_SERVER_PROPERTIES::IMAGE_ID => $osServer->image->id,
	       					OPENSTACK_SERVER_PROPERTIES::FLAVOR_ID => $osServer->flavor->id,
	       					OPENSTACK_SERVER_PROPERTIES::HOST_ID => $osServer->hostId,
	       					SERVER_PROPERTIES::ARCHITECTURE => $message->architecture,
	       				));
       					break;
       			}
    			
       			// Bundle image
       			$creInfo = new ServerSnapshotCreateInfo(
       				$dbserver, 
       				$dbserver->GetProperty(SERVER_PROPERTIES::SZR_IMPORTING_ROLE_NAME),
       				SERVER_REPLACEMENT_TYPE::NO_REPLACE
       			);
       			$bundleTask = BundleTask::Create($creInfo);
       		}
        }
        
        private function onHostInit($message, $dbserver) {
       		if ($dbserver->status == SERVER_STATUS::PENDING) {
       			// Update server crypto key
       			$srv_props = array();	
       			if ($message->cryptoKey) {
       				$srv_props[SERVER_PROPERTIES::SZR_KEY] = trim($message->cryptoKey);
       				$srv_props[SERVER_PROPERTIES::SZR_KEY_TYPE] = SZR_KEY_TYPE::PERMANENT;
       			}

				if ($dbserver->platform != SERVER_PLATFORMS::CLOUDSTACK) {
       				$srv_props[SERVER_PROPERTIES::SZR_SNMP_PORT] = $message->snmpPort;
       				$remoteIp = $message->remoteIp;
				} else {
					if ($dbserver->farmRoleId) {
						$dbFarmRole = $dbserver->GetFarmRoleObject();
						$networkType = $dbFarmRole->GetSetting(DBFarmRole::SETTING_CLOUDSTACK_NETWORK_TYPE);
						if ($networkType == 'Direct') {
							$remoteIp = $message->localIp;
							$srv_props[SERVER_PROPERTIES::SZR_SNMP_PORT] = $message->snmpPort;
						}
						else {
							$env = $dbserver->GetEnvironmentObject();
							$remoteIp = $env->getPlatformConfigValue(Modules_Platforms_Cloudstack::SHARED_IP . "." . $dbserver->GetProperty(CLOUDSTACK_SERVER_PROPERTIES::CLOUD_LOCATION), false);		
						} 
					}
					else {
						$remoteIp = $message->localIp;
						$srv_props[SERVER_PROPERTIES::SZR_SNMP_PORT] = $message->snmpPort;
					}
				}
				
       			
				
       			// MySQL specific
       			$dbFarmRole = $dbserver->GetFarmRoleObject();
       			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
                    $master = $dbFarmRole->GetFarmObject()->GetMySQLInstances(true);
                    // If no masters in role this server becomes it
                    if (!$master[0] && 
                    	!(int)$dbFarmRole->GetSetting(DbFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER)) {
                    	$srv_props[SERVER_PROPERTIES::DB_MYSQL_MASTER] = 1;
                    }
       			}
       			
       			//MSR Replication Master
       			//TODO: MySQL
       			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL) || $dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS)) {
       				$servers = $dbFarmRole->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
       				if (!$dbFarmRole->GetSetting(Scalr_Db_Msr::SLAVE_TO_MASTER)) {
       					$masterFound = false;
       					foreach ($servers as $server) {
       						if ($server->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER))
       							$masterFound = true;
       					}
       					
       					if (!$masterFound)
       						$srv_props[Scalr_Db_Msr::REPLICATION_MASTER] = 1;
       				} elseif ($dbFarmRole->GetSetting(Scalr_Db_Msr::SLAVE_TO_MASTER) && count($servers) == 0) {
       					$dbFarmRole->SetSetting(Scalr_Db_Msr::SLAVE_TO_MASTER, 0);
       					$srv_props[Scalr_Db_Msr::REPLICATION_MASTER] = 1;
       				}
       			}
       			
       			$dbserver->SetProperties($srv_props);
       			
				return new HostInitEvent(
					$dbserver, 
					$message->localIp,
					$remoteIp,
					$message->sshPubKey
				);
       			
       		} else {
       			$this->logger->error("Strange situation. Received HostInit message"
       					. " from server '{$dbserver->serverId}' ({$message->remoteIp})"
       					. " with state {$dbserver->status}!");
       		}        
        }
        
        /**
         * @param Scalr_Messaging_Msg $message
         * @param DBServer $dbserver
         */
	    private function onHostUp ($message, $dbserver, $skipStatusCheck = false) {
       		if ($dbserver->status == SERVER_STATUS::INIT || $skipStatusCheck) {
       			$event = new HostUpEvent($dbserver, "");
       			
       			$dbFarmRole = $dbserver->GetFarmRoleObject();
 
       			foreach (Scalr_Role_Behavior::getListForFarmRole($dbFarmRole) as $behavior)
					$behavior->handleMessage($message, $dbserver);
       			
					
       			//TODO: Move MySQL to MSR
       			/****** MOVE TO MSR ******/
       			//TODO: Legacy MySQL code
       			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
       				if (!$message->mysql) {
       					$this->logger->error(sprintf(
       							"Strange situation. HostUp message from MySQL behavior doesn't contains `mysql` property. Server %s (%s)", 
       							$dbserver->serverId, $dbserver->remoteIp));
       					return;
       				}
				
       				$mysqlData = $message->mysql;	
       				
                    if ($dbserver->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER)) {
                   		if ($mysqlData->rootPassword) {
	       					$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_REPL_PASSWORD, $mysqlData->replPassword);                    		
		       				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD, $mysqlData->rootPassword);
		       				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_STAT_PASSWORD, $mysqlData->statPassword);
                   		}

                   		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LOG_FILE, $mysqlData->logFile);
                   		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LOG_POS, $mysqlData->logPos);
                   		
                   		if ($dbserver->IsSupported("0.7"))
                   		{
                   			//$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID, $mysqlData->snapshotConfig);
                   			//$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID, $mysqlData->volumeConfig);
                   			
                   			if ($mysqlData->volumeConfig)
                   			{
                   				try {
									$storageVolume = Scalr_Storage_Volume::init();
									try {
										$storageVolume->loadById($mysqlData->volumeConfig->id);
										$storageVolume->setConfig($mysqlData->volumeConfig);
										$storageVolume->save();
									} catch (Exception $e) {
										if (strpos($e->getMessage(), 'not found')) {
											$storageVolume->loadBy(array(
												'id'			=> $mysqlData->volumeConfig->id,
												'client_id'		=> $dbserver->clientId,
												'env_id'		=> $dbserver->envId,
												'name'			=> "MySQL data volume",
												'type'			=> $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE),
												'platform'		=> $dbserver->platform,
												'size'			=> $mysqlData->volumeConfig->size,
												'fstype'		=> $mysqlData->volumeConfig->fstype,
												'purpose'		=> ROLE_BEHAVIORS::MYSQL,
												'farm_roleid'	=> $dbserver->farmRoleId,
												'server_index'	=> $dbserver->index
											));
											$storageVolume->setConfig($mysqlData->volumeConfig);
											$storageVolume->save(true);
										} 
										else
											throw $e;
									}
									
									$dbFarmRole->SetSetting(
										DBFarmRole::SETTING_MYSQL_SCALR_VOLUME_ID, 
										$storageVolume->id
									);
								}
								catch(Exception $e) {
									$this->logger->error(new FarmLogMessage($event->DBServer->farmId, "Cannot save storage volume: {$e->getMessage()}"));
								}
                   			}
                   			
                   			if ($mysqlData->snapshotConfig)
                   			{
                   				try {
                   					$storageSnapshot = Scalr_Storage_Snapshot::init();
                   					try {
										$storageSnapshot->loadById($mysqlData->snapshotConfig->id);
										$storageSnapshot->setConfig($mysqlData->snapshotConfig);
										$storageSnapshot->save();
                   					} catch (Exception $e) {
                   						if (strpos($e->getMessage(), 'not found')) {
	                   						$storageSnapshot->loadBy(array(
												'id'			=> $mysqlData->snapshotConfig->id,
												'client_id'		=> $dbserver->clientId,
	                   							'farm_id'		=> $dbserver->farmId,
												'farm_roleid'	=> $dbserver->farmRoleId,
												'env_id'		=> $dbserver->envId,
												'name'			=> sprintf(_("MySQL data bundle #%s"), $mysqlData->snapshotConfig->id),
												'type'			=> $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE),
												'platform'		=> $dbserver->platform,
												'description'	=> sprintf(_("MySQL data bundle created on Farm '%s' -> Role '%s'"), 
													$dbFarmRole->GetFarmObject()->Name, 
													$dbFarmRole->GetRoleObject()->name
												),
												'ismysql'		=> true,
												'service'		=> ROLE_BEHAVIORS::MYSQL
											));
											
											$storageSnapshot->setConfig($mysqlData->snapshotConfig);
											
											$storageSnapshot->save(true);
                   						} 
                   						else
											throw $e;	
									}
									
									$dbFarmRole->SetSetting(
										DBFarmRole::SETTING_MYSQL_SCALR_SNAPSHOT_ID, 
										$storageSnapshot->id
									);
								}
								catch(Exception $e) {
									$this->logger->error(new FarmLogMessage($event->DBServer->farmId, "Cannot save storage snapshot: {$e->getMessage()}"));
								} 
                   			}
                   		}
                   		else
                   		{
                   			/**
                   		 	* @deprecated
                   		 	*/
	       					$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID, $mysqlData->snapshotId);	
                   		}
                   		
                    }
       			}
       			return $event;
       		} else {
       			$this->logger->error("Strange situation. Received HostUp message"
       					. " from server '{$dbserver->serverId}' ('{$message->remoteIp})"
       					. " with state {$dbserver->status}!");
       		}
	    }
	    
        /**
         * @param Scalr_Messaging_Msg_Mysql_PromoteToMasterResult $message
         * @param DBServer $dbserver
         */	    
	    private function onMysql_PromoteToMasterResult ($message, DBServer $dbserver) {
    		$dbserver->GetFarmRoleObject()->SetSetting(DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER, 0);
	    	if ($message->status == Scalr_Messaging_Msg_Mysql_PromoteToMasterResult::STATUS_OK) {
		    	$dbFarm = $dbserver->GetFarmObject();
		    	$dbFarmRole = $dbserver->GetFarmRoleObject();
				$oldMaster = $dbFarm->GetMySQLInstances(true);

				if ($dbserver->IsSupported("0.7")) {

					if ($message->volumeConfig) {
						try {					
	
							$storageVolume = Scalr_Storage_Volume::init();
							try {
								$storageVolume->loadById($message->volumeConfig->id);
								$storageVolume->setConfig($message->volumeConfig);
								$storageVolume->save();
							} catch (Exception $e) {
								if (strpos($e->getMessage(), 'not found')) {
									$storageVolume->loadBy(array(
										'id'			=> $message->volumeConfig->id,
										'client_id'		=> $dbserver->clientId,
										'env_id'		=> $dbserver->envId,
										'name'			=> "MySQL data volume",
										'type'			=> $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE),
										'platform'		=> $dbserver->platform,
										'size'			=> $message->volumeConfig->size,
										'fstype'		=> $message->volumeConfig->fstype,
										'purpose'		=> ROLE_BEHAVIORS::MYSQL,
										'farm_roleid'   => $dbserver->farmRoleId,
										'server_index'	=> $dbserver->index
									));
									$storageVolume->setConfig($message->volumeConfig);
									$storageVolume->save(true);
								} else {
									throw $e;
								}
							}
						}
						catch(Exception $e) {
							$this->logger->error(new FarmLogMessage($dbserver->farmId, "Cannot save storage volume: {$e->getMessage()}"));
						} 
					}
	
	
					if ($message->snapshotConfig) {
						try {					
							$snapshot = Scalr_Model::init(Scalr_Model::STORAGE_SNAPSHOT);
							$snapshot->loadBy(array(
								'id'			=> $message->snapshotConfig->id,
								'client_id'		=> $dbserver->clientId,
								'env_id'		=> $dbserver->envId,
								'name'			=> "Automatical MySQL data bundle",
								'type'			=> $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE),
								'platform'		=> $dbserver->platform,
								'description'	=> "MySQL data bundle created automatically by Scalr",
								'ismysql'		=> true
							));
							$snapshot->setConfig($message->snapshotConfig);
							$snapshot->save(true);
														
							
							$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_SCALR_SNAPSHOT_ID, $snapshot->id);
	                   		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LOG_FILE, $message->logFile);
	                   		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_LOG_POS, $message->logPos);
	                   		
	                   		
						}
						catch(Exception $e) {
							$this->logger->error(new FarmLogMessage($dbserver->farmId, "Cannot save storage snapshot: {$e->getMessage()}"));
						} 
					}
					
				} else {
					// TODO: delete old slave volume if new one was created
		    		$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID, $message->volumeId);
				}
				
				return new NewMysqlMasterUpEvent($dbserver, "", $oldMaster[0]);	    	
	    		
	    	} elseif ($message->status == Scalr_Messaging_Msg_Mysql_PromoteToMasterResult::STATUS_FAILED) {
	    		
	    		$dbserver->SetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER, 0);
	    		$dbserver->SetProperty(Scalr_Db_Msr::REPLICATION_MASTER, 0);
	    		
	    		// XXX: Need to do smth
	    		$this->logger->error(sprintf("Promote to Master failed for server %s. Last error: %s", 
	    				$dbserver->serverId, $message->lastError));
	    	}
 	
	    }
    }
    
