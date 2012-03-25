<?
    Core::Load("Data/RRD");
    
    class CPUSNMPWatcher
    {
        /**
	     * Watcher Name
	     *
	     * @var string
	     */
	    public $WatcherName = "CPU Usage (SNMP)";
		
		const COLOR_CPU_USER = "#eacc00";
		const COLOR_CPU_SYST = "#ea8f00";
		const COLOR_CPU_NICE = "#ff3932";
		const COLOR_CPU_IDLE = "#fafdce";
		
		private $RRD;
		
		/**
		 * Constructor
		 *
		 */
    	function __construct($SNMPTree, $path)
		{
		      $this->Path = $path;
		      $this->SNMPTree = $SNMPTree;
		}
        
        /**
         * This method is called after watcher assigned to node
         *
         */
        public function CreateDatabase($rrddbpath)
        {            
            @mkdir(dirname($rrddbpath), 0777, true);
            
            $this->RRD = new RRD($rrddbpath);
            
            $this->RRD->AddDS(new RRDDS("user", "COUNTER", 600));
            $this->RRD->AddDS(new RRDDS("system", "COUNTER", 600));
            $this->RRD->AddDS(new RRDDS("nice", "COUNTER", 600));
            $this->RRD->AddDS(new RRDDS("idle", "COUNTER", 600));
            
            $this->RRD->AddRRA(new RRA("AVERAGE", array(0.5, 1, 800)));
            $this->RRD->AddRRA(new RRA("AVERAGE", array(0.5, 6, 800)));
            $this->RRD->AddRRA(new RRA("AVERAGE", array(0.5, 24, 800)));
            $this->RRD->AddRRA(new RRA("AVERAGE", array(0.5, 288, 800)));

            $this->RRD->AddRRA(new RRA("MAX", array(0.5, 1, 800)));
            $this->RRD->AddRRA(new RRA("MAX", array(0.5, 6, 800)));
            $this->RRD->AddRRA(new RRA("MAX", array(0.5, 24, 800)));
            $this->RRD->AddRRA(new RRA("MAX", array(0.5, 288, 800)));
            
            $this->RRD->AddRRA(new RRA("LAST", array(0.5, 1, 800)));
            $this->RRD->AddRRA(new RRA("LAST", array(0.5, 6, 800)));
            $this->RRD->AddRRA(new RRA("LAST", array(0.5, 24, 800)));
            $this->RRD->AddRRA(new RRA("LAST", array(0.5, 288, 800)));
            
            $res = $this->RRD->Create("-1m", 180);
            
            @chmod($rrddbpath, 0777);
            
            return $res;
        }

    	public function GetOIDs()
        {
            //
            // Add data to rrd
            //    
            return array(
            	".1.3.6.1.4.1.2021.11.50.0", // User
            	".1.3.6.1.4.1.2021.11.52.0", // System
            	".1.3.6.1.4.1.2021.11.51.0", // Nice
            	".1.3.6.1.4.1.2021.11.53.0" // Idle
           	);
        }
        
        /**
         * Retrieve data from node
         *
         */
        public function RetreiveData($name)
        {
            //
            // Add data to rrd
            //    
            preg_match_all("/[0-9]+/si", $this->SNMPTree->Get($this->GetOIDs()), $matches);
            $CPURawUser = $matches[0][0];
            $CPURawSystem = $matches[0][1];
            $CPURawNice = $matches[0][2];
            $CPURawIdle = $matches[0][3];
			
            return array($CPURawUser, $CPURawSystem, $CPURawNice, $CPURawIdle);
        }
        
    	public function UpdateRRDDatabase($name, $data)
        {
        	$rrddbpath = $this->Path."/{$name}/CPUSNMP/db.rrd";
        	
        	if (!file_exists($rrddbpath))
        		$this->CreateDatabase($rrddbpath);
        	
        	if (!$this->RRD)
                $this->RRD = new RRD($rrddbpath);
  
            $data = array_map("ceil", $data);
                
        	$this->RRD->Update($data);
        }
        
        /**
         * Plot graphic
         *
         * @param integer $serverid
         */
        public static function PlotGraphic($rrddbpath, $image_path, $r)
        {		
        	
        	$graph = new RRDGraph(440, 160, CONFIG::$RRDTOOL_PATH);
			
        	$graph->AddDEF("a", $rrddbpath, "user", "AVERAGE");
			$graph->AddDEF("b", $rrddbpath, "system", "AVERAGE");
			$graph->AddDEF("c", $rrddbpath, "nice", "AVERAGE");
			$graph->AddDEF("d", $rrddbpath, "idle", "AVERAGE");

			        				
            $graph->AddCDEF("total", "a,b,c,d,+,+,+");
            
            $graph->AddCDEF("a_perc", "a,total,/,100,*");
            $graph->AddVDEF("a_perc_last", "a_perc,LAST");
            $graph->AddVDEF("a_perc_avg", "a_perc,AVERAGE");
            $graph->AddVDEF("a_perc_max", "a_perc,MAXIMUM");
            
            $graph->AddCDEF("b_perc", "b,total,/,100,*");
            $graph->AddVDEF("b_perc_last", "b_perc,LAST");
            $graph->AddVDEF("b_perc_avg", "b_perc,AVERAGE");
            $graph->AddVDEF("b_perc_max", "b_perc,MAXIMUM");
            
            $graph->AddCDEF("c_perc", "c,total,/,100,*");
            $graph->AddVDEF("c_perc_last", "c_perc,LAST");
            $graph->AddVDEF("c_perc_avg", "c_perc,AVERAGE");
            $graph->AddVDEF("c_perc_max", "c_perc,MAXIMUM");
            
            $graph->AddCDEF("d_perc", "d,total,/,100,*");
            $graph->AddVDEF("d_perc_last", "d_perc,LAST");
            $graph->AddVDEF("d_perc_avg", "d_perc,AVERAGE");
            $graph->AddVDEF("d_perc_max", "d_perc,MAXIMUM");
			
            $graph->AddComment('<b><tt>               Current    Average    Maximum</tt></b>\\j');
            
            $graph->AddArea("a_perc", self::COLOR_CPU_USER, "<tt>user    </tt>");
            $graph->AddGPrint("a_perc_last", '<tt>    %3.0lf%%</tt>');
            $graph->AddGPrint("a_perc_avg",  '<tt>     %3.0lf%%</tt>');
            $graph->AddGPrint("a_perc_max",  '<tt>     %3.0lf%%</tt>\\n');
            
            $graph->AddArea("b_perc", self::COLOR_CPU_SYST, "<tt>system  </tt>", "STACK");
            $graph->AddGPrint("b_perc_last", '<tt>    %3.0lf%%</tt>');
            $graph->AddGPrint("b_perc_avg",  '<tt>     %3.0lf%%</tt>');
            $graph->AddGPrint("b_perc_max",  '<tt>     %3.0lf%%</tt>\\n');
            
            $graph->AddArea("c_perc", self::COLOR_CPU_NICE, "<tt>nice    </tt>", "STACK");
            $graph->AddGPrint("c_perc_last", '<tt>    %3.0lf%%</tt>');
            $graph->AddGPrint("c_perc_avg",  '<tt>     %3.0lf%%</tt>');
            $graph->AddGPrint("c_perc_max",  '<tt>     %3.0lf%%</tt>\\n');
            
            $graph->AddArea("d_perc", self::COLOR_CPU_IDLE, "<tt>idle    </tt>", "STACK");
            $graph->AddGPrint("d_perc_last", '<tt>    %3.0lf%%</tt>');
            $graph->AddGPrint("d_perc_avg",  '<tt>     %3.0lf%%</tt>');
            $graph->AddGPrint("d_perc_max",  '<tt>     %3.0lf%%</tt>\\n');
            
            if (CONFIG::$RRD_DEFAULT_FONT_PATH)
            	$graph->AddFont("DEFAULT", "0", CONFIG::$RRD_DEFAULT_FONT_PATH);
            	
            $dt = date("M j, Y H:i:s");
            	
            $res = $graph->Plot($image_path, $r["start"], $r["end"], 
                            array(
                            		"--step", $r["step"],
                            		"--pango-markup",
                            		"-v", "Percent CPU Utilization", 
                                    "-t", "CPU Utilization ({$dt})",
                                    "-u", "100", 
                                    "--alt-autoscale-max",
                            		"--alt-autoscale-min",
                                    "--rigid",
                            		"--no-gridfit",
                            		"--slope-mode",
                            		"--x-grid", $r["x_grid"]
                                 )
                         );
         
             return true;
        }
    }
?>