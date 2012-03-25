<?php
class Scalr_Service_GoogleAnalytics_Parser
{
	public $campaignSource;
  	public $campaignName;
    public $campaignMedium;
  	public $campaignContent;
  	public $campaignTerm;
 
    public $firstVisit;
    public $previousVisit;
    public $currentVisitStarted;
    public $timesVisited;
 
    private $utma;
    private $utmz;
    
	public function __construct() {
		$this->utmz = $_COOKIE["__utmz"];
		$this->utma = $_COOKIE["__utma"];
		
		$this->parse();
	}
	
	private function parse()
	{
		list($domainHash, $timestamp, $sessionNumber, $campaignNumer, $campaignData) = split('[\.]', $this->utmz);
		
		$campaign_data = parse_str(strtr($campaignData, "|", "&amp;"));
		$this->campaignSource = $utmcsr;
		$this->campaignName = $utmccn;
		$this->campaignMedium = $utmcmd;
		$this->campaignTerm = $utmctr;
		$this->campaignContent = $utmcct;
		  
		if($utmgclid) {
    		$this->campaignSource = "google";
    		$this->campaignName = "";
    		$this->campaignMedium = "cpc";
    		$this->campaignContent = "";
    		$this->campaignTerm = $utmctr;
  		}
  	
  		list($domainHash, $randomId, $timeInitialVisit, $timeBeginningPreviousVisit, $timeBeginningCurrentVisit, $sessionCounter) = split('[\.]', $this->utma);

  		$this->firstVisit = $timeInitialVisit;
  		$this->previousVisit = $timeBeginningPreviousVisit;
  		$this->timesVisited = $sessionCounter;
	}
}