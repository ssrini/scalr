<?php
	class Scalr_Scaling_Manager
	{		
		private $db,
			$farmRoleMetrics,
			$dbFarmRole;

		
		/**
		 * Constructor
		 * @param $DBFarmRole
		 * @return void
		 */
		function __construct(DBFarmRole $DBFarmRole)
		{
			$this->db = Core::GetDBInstance();
			$this->dbFarmRole = $DBFarmRole;
			$this->logger = Logger::getLogger(__CLASS__);
			
			$role_metrics = $this->db->Execute("SELECT id, metric_id FROM farm_role_scaling_metrics WHERE farm_roleid = ?", array($this->dbFarmRole->ID));
			$this->farmRoleMetrics = array();
			while ($role_metric = $role_metrics->FetchRow())
				$this->farmRoleMetrics[$role_metric['metric_id']] = Scalr_Model::init(Scalr_Model::SCALING_FARM_ROLE_METRIC)->loadById($role_metric['id']);
		}
		
		function setFarmRoleMetrics($metrics)
		{
			foreach ($this->farmRoleMetrics as $id => $farmRoleMetric) {
				if (!$metrics[$farmRoleMetric->metricId]) {
					$farmRoleMetric->delete();
					unset($this->farmRoleMetrics[$farmRoleMetric->metricId]);
				}
			}
			
			foreach ($metrics as $metric_id => $metric_settings) {
				if (!$this->farmRoleMetrics[$metric_id]) {
					$this->farmRoleMetrics[$metric_id] = Scalr_Model::init(Scalr_Model::SCALING_FARM_ROLE_METRIC);
					$this->farmRoleMetrics[$metric_id]->metricId = $metric_id;
					$this->farmRoleMetrics[$metric_id]->farmRoleId = $this->dbFarmRole->ID;
				}
				
				$this->farmRoleMetrics[$metric_id]->setSettings($metric_settings);
				$this->farmRoleMetrics[$metric_id]->save();
			}
		}
		
		function getFarmRoleMetrics()
		{
			return $this->farmRoleMetrics;
		}
		
		/**
		 * 
		 * @return Scalr_Scaling_Decision
		 */
		function makeScalingDecition()
		{
			/*
			Base Scaling
			 */
			foreach (Scalr_Role_Behavior::getListForFarmRole($this->dbFarmRole) as $behavior) {
				$result = $behavior->makeUpscaleDecision($this->dbFarmRole);
				if ($result === false)
					continue;
				else	
					return $result;
			}
			
			$farm_pending_instances = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_id=? AND status IN (?,?,?)",
				array($this->dbFarmRole->FarmID, SERVER_STATUS::PENDING, SERVER_STATUS::INIT, SERVER_STATUS::PENDING_LAUNCH)
			);
			
			if ($this->dbFarmRole->GetFarmObject()->RolesLaunchOrder == 1 && $farm_pending_instances > 0) {
                if ($this->dbFarmRole->GetRunningInstancesCount() == 0) {
					$this->logger->info("{$farm_pending_instances} instances in pending state. Launch roles one-by-one. Waiting...");
                	return Scalr_Scaling_Decision::STOP_SCALING;
                }
			}
			
			$isDbMsr = $this->dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL) || 
				$this->dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL) ||
				$this->dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS); 
			
			$needOneByOneLaunch = $this->dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::RABBITMQ) ||
				$this->dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MONGODB);
				
			// Check do we need upscale to min instances count
			$roleTotalInstances = $this->dbFarmRole->GetRunningInstancesCount()+$this->dbFarmRole->GetPendingInstancesCount(); 
				
            if ($roleTotalInstances < $this->dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES)) {
            	if ($needOneByOneLaunch) {
            		$pendingTerminateInstances = count($this->dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::PENDING_TERMINATE)));
            		// If we launching DbMSR instances. Master should be running.
            		if ($this->dbFarmRole->GetPendingInstancesCount() == 0 && !$pendingTerminateInstances) {
            			$this->logger->info(_("Increasing number of running instances to fit min instances setting"));
            			return Scalr_Scaling_Decision::UPSCALE;
            		} else {
            			$this->logger->info(_("Found servers in Pending or PendingTerminate state. Waiting..."));
            			return Scalr_Scaling_Decision::NOOP;
            		}
            	} elseif ($isDbMsr) {
            		// If we launching DbMSR instances. Master should be running.
            		if ($this->dbFarmRole->GetRunningInstancesCount() > 0 || $this->dbFarmRole->GetPendingInstancesCount() == 0) {
            			$this->logger->info(_("Increasing number of running instances to fit min instances setting"));
            			return Scalr_Scaling_Decision::UPSCALE;
            		} else {
            			$this->logger->info(_("Waiting for running master"));
            			return Scalr_Scaling_Decision::NOOP;
            		}
            			
            	} else {
            		$this->logger->info(_("Increasing number of running instances to fit min instances setting"));
            		return Scalr_Scaling_Decision::UPSCALE;
            	}
            }
            elseif ($this->dbFarmRole->GetRunningInstancesCount() > $this->dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES)) {
            	$this->logger->info(_("Decreasing number of running instances to fit max instances setting"));
				return Scalr_Scaling_Decision::DOWNSCALE;
			}
			
			/*
			 Metrics scaling
			 */
			foreach ($this->getFarmRoleMetrics() as $farmRoleMetric) {				
				$res = $farmRoleMetric->getScalingDecision();
				
				$this->logger->info(sprintf(_("Metric: %s. Decision: %s. Last value: %s"), 
					$farmRoleMetric->getMetric()->name, $res, $farmRoleMetric->lastValue)
				);
				
				if ($res == Scalr_Scaling_Decision::NOOP)
					continue;
					
				Logger::getLogger(LOG_CATEGORY::FARM)->info(new FarmLogMessage($this->dbFarmRole->FarmID, sprintf("%s: Role '%s' on farm '%s'. Metric name: %s. Last metric value: %s.", 
					$res,
					$this->dbFarmRole->GetRoleObject()->name,
                    $this->dbFarmRole->GetFarmObject()->Name,
                    $farmRoleMetric->getMetric()->name,
                    $farmRoleMetric->lastValue
				)));
					
				return $res;
			}
			
			return Scalr_Scaling_Decision::NOOP;
		}
	}
?>