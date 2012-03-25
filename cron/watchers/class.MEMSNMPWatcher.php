<?
    Core::Load("Data/RRD");
    
    class MEMSNMPWatcher
    {
		private $RRD;
				
		/**
	     * Watcher Name
	     *
	     * @var string
	     */
	    public $WatcherName = "RAM Usage (SNMP)";
		
		const COLOR_MEM_SHRD = "#00FFFF";
		const COLOR_MEM_BUFF = "#3399FF";
		const COLOR_MEM_CACH = "#0000FF";
		const COLOR_MEM_FREE = "#99FF00";
		const COLOR_MEM_REAL = "#00CC00";
		const COLOR_MEM_SWAP = "#FF0000";
		
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
            
            $this->RRD->AddDS(new RRDDS("swap", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("swapavail", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("total", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("avail", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("free", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("shared", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("buffer", "GAUGE", 600));
            $this->RRD->AddDS(new RRDDS("cached", "GAUGE", 600));
            
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
            	".1.3.6.1.4.1.2021.4.3.0", // Swap
            	".1.3.6.1.4.1.2021.4.4.0", // SwapAvail
            	".1.3.6.1.4.1.2021.4.5.0", // Total
            	".1.3.6.1.4.1.2021.4.6.0", // Avail
            	".1.3.6.1.4.1.2021.4.11.0", // Free
            	".1.3.6.1.4.1.2021.4.13.0", // Shared
            	".1.3.6.1.4.1.2021.4.14.0", // Buffer
            	".1.3.6.1.4.1.2021.4.15.0" // Cached
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
            
            
            $MEMSwap = $matches[0][0];
            $MEMSwapAvail = $matches[0][1];
            $MEMTotal = $matches[0][2];
            $MEMAvail = $matches[0][3];
			$MEMFree = $matches[0][4];
            $MEMShared = $matches[0][5];
            $MEMBuffer = $matches[0][6];
            $MEMCached = $matches[0][7];
            
            $data = array($MEMSwap, $MEMSwapAvail, $MEMTotal, $MEMAvail, $MEMFree, $MEMShared, $MEMBuffer, $MEMCached);
            return $data;
        }
        
    	public function UpdateRRDDatabase($name, $data)
        {
        	$rrddbpath = $this->Path."/{$name}/MEMSNMP/db.rrd";
        	
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
        	
        	$graph = new RRDGraph(440, 180, CONFIG::$RRDTOOL_PATH);
        	
			$graph->AddDEF("mem1", $rrddbpath, "swap", "AVERAGE");
			$graph->AddDEF("mem2", $rrddbpath, "swapavail", "AVERAGE");
			$graph->AddDEF("mem3", $rrddbpath, "total", "AVERAGE");
			$graph->AddDEF("mem4", $rrddbpath, "avail", "AVERAGE");
			$graph->AddDEF("mem5", $rrddbpath, "free", "AVERAGE");
			$graph->AddDEF("mem6", $rrddbpath, "shared", "AVERAGE");
			$graph->AddDEF("mem7", $rrddbpath, "buffer", "AVERAGE");
			$graph->AddDEF("mem8", $rrddbpath, "cached", "AVERAGE");
            
            $graph->AddCDEF("swap_total", "mem1,1024,*");
            $graph->AddVDEF("swap_total_min", "swap_total,MINIMUM");
            $graph->AddVDEF("swap_total_last", "swap_total,LAST");
            $graph->AddVDEF("swap_total_avg", "swap_total,AVERAGE");
            $graph->AddVDEF("swap_total_max", "swap_total,MAXIMUM");
            
            
            $graph->AddCDEF("swap_avail", "mem2,1024,*");
            $graph->AddVDEF("swap_avail_tot", "swap_avail,LAST");
            $graph->AddVDEF("swap_avail_min", "swap_avail,MINIMUM");
            $graph->AddVDEF("swap_avail_last", "swap_avail,LAST");
            $graph->AddVDEF("swap_avail_avg", "swap_avail,AVERAGE");
            $graph->AddVDEF("swap_avail_max", "swap_avail,MAXIMUM");
            
            $graph->AddCDEF("swap_used", "swap_total,swap_avail,-");
            $graph->AddVDEF("swap_used_min", "swap_used,MINIMUM");
            $graph->AddVDEF("swap_used_last", "swap_used,LAST");
            $graph->AddVDEF("swap_used_avg", "swap_used,AVERAGE");
            $graph->AddVDEF("swap_used_max", "swap_used,MAXIMUM");
            
            
            $graph->AddCDEF("mem_total", "mem3,1024,*");
            $graph->AddVDEF("mem_total_min", "mem_total,MINIMUM");
            $graph->AddVDEF("mem_total_last", "mem_total,LAST");
            $graph->AddVDEF("mem_total_avg", "mem_total,AVERAGE");
            $graph->AddVDEF("mem_total_max", "mem_total,MAXIMUM");
            
            $graph->AddCDEF("mem_avail", "mem4,1024,*");
            $graph->AddVDEF("mem_avail_min", "mem_avail,MINIMUM");
            $graph->AddVDEF("mem_avail_last", "mem_avail,LAST");
            $graph->AddVDEF("mem_avail_avg", "mem_avail,AVERAGE");
            $graph->AddVDEF("mem_avail_max", "mem_avail,MAXIMUM");
            
            $graph->AddCDEF("mem_free", "mem5,1024,*");
            $graph->AddVDEF("mem_free_min", "mem_free,MINIMUM");
            $graph->AddVDEF("mem_free_last", "mem_free,LAST");
            $graph->AddVDEF("mem_free_avg", "mem_free,AVERAGE");
            $graph->AddVDEF("mem_free_max", "mem_free,MAXIMUM");
            
            $graph->AddCDEF("mem_shared", "mem6,1024,*");
            $graph->AddVDEF("mem_shared_min", "mem_shared,MINIMUM");
            $graph->AddVDEF("mem_shared_last", "mem_shared,LAST");
            $graph->AddVDEF("mem_shared_avg", "mem_shared,AVERAGE");
            $graph->AddVDEF("mem_shared_max", "mem_shared,MAXIMUM");
            
            $graph->AddCDEF("mem_buffer", "mem7,1024,*");
            $graph->AddVDEF("mem_buffer_min", "mem_buffer,MINIMUM");
            $graph->AddVDEF("mem_buffer_last", "mem_buffer,LAST");
            $graph->AddVDEF("mem_buffer_avg", "mem_buffer,AVERAGE");
            $graph->AddVDEF("mem_buffer_max", "mem_buffer,MAXIMUM");
            
            $graph->AddCDEF("mem_cached", "mem8,1024,*");
            $graph->AddVDEF("mem_cached_min", "mem_cached,MINIMUM");
            $graph->AddVDEF("mem_cached_last", "mem_cached,LAST");
            $graph->AddVDEF("mem_cached_avg", "mem_cached,AVERAGE");
            $graph->AddVDEF("mem_cached_max", "mem_cached,MAXIMUM");
            
            
            $graph->AddComment('<b><tt>                      Minimum       Current       Average       Maximum</tt></b>\\j');
            
            $graph->AddArea("mem_shared", self::COLOR_MEM_SHRD, "<tt>Shared        </tt>");
            $graph->AddGPrint("swap_total_min",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("swap_total_last", '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("swap_total_avg",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("swap_total_max",  '<tt>%4.1lf %s</tt>\\j');
            
            $graph->AddArea("mem_buffer", self::COLOR_MEM_BUFF, "<tt>Buffer        </tt>", "STACK");
            $graph->AddGPrint("mem_buffer_min",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_buffer_last", '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_buffer_avg",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_buffer_max",  '<tt>%4.1lf %s</tt>\\j');
            
            $graph->AddArea("mem_cached", self::COLOR_MEM_CACH, "<tt>Cached        </tt>", "STACK");
            $graph->AddGPrint("mem_cached_min",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_cached_last", '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_cached_avg",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_cached_max",  '<tt>%4.1lf %s</tt>\\j');
            
            
            $graph->AddArea("mem_free", self::COLOR_MEM_FREE,   "<tt>Free          </tt>", "STACK");
            $graph->AddGPrint("mem_free_min",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_free_last", '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_free_avg",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_free_max",  '<tt>%4.1lf %s</tt>\\j');
            
            $graph->AddArea("mem_avail", self::COLOR_MEM_REAL,  "<tt>Real          </tt>", "STACK");
            $graph->AddGPrint("mem_avail_min",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_avail_last", '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_avail_avg",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("mem_avail_max",  '<tt>%4.1lf %s</tt>\\j');
            //$graph->AddGPrint("swap_avail_tot", '       Mem Total: %4.1lf%S\\j');
            
           $graph->AddLine(1, "swap_used", self::COLOR_MEM_SWAP,"<tt>Swap In Use   </tt>");
            $graph->AddGPrint("swap_used_min",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("swap_used_last", '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("swap_used_avg",  '<tt>%4.1lf %s</tt>');
            $graph->AddGPrint("swap_used_max",  '<tt>%4.1lf %s</tt>\\j');
           // $graph->AddGPrint("swap_used_last", '            Swap Total:%4.1lf%S\\j');
            
            if (CONFIG::$RRD_DEFAULT_FONT_PATH)
            	$graph->AddFont("DEFAULT", "0", CONFIG::$RRD_DEFAULT_FONT_PATH);
            	
            $dt = date("M j, Y H:i:s");
            	
            //
            // Plot graphics
            //   
            $res = $graph->Plot($image_path, $r["start"], $r["end"], 
                            array(
                            		"--step", $r["step"],
                            		"--pango-markup",
                            		"-v", "Memory Usage", 
                                    "-t", "Memory Usage ({$dt})",
                                    "-l", "0", 
                                    "-b", "1024",
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