<?php 
	interface Scalr_Role_iBehavior
	{		
		public function handleMessage(Scalr_Messaging_Msg $message, DBServer $dbServer);
		
		
		
		
		
		
		public function getSecurityRules();
		
		public function getDnsRecords(DBServer $dbServer);
		
		public function extendMessage(Scalr_Messaging_Msg $message, DBServer $dbServer);
		
		public function onFarmSave(DBFarm $dbFarm, DBFarmRole $dbFarmRole);
	}