<?php
	class Scalr_Role_Behavior_MySql extends Scalr_Role_DbMsrBehavior implements Scalr_Role_iBehavior
	{
		/** DBFarmRole settings **/
		
		
		
		//In Scalr_Db_Msr
		
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function getSecurityRules()
		{
			return array(
				"tcp:3306:3306:0.0.0.0/0"
			);
		}
		
		/*
		public function handleMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostUp":
					
					if ($message->redis->volumeConfig)
						$this->setVolumeConfig($message->cfCloudController->volumeConfig, $dbServer->GetFarmRoleObject(), $dbServer);
					else
						throw new Exception("Received hostUp message from CF Cloud Controller server without volumeConfig");
					
					break;
			}
		}
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			$message = parent::extendMessage($message);
			
			
			
			return $message;
		}
		*/
	}