<?php

	class Modules_Platforms_Aws
	{		
		/**
		 * 
		 * @return array
		 */
		public function getLocations()
		{
			return array(
				'us-east-1'		 => 'AWS / US East 1 (N. Virginia)',
				'us-west-1' 	 => 'AWS / US West 1 (N. California)',
				'us-west-2' 	 => 'AWS / US West 2 (Oregon)',
				'eu-west-1'		 => 'AWS / EU West 1 (Ireland)',
				'sa-east-1'		 => 'AWS / SA East 1 (Sao Paulo)', 
				'ap-southeast-1' => 'AWS / Asia Pacific East 1 (Singapore)',
				'ap-northeast-1' => 'AWS / Asia Pacific North 1 (Tokyo)'
			);
		}
	}
?>