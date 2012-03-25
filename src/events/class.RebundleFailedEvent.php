<?php
	
	class RebundleFailedEvent extends Event
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		public $BundleTaskID;
		
		public function __construct(DBServer $DBServer, $BundleTaskID, $LastErrorMessage)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			$this->BundleTaskID = $BundleTaskID;
			$this->LastErrorMessage = $LastErrorMessage;
		}
	}
?>