<?
	final class FARM_STATUS
	{
		const RUNNING 		= 1;
		const TERMINATED 	= 0;
		const TERMINATING 	= 2;
		const SYNCHRONIZING = 3;
		
		public static function GetStatusName($status)
		{
			$statuses = array
			(
				self::RUNNING 		=> "Running",
				self::TERMINATED 	=> "Terminated",
				self::TERMINATING	=> "Terminating",
				self::SYNCHRONIZING => "Synchronizing"
			);
			
			return $statuses[$status];
		}
	}
?>