<?php
	class Scalr_Role_Behavior_HAProxy extends Scalr_Role_Behavior implements Scalr_Role_iBehavior
	{	
		const ROLE_CONFIGURED = 'haproxy.configured';
		const ROLE_HC_TARGET = 'haproxy.healthcheck.target';
		const ROLE_HC_INTERVAL = 'haproxy.healthcheck.interval';
		const ROLE_HC_TIMEOUT = 'haproxy.healthcheck.timeout';
		const ROLE_HC_HEALTHY_TH = 'haproxy.healthcheck.healthyth';
		const ROLE_HC_UNHEALTHY_TH = 'haproxy.healthcheck.unhealthyth';
		
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function getSecurityRules()
		{
			return array();
		}
		
		public function onFarmSave(DBFarm $dbFarm, DBFarmRole $dbFarmRole)
		{
			if (!$dbFarmRole->GetSetting(self::ROLE_CONFIGURED)) {
				
				//TOOD: Update healthcheck
			    
			    $dbFarmRole->SetSetting(self::ROLE_CONFIGURED, 1);
			}
		}
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			$message = parent::extendMessage($message);
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostInitResponse":
					
					//Healthchecks
					$healthcheck = new stdClass();
					$healthcheck->target = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_HC_TARGET);
				    $healthcheck->interval = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_HC_INTERVAL);
				    $healthcheck->timeout = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_HC_TIMEOUT);
				    $healthcheck->unhealthy_threshold = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_HC_UNHEALTHY_TH);
				    $healthcheck->healthy_threshold = $dbServer->GetFarmRoleObject()->GetSetting(self::ROLE_HC_HEALTHY_TH);
					
				  	// Listeners
				    $listeners = array();
	        		$settings = $dbServer->GetFarmRoleObject()->GetSettingsByFilter('haproxy.listener');
					foreach ($settings as $sk=>$sv) {
			    		$listener_chunks = explode("#", $sv);
			    		$itm = new stdClass();
			    		$itm->protocol = $listener_chunks[0];
			    		$itm->port = $listener_chunks[1];
			    		$itm->server_port = $listener_chunks[2];
			    		$itm->backend = "role:{$listener_chunks[3]}";
			    		$listeners[] = $itm;
				    }
					
					$message->haproxy = new stdClass();
					$message->haproxy->healthchecks[] = $healthcheck;
					$message->haproxy->listeners = $listeners;
					$message->haproxy->servers = array();
					
					break;
			}
			
			return $message;
		}
	}