<?php
	class Scalr_Scaling_Sensor
	{
		private static $sensors = array();
		
		public static function get($metricAlias)
		{
			if (!self::$sensors[$metricAlias])
			{
				switch($metricAlias)
				{
					case "la":
						self::$sensors[$metricAlias] = new Scalr_Scaling_Sensors_LoadAverage();				
						break;
					case "bw":
						self::$sensors[$metricAlias] = new Scalr_Scaling_Sensors_BandWidth();				
						break;
					case "custom":
						self::$sensors[$metricAlias] = new Scalr_Scaling_Sensors_Custom();				
						break;
					case "sqs":
						self::$sensors[$metricAlias] = new Scalr_Scaling_Sensors_Sqs();				
						break;
					case "http":
						self::$sensors[$metricAlias] = new Scalr_Scaling_Sensors_Http();				
						break;
					case "ram":
						self::$sensors[$metricAlias] = new Scalr_Scaling_Sensors_FreeRam();				
						break;
				}
			}
			
			return self::$sensors[$metricAlias];
		}
	}
?>