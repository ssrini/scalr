<?
	final class SCHEDULE_TASK_TYPE
	{
		const SCRIPT_EXEC   = "script_exec";
		const TERMINATE_FARM= "terminate_farm";
		const LAUNCH_FARM	= "launch_farm";
		
		public static function GetAll()
		{
			return array(
				self::SCRIPT_EXEC => 'Execute script',
				self::TERMINATE_FARM => 'Terminate farm',
				self::LAUNCH_FARM => 'Launch farm'
			);
		}
		public static function GetTypeByName($name)
		{
			switch($name)
			{	
				case self::SCRIPT_EXEC: 	return "Execute script";
				case self::TERMINATE_FARM: 	return "Terminate farm";
				case self::LAUNCH_FARM: 	return "Launch farm";
			}
		}
	}
?>