<?php
	class Scalr_Role_Behavior_CfCloudController extends Scalr_Role_Behavior implements Scalr_Role_iBehavior
	{
		/** DBFarmRole settings **/
		const ROLE_VOLUME_ID    = 'cf.data_storage.volume_id';
		const ROLE_DATA_STORAGE_ENGINE = 'cf.data_storage.engine';
		
		// For EBS storage
		const ROLE_DATA_STORAGE_EBS_SIZE = 'cf.data_storage.ebs.size';
		
		
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function getSecurityRules()
		{
			return array(
				"tcp:4222:4222:0.0.0.0/0",
				"tcp:9022:9022:0.0.0.0/0"
			);
		}
		
		public function handleMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			if (!$message->cfCloudController)
				return;
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostUp":
					
					if ($message->cfCloudController->volumeConfig)
						$this->setVolumeConfig($message->cfCloudController->volumeConfig, $dbServer->GetFarmRoleObject(), $dbServer);
					else
						throw new Exception("Received hostUp message from CF Cloud Controller server without volumeConfig");
					
					break;
			}
		}
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			$message = parent::extendMessage($message);
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostInitResponse":
					
					$message->cfCloudController = new stdClass();
					$message->cfCloudController->volumeConfig = $this->getVolumeConfig($dbServer->GetFarmRoleObject(), $dbServer);
					
					break;
			}
			
			return $message;
		}
	}