<?php
	
	class HostDownEvent extends Event
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		
		/**
		 * 
		 * @var DBServer
		 */
		public $replacementDBServer;
		
		public function __construct(DBServer $DBServer)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			
			$r_server = Core::GetDBInstance()->GetRow("SELECT server_id FROM servers WHERE replace_server_id=?", array($DBServer->serverId));
			if ($r_server)
				$this->replacementDBServer = DBServer::LoadByID($r_server['server_id']);
			
		}
	}
?>