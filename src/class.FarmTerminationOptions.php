<?
	class FarmTerminationOptions
    {
    	public $RemoveZoneFromDNS = false;
    	public $KeepElasticIPs = true;
    	public $TermOnSyncFail = true;
    	public $KeepEBS = true;
    	
    	function __construct($remove_zone_from_DNS, $keep_elastic_ips, $term_on_sync_fail, $keep_ebs)
    	{
    		$this->RemoveZoneFromDNS = $remove_zone_from_DNS;
    		$this->KeepElasticIPs = $keep_elastic_ips;
    		$this->TermOnSyncFail = $term_on_sync_fail;
    		$this->KeepEBS = $keep_ebs;
    	}
    }
?>