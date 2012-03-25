<?php
	class Scalr_Scaling_Sensors_Custom extends Scalr_Scaling_Sensor
	{
		public function __construct()
		{
			$this->snmpClient = new Scalr_Net_Snmp_Client();
		}
		
		public function getValue(DBFarmRole $dbFarmRole, Scalr_Scaling_FarmRoleMetric $farmRoleMetric)
		{
			$servers = $dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));
			$dbFarm = $dbFarmRole->GetFarmObject();
			
			$retval = array();
			
			if (count($servers) == 0)
				return false;
			
			foreach ($servers as $DBServer)
			{
				$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
				
				// Think about global cache
				
				$this->snmpClient->connect($DBServer->remoteIp, $port ? $port : 161, $dbFarm->Hash, 7, null, true);
				$res = $this->snmpClient->getFullTree(".1.3.6.1.4.1.36632.5.1");
				$result = array();
				foreach ($res as $oid => $value)
				{
					preg_match("/^(.*?)\.36632\.5\.1\.([0-9]+)\.([0-9]+)$/", $oid, $matches);
					switch($matches[2])
					{
						case "1": //index
							$result['index'][$matches[3]] = $value;
							break;
						case "2": //metric_id
							$result['metric_id'][$matches[3]] = $value;
							break;
						case "3": //metric_name
							$result['metric_name'][$matches[3]] = $value;
							break;
						case "4": //metric_value
							$result['metric_value'][$matches[3]] = $value;
							break;
						case "5": //error
							$result['error'][$matches[3]] = $value;
							break;
					}
				}
				
				foreach ($result['metric_id'] as $index => $metric_id)
				{
					if ($metric_id == $farmRoleMetric->metricId)
					{
						if ($result['error'][$index])
						{
							throw new Exception(sprintf(_("%s metric error on '%s' (%s): %s"),
								$result['metric_name'][$index],
								$DBServer->serverId,
								$DBServer->remoteIp,
								$result['error'][$index]
							));
						}
						
						$retval[] = $result['metric_value'][$index];
						
						break;
					}
				}
			}
			
			return $retval;
		}
	}