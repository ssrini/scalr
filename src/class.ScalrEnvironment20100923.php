<?

	class ScalrEnvironment20100923 extends ScalrEnvironment20090305
    {    	
    	protected function GetScalingMetrics()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();

    		$metricsNode = $ResponseDOMDocument->createElement("metrics");
    		
    		$dbFarmRole = $this->DBServer->GetFarmRoleObject();
    		$scalingManager = new Scalr_Scaling_Manager($dbFarmRole);
			foreach($scalingManager->getFarmRoleMetrics() as $farmRoleScalingMetric)
    		{
    			$scalingMetric = $farmRoleScalingMetric->getMetric();
    			
    			if ($scalingMetric->clientId == 0)
    				continue;

    			$metric = $ResponseDOMDocument->createElement("metric");
    			$metric->setAttribute("id", $scalingMetric->id);
    			$metric->setAttribute("name", $scalingMetric->name);
    			
    			$metricFilePath = $ResponseDOMDocument->createElement("path", $scalingMetric->filePath);
    			$metricRM = $ResponseDOMDocument->createElement("retrieve-method", $scalingMetric->retrieveMethod);
    			
    			$metric->appendChild($metricFilePath);
    			$metric->appendChild($metricRM);
    					
    			$metricsNode->appendChild($metric);
    		}
    		
    		$ResponseDOMDocument->documentElement->appendChild($metricsNode);
    		
    		return $ResponseDOMDocument;
    	}
    	
    	protected function GetServiceConfiguration()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$dbFarmRole = $this->DBServer->GetFarmRoleObject();
    		$dbRole = $dbFarmRole->GetRoleObject();
    		
    		foreach ($dbRole->getBehaviors() as $behavior)
    		{
    			$settingsNode = $ResponseDOMDocument->createElement("settings");
    			
    			$serviceConfiguration = $dbFarmRole->GetServiceConfiguration($behavior);
    			if ($serviceConfiguration instanceOf Scalr_ServiceConfiguration)
    			{
    				$settingsNode->setAttribute("preset-name", $serviceConfiguration->name);
    				$settingsNode->setAttribute("restart-service", 1);
    				$settingsNode->setAttribute("behaviour", $behavior);
    				foreach ($serviceConfiguration->getParameters() as $param)
    				{
    					$setting = $ResponseDOMDocument->createElement("setting", $param->getValue());
    					$setting->setAttribute("key", $param->getName());
    					$settingsNode->appendChild($setting);
    				}
    			}
    			else
    			{
    				$settingsNode->setAttribute("preset-name", "default");
    				$settingsNode->setAttribute("restart-service", "0");
    				$settingsNode->setAttribute("behaviour", $behavior);
    			}
    			
    			$ResponseDOMDocument->documentElement->appendChild($settingsNode);
    		}
    		
    		return $ResponseDOMDocument;
    	}
    }
?>