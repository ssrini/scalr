<?php
	
	class ServerNotFoundException extends Exception
	{
		
	}

	class DBServer
	{			
		public $serverId,
			$farmId,
			$farmRoleId,
			$envId,
			$roleId,
			$clientId,
			$platform,
			$status,
			$remoteIp,
			$localIp,
			$dateAdded,
			$dateShutdownScheduled,
			$dateRebootStart,
			$dateLastSync,
			$replaceServerID,
			$index;
		
		private 
			$platformProps,
			$realStatus,
			$cloudServerID,
			$cloudLocation,
			$flavor,
			$Db,
			$dbId,
			$propsCache,
			$environment,
			$client,
			$dbFarmRole,
			$dbFarm;
			
		public static $platformPropsClasses = array(
			SERVER_PLATFORMS::EC2 => 'EC2_SERVER_PROPERTIES',
			SERVER_PLATFORMS::RACKSPACE => 'RACKSPACE_SERVER_PROPERTIES',
			SERVER_PLATFORMS::OPENSTACK => 'OPENSTACK_SERVER_PROPERTIES',
			SERVER_PLATFORMS::RDS => 'RDS_SERVER_PROPERTIES',
			SERVER_PLATFORMS::EUCALYPTUS => 'EUCA_SERVER_PROPERTIES',
			SERVER_PLATFORMS::NIMBULA => 'NIMBULA_SERVER_PROPERTIES',
			SERVER_PLATFORMS::CLOUDSTACK => 'CLOUDSTACK_SERVER_PROPERTIES'
		);
					
		private static $FieldPropertyMap = array(
			'id'			=> 'dbId',
			'server_id' 	=> 'serverId',
			'env_id'		=> 'envId',
			'farm_id'		=> 'farmId',
			'role_id'		=> 'roleId',
			'farm_roleid'	=> 'farmRoleId',
			'client_id'		=> 'clientId',
			'platform'		=> 'platform',
			'status'		=> 'status',
			'remote_ip'		=> 'remoteIp',
			'local_ip'		=> 'localIp',
			'dtadded'		=> 'dateAdded',
			'dtshutdownscheduled'	=> 'dateShutdownScheduled',
			'dtrebootstart'	=> 'dateRebootStart',
			'dtlastsync'	=> 'dateLastSync',
			'index'			=> 'index',
			'replace_server_id' => 'replaceServerID'
		);
		
		public function __construct($serverId)
		{
			$this->serverId = $serverId;
			$this->Db = Core::GetDBInstance();
		}
		
		/**
		 * @return Scalr_Net_Ssh2_Client
		 * Enter description here ...
		 */
		public function GetSsh2Client()
		{
			$ssh2Client = new Scalr_Net_Ssh2_Client();
	         			
			switch($this->platform) {
				case SERVER_PLATFORMS::RACKSPACE:
					$ssh2Client->addPassword(
						'root', 
						$this->GetProperty(RACKSPACE_SERVER_PROPERTIES::ADMIN_PASS)
					);
         					
         		break;
         		
				case SERVER_PLATFORMS::EC2:
					
					$userName = 'root';
					
					// Temporary server for role builder
					if ($this->status == SERVER_STATUS::TEMPORARY) {
						$keyName = 'SCALR-ROLESBUILDER';
					}
					else {
						$keyName = "FARM-{$this->farmId}";
					}
					
					try {
						$key = Scalr_Model::init(Scalr_Model::SSH_KEY)->loadGlobalByName(
							$keyName,
							$this->GetProperty(EC2_SERVER_PROPERTIES::REGION),
							$this->envId
						);
						
						if (!$key)
							throw new Exception(_("There is no SSH key for server: {$this->serverId}"));
					}
					catch(Exception $e){
						throw new Exception("Cannot init SshKey object: {$e->getMessage()}");
					}
					
					$priv_key_file = tempnam("/tmp", "AWSK");
					@file_put_contents($priv_key_file, $key->getPrivate());
					
					$pub_key_file = tempnam("/tmp", "AWSK");
					@file_put_contents($pub_key_file, $key->getPublic());
					
					$ssh2Client->addPubkey($userName, $pub_key_file, $priv_key_file);
					
					break;
         	}
	         			
			return $ssh2Client;
		}
		
		public function GetCloudUserData()
		{
			$dbFarmRole = $this->GetFarmRoleObject();
			
			$retval = array(
	        	"farmid" 			=> $this->farmId,
	        	"role"				=> implode(",", $dbFarmRole->GetRoleObject()->getBehaviors()),
	        	"eventhandlerurl"	=> CONFIG::$EVENTHANDLER_URL,
	        	"hash"				=> $this->GetFarmObject()->Hash,
	        	"realrolename"		=> $dbFarmRole->GetRoleObject()->name,
	        	"httpproto"			=> CONFIG::$HTTP_PROTO,
	        	"region"			=> $this->GetProperty(EC2_SERVER_PROPERTIES::REGION),
	        	
	        	/*** For Scalarizr ***/
	        	"szr_key"				=> $this->GetKey(),
	        	"serverid"				=> $this->serverId,
	        	'p2p_producer_endpoint'	=> CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/messaging",
				'queryenv_url'			=> CONFIG::$HTTP_PROTO."://".CONFIG::$EVENTHANDLER_URL."/query-env",
				'behaviors'				=> implode(",", $dbFarmRole->GetRoleObject()->getBehaviors()),
				'farm_roleid'			=> $dbFarmRole->ID,
				'roleid'				=> $dbFarmRole->RoleID,
				'env_id'				=> $dbFarmRole->GetFarmObject()->EnvID
	        );
	        
	        switch($this->platform)
	        {
	        	case SERVER_PLATFORMS::EC2:

	        		$retval["s3bucket"]	= $dbFarmRole->GetSetting(DBFarmRole::SETTING_AWS_S3_BUCKET);
	        		$retval["cloud_storage_path"] = "s3://".$dbFarmRole->GetSetting(DBFarmRole::SETTING_AWS_S3_BUCKET);
	        		
	        		break;
	        		
	        	case SERVER_PLATFORMS::RACKSPACE:
	        		
	        		$retval["cloud_storage_path"] = "cf://scalr-{$this->GetFarmObject()->Hash}";
	        		
	        		break;
	        }
	        
	        // Custom settings
	        foreach ($dbFarmRole->GetSettingsByFilter("user-data") as $k=>$v)
	        	$retval[str_replace("user-data", "custom", $k)] = $v;
	        	
	        return $retval;
		}
		
		public function GetOsFamily()
		{
			try {
				$os = $this->GetFarmRoleObject()->GetRoleObject()->os;
			} catch (Exception $e) {
				return 'unknown';
			}
			
			if (stristr($os, 'windows'))
				return 'windows';
			else 
				return 'linux';
		} 
		
		public function IsRebooting()
		{
			return $this->GetProperty(SERVER_PROPERTIES::REBOOTING, true);
		}
		
		/**
		 * 
		 * Return cloud location (region)
		 * @param bool $skipCache
		 * @return string
		 */
		public function GetCloudLocation($skipCache = false)
		{
			if (!$this->cloudLocation || $skipCache == true)
				$this->cloudLocation = PlatformFactory::NewPlatform($this->platform)->GetServerCloudLocation($this);
			
			return $this->cloudLocation;
		}
		
		/**
		 * 
		 * Return real (Cloud) server ID
		 * @param bool $skipCache
		 * @return string
		 */
		public function GetCloudServerID($skipCache = false)
		{
			if (!$this->cloudServerID || $skipCache == true)
				$this->cloudServerID = PlatformFactory::NewPlatform($this->platform)->GetServerID($this);
			
			return $this->cloudServerID;
		}

		/**
		 * 
		 * Return server flavor (instance type)
		 * @param bool $skipCache
		 * @return string
		 */
		public function GetFlavor($skipCache = false)
		{
			if (!$this->flavor || $skipCache == true)
				$this->flavor = PlatformFactory::NewPlatform($this->platform)->GetServerFlavor($this);
			
			return $this->flavor;
		}
		
		/**
		 * 
		 * @return Modules_Platforms_Ec2_Adapters_Status
		 */
		public function GetRealStatus($skipCache = false)
		{
			if (!$this->realStatus || $skipCache == true)
				$this->realStatus = PlatformFactory::NewPlatform($this->platform)->GetServerRealStatus($this);
			
			return $this->realStatus;
		}
		
		/**
		 * @return Scalr_Environment
		 * Enter description here ...
		 */
		public function GetEnvironmentObject()
		{
			if (!$this->environment)
				$this->environment = Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadById($this->envId);
				
			return $this->environment;
		}
		
		/**
		 * 
		 * Returns DBFarme object
		 * @return DBFarm
		 */
		public function GetFarmObject()
		{
			if (!$this->dbFarm)
				$this->dbFarm = DBFarm::LoadByID($this->farmId);
				
			return $this->dbFarm;
		}
		
		/**
		 * 
		 * Returns DBFarmRole object
		 * @return DBFarmRole
		 */
		public function GetFarmRoleObject()
		{
			if (!$this->dbFarmRole)
				$this->dbFarmRole = DBFarmRole::LoadByID($this->farmRoleId);
				
			return $this->dbFarmRole;
		}
		
		/**
		 * 
		 * @return Client
		 */
		public function GetClient()
		{
			if (!$this->client)
				$this->client = Client::Load($this->clientId);
				
			return $this->client;
		}
		
		/**
		 * Returns Server authentification key (For messaging and Query-Env)
		 * @param bool $plain
		 * @return string
		 */
		public function GetKey($plain = false)
		{
			$key = $this->GetProperty(SERVER_PROPERTIES::SZR_KEY, true);
			
			return ($plain) ? base64_decode($key) : $key;
		}
		
		public function GetAllProperties()
		{
			$props = $this->Db->GetAll("SELECT * FROM server_properties WHERE server_id=?", array($this->serverId));
			foreach ($props as $prop)
				$this->propsCache[$prop['name']] = $prop['value'];
				
			return $this->propsCache;
		}
		
		/**
		 * Get Server Property
		 * @param string $propertyName
		 * @param boolean $ignoreCache
		 * @return mixed
		 */
		public function GetProperty($propertyName, $ignoreCache = false)
		{
			if (!$this->propsCache[$propertyName] || $ignoreCache)
			{
				$this->propsCache[$propertyName] = $this->Db->GetOne("SELECT value FROM server_properties WHERE server_id=? AND name=?", array(
					$this->serverId,
					$propertyName
				));
			}
			
			return $this->propsCache[$propertyName];
		}
		
		/**
		 * Removes server from database  
		 * @return void
		 */
		public function Remove()
		{						
			$this->Db->Execute('DELETE FROM servers WHERE server_id=?', array($this->serverId));
			
			$this->Db->Execute('DELETE FROM messages WHERE server_id=?', array($this->serverId));
			$this->Db->Execute('DELETE FROM scripting_log WHERE server_id=?', array($this->serverId));
			
			$this->Db->Execute('UPDATE dm_deployment_tasks SET status=? WHERE server_id=?', array(Scalr_Dm_DeploymentTask::STATUS_ARCHIVED, $this->serverId));
		}
		
		/**
		 * Set multiple server properties
		 * @param array $props
		 * @return void
		 */
		public function SetProperties(array $props)
		{
			foreach ($props as $k=>$v)
				$this->SetProperty($k, $v);
		}
		
		/**
		 * Set server property
		 * @param string $propertyName
		 * @param mixed $propertyValue
		 * @return void
		 */
		public function SetProperty($propertyName, $propertyValue)
		{
			/*
			if (!$this->platformProps)
			{
				$Reflect = new ReflectionClass(DBServer::$platformPropsClasses[$this->platform]);
				foreach ($Reflect->getConstants() as $k=>$v)
					$this->platformProps[] = $v;
			}
			
			if (!in_array($propertyName, $this->platformProps))
				throw new Exception(sprintf("Unknown property '%s' for server on '%s'", $k, $this->platform));
			*/
				
			$this->Db->Execute("REPLACE INTO server_properties SET server_id = ?, name = ?, value = ?", array(
				$this->serverId, $propertyName, $propertyValue
			));
			
			$this->propsCache[$propertyName] = $propertyValue;

			return true;
		}

		static public function IsExists($serverId)
		{
			$db = Core::GetDBInstance();
			
			return (bool)$db->GetOne("SELECT id FROM servers WHERE server_id=?", array($serverId));
		}
		
		/**
		 * 
		 * @param int $farm_roleid
		 * @param int $index
		 * @return DBInstance
		 */
		static public function LoadByFarmRoleIDAndIndex($farm_roleid, $index)
		{
			$db = Core::GetDBInstance();
			
			$server_id = $db->GetOne("SELECT server_id FROM servers WHERE farm_roleid = ? AND `index` = ?", 
				array($farm_roleid, $index)
			);
			
			if (!$server_id)
			{
				throw new Exception(sprintf(
					_("Server with FarmRoleID #%s and index #%s not found in database"), 
					$farm_roleid,
					$index
				));
			}
			
			return self::LoadByID($server_id);		
		}
		
		public static function LoadByLocalIp($localIp)
		{
			$db = Core::GetDBInstance();
				
			$serverId = $db->GetOne("SELECT server_id FROM servers WHERE `local_ip`=?", array($localIp));
			if (!$serverId)
				throw new ServerNotFoundException(sprintf("Server with local IP '%s' not found in database", $localIp));
				
			return self::LoadByID($serverId);
		}
		
		/**
		 * Return DBServer by property value
		 * @param string $propName
		 * @param string $propValue
		 * @return DBServer
		 */
		public static function LoadByPropertyValue($propName, $propValue)
		{
			$db = Core::GetDBInstance();
				
			$serverId = $db->GetOne("SELECT server_id FROM server_properties WHERE `name`=? AND `value`=?", array($propName, $propValue));
			if (!$serverId)
				throw new ServerNotFoundException(sprintf("Server with property '%s'='%s' not found in database", $propName, $propValue));
				
			return self::LoadByID($serverId);
		}
		
		/**
		 * 
		 * @param $serverId
		 * @return DBServer
		 */
		public static function LoadByID($serverId)
		{
			$db = Core::GetDBInstance();
			
			$serverinfo = $db->GetRow("SELECT * FROM servers WHERE server_id=?", array($serverId));
			if (!$serverinfo)
				throw new ServerNotFoundException(sprintf(_("Server ID#%s not found in database"), $serverId));
				
			$DBServer = new DBServer($serverId);

			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if (isset($serverinfo[$k]))
					$DBServer->{$v} = $serverinfo[$k];
			}
			
			return $DBServer;
		}
			
		public function GetFreeDeviceName()
		{
			$DBFarm = $this->GetFarmObject();
			
			$snmpClient = new Scalr_Net_Snmp_Client();

			$port = $this->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
			$snmpClient->Connect($this->remoteIp, $port ? $port : 161, $DBFarm->Hash, false, false, true);
			
			$result = implode(":", $snmpClient->getTree("UCD-DISKIO-MIB::diskIODevice"));
			
			$map = array(
				"f", "g", "h", "i", "j", 
				"k", "l", "m", "n", "p"
			);
			
			$n_map = array(
				"1", "2", "3", "4", "5", "6", "7", "8", "9"
			);
			
			$map_used = array();
			
			preg_match_all("/sd|xvd([a-z][0-9]*)/", $result, $matches);
			foreach ($matches[1] as $v)
			{
				if (!in_array($map_used, $v))
					array_push($map_used, $v);
			}
			
			if (count($map_used) == 0)
				throw new Exception(_("Cannot get a list of used disk devices: {$result}"));
			
			$device_l = false; 
			foreach ($n_map as $v) {
				foreach ($map as $letter) {
					if (in_array($letter, $map_used))
						continue;
					
					$device_l = "{$letter}{$v}";
					if (!in_array($device_l, $map_used)) {
						break;
					} else 
						$device_l = false;	
				}
				
				if ($device_l)
					break;
			}
				
			if (!$device_l)
				throw new Exception(_("There is no available device letter on instance for attaching EBS"));
				
			return "/dev/sd{$device_l}";
		}
		
		/**
		 * 
		 * @param ServerCreateInfo $serverCreateInfo
		 * @param bool $isImport
		 * @return DBServer
		 */
		public static function Create(ServerCreateInfo $creInfo, $isImport = false, $setPendingStatus = false)
		{
			$db = Core::GetDBInstance();
			
			$server_id = Scalr::GenerateUID();

			$status = (!$isImport) ? SERVER_STATUS::PENDING_LAUNCH : SERVER_STATUS::IMPORTING;
			if ($setPendingStatus)
				$status = SERVER_STATUS::PENDING;
			
			// IF no index defined
			if (!$creInfo->index && !$isImport)
			{
        		$indexes = $db->GetAll("SELECT `index` FROM servers WHERE farm_roleid=? AND status NOT IN (?,?)",
	        		array($creInfo->dbFarmRole->ID, SERVER_STATUS::TERMINATED, SERVER_STATUS::PENDING_TERMINATE)
	        	);
	        	$used_indexes = array();
	
	        	if (count($indexes) > 0)
	        		foreach ($indexes as $index)
	        			$used_indexes[$index['index']] = true;
	        	
	        	for ($i = 1;;$i++)
	        	{
	        		if (!$used_indexes[$i])
	        		{
	        			$creInfo->index = $i;
	        			break;
	        		} 
	        	}
			}
			elseif ($isImport)
			{
				$creInfo->index = 0;	
			}
			
			$client_id = $creInfo->clientId ? $creInfo->clientId : $creInfo->dbFarmRole->GetFarmObject()->ClientID;
			
			$db->Execute("INSERT INTO servers SET
				`server_id`		= ?,
				`farm_id`		= ?,
				`role_id`		= ?,
				`env_id`		= ?,
				`farm_roleid`	= ?,
				`client_id`		= ?,
				`platform`		= ?,
				`status`		= ?,
				`remote_ip`		= ?,
				`local_ip`		= ?,
				`dtadded`		= NOW(),
				`index`			= ?
			", array(
				$server_id,
				$creInfo->farmId ? $creInfo->farmId : $creInfo->dbFarmRole->FarmID,
				$creInfo->roleId,
				$creInfo->envId,
				$creInfo->dbFarmRole ? $creInfo->dbFarmRole->ID : 0,
				$client_id,
				$creInfo->platform,
				$status,
				$creInfo->remoteIp,
				$creInfo->localIp,
				$creInfo->index
			));
			
			$db->Execute("INSERT INTO servers_history SET
				`client_id`	= ?,
				`server_id`	= ?,
				`platform`	= ?
			", array(
				$client_id,
				$server_id,
				$creInfo->platform
			));
			
			$DBServer = DBServer::LoadByID($server_id);
			$DBServer->SetProperties($creInfo->GetProperties());
			
			return $DBServer;
		}
		
		private function Unbind () {
			$row = array();
			foreach (self::$FieldPropertyMap as $field => $property) {
				$row[$field] = $this->{$property};
			}
			
			return $row;		
		}
		
		function Save () {
				
			$row = $this->Unbind();
			unset($row['server_id']);
			unset($row['id']);
			
			// Prepare SQL statement
			$set = array();
			$bind = array();
			foreach ($row as $field => $value) {
				$set[] = "`$field` = ?";
				$bind[] = $value;
			}
			$set = join(', ', $set);
	
			try	{
				if ($this->dbId) {
					// Perform Update
					$bind[] = $this->dbId;
					$this->Db->Execute("UPDATE servers SET $set WHERE id = ?", $bind);
				}
				else {
					// Perform Insert
					$this->Db->Execute("INSERT INTO servers SET $set", $bind);
					$this->dbId = $this->Db->Insert_ID();
				}
				
			} catch (Exception $e) {
				throw new Exception ("Cannot save server. Error: " . $e->getMessage(), $e->getCode());			
			}
		}
		
		function Delete ()
		{
			$this->Db->Execute("DELETE FROM servers WHERE server_id = ?", array($this->serverId));
			$this->Db->Execute("DELETE FROM server_properties WHERE server_id = ?", array($this->serverId));
			$this->Db->Execute("DELETE FROM messages WHERE server_id = ?", array($this->serverId));
		}
		
		
		private function GetVersionInfo($v) {
			if (preg_match("/^([0-9]+)\.([0-9]+)[-\.]?[r]*([0-9]+)?$/si", $v, $matches)) {
				$verInfo = array_map("intval", array_slice($matches, 1));
				while (count($verInfo) < 3) {
					$verInfo[] = 0;
				}
				return $verInfo;
			} else {
				return array(0, 0, 0);
			}
		}
		
		/**
		 * Return information about scalarizr version installed on instance
		 * @return array
		 */
		public function GetScalarizrVersion() {
			return $this->GetVersionInfo($this->GetProperty(SERVER_PROPERTIES::SZR_VESION, true));
		}
		
		public function IsSupported($v) {
			return $this->GetScalarizrVersion() >= $this->GetVersionInfo($v);
		}
		
		/**
		 * Send message to instance
		 * @param Scalr_Messaging_Msg $message
		 * @return Scalr_Messaging_Msg
		 */
		public function SendMessage(Scalr_Messaging_Msg $message, $isEventNotice = false, $delayed = false)
		{
			if ($this->farmId) {
				if ($this->GetFarmObject()->Status == FARM_STATUS::TERMINATED) {
					$this->Db->Execute("UPDATE messages SET status = ? WHERE messageid = ?", array(MESSAGE_STATUS::FAILED, $message->messageId));
					return;
				}
			}
			
			$logger = Logger::getLogger('DBServer');
			$serializer = Scalr_Messaging_XmlSerializer::getInstance();
			$cryptoTool = Scalr_Messaging_CryptoTool::getInstance();
			
			$rawMessage = $serializer->serialize($message);

			// Add message to database
			$this->Db->Execute("INSERT INTO messages SET
				`messageid`	= ?,
				`server_id`	= ?,
				`message`	= ?,
				`type`		= 'out',
				`message_name` = ?,
				`handle_attempts` = ?,
				`message_version` = ?,
				`dtlasthandleattempt` = NOW()
			ON DUPLICATE KEY UPDATE handle_attempts = handle_attempts+1, dtlasthandleattempt = NOW()  
			", array(
				$message->messageId,
				$this->serverId,
				$rawMessage,
				$message->getName(),
				($delayed) ? '0' : '1',
				($this->IsSupported("0.5")) ? 2 : 1
			));
		
			if ($this->platform == SERVER_PLATFORMS::RDS)
			{
				$logger->info("RDS platform doesn't support messaging. Skipping...");
				$this->Db->Execute("UPDATE messages SET status = ? WHERE messageid = ?", 
					array(MESSAGE_STATUS::UNSUPPORTED, $message->messageId));
				
				return $message;
			}
			
			if ($delayed)
				return $message;
			
			if ($this->IsSupported("0.5") && !$isEventNotice)
			{
				if (!$this->remoteIp)
					return;
				
				// Put access data and reserialize message
				$pl = PlatformFactory::NewPlatform($this->platform);
				$pl->PutAccessData($this, $message);
				$rawMessage = $serializer->serialize($message);
				
				$cryptoKey = $this->GetKey(true);
				$encMessage = $cryptoTool->encrypt($rawMessage, $cryptoKey);
				list($signature, $timestamp) = $cryptoTool->sign($encMessage, $cryptoKey);
				
				try
				{
					$ctrlPort = $this->GetProperty(SERVER_PROPERTIES::SZR_CTRL_PORT);
					if (!$ctrlPort)
						$ctrlPort = 8013;
					
					// Prepare request
				  	$request = new HttpRequest("http://{$this->remoteIp}:{$ctrlPort}/control", HTTP_METH_POST);
				  	$request->setOptions(array(
				  		'timeout'	=> 4,
				  		'connecttimeout' => 4
				  	));
				  	$request->setHeaders(array(
						"Date" =>  $timestamp, 
						"X-Signature" => $signature
				  	));
					$request->setRawPostData($encMessage);
					
					// Send request
					$request->send();
					
					// Process response
					if ($request->getResponseCode() == 201) {
						$logger->info(sprintf("[FarmID: %s] Sending message '%s' via REST to server '%s' (server_id: %s) complete",
								$this->farmId, $message->getName(), $this->remoteIp, $this->serverId));
						$this->Db->Execute("UPDATE messages SET status = ?, message = '' WHERE messageid = ?", 
								array(MESSAGE_STATUS::HANDLED, $message->messageId));
					} else {
						$logger->warn(sprintf("[FarmID: %s] Cannot deliver message '%s' (message_id: %s) via REST"
								. " to server '%s' (server_id: %s). Error: %s %s",
								$this->farmId, $message->getName(), $message->messageId, 
								$this->remoteIp, $this->serverId, $request->getResponseCode(), $request->getResponseStatus()));				
					}
				}
				catch(HttpException $e)
				{
					if (isset($e->innerException))
						$msg = $e->innerException->getMessage();
				    else
						$msg = $e->getMessage();
					
					if ($this->farmId)
					{
					    $logger->warn(new FarmLogMessage($this->farmId, sprintf("Cannot deliver message '%s' (message_id: %s) via REST"
							. " to server '%s' (server_id: %s). Error: %s %s",
							$message->getName(), $message->messageId, 
							$this->remoteIp, $this->serverId, $request->getResponseCode(), $msg
						)));
					} else {
						$logger->fatal(sprintf("Cannot deliver message '%s' (message_id: %s) via REST"
							. " to server '%s' (server_id: %s). Error: %s %s",
							$message->getName(), $message->messageId, 
							$this->remoteIp, $this->serverId, $request->getResponseCode(), $msg
						));
					}
					
					return false;
				}
			}
			else
			{
				if ($this->remoteIp)
				{
					$community = $this->Db->GetOne("SELECT hash FROM farms WHERE id=?", array($this->farmId));
					
					$snmpClient = new Scalr_Net_Snmp_Client();
					$snmpClient->connect($this->remoteIp, 162, $community);
					
					$converter = Scalr_Messaging_SnmpConverter::getInstance();
					$trap = $converter->convert($message, $isEventNotice); 
					$res = $snmpClient->sendTrap($trap);
					
					Logger::getLogger('DBServer')->info("[FarmID: {$this->farmId}] Sending message ".$message->getName()." via SNMP ({$trap}) to '{$this->serverId}' ('{$this->remoteIp}') complete ({$res})");
				}
			}
			
			return $message;
		}
		
		/**
		 * 
		 * @return array
		 */
		public function GetScriptingVars()
		{
			$dbFarmRole = $this->GetFarmRoleObject();
			$roleId = $dbFarmRole->NewRoleID ? $dbFarmRole->NewRoleID : $dbFarmRole->RoleID;
			$dbRole = DBRole::loadById($roleId);
			
			$retval =  array(
				'image_id'		=> $dbRole->getImageId($this->platform, $dbFarmRole->CloudLocation),
				'external_ip' 	=> $this->remoteIp,
				'internal_ip'	=> $this->localIp,
				'role_name'		=> $dbRole->name,
				'isdbmaster'	=> $this->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER),
				'instance_index'=> $this->index,
				'server_id'		=> $this->serverId,
				'farm_id'		=> $this->farmId,
				'farm_name'		=> $this->GetFarmObject()->Name,
				'behaviors'		=> implode(",", $dbRole->getBehaviors())
			);
			
			if ($this->platform == SERVER_PLATFORMS::EC2)
			{
				$retval['instance_id'] = $this->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID);
				$retval['ami_id'] = $this->GetProperty(EC2_SERVER_PROPERTIES::AMIID);
				$retval['region'] = $this->GetProperty(EC2_SERVER_PROPERTIES::REGION);
				$retval['avail_zone'] = $this->GetProperty(EC2_SERVER_PROPERTIES::AVAIL_ZONE);
			}
			
			return $retval;
		}
	}
?>