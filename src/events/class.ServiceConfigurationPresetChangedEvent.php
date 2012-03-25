<?php
	
	class ServiceConfigurationPresetChangedEvent extends Event
	{
		public $ServiceConfiguration;
		public $ResetToDefaults;
		
		public function __construct(Scalr_ServiceConfiguration $serviceConfiguration, $resetToDefaults = false)
		{
			parent::__construct();
			
			$this->ServiceConfiguration = $serviceConfiguration;
			$this->ResetToDefaults = $resetToDefaults;
		}
	}
?>