<?
	class Scalr_Scaling_Algorithms_DateTime
	{		
		public function __construct()
		{
			$this->logger = Logger::getLogger(get_class($this));
			$this->db = Core::GetDBInstance();
		}
		
		public function makeDecision(DBFarmRole $dbFarmRole, Scalr_Scaling_FarmRoleMetric $farmRoleMetric, $isInvert = false)
		{			
			//
			// Get data from BW sensor
			//
			$dbFarm = $dbFarmRole->GetFarmObject();
			
			$env = $dbFarm->GetEnvironmentObject();
    		$tz = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::TIMEZONE);
    		if ($tz)
    		{
	    		$default_tz = @date_default_timezone_get();
    			@date_default_timezone_set($tz);
    		}
			
			$currentDate = array((int)date("Hi"), date("D"));
			
			if ($default_tz)
				@date_default_timezone_set($default_tz);
				
			$scaling_period = $this->db->GetRow("SELECT * FROM farm_role_scaling_times WHERE
				'{$currentDate[0]}' >= start_time AND
				'{$currentDate[0]}' <= end_time-10 AND
				INSTR(days_of_week, '{$currentDate[1]}') != 0 AND
				farm_roleid = '{$dbFarmRole->ID}'
			");
			
			if ($scaling_period)
			{
				$this->logger->info("TimeScalingAlgo({$dbFarmRole->FarmID}, {$dbFarmRole->AMIID}) Found scaling period. Total {$scaling_period['instances_count']} instances should be running.");
				$num_instances = $scaling_period['instances_count'];

				//$dbFarmRole->SetSetting(self::PROPERTY_NEED_INSTANCES_IN_CURRENT_PERIOD, $num_instances);
				if (($dbFarmRole->GetRunningInstancesCount()+$dbFarmRole->GetPendingInstancesCount()) < $num_instances)
					return Scalr_Scaling_Decision::UPSCALE;
				else
					return Scalr_Scaling_Decision::NOOP;
			}
			else
			{
				//$dbFarmRole->SetSetting(self::PROPERTY_NEED_INSTANCES_IN_CURRENT_PERIOD, "");
				if ($dbFarmRole->GetRunningInstancesCount() > $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES))
					return Scalr_Scaling_Decision::DOWNSCALE;
				else
					return Scalr_Scaling_Decision::NOOP;
			}	
		}
	}
?>