<?php

	class Scalr_Service_Cloud_Openstack_v1_1_Client extends Scalr_Service_Cloud_Openstack_Connection
	{
		protected $apiVersion = 'v1.1';
		
		public function __construct ($authUser, $authKey, $apiUrl, $project = "")
		{
			parent::__construct($authUser, $authKey, $apiUrl, $project);
		}
		
	 	/**
        * deletes an image from the system.
        * 
        * @name  deleteImage
        * @param mixed $imageId 
        */
        public function imageDelete($imageId)
        {
			$this->request("images/{$imageId}", "DELETE");
        }
		
		/**
        * returns details of the specified image
        * 
        * @name getImageDetails
        * @param mixed $imageId
        * @return mixed  $response
        */
        public function imageGetDetails($imageId)
        {
			return $this->request("images/{$imageId}");
        }
		
		/**
        * List available images
        * 
        * @name  listImages
        * @param mixed $detail
        * @return mixed $response
        */
        public function imagesList($details = true)
        {
        	$detailsUri = ($details) ? "/detail" : "";

			return $response = $this->request("images{$detailsUri}");
        }
		
		/**
        * This operation returns details of the specified image
        * 
        * @name  getFlavorDetails
        * @param mixed $flavorId
        * @return mixed $response
        */
        public function flavorGetDetails($flavorId)
        {
			return $this->request("flavors/{$flavorId}");
        }
		
		/**
        * This operation will list all available flavors with details (if detail == true)
        * 
        * @name  listFlavors
        * @param boolean $detail
        * @return mixed $response
        */
        public function flavorsList($details = true)
        {
        	$detailsUri = ($details) ? "/detail" : "";
        
			return $response = $this->request("flavors{$detailsUri}");
        }
		
		/**
		 * 
		 * provides a list of servers associated with your account
		 * deleted servres can be shown with parameter $detail = true
		 * 
		 * @name  listServers    
		 * @param mixed $detail
		 * @return  mixed $response
		 */
		public function serversList($details = true)
		{
			$detailsUri = ($details) ? "/detail" : "";
    			
			return $this->request("servers{$detailsUri}");
		}
		
		/**
        * asynchronously provisions a new server.
        * 
        * @name createServer
        * @return mixed $response
        */
        public function serverCreate($name, $imageId, $flavorId, $metaData = array(), $personality = array())
        {
             $args['server'] = array(
             	'name'		=> $name,
             	'imageId'	=> (int)$imageId,
             	'flavorId'	=> (int)$flavorId
             );
             
             if (!empty($personality))
             	$args['server']['personality'][] = array('path' => $personality['path'], 'contents' => $personality['contents']);
		
			 return $this->request("servers", "POST", $args);
        }
        
		/**
        * returns the details of a specific server by its ID
        * 
        * @name  getServerDetails
        * @param mixed $serverId
        * @return mixed $response
        */
        public function serverGetDetails($serverId)
        {
			 return $this->request("servers/{$serverId}");
        }
        
		/**
        * allows you to update the name of the server and/or change the administrative password.
        * This operation changes the name of the server in the Cloud Servers system and
        * does not change the server host name itself.
        * 
        * @name  updateServerName
        * @param mixed $serverId
        * @param mixed $name
        * @param mixed $password
        * @todo: addresses
        */
        public function serverUpdate($serverId, $name)
        {
			$this->request("servers/{$serverId}", "PUT", array('server' => array('name' => $name)));
        }
        
		/**
        * This operation deletes a cloud server instance from the system
        * 
        * @name deleteServer 
        * @param mixed $serverId
        */
        public function serverDelete($serverId)
        {
			$this->request("servers/{$serverId}", "DELETE");
        }
        
		/**
		* Returns a list of IP addresses
		* 
		* @name listAddresses
		* @param mixed $serverId
		* @return mixed $response
		*/
		public function serverListAddresses($serverId, $networkId = null)
        {
			$networkUri = ($networkId) ? "/{$networkId}" : "";
        	
        	return $this->request("servers/{$serverId}/ips{$networkUri}");
        }
        
        public function serverUpdateAdminPassword($serverId, $adminPass)
        {
        	return $this->serverAction("changePassword", $serverId, array('adminPass' => $adminPass));
        }
        
		public function serverReboot($serverId, $type = "SOFT")
        {
			return $this->serverAction("reboot", $serverId, array('type' => $type));
        }
        
   		public function serverCreateImage($serverId, $imageName)
   		{
   			return $this->serverAction("createImage", $serverId, array('name' => $imageName));
   		}
        
        
        private function serverAction($action, $serverId, $args)
        {
        	return $this->request("servers/{$serverId}/action", "POST", array($action => $args));
        }
	}
?>
