<?
    Core::Load("Data/RRD");
    
    class LASNMPWatcher
    {
        /**
	     * Watcher Name
	     *
	     * @var string
	     */
	    public $WatcherName = "Load averages (SNMP)";
		
		private $RRD;
		
		
		const COLOR_LA1 = "#FF0000";
		const COLOR_LA5 = "#0000FF";
		const COLOR_LA15 = "#00FF00";
		
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
            
            $this->RRD->AddDS(new RRDDS("la1", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("la5", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("la15", "GAUGE", 600));
            
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
            return array(
            	".1.3.6.1.4.1.2021.10.1.3.1", // 1 min 
            	".1.3.6.1.4.1.2021.10.1.3.2", // 5 mins
            	".1.3.6.1.4.1.2021.10.1.3.3" // 15 mins
           	);
		}
        
        /**
         * Retrieve data from node
         *
         */
        public function RetreiveData($name)
        {
            preg_match_all("/[0-9\.]+/si", $this->SNMPTree->Get($this->GetOIDs()), $matches);
            $La1 = $matches[0][0];            
            $La5 = $matches[0][1];
            $La15 = $matches[0][2];
            
            return array($La1, $La5, $La15);
        }
        
    	public function UpdateRRDDatabase($name, $data)
        {
        	$rrddbpath = $this->Path."/{$name}/LASNMP/db.rrd";
        	
        	if (!file_exists($rrddbpath))
        		$this->CreateDatabase($rrddbpath);
        	
        	if (!$this->RRD)
                $this->RRD = new RRD($rrddbpath);
  
        	$this->RRD->Update($data);
        }
        
        /**
         * Plot graphic
         *
         * @param integer $serverid
         */
        public static function PlotGraphic($rrddbpath, $image_path, $r)
        {

        	$graph = new RRDGraph(440, 140, CONFIG::$RRDTOOL_PATH);
			$graph->AddDEF("la1", $rrddbpath, "la1", "AVERAGE");
			
			$graph->AddVDEF("la1_min", "la1,MINIMUM");
            $graph->AddVDEF("la1_last", "la1,LAST");
            $graph->AddVDEF("la1_avg", "la1,AVERAGE");
            $graph->AddVDEF("la1_max", "la1,MAXIMUM");
			
			$graph->AddDEF("la5", $rrddbpath, "la5", "AVERAGE");
			
			$graph->AddVDEF("la5_min", "la5,MINIMUM");
            $graph->AddVDEF("la5_last", "la5,LAST");
            $graph->AddVDEF("la5_avg", "la5,AVERAGE");
            $graph->AddVDEF("la5_max", "la5,MAXIMUM");
			
			$graph->AddDEF("la15", $rrddbpath, "la15", "AVERAGE");
			
			$graph->AddVDEF("la15_min", "la15,MINIMUM");
            $graph->AddVDEF("la15_last", "la15,LAST");
            $graph->AddVDEF("la15_avg", "la15,AVERAGE");
            $graph->AddVDEF("la15_max", "la15,MAXIMUM");
			
            $graph->AddComment('<b><tt>                              Minimum     Current     Average     Maximum</tt></b>\\j');
            
            $graph->AddArea("la15", self::COLOR_LA15, "<tt>15 Minutes system load:</tt>");
            $graph->AddGPrint("la15_min",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la15_last", '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la15_avg",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la15_max",  '<tt>%3.2lf</tt>\\j');
			
            $graph->AddLine(1, "la5", self::COLOR_LA5, "<tt> 5 Minutes system load:</tt>");
            $graph->AddGPrint("la5_min",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la5_last", '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la5_avg",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la5_max",  '<tt>%3.2lf</tt>\\j');
            
			$graph->AddLine(1, "la1", self::COLOR_LA1, "<tt> 1 Minute  system load:</tt>");
            $graph->AddGPrint("la1_min",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la1_last", '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la1_avg",  '<tt>%3.2lf</tt>');
            $graph->AddGPrint("la1_max",  '<tt>%3.2lf</tt>\\j');          

            if (CONFIG::$RRD_DEFAULT_FONT_PATH)
            	$graph->AddFont("DEFAULT", "0", CONFIG::$RRD_DEFAULT_FONT_PATH);
            	
            $dt = date("M j, Y H:i:s");
            	
            $res = $graph->Plot($image_path, $r["start"], $r["end"], 
                            array(
                            		"--step", $r["step"],
                            		"--pango-markup",
                            		"-v", "Load averages", 
                                    "-t", "Load averages ({$dt})",
                                    "-l", "0",
									"--alt-autoscale-max",
                            		"--alt-autoscale-min",
                                    "--rigid",
                            		"--no-gridfit",
                            		"--slope-mode",
									"--alt-y-grid",
									"-X0",
                            		"--x-grid", $r["x_grid"]
                                 )
                        );
         
             return true;
        }
    }
?>
