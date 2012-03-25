<?php
	
	class RebundleCompleteEvent extends Event
	{
		public $SnapshotID;
		public $BundleTaskID;
		public $MetaData;
		
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		
		public function __construct(DBServer $DBServer, $SnapshotID, $BundleTaskID, $MetaData=array())
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			$this->SnapshotID = $SnapshotID;
			$this->BundleTaskID = $BundleTaskID;
			$this->MetaData = $MetaData;
		}
	}
?>