<?php
	
	class Scalr_Scaling_Algorithm 
	{
		const SENSOR_ALGO = 'Sensor';
		const DATETIME_ALGO = 'DateTime';
		
		private static $algos = array();
		
		/**
		 * 
		 * @param string $algoName
		 * @throws Exception
		 * return 
		 */
		public static function get($algoName)
		{
			if (!self::$algos[$algoName])
			{
				$class_name = "Scalr_Scaling_Algorithms_{$algoName}";
				if (class_exists($class_name))		
					self::$algos[$algoName] = new $class_name();
				else
					throw new Exception(sprintf(_("Scaling algorithm '%s' not found"), $algoName));
			}
			
			return self::$algos[$algoName];
		}
	}
?>