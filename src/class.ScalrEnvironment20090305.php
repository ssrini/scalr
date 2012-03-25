<?

	class ScalrEnvironment20090305 extends ScalrEnvironment20081216
    {    	
    	protected function ListRoleParams()
    	{
    		$ResponseDOMDocument = parent::ListRoleParams();

    		$DBFarmRole = $this->DBServer->GetFarmRoleObject();
    		
    		if ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL))
    		{
    			$DOMXPath = new DOMXPath($ResponseDOMDocument);
    			$ParamsDOMNode = $DOMXPath->query("//params")->item(0);
    			
    			$mysql_options = array(
    				"mysql_data_storage_engine" 	=> $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE),
    				"mysql_master_ebs_volume_id" 	=> $DBFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID),

    			
    			/*
    				"mysql_root_user" => "",
    				"mysql_root_pass" => "",
    				"mysql_"
    			*/
    			);
    			
    			foreach ($mysql_options as $k=>$v)
    			{
    				if (!$this->GetArg("name") || $this->GetArg("name") == $k)
					{
	    				$ParamDOMNode = $ResponseDOMDocument->createElement("param");
	    				$ParamDOMNode->setAttribute("name", $k);
	    				
	    				$ValueDomNode = $ResponseDOMDocument->createElement("value");
	    				$ValueDomNode->appendChild($ResponseDOMDocument->createCDATASection($v));
	    				
	    				$ParamDOMNode->appendChild($ValueDomNode);
	    				$ParamsDOMNode->appendChild($ParamDOMNode);
					}
    			}
    		}
    		    		
    		return $ResponseDOMDocument;
    	}
    }
?>