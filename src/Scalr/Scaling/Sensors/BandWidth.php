<?php
	class Scalr_Scaling_Sensors_BandWidth extends Scalr_Scaling_Sensor
	{
		const SETTING_BW_TYPE = 'type';
		const SETTING_BW_LAST_VALUE_RAW = 'raw_last_value';
		
		private $snmpOids = array(
			'inbound'		=> '.1.3.6.1.2.1.2.2.1.10.2',
			'outbound'		=> '.1.3.6.1.2.1.2.2.1.16.2'
		);
		
		public function __construct()
		{
			$this->snmpClient = new Scalr_Net_Snmp_Client();
			$this->db = Core::GetDBInstance();
		}
		
		public function getValue(DBFarmRole $dbFarmRole, Scalr_Scaling_FarmRoleMetric $farmRoleMetric)
		{
			$servers = $dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));
			$DBFarm = $dbFarmRole->GetFarmObject();
			
			if (count($servers) == 0)
				return 0;
			
			$_roleBW = array();
			$retval = array();	
			
			foreach ($servers as $DBServer)
			{
				$port = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_SNMP_PORT);
				$type = $farmRoleMetric->getSetting(self::SETTING_BW_TYPE);
				if (!$type)
					$type = 'outbound';
				
				$this->snmpClient->connect($DBServer->remoteIp, $port ? $port : 161, $DBFarm->Hash, null, null, false);
            	preg_match_all("/[0-9]+/si", $this->snmpClient->get(
            		$this->snmpOids[$type]
            	), $matches);
				$bw_out = (float)$matches[0][0];
				
            	$bw = round($bw_out/1024/1024, 2);
                $_roleBW[]= $bw;
			}
			
			$roleBW = round(array_sum($_roleBW)/count($_roleBW), 2);
			
			if ($farmRoleMetric->getSetting(self::SETTING_BW_LAST_VALUE_RAW) !== null && $farmRoleMetric->getSetting(self::SETTING_BW_LAST_VALUE_RAW) !== '')
			{
				$time = (time()-$farmRoleMetric->dtLastPolled);				
				$bandwidth_usage = ($roleBW - (float)$farmRoleMetric->getSetting(self::SETTING_BW_LAST_VALUE_RAW))*8;
				$bandwidth_channel_usage = $bandwidth_usage/$time; // in Mbits/sec
				$retval = round($bandwidth_channel_usage, 2);
			}
			else
				$retval = 0;
				
			$farmRoleMetric->setSetting(self::SETTING_BW_LAST_VALUE_RAW, $roleBW);
			
			return array($retval);
		}
	}