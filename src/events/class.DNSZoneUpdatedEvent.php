<?php
	
	class DNSZoneUpdatedEvent extends Event 
	{
		public $ZoneName;
		
		public function __construct($ZoneName)
		{
			parent::__construct();
			
			$this->ZoneName = $ZoneName;
		}
		
		public static function GetScriptingVars()
		{
			return array("zone_name" => "ZoneName");
		}
	}
?>