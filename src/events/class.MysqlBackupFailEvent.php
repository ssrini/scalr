<?php
	
	class MysqlBackupFailEvent extends Event
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		
		public $Operation;
		
		public function __construct(DBServer $DBServer, $Operation)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			$this->Operation = $Operation;
		}
	}
?>