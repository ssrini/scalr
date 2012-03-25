<?php
	
	class HostUpEvent extends Event
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		
		public $ReplUserPass;
		
		public function __construct(DBServer $DBServer, $ReplUserPass)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			$this->ReplUserPass = $ReplUserPass;
		}
	}
?>