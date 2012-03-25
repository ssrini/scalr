<?
    Core::Load("Data/RRD");
    
    class NETSNMPWatcher
    {
        /**
	     * Watcher Name
	     *
	     * @var string
	     */
	    public $WatcherName = "NET Usage (SNMP)";
		
		const COLOR_INBOUND = "#00cc00";
		const COLOR_OUBOUND = "#0000ff";
				
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
            
            $this->RRD->AddDS(new RRDDS("in", "COUNTER", 600, "U", "21474836480"));
            $this->RRD->AddDS(new RRDDS("out", "COUNTER", 600, "U", "21474836480"));
            
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
            	".1.3.6.1.2.1.2.2.1.10.2",  // BW in
            	".1.3.6.1.2.1.2.2.1.16.2"   // BW out
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
            $in = $matches[0][0];
            $out = $matches[0][1];

            return array($in, $out);
        }
        
    	public function UpdateRRDDatabase($name, $data)
        {
        	$rrddbpath = $this->Path."/{$name}/NETSNMP/db.rrd";
        	
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
        	
        	$graph = new RRDGraph(440, 100, CONFIG::$RRDTOOL_PATH);
			
        	$graph->AddDEF("in", $rrddbpath, "in", "AVERAGE");
			$graph->AddDEF("out", $rrddbpath, "out", "AVERAGE");
			
			$graph->AddCDEF("in_bits", "in,8,*");
			$graph->AddCDEF("out_bits", "out,8,*");
			
			$graph->AddVDEF("in_last", "in_bits,LAST");
            $graph->AddVDEF("in_avg", "in_bits,AVERAGE");
            $graph->AddVDEF("in_max", "in_bits,MAXIMUM");
            
            $graph->AddVDEF("out_last", "out_bits,LAST");
            $graph->AddVDEF("out_avg", "out_bits,AVERAGE");
            $graph->AddVDEF("out_max", "out_bits,MAXIMUM");
            
            $graph->AddComment('<b><tt>           Current   Average   Maximum</tt></b>\\j');
            
			$graph->AddArea("in_bits", self::COLOR_INBOUND, "<tt>In:    </tt>");
            $graph->AddGPrint("in_last", '<tt>  %4.1lf%s</tt>');
            $graph->AddGPrint("in_avg",  '<tt>  %4.1lf%s</tt>');
            $graph->AddGPrint("in_max",  '<tt>  %4.1lf%s</tt>\n');
            
            $graph->AddLine(1, "out_bits", self::COLOR_OUBOUND, "<tt>Out:   </tt>");
            $graph->AddGPrint("out_last", '<tt>  %4.1lf%s</tt>');
            $graph->AddGPrint("out_avg",  '<tt>  %4.1lf%s</tt>');
            $graph->AddGPrint("out_max",  '<tt>  %4.1lf%s</tt>\n');
            
            if (CONFIG::$RRD_DEFAULT_FONT_PATH)
            	$graph->AddFont("DEFAULT", "0", CONFIG::$RRD_DEFAULT_FONT_PATH);
            	
            $dt = date("M j, Y H:i:s");
            	
            $res = $graph->Plot($image_path, $r["start"], $r["end"], 
                            array(
                            		"--step", $r["step"],
                                    "--pango-markup",
                            		"-v", "Bits per second", 
                                    "-t", "Network usage ({$dt})",
                                    "-l", "0", 
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