<?
	class Scalr_Scaling_Algorithms_Sensor
	{		
		public function __construct()
		{
			$this->logger = Logger::getLogger(get_class($this));
		}
		
		public function makeDecision(DBFarmRole $dbFarmRole, Scalr_Scaling_FarmRoleMetric $farmRoleMetric, $isInvert = false)
		{			
			if ($farmRoleMetric->lastValue > $farmRoleMetric->getSetting('max'))
				$retval = Scalr_Scaling_Decision::UPSCALE;
			elseif ($farmRoleMetric->lastValue < $farmRoleMetric->getSetting('min'))
				$retval = Scalr_Scaling_Decision::DOWNSCALE;
			
			
			if (!$retval) {
				return Scalr_Scaling_Decision::NOOP;
			}
			else
			{
				if ($isInvert)
				{
					if ($retval == Scalr_Scaling_Decision::UPSCALE)
						$retval = Scalr_Scaling_Decision::DOWNSCALE;
					else
						$retval = Scalr_Scaling_Decision::UPSCALE;
				}
				
				if ($retval == Scalr_Scaling_Decision::UPSCALE) {
					if(($dbFarmRole->GetRunningInstancesCount()+$dbFarmRole->GetPendingInstancesCount()) >= $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES))
						$retval = Scalr_Scaling_Decision::NOOP;
				}
				
				if ($retval == Scalr_Scaling_Decision::DOWNSCALE) {
					if ($dbFarmRole->GetRunningInstancesCount() <= $dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES))
						$retval = Scalr_Scaling_Decision::NOOP;
				}
		
				$isSzr = $dbFarmRole->GetRoleObject()->isSupported("0.5");
				
				if ($retval == Scalr_Scaling_Decision::UPSCALE && ($dbFarmRole->GetPendingInstancesCount() > 5 && !$isSzr))
					return Scalr_Scaling_Decision::NOOP;
				else
					//if ($isSzr && $dbFarmRole->GetPendingInstancesCount() > 10)
						//return Scalr_Scaling_Decision::NOOP;
					//else
						return $retval;
			}
		}
	}
?>