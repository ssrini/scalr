<?php

	interface IDeferredEventObserver
	{
		public function SetConfig($config);
		
		public static function GetConfigurationForm();
		
		public function __call($method, $args);
	}
?>