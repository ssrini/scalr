<?
    try
    {
	    require(dirname(__FILE__)."/../src/prepend.inc.php");
	    
    	if ($req_FarmID && $req_Hash)
	    {
	        $farm_id = (int)$req_FarmID;
	        $hash = preg_replace("/[^A-Za-z0-9]+/", "", $req_Hash);
	        
	        $Logger->warn("Instance={$req_InstanceID} from FarmID={$farm_id} with Hash={$hash} trying to view option '{$req_option}'");
	                
	        $farminfo = $db->GetRow("SELECT * FROM farms WHERE id=? AND hash=?", array($farm_id, $hash));
	        	        
	        $option = explode(".", $req_option);
	        	        
			// Parse additional data
	    	$chunks = explode(";", $req_Data);
			foreach ($chunks as $chunk)
			{
				$dt = explode(":", $chunk);
				$data[$dt[0]] = trim($dt[1]);
			}
			
			ob_start();
	        if ($farminfo)
	        {
	            switch ($option[0])
	            {
	       			case "db":
	                    
	                    $rolename = $option[1];
	                    
	                    $DBFarm = DBFarm::LoadByID($farm_id);
	                    $master = $DBFarm->GetMySQLInstances(true);
	                    $master = $master[0];

	                    switch($option[2])
	                    {
	                    	case "role":
	                            
	                            if ($master && $master->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_ID) == $req_InstanceID)
	                            	print "master";
	                            elseif (!$master)
	                            {
	                                
	                            	$DBServer = DBServer::LoadByPropertyValue(EC2_SERVER_PROPERTIES::INSTANCE_ID, $req_InstanceID);
	                            	$DBServer->SetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER, true);
	                            	
	                                print "master";
	                            }
	                            else 
	                                print "slave";
	                            
	                        break;
	                        
	                        case "master":
	                            
	                            switch($option[3])
	                            {
	                                case "ip":
	                                
	                                    print $master->remoteIp;   
	                                
	                                break;
	                            }
	                            
	                        break;
	                    }
	                    
	                    break;
	            }
	        }
	        $contents = ob_get_contents();
	        ob_end_clean();
	        
	        $Logger->info("config_opts.php output: {$contents}");
	        
	        print $contents;
	        
	        exit();
	    }
    }
    catch(Exception $e)
    {
    	header("HTTP/1.0 500 Internal Server Error");
    	die($e->getMessage());
    }
?>