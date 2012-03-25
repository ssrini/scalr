<?php 
	class Scalr_Role_DbMsrBehavior extends Scalr_Role_Behavior
	{		
		/* ALL SETTINGS IN SCALR_DB_MSR_* */
		
		protected $behavior;
		
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer)
		{
			$message = parent::extendMessage($message);
			
			try {
				$dbFarmRole = $dbServer->GetFarmRoleObject();
			} catch (Exception $e) {}
			
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostInitResponse":
					
					$dbMsrInfo = Scalr_Db_Msr_Info::init($dbFarmRole, $dbServer, $this->behavior);
					$message->addDbMsrInfo($dbMsrInfo);
					
					break;
			}
			
			return $message;
		}
		
		public function handleMessage(Scalr_Messaging_Msg $message, DBServer $dbServer) 
		{ 
			try {
				$dbFarmRole = $dbServer->GetFarmRoleObject();
			} catch (Exception $e) {}
				
			switch (get_class($message))
			{
				case "Scalr_Messaging_Msg_HostUp":
					
					if ($message->dbType && in_array($message->dbType, array(ROLE_BEHAVIORS::REDIS, ROLE_BEHAVIORS::POSTGRESQL)))
					{
						$dbMsrInfo = Scalr_Db_Msr_Info::init($dbFarmRole, $dbServer, $message->dbType);
       					$dbMsrInfo->setMsrSettings($message->{$message->dbType});
					}
					
					break;
				
				case "Scalr_Messaging_Msg_DbMsr_PromoteToMasterResult":
					
					if (Scalr_Db_Msr::onPromoteToMasterResult($message, $dbServer))
	       				Scalr::FireEvent($dbServer->farmId, new NewDbMsrMasterUpEvent($dbServer));
	       				
					break;
					
				case "Scalr_Messaging_Msg_DbMsr_CreateDataBundleResult":

					if ($message->status == "ok")
       					Scalr_Db_Msr::onCreateDataBundleResult($message, $dbServer);
       				else {
       					$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_IS_RUNNING, 0);
       						//TODO: store last error
       				}
					
					break;
					
				case "Scalr_Messaging_Msg_DbMsr_CreateBackupResult":

					if ($message->status == "ok")
       					Scalr_Db_Msr::onCreateBackupResult($message, $dbServer);
       				else {
       					$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BACKUP_IS_RUNNING, 0);
       						//TODO: store last error
       				}
					
					break;
			}
		}
	}