<?php
	
	class HostCrashEvent extends Event
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		
		public function __construct(DBServer $DBServer)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
		}
	}
?>