<?php
	class Scalr_Role_Behavior_Chef extends Scalr_Role_Behavior implements Scalr_Role_iBehavior
	{
		/** DBFarmRole settings **/
		const ROLE_CHEF_SERVER_ID		= 'chef.server_id';
		const ROLE_CHEF_ROLE_NAME			= 'chef.role_name';
		const ROLE_CHEF_RUNLIST_ID		= 'chef.runlist_id';
		const ROLE_CHEF_ATTRIBUTES		= 'chef.attributes';
		const ROLE_CHEF_CHECKSUM		= 'chef.checksum';
		
		const SERVER_CHEF_NODENAME		= 'chef.node_name';
		
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function getSecurityRules()
		{
			return array();
		}
		
		private function removeChefRole($chefServerId, $chefRoleName)
		{
			//Remove role and clear chef settings
			$chefServerInfo = $this->db->GetRow("SELECT * FROM services_chef_servers WHERE id=?", array($chefServerId));
			$chefServerInfo['auth_key'] = $this->getCrypto()->decrypt($chefServerInfo['auth_key'], $this->cryptoKey);
			$chefClient = Scalr_Service_Chef_Client::getChef($chefServerInfo['url'], $chefServerInfo['username'], trim($chefServerInfo['auth_key']));
			
			$chefClient->removeRole($chefRoleName);
		}
		
		public function onFarmSave(DBFarm $dbFarm, DBFarmRole $dbFarmRole)
		{
			try {
				$account = Scalr_Account::init()->loadById($dbFarm->ClientID);
				if (!$account->isFeatureEnabled(Scalr_Limits::FEATURE_CHEF)) {
					$dbFarmRole->ClearSettings("chef.");
					return false;
				}
				
				$db = Core::GetDBInstance();
				$runListId = $dbFarmRole->GetSetting(self::ROLE_CHEF_RUNLIST_ID);
				$attributes = $dbFarmRole->GetSetting(self::ROLE_CHEF_ATTRIBUTES); 
				$checksum = $dbFarmRole->GetSetting(self::ROLE_CHEF_CHECKSUM);
				$chefRoleName = $dbFarmRole->GetSetting(self::ROLE_CHEF_ROLE_NAME);
				$chefServerId = $dbFarmRole->GetSetting(self::ROLE_CHEF_SERVER_ID);
				
				// Need to remove chef role if chef was disabled for current farmrole
				if (!$runListId && $chefRoleName) {
					$this->removeChefRole($chefServerId, $chefRoleName);
					$dbFarmRole->ClearSettings("chef.");
					return true;
				}
					
				if ($runListId)
				{
					$runListInfo = $this->db->GetRow("SELECT chef_server_id, runlist FROM services_chef_runlists WHERE id=?", array($runListId));	
					$newChefServerId = $runListInfo['chef_server_id'];
					if ($newChefServerId != $chefServerId && $chefServerId) {
						// Remove role from old server
						$this->removeChefRole($chefServerId, $chefRoleName);
						$createNew = true;
					}
					
					if (!$chefServerId)
						$createNew = true;
					
					$chefServerInfo = $this->db->GetRow("SELECT * FROM services_chef_servers WHERE id=?", array($runListInfo['chef_server_id']));
					$chefServerInfo['auth_key'] = $this->getCrypto()->decrypt($chefServerInfo['auth_key'], $this->cryptoKey);
					
					$chefClient = Scalr_Service_Chef_Client::getChef($chefServerInfo['url'], $chefServerInfo['username'], trim($chefServerInfo['auth_key']));
						
					$roleName = "scalr-{$dbFarmRole->ID}";
					$setSettings = false;
					
					if ($createNew) {
						$chefClient->createRole($roleName, $roleName, json_decode($runListInfo['runlist']), json_decode($attributes), $runListInfo['chef_environment']);
						$setSettings = true;					
					} else {
						if ($dbFarmRole->GetSetting(self::ROLE_CHEF_CHECKSUM) != md5("{$runListInfo['runlist']}.$attributes")) {
							$chefClient->updateRole($roleName, $roleName, json_decode($runListInfo['runlist']), json_decode($attributes), $runListInfo['chef_environment']);
							$setSettings = true;
						}
					}
					
					if ($setSettings) {
						$dbFarmRole->SetSetting(self::ROLE_CHEF_ROLE_NAME, $roleName);
						$dbFarmRole->SetSetting(self::ROLE_CHEF_SERVER_ID, $runListInfo['chef_server_id']);
						$dbFarmRole->SetSetting(self::ROLE_CHEF_CHECKSUM, md5("{$runListInfo['runlist']}.$attributes"));
					}
				}
			} catch (Exception $e) {
				throw new Exception("Chef settings error: {$e->getMessage()} ({$e->getTraceAsString()})");
			}
		}
		
		public function handleMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			if (!$message->chef)
				return;
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostUp":
					$dbServer->SetProperty(self::SERVER_CHEF_NODENAME, $message->chef->nodeName);
					break;
			}
		}
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			$message = parent::extendMessage($message);
			
			$dbFarmRole = $dbServer->GetFarmRoleObject();
			$chefServerId = $dbFarmRole->GetSetting(self::ROLE_CHEF_SERVER_ID);
			$runListId = $dbFarmRole->GetSetting(self::ROLE_CHEF_RUNLIST_ID);
			if (!$chefServerId || !$runListId)
				return $message;
				
			$chefRunListInfo = $this->db->GetRow("SELECT * FROM services_chef_runlists WHERE id=?", array($runListId));
			
			$chefServerInfo = $this->db->GetRow("SELECT * FROM services_chef_servers WHERE id=?", array($chefRunListInfo['chef_server_id']));
			$chefServerInfo['v_auth_key'] = trim($this->getCrypto()->decrypt($chefServerInfo['v_auth_key'], $this->cryptoKey));
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostInitResponse":
					
					$message->chef = new stdClass();
					$message->chef->serverUrl = $chefServerInfo['url'];
					$message->chef->role = $dbFarmRole->GetSetting(self::ROLE_CHEF_ROLE_NAME);
					$message->chef->validatorName = $chefServerInfo['v_username'];
					$message->chef->validatorKey = $chefServerInfo['v_auth_key'];
					$message->chef->environment = $chefRunListInfo['chef_environment'];
					
					break;
			}
			
			return $message;
		}
	}