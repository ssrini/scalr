<?php
	
	class RoleOptionChangedEvent extends Event
	{
		public $DBFarmRole;
		public $OptionName;
		
		public function __construct(DBFarmRole $DBFarmRole, $option_name)
		{
			parent::__construct();
			
			$this->DBFarmRole = $DBFarmRole;
			$this->OptionName = $option_name;
		}
	}
?>