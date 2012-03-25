<?php
  class Scalr_Service_Cloud_GoGrid_CH extends Scalr_Service_Cloud_GoGrid_Connection
  {
 
   	 /**
  	 * This call will add a single load balancer object to your grid
  	 *  
  	 * @name  gridLoadbalancerAdd
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	 public function gridLoadbalancerAdd($args)
	 {
		return $this->request("GET","grid/loadbalancer/add",$args);
	 }
  	 
  	 
  	 /**
  	 * This call will edit the real IP pool membership of a single load balancer object on your grid
  	 *  
  	 * @name  gridLoadbalancerEdit
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	 public function gridLoadbalancerEdit($args)
	 { 		 	
		return $this->request("GET","grid/loadbalancer/edit",$args);
	 }  	 


	 /**
  	 * This call will delete a single load balancer object from your grid.
  	 *  
  	 * @name  gridLoadbalancerEdit
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	 public function gridLoadbalancerDelete($args)
	 { 
	 	return $this->request("GET","grid/loadbalancer/delete",$args);
	 }


  	 /**
  	 * This call will retrieve one or many load balancer objects from your list of load balancers
  	 *
  	 * @name  gridLoadbalancerGet
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	 public function gridLoadbalancerGet($args)
	 {
		return $this->request("GET","grid/loadbalancer/get",$args);	
	 }
  	 
  	 /**
  	 * This call will list all the load balancers in the system
  	 *  
  	 * @name  gridLoadbalancerList
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	 public function gridLoadbalancerList($args = null)
	 {
		return $this->request("GET","grid/loadbalancer/list",$args);
  	 }
  	 
  	  
	/**
	* This is an API method to list options and lookups. 
	* To list all the available lookups, set the parameter lookup to lookups
	* 
	* @name  commonLookupList
	* @param mixed $args
	* @return mixed $response
	*/
	 public function commonLookupList($args = null)
	 {
	 	if(!$args)
	 		$args = array("lookup" => "lookups");
	 			
		return $this->request("GET","common/lookup/list",$args);
	 }

	/**
	* This call will delete a single image from your grid
	* 
	* @name  gridImageDelete
	* @param mixed $args
	* @return mixed $response   
	*/
	public function gridImageDelete($args)
	{
		return $this->request("GET","grid/image/delete",$args);
	}
      
	/**   BETA
	* This call will retrieve one or many server images from the list of server images
	* 
	* @name  gridImageGet
	* @param mixed $args
	* @return mixed $response
	*/
	public function gridImageGet($args)
	{
		return $this->request("GET","grid/image/get",$args);
	}
	
 	
	/**   BETA
	* This call will edit the name and description metadata of a single server image on your grid
	* 
	* @name  gridImageEdit
	* @param mixed $args
	* @return mixed $response
	*/
	public function gridImageEdit($args)
	{
		return $this->request("GET","grid/image/edit",$args);
	}
	
		 
	 /**
  	 * This call will list all the images in the system
  	 * 
  	 * @name  gridImageList
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function gridImageList($args = null)
	{
		return $this->request("GET","grid/image/list",$args);
	}
	  
	 
	/**  BETA
  	 * This call will save a private (visible to only you) server image to your library of available images. 
  	 * The image will be saved from an existing image sandbox
  	 * 
  	 * @name  gridImageSave
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function gridImageSave($args)
	{
		return $this->request("GET","grid/image/save",$args);
	}
	
	
	 /**   BETA
  	 * This call will restore a single image from the trash on your grid
  	 * 
  	 * @name  gridImageRestore
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function gridImageRestore($args)
	{ 	
		return $this->request("GET","grid/image/restore",$args);
	}	 
	
	
	/**
  	 * This call will list the ips available to in your grid.
  	 * With filtering you can see all, unassigned, public, or private ips.
  	 * 
  	 * @name  gridIpList
  	 * @param mixed $args
  	 * @return mixed $response   
  	 */
	public function gridIpList($args = null)
	{	
		return $this->request("GET","grid/ip/list",$args);
	}	
	
	
  	 /**
  	 * his call will list all the jobs in the system for a specified date range.
  	 * The default is the last month
  	 * 
  	 * @name  gridJobList
  	 * @param mixed $args
  	 * @return mixed
  	 */
	public function gridJobList($args = null)
	{
		return $this->request("GET","grid/job/list",$args);
	}


	/**
  	 * This call will add a single server object to your grid
  	 * 
  	 * @name  gridServerAdd
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function gridServerAdd($args)
	{
		return  $this->request("GET","grid/server/add",$args);
	}
	
	
	/**
  	 * This call will delete a single server object from your grid
  	 * 
  	 * @name  gridServerDelete
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function gridServerDelete($args)
	{ 
		return $this->request("GET","grid/server/delete",$args);
	}
	
	
	/**
  	 * This call will retrieve one or many server objects from your list of servers.
  	 * 
  	 * @name  gridServerGet
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function gridServerGet($args)
	{
		return $this->request("GET","grid/server/get",$args);
	}
	
	
  	 /**
  	 * This call will list all the servers in the system
  	 * 
  	 * @name  gridServerList
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function gridServerList($args = null)
	{	
		return  $this->request("GET","grid/server/list",$args);	
	} 

	
	/**
  	 * This call will issue a power command to a server object in your grid
  	 * 
  	 * @name  gridServerPower
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function gridServerPower($args)
	{
		return	$response = $this->request("GET","grid/server/power",$args);
	}
	
	
     /**
     *  retrieve one or many job objects from your list of jobs
     * 
     * @name  gridJobGet
     * @param mixed $id
     * @param mixed $job
     * @param mixed $format
     * @return mixed $response
     */
	public function gridJobGet($args = null)
	{
		return $this->request("GET","grid/job/get",$args);
	}
	 
	 
	 /**
  	 * This call will retrieve a single billing summary object.
  	 *  
  	 * @name  myAccountBillingGet
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	 public function myAccountBillingGet($args = null)
	 {
		return $this->request("GET","myaccount/billing/get",$args);
	 }
	
	/**
  	 * This call will retrieve a single password from your list of passwords
  	 * 
  	 * @name  supportPasswordGet
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function supportPasswordGet($args)
	{
		return $this->request("GET","support/password/get",$args);
	}


	/**
  	 * This call will list all the passwords registered in the system
  	 * 
  	 * @name  supportPasswordList
  	 * @param mixed $args
  	 * @return mixed $response
  	 */
	public function supportPasswordList($args = null)
	{
		return $this->request("GET","support/password/list",$args);
	}
	
  }
?>
