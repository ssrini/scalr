<?
    require(dirname(__FILE__)."/../../src/prepend.inc.php");
    Core::Load("IO/PCNTL/interface.IProcess.php"); 

    require_once(dirname(__FILE__)."/../../cron/watchers/class.SNMPWatcher.php");
	require_once(dirname(__FILE__)."/../../cron/watchers/class.CPUSNMPWatcher.php");
	require_once(dirname(__FILE__)."/../../cron/watchers/class.LASNMPWatcher.php");
	require_once(dirname(__FILE__)."/../../cron/watchers/class.MEMSNMPWatcher.php");
	require_once(dirname(__FILE__)."/../../cron/watchers/class.NETSNMPWatcher.php");
    
    if ($req_task == "get_stats_image_url")
    {    	
    	$farmid = (int)$req_farmid;
    	$watchername = $req_watchername;
    	$graph_type = $req_graph_type;
    	$role_name = ($req_role_name) ? $req_role_name : $req_role;
    	
    	if ($req_version == 2)
    	{
    		if ($role_name != 'FARM' && !stristr($role_name, "INSTANCE_"))
    			$role_name = "FR_{$role_name}";
    	}
    	
    	$farminfo = $db->GetRow("SELECT status, id, env_id FROM farms WHERE id=?", array($farmid));
    	if ($farminfo["status"] != FARM_STATUS::RUNNING)
    		$result = array("type" => "error", "msg" => _("Statistics not available for terminated farm"));
    	else
    	{
	    	if ($farminfo['clientid'] != 0)
	    	{
	    		define("SCALR_SERVER_TZ", date("T"));
	    		
	    		$env = Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadById($farminfo['env_id']);
    			$tz = $env->getPlatformConfigValue(ENVIRONMENT_SETTINGS::TIMEZONE);
	    		if ($tz)
		    		date_default_timezone_set($tz);
	    	}
    		
    		$graph_info = GetGraphicInfo($graph_type);
	
	    	$image_path = APPPATH."/cache/stats/{$farmid}/{$role_name}.{$watchername}.{$graph_type}.gif";
	    	
	    	$farm_rrddb_dir = CONFIG::$RRD_DB_DIR."/{$farminfo['id']}";
	    	$rrddbpath = "{$farm_rrddb_dir}/{$role_name}/{$watchername}/db.rrd";
	    	
	    	CONFIG::$RRD_GRAPH_STORAGE_TYPE = RRD_STORAGE_TYPE::LOCAL_FS;
	    	
	    	if (file_exists($rrddbpath))
	    	{
	        	try
	        	{
	    			GenerateGraph($farmid, $role_name, $rrddbpath, $watchername, $graph_type, $image_path);
	    			
	    			$url = str_replace(array("%fid%","%rn%","%wn%"), array($farmid, $role_name, $watchername), CONFIG::$RRD_STATS_URL);
					$url = "{$url}{$graph_type}.gif";
	    			
	    			$result = array("type" => "ok", "msg" => $url);
	        	}
	        	catch(Exception $e)
	        	{
	        		$result = array("type" => "error", "msg" => $e->getMessage());
	        	}
	    	}
	    	else
	    		$result = array("type" => "error", "msg" => _("Statistics not available yet"));
    	}
    }
    
    print json_encode($result);
    
    function GenerateGraph($farmid, $role_name, $rrddbpath, $watchername, $graph_type)
	{
        if (CONFIG::$RRD_GRAPH_STORAGE_TYPE == RRD_STORAGE_TYPE::AMAZON_S3)
        	$image_path = APPPATH."/cache/stats/{$farmid}/{$role_name}.{$watchername}.{$graph_type}.gif";
        else
        	$image_path = CONFIG::$RRD_GRAPH_STORAGE_PATH."/{$farmid}/{$role_name}_{$watchername}.{$graph_type}.gif";
        
        @mkdir(dirname($image_path), 0777, true);

        $graph_info = GetGraphicInfo($graph_type);
        
        if (file_exists($image_path))
        {
        	clearstatcache();
	        $time = filemtime($image_path);
	        
	        if ($time > time()-$graph_info['update_every'])
	        	return;
        }
        
		// Plot daily graphic
		try
		{
            $Reflect = new ReflectionClass("{$watchername}Watcher");
            $PlotGraphicMethod = $Reflect->getMethod("PlotGraphic");
            $PlotGraphicMethod->invoke(NULL, $rrddbpath, $image_path, $graph_info);
		}
		catch(Exception $e)
		{
            Logger::getLogger('STATS')->fatal("Cannot plot graphic: {$e->getMessage()}");
            return;
		}
	}
        
	function GetGraphicInfo($type)
	{
        switch($type)
		{
            case GRAPH_TYPE::DAILY:
            	$r = array(
            		"start" => "-1d5min", 
            		"end" => "-5min", 
            		"step" => 180, 
            		"update_every" => 600,
            		"x_grid" => "HOUR:1:HOUR:2:HOUR:2:0:%H"
            	);
            	break;
            case GRAPH_TYPE::WEEKLY:
            	$r = array(
            		"start" => "-1wk5min", 
            		"end" => "-5min", 
            		"step" => 1800, 
            		"update_every" => 7200,
            		"x_grid" => "HOUR:12:HOUR:24:HOUR:24:0:%a"
            	);
            	break;
            case GRAPH_TYPE::MONTHLY:
            	$r = array(
            		"start" => "-1mon5min", 
            		"end" => "-5min", 
            		"step" => 7200, 
            		"update_every" => 43200,
            		"x_grid" => "DAY:2:WEEK:1:WEEK:1:0:week %V"
            	);
            	break;
            case GRAPH_TYPE::YEARLY:
            	$r = array(
            		"start" => "-1y", 
            		"end" => false, 
            		"step" => 86400, 
            		"update_every" => 86400,
            		"x_grid" => "MONTH:1:MONTH:1:MONTH:1:0:%b"
            	);
            	break;
		}
            
		return $r;
	}
?>