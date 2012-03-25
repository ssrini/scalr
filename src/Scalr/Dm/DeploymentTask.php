<?php

	class Scalr_Dm_DeploymentTask extends Scalr_Model
	{
		protected $dbTableName = 'dm_deployment_tasks';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "Deployment task #%s not found in database";
		
		protected $dbPropertyMap = array(
			'id'			=> 'id',
			'env_id'		=> array('property' => 'envId', 'is_filter' => true),
			'farm_role_id'	=> array('property' => 'farmRoleId', 'is_filter' => true),
			'dm_application_id'	=> array('property' => 'applicationId', 'is_filter' => true),
			'remote_path'	=> array('property' => 'remotePath', 'is_filter' => true),
			'status'		=> array('property' => 'status', 'is_filter' => true),
			'server_id'		=> array('property' => 'serverId', 'is_filter' => true),
			'last_error'	=> array('property' => 'lastError', 'is_filter' => true),
			'type'			=> array('property' => 'type', 'is_filter' => true),
			'dtdeployed'	=> array('property' => 'dtDeployed'),
			'dtadded'		=> array('property' => 'dtAdded', 'createSql' => 'NOW()', 'type' => 'datetime', 'update' => false)
		);
		
		const TYPE_MANUAL = 'manual';
		const TYPE_API	  = 'api';
		const TYPE_AUTO	  = 'auto';
		
		const STATUS_PENDING   = 'pending';
		const STATUS_DEPLOYING = 'deploying';
		const STATUS_DEPLOYED  = 'deployed';
		const STATUS_UPDATING  = 'updating';
		const STATUS_FAILED    = 'failed';
		const STATUS_ARCHIVED  = 'archived';
		
		public
			$id,
			$envId,
			$farmRoleId,
			$applicationId,
			$remotePath,
			$serverId,
			$dtAdded,
			$lastError,
			$type,
			$dtDeployed,
			$status;
			
		private $application;
			
		public function getApplication()
		{
			if (!$this->application)
				$this->application = Scalr_Model::init(Scalr_Model::DM_APPLICATION)->loadById($this->applicationId);
				
			return $this->application;
		}
			
		public function log($message)
		{
			if ($this->id)
			{
				try
				{
					$this->db->Execute("INSERT INTO dm_deployment_task_logs SET 
						`dm_deployment_task_id` = ?,
						`dtadded` = NOW(),
						`message` = ?
					", array(
						$this->id,
						$message
					));
				}
				catch(ADODB_Exception $e){}
			}
		}
		
		public static function getId($applicationId, $serverId, $remotePath) 
		{
			return Core::GetDBInstance()->GetOne("SELECT id FROM dm_deployment_tasks WHERE server_id=? AND dm_application_id=? AND remote_path=?", array(
				$serverId, $applicationId, $remotePath
			));
		}
		
		public function create($farmRoleId, $applicationId, $serverId, $type, $remotePath, $envId = null, $status = Scalr_Dm_DeploymentTask::STATUS_PENDING)
		{
			$this->farmRoleId = $farmRoleId;
			$this->applicationId = $applicationId;
			$this->serverId = $serverId;
			$this->type = $type;
			$this->remotePath = $remotePath;
			
			if (!$envId)
				$this->envId = Scalr_Session::getInstance()->getEnvironmentId();
			else
				$this->envId = $envId;
			
			$this->log("Deployment task created. Status: pending");
			
			$this->status = $status;
			
			$this->save();
		}
		
		public function save()
		{
			if (!$this->id)
			{
				$forceInsert = true;
				$this->id = Scalr::GenerateUID(true);
			}
			
			parent::save($forceInsert);
		}
		
		public function getDeployMessage()
		{
			$dbServer = DBServer::LoadByID($this->serverId);
			$application = Scalr_Dm_Application::init()->loadById($this->applicationId);
			$source = $application->getSource();
			
			$msgSource = new stdClass();
			$msgSource->url = $source->url;
			$msgSource->type = $source->type;
			
			switch ($source->authType)
			{
				case Scalr_Dm_Source::AUTHTYPE_PASSWORD:
					$msgSource->login = $source->getAuthInfo()->login;
					$msgSource->password = $source->getAuthInfo()->password;	
				break;
				case Scalr_Dm_Source::AUTHTYPE_CERT:
					$msgSource->sshPrivateKey = $source->getAuthInfo()->sshPrivateKey;
				break;
			}
			
			$params['remote_path'] = $this->remotePath;
			
			// Prepare keys array and array with values for replacement in script
			$keys = array_keys($params);
			$f = create_function('$item', 'return "%".$item."%";');
			$keys = array_map($f, $keys);
			$values = array_values($params);
			
			// Generate script contents
			$preDeployScriptContents = str_replace($keys, $values, $application->getPreDeployScript());
			$preDeployScriptContents = str_replace('\%', "%", $preDeployScriptContents);
			
			$postDeployScriptContents = str_replace($keys, $values, $application->getPostDeployScript());
			$postDeployScriptContents = str_replace('\%', "%", $postDeployScriptContents);
			
			return new Scalr_Messaging_Msg_Deploy(
				$this->id, 
				$this->remotePath, 
				$msgSource, 
				$preDeployScriptContents,
				$postDeployScriptContents
			);
		}
		
		public function deploy()
		{
			try {
				$msg = $this->getDeployMessage();
				
				$this->log("Sending deploy message to the server");
				
				$dbServer = DBServer::LoadByID($this->serverId);
				$dbServer->SendMessage($msg);
				
				$this->log("Message sent (id: {$msg->messageId}). Status: deploying");
				
				$this->status = self::STATUS_DEPLOYING;
				
			} catch (Exception $e) {
				$this->log("Cannot deploy application: {$e->getMessage()}");
				$this->status = self::STATUS_FAILED;
			}
			
			$this->save();
		}
	}
