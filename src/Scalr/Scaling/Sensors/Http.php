<?php
	class Scalr_Scaling_Sensors_Http extends Scalr_Scaling_Sensor
	{		
		const SETTING_URL = 'url';
		
		public function __construct()
		{
			$this->snmpClient = new Scalr_Net_Snmp_Client();
		}
		
		public function getValue(DBFarmRole $dbFarmRole, Scalr_Scaling_FarmRoleMetric $farmRoleMetric)
		{
			$start_time = microtime(true);
			
			// REQUEST
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(
				"redirect" => 10, 
				"useragent" => "Scalr (http://scalr.net) HTTPResponseTime Scaling Sensor"
			));
			$HttpRequest->setUrl($farmRoleMetric->getSetting(self::SETTING_URL));
			$HttpRequest->setMethod(constant("HTTP_METH_GET"));
			try 
            {
                $HttpRequest->send();
            }
            catch (Exception $e)
            {
            	if ($e->innerException)
            		$message = $e->innerException->getMessage();
            	else
            		$message = $e->getMessage();  
            	
            	throw new Exception("HTTPResponseTime Scaling Sensor cannot get value: {$message}");
            }
			
			$retval = round(microtime(true) - $start_time, 2);
			
			return array($retval);
		}
	}