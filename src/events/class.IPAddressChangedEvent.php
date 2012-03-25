<?php
	
	class IPAddressChangedEvent extends Event
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		
		public $NewIPAddress;
		
		public function __construct(DBServer $DBServer, $NewIPAddress)
		{
			parent::__construct();
			
			$this->DBServer = $DBServer;
			$this->NewIPAddress = $NewIPAddress;
		}
		
		public static function GetScriptingVars()
		{
			return array("new_ip_address" => "NewIPAddress");
		}
	}
?>