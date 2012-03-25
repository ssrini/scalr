<?php
	
	class NewMysqlMasterUpEvent extends Event
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		public $SnapURL;
		
		/**
		 * 
		 * @var DBServer
		 */
		public $OldMasterDBServer;
		
		public function __construct(DBServer $DBServer, $SnapURL, DBServer $OldMasterDBServer)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			$this->SnapURL = $SnapURL;
			$this->OldMasterDBServer = $OldMasterDBServer;
		}
	}
?>