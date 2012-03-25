<?php
	class Scalr_Scaling_Sensors_LoadAverage extends Scalr_Scaling_Sensor
	{
		const SETTING_LA_PERIOD = 'period';
		
		private $snmpOids = array(
			'1'		=> '.1.3.6.1.4.1.2021.10.1.3.1',
			'5'		=> '.1.3.6.1.4.1.2021.10.1.3.2',
			'15'	=> '.1.3.6.1.4.1.2021.10.1.3.3'
		);
		
		public function __construct()
		{
			$this->snmpClient = new Scalr_Net_Snmp_Client();
		}
		
		public function getValue(DBFarmRole $dbFarmRole, Scalr_Scaling_FarmRoleMetric $farmRoleMetric)
		{
			$servers = $dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));
			$dbFarm = $dbFarmRole->GetFarmObject();
			
			$roleLA = 0;
			
			if (count($servers) == 0)
				return false;
			
			$retval = array();
				
			foreach ($servers as $DBServer)
			{
				if ($dbFarmRole->GetSetting(DBFarmRole::SETTING_SCALING_EXCLUDE_DBMSR_MASTER) == 1)
				{
					$isMaster = ($DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) == 1 || $DBServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) == 1);
					if ($isMaster)
						continue;
				}
				
				$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
				
				$period = $farmRoleMetric->getSetting(self::SETTING_LA_PERIOD);
				if (!$period)
					$period = '15';
				
				$this->snmpClient->connect($DBServer->remoteIp, $port ? $port : 161, $dbFarm->Hash, null, null, false);
            	$res = $this->snmpClient->get(
            		$this->snmpOids[$period]
            	);
            	
            	$la = (float)$res;
                                    
                $retval[] = $la;
			}
			
			return $retval;
		}
	}