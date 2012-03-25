<?php
	
	class FarmLaunchedEvent extends Event 
	{
		public $MarkInstancesAsActive;
		
		public function __construct($MarkInstancesAsActive)
		{
			parent::__construct();
			
			$this->MarkInstancesAsActive = $MarkInstancesAsActive;
		}
	}
?>