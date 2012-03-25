<?php
	class Scalr_Scaling_Sensors_Sqs extends Scalr_Scaling_Sensor
	{		
		const SETTING_QUEUE_NAME = 'queue_name';
		
		public function __construct()
		{
			$this->snmpClient = new Scalr_Net_Snmp_Client();
		}
		
		public function getValue(DBFarmRole $dbFarmRole, Scalr_Scaling_FarmRoleMetric $farmRoleMetric)
		{
			$dbFarm = $dbFarmRole->GetFarmObject();
			
			$AmazonSQS = AmazonSQS::GetInstance(
				$dbFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
				$dbFarm->GetEnvironmentObject()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
			);
			
			try
			{
				$res = $AmazonSQS->GetQueueAttributes($farmRoleMetric->getSetting(self::SETTING_QUEUE_NAME));
				$retval = $res['ApproximateNumberOfMessages'];
			}
			catch(Exception $e)
			{
				throw new Exception(sprintf("SQSScalingSensor failed during SQS request: %s", $e->getMessage()));
			}
			
			return array($retval);
		}
	}