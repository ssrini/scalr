<?php
	
	class ScalrAPI_2_3_0 extends ScalrAPI_2_2_0
	{
		public function ServerGetExtendedInformation($ServerID)
		{
			$DBServer = DBServer::LoadByID($ServerID);
			if ($DBServer->envId != $this->Environment->id)
				throw new Exception(sprintf("Server ID #%s not found", $ServerID));
				
			$response = $this->CreateInitialResponse();
			
			$info = PlatformFactory::NewPlatform($DBServer->platform)->GetServerExtendedInformation($DBServer);
			
			$response->ServerInfo = new stdClass();
			$scalrProps = array(
				'ServerID' => $DBServer->serverId,
				'Platform' => $DBServer->platform,
				'RemoteIP' => ($DBServer->remoteIp) ? $DBServer->remoteIp : '' ,
				'LocalIP' => ($DBServer->localIp) ? $DBServer->localIp : '' ,
				'Status' => $DBServer->status,
				'Index' => $DBServer->index,
				'AddedAt' => $DBServer->dateAdded
			);
			foreach ($scalrProps as $k=>$v) {
				$response->ServerInfo->{$k} = $v;
			}
			
	
			$response->PlatformProperties = new stdClass();
			if (is_array($info) && count($info)) {
				foreach ($info as $name => $value) {
					$name = str_replace(".", "_", $name);
					$name = preg_replace("/[^A-Za-z0-9_-]+/", "", $name);
					
					if ($name == 'MonitoringCloudWatch')
						continue;
					
					$response->PlatformProperties->{$name} = $value;
				}
			}

			$response->ScalrProperties = new stdClass();
			if (count($DBServer->GetAllProperties())) {
				$it = array();
				foreach ($DBServer->GetAllProperties() as $name => $value) {
					$name = preg_replace("/[^A-Za-z0-9-]+/", "", $name);
					$response->ScalrProperties->{$name} = $value;
				}
			}
			
			return $response;
		}
		
		public function DmSourcesList()
		{
			$response = $this->CreateInitialResponse();
			$response->SourceSet = new stdClass();
			$response->SourceSet->Item = array();
			
			$rows = $this->DB->Execute("SELECT * FROM dm_sources WHERE env_id=?", array($this->Environment->id));
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->{"ID"} = $row['id'];
				$itm->{"Type"} = $row['type'];
				$itm->{"URL"} = $row['url'];
				$itm->{"AuthType"} = $row['auth_type'];
				
				$response->SourceSet->Item[] = $itm; 	
		    }
		    
		    return $response;
		}
		
		public function DmSourceCreate($Type, $URL, $AuthLogin=null, $AuthPassword=null)
		{
	
			$source = Scalr_Model::init(Scalr_Model::DM_SOURCE);
			
			$authInfo = new stdClass();
			if ($Type == Scalr_Dm_Source::TYPE_SVN)
			{
				$authInfo->login = $AuthLogin;
				$authInfo->password	= $AuthPassword;
				$authType = Scalr_Dm_Source::AUTHTYPE_PASSWORD;
			}
			
			if (Scalr_Dm_Source::getIdByUrlAndAuth($URL, $authInfo))
				throw new Exception("Source already exists in database");
		
			$source->envId = $this->Environment->id;
			
			$source->url = $URL;
			$source->type = $Type;
			$source->authType = $authType;
			$source->setAuthInfo($authInfo);
		
			$source->save();
		
			$response = $this->CreateInitialResponse();
			$response->SourceID = $source->id;
			
			return $response;
		}
		
		public function DmApplicationCreate($Name, $SourceID, $PreDeployScript=null, $PostDeployScript=null)
		{
			$application = Scalr_Model::init(Scalr_Model::DM_APPLICATION);
			$application->envId = $this->Environment->id;
			
			if (Scalr_Dm_Application::getIdByNameAndSource($Name, $SourceID))
				throw new Exception("Application already exists in database");
			
			$application->name = $Name;
			$application->sourceId = $SourceID;
			
			$application->setPreDeployScript($PreDeployScript);
			$application->setPostDeployScript($PostDeployScript);
			
			$application->save();
			
			$response = $this->CreateInitialResponse();
			$response->ApplicationID = $application->id;
			
			return $response;
		}
		
		public function DmApplicationsList()
		{
			$response = $this->CreateInitialResponse();
			$response->ApplicationSet = new stdClass();
			$response->ApplicationSet->Item = array();
			
			$rows = $this->DB->Execute("SELECT * FROM dm_applications WHERE env_id=?", array($this->Environment->id));
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->{"ID"} = $row['id'];
				$itm->{"SourceID"} = $row['dm_source_id'];
				$itm->{"Name"} = $row['name'];
				//$itm->{"PreDeployScript"} = $row['pre_deploy_script'];
				//$itm->{"PostDeployScript"} = $row['post_deploy_script'];
				
				$response->ApplicationSet->Item[] = $itm; 	
		    }
		    
		    return $response;
		}
		
		public function DmDeploymentTasksList($FarmRoleID = null, $ApplicationID = null, $ServerID = null)
		{
			$sql = "SELECT id FROM dm_deployment_tasks WHERE status !='".Scalr_Dm_DeploymentTask::STATUS_ARCHIVED."' AND env_id = '{$this->Environment->id}'";
			if ($FarmRoleID)
				$sql .= ' AND farm_role_id = ' . $this->DB->qstr($FarmRoleID);
				
			if ($ApplicationID)
				$sql .= ' AND dm_application_id = ' . $this->DB->qstr($ApplicationID);
				
			if ($ServerID)
				$sql .= ' AND server_id = ' . $this->DB->qstr($ServerID);
				
			$response = $this->CreateInitialResponse();
			$response->DeploymentTasksSet = new stdClass();
			$response->DeploymentTasksSet->Item = array();
				
			$rows = $this->DB->Execute($sql);
			while ($task = $rows->FetchRow()) {
				$deploymentTask = Scalr_Model::init(Scalr_Model::DM_DEPLOYMENT_TASK)->loadById($task['id']);
				
				$itm = new stdClass();
				$itm->ServerID = $deploymentTask->serverId;
				$itm->DeploymentTaskID = $deploymentTask->id;
				$itm->FarmRoleID = $deploymentTask->farmRoleId;
				$itm->RemotePath = $deploymentTask->remotePath;
				$itm->Status = $deploymentTask->status;
				
				$response->DeploymentTasksSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		public function DmDeploymentTaskGetLog($DeploymentTaskID, $StartFrom = 0, $RecordsLimit = 20)
		{
			$deploymentTask = Scalr_Model::init(Scalr_Model::DM_DEPLOYMENT_TASK)->loadById($DeploymentTaskID);
			if ($deploymentTask->envId != $this->Environment->id)
				throw new Exception(sprintf("Deployment task #%s not found", $DeploymentTaskID));
				
			$response = $this->CreateInitialResponse();
			
			$sql = "SELECT * FROM dm_deployment_task_logs WHERE dm_deployment_task_id = " . $this->DB->qstr($DeploymentTaskID);
			
			$total = $this->DB->GetOne(preg_replace('/\*/', 'COUNT(*)', $sql, 1));
			
			$sql .= " ORDER BY id DESC";
			
			$start = $StartFrom ? (int) $StartFrom : 0;
			$limit = $RecordsLimit ? (int) $RecordsLimit : 20;
			$sql .= " LIMIT {$start}, {$limit}";
			
			$response = $this->CreateInitialResponse();
			$response->TotalRecords = $total;
			$response->StartFrom = $start;
			$response->RecordsLimit = $limit;
			$response->LogSet = new stdClass();
			$response->LogSet->Item = array();
			
			$rows = $this->DB->Execute($sql);
			while ($row = $rows->FetchRow())
			{
				$itm = new stdClass();
				$itm->Message = $row['message'];
				$itm->Timestamp = strtotime($row['dtadded']);
				
				$response->LogSet->Item[] = $itm;
			}
			
			return $response;
		}
		
		public function DmDeploymentTaskGetStatus($DeploymentTaskID) 
		{
			$deploymentTask = Scalr_Model::init(Scalr_Model::DM_DEPLOYMENT_TASK)->loadById($DeploymentTaskID);
			if ($deploymentTask->envId != $this->Environment->id)
				throw new Exception(sprintf("Deployment task #%s not found", $DeploymentTaskID));
				
			$response = $this->CreateInitialResponse();	
            $response->DeploymentTaskStatus = $deploymentTask->status;
            if ($deploymentTask->status == Scalr_Dm_DeploymentTask::STATUS_FAILED)
            	$response->FailureReason = $deploymentTask->lastError;
            
            return $response;
		}
		
		public function DmApplicationDeploy($ApplicationID, $FarmRoleID, $RemotePath)
		{	
			$application = Scalr_Model::init(Scalr_Model::DM_APPLICATION)->loadById($ApplicationID);
			if ($application->envId != $this->Environment->id)
				throw new Exception("Aplication not found in database");
			
			$dbFarmRole = DBFarmRole::LoadByID($FarmRoleID);
			if ($dbFarmRole->GetFarmObject()->EnvID != $this->Environment->id)
				throw new Exception("Farm Role not found in database");
			
			$servers = $dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));
			
			if (count($servers) == 0)
				throw new Exception("There is no running servers on selected farm/role");
				
			$response = $this->CreateInitialResponse();
			$response->DeploymentTasksSet = new stdClass();
			$response->DeploymentTasksSet->Item = array();
				
			foreach ($servers as $dbServer) {
				$taskId = Scalr_Dm_DeploymentTask::getId($ApplicationID, $dbServer->serverId, $RemotePath);
				$deploymentTask = Scalr_Model::init(Scalr_Model::DM_DEPLOYMENT_TASK);
				
				if (!$taskId) {
					
					try {
						if (!$dbServer->IsSupported("0.7.38"))
							throw new Exception("Scalr agent installed on this server doesn't support deployments. Please update it to the latest version");
						
						$deploymentTask->create(
							$FarmRoleID,
							$ApplicationID,
							$dbServer->serverId,
							Scalr_Dm_DeploymentTask::TYPE_API,
							$RemotePath,
							$this->Environment->id 
						);
					} catch (Exception $e) {
						$itm = new stdClass();
						$itm->ServerID = $dbServer->serverId;
						$itm->ErrorMessage = $e->getMessage();
						
						$response->DeploymentTasksSet->Item[] = $itm;
						
						continue;
					}
				} else {
					$deploymentTask->loadById($taskId);
					$deploymentTask->status = Scalr_Dm_DeploymentTask::STATUS_PENDING;
					$deploymentTask->log("Re-deploying application. Status: pending");
					$deploymentTask->save();
				}
				
				$itm = new stdClass();
				$itm->ServerID = $dbServer->serverId;
				$itm->DeploymentTaskID = $deploymentTask->id;
				$itm->FarmRoleID = $deploymentTask->farmRoleId;
				$itm->RemotePath = $deploymentTask->remotePath;
				$itm->Status = $deploymentTask->status;
				
				$response->DeploymentTasksSet->Item[] = $itm;
			}
			
			return $response;
		}
	}
?>