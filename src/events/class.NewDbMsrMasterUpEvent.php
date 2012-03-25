<?php
	
	class NewDbMsrMasterUpEvent extends Event
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
		public $OldMasterDBServer;
		
		public function __construct(DBServer $DBServer)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
		}
	}
?>