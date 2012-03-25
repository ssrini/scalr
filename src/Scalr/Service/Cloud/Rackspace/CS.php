<?php

	class Scalr_Service_Cloud_Rackspace_CS extends Scalr_Service_Cloud_Rackspace_Connection
	{

		public function __construct ($user, $key, $cloudLocation)
		{
			parent::__construct($user, $key, $cloudLocation);
		}
		
 
		/**
        * returns version of current api
        * 
        * @name apiVersion
        * @return mixed $response
        */
        public function apiVersion($version = null)
        {
        	if(!$version)
        		$version = "v1.0";
        		
			// api version can be recieved only from by root URL request
			return $this->request("GET", null, null, "https://servers.api.rackspacecloud.com/{$version}/");				     		 
        }
		
		
        /**
        * fter confirmation, the original server is removed and cannot be
        * rolled back to. All resizes are automatically confirmed 
        * after 24 hours if they are not explicitly confirmed or reverted.
        * 
        * @name  confirmResizedServer
        * @param mixed $serverId
        */
        public function confirmResizedServer($serverId )
        {
        	$args = array("confirmResize" => null);
        	
			return $this->request("POST","servers/{$serverId}/action",$args);
        }
        
        
        /**
        * This operation creates a new backup schedule or updates an existing backup schedule
        * for the specified server. Backup schedules will occur only when
        * the enabled attribute is set to true. The weekly and daily attributes can be
        * used to set or to disable individual backup schedules
        * 
        * @name  createBackupSchedule
        * @param mixed  $serverId
        * @param mixed  $dayOftheWeek
        * @param mixed  $hour
        * @return mixed $response
        */
        public function createBackupSchedule($serverId, $dayOftheWeek, $hour )
        {
        	$args = array("backupSchedule" => array(
        		"enabled"	=> true,
        		"weekly"	=> $dayOftheWeek,
        		"daily"		=> $hour)
        	);
        
			return $this->request("POST","servers/{$serverId}/backup_schedule",$args);
		}
        
        /**
        * creates a new image for the given server ID.
        * Once complete, a new image will be available that can be used
        * to rebuild or create servers.
        * 
        * @name  createImage
        * @param mixed $serverId
        * @param mixed $name
        * @return mixed $response
        */
        public function createImage($serverId, $name )
        {
        	$args = array("image" => array(
        		"serverId"	=> $serverId,
        		"name"		=> $name)
        	);
        
			return $this->request("POST","images",$args);
        }
        
        /**
        * asynchronously provisions a new server.
        * 
        * @name createServer
        * @return mixed $response
        */
        public function createServer($name, $imageId, $flavorId, $metadata = array(), $personality = array() )
        {
             $args['server'] = array(
             	'name'		=> $name,
             	'imageId'	=> (int)$imageId,
             	'flavorId'	=> (int)$flavorId
             );
             
             if (!empty($personality))
             {
             	$args['server']['personality'][] = array('path' => $personality['path'], 'contents' => $personality['contents']);
             }
		
			 return $this->request("POST", "servers", $args);
        }
        
        
        /**
        * creates a new shared IP group. Please note, all responses to requests 
        * for shared_ip_groups return an array of servers. However, on a create request,
        * the shared IP group can be created empty or can be initially populated 
        * with a single server.
        *
        * @name  createSharedIpGroup
        * @param mixed $serverId
        * @param mixed $name
        * @return mixed $response
        */
        public function createSharedIpGroup($serverId, $name )
        {
        	$args = array("sharedIpGroup" => array(
        		"name"		=> $name,
        		"server"	=> $serverId)
        	);
        
			return $this->request("POST","shared_ip_groups",$args);
        }


        /**
        * deletes an image from the system.
        * 
        * @name  deleteImage
        * @param mixed $imageId 
        */
        public function deleteImage($imageId)
        {
			$this->request("DELETE", "images/{$imageId}");
        }
        
        /**
        * This operation deletes a cloud server instance from the system
        * 
        * @name deleteServer 
        * @param mixed $serverId
        */
        public function deleteServer($serverId)
        {
			$this->request("DELETE", "servers/{$serverId}");
        }
        
        
        /**
        * This operation deletes the specified shared IP group. 
        * This operation will ONLY succeed if 
        * 1) there are no active servers in the group (i.e. they have all been terminated) or 
        * 2) no servers in the group are actively sharing IPs.
        * 
        * @name  deleteSharedIpGroup
        * @param mixed $groupId
        */
		public function deleteSharedIpGroup($groupId)
        {
			$this->request("DELETE", "shared_ip_groups/{$groupId}");
        }

        /**
        * disables the backup schedule for the specified server
        * 
        * @name  disableBackupSchedule
        * @param mixed $serverId
        */
        public function disableBackupSchedule($serverId)
        {
			$this->request("DELETE", "servers/{$serverId}/backup_schedule");
        }
        
        /**
        * This operation returns details of the specified image
        * 
        * @name  getFlavorDetails
        * @param mixed $flavorId
        * @return mixed $response
        */
        public function getFlavorDetails($flavorId)
        {
			return $this->request("GET","flavors/{$flavorId}");
        }
        
        /**
        * returns details of the specified image
        * 
        * @name getImageDetails
        * @param mixed $imageId
        * @return mixed  $response
        */
        public function getImageDetails($imageId)
        {
			return $this->request("GET","images/{$imageId}");
        }
        
        /**
        * returns the details of a specific server by its ID
        * 
        * @name  getServerDetails
        * @param mixed $serverId
        * @return mixed $response
        */
        public function getServerDetails($serverId)
        {
			 return $this->request("GET","servers/{$serverId}");
        }
        
        
        /**
        * This operation returns details of the specified shared IP group
        * 
        * @param mixed $ipId
        * @return mixed $response
        */
        public function getSharedIpGroupsDetails($ipId)
        {
			return $this->request("GET","shared_ip_groups/{$ipId}");
        }
        
		
		/**		
		* show limits of the account
		* 
		* @name	limits
		* @return mixed $response 
		*/
		public function limits()
		{
			return $this->request("GET","limits");
		}
		
		/**
		* Returns a list of IP addresses
		* 
		* @name listAddresses
		* @param mixed $serverId
		* @return mixed $response
		*/
		public function listAddresses($serverId)
        {
			return $response = $this->request("GET", "servers/{$serverId}/ips");
        }
        
        
        /**
        * lists the backup schedule for the specified server.
        * 
        * @name listBackupSchedule
        * @param mixed $serverId
        * @return mixed $response 
        */
        public function listBackupSchedule($serverId)
        {
			return $response = $this->request("GET", "servers/{$serverId}/backup_schedule");
        }
        
        /**
        * This operation will list all available flavors with details (if detail == true)
        * 
        * @name  listFlavors
        * @param boolean $detail
        * @return mixed $response
        */
        public function listFlavors($detail = false)
        {
        	if($detail)
        		$detail = "/detail";
        
			return $response = $this->request("GET", "flavors{$detail}");
        }
        
        /**
        * List available images
        * 
        * @name  listImages
        * @param mixed $detail
        * @return mixed $response
        */
        public function listImages($detail = false)
        {
        	if($detail)
        		$detail = "/detail";

			return $response = $this->request("GET", "images{$detail}");
        }
        
        
        /**
		* Returns a list of private IP addresses
		* 
		* @name listPrivateAddresses
		* @param mixed $serverId
		* @return mixed $response
		*/
        public function listPrivateAddresses($serverId)
        {
			return $response = $this->request("GET", "servers/{$serverId}/ips/private");
        }
        
        
        /**
		* Returns a list of public IP addresses
		* 
		* @name listPublicAddresses
		* @param mixed $serverId
		* @return mixed $response
		*/
        public function listPublicAddresses($serverId)
        {
			return $response = $this->request("GET", "servers/{$serverId}/ips/public");
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
		public function listServers($details = false)
		{
			$details_uri = ($details) ? "/detail" : "";
    			
			 return $this->request("GET", "servers{$details_uri}");
		}
		
		
		/**
		* provides a list of shared IP groups associated with your account
		* 
		* @name	 listSharedIpGroups
		* @param mixed $detail
		* @return mixed $response
		*/
		public function listSharedIpGroups($detail = null)
		{
			if($detail)
				$detail = "/detail";

			return $response = $this->request("GET","shared_ip_groups{$detail}");
		}
        
        
        /**
        * allows for either a soft or hard reboot of a server. 
        * With a soft reboot (SOFT), the operating system is signaled to restart,
        * which allows for a graceful shutdown of all processes. 
        * A hard reboot (HARD) is the equivalent of power cycling the server
        * 
        * @name  rebootServer
        * @param mixed $serverId
        * @param mixed $rebootType
        */
        public function rebootServer($serverId, $rebootType = "SOFT")
        {
        	$args = array("reboot" => array("type" =>$rebootType));
        	
			$this->request("POST", "servers/{$serverId}/action",$args);
        }
        
        
        /**
        * The rebuild function removes all data on the server 
        * and replaces it with the specified image. serverId and IP addresses
        * will remain the same.
        * 
        * @name rebuildServer
        * @param mixed $serverId
        * @param mixed $imageId
        */
		
		
        public function rebuildServer($serverId, $imageId)
        {
			$args = array("rebuild" => array("imageId" =>$imageId));

			$this->request("POST", "servers/{$serverId}/action",$args);  
        }
        
        /**
        * converts an existing server to a different flavor, in essence, scaling the server up or down
        * 
        * @name  resizeServer
        * @param mixed $serverId
        * @param mixed $flavorId
        */
		
		
        public function resizeServer($serverId, $flavorId)
        {
			$args = array("resize" => array("flavorId" =>$flavorId));

			$this->request("POST", "servers/{$serverId}/action",$args);
        }
        
        /**
        * Cancel and revert a pending resize action
        * 
        * @name revertResizedServer
        * @param mixed $serverId
        */
        public function revertResizedServer($serverId)
        {
			$args = array("revertResize" => null);

			$this->request("POST", "servers/{$serverId}/action",$args);

        }
		
		
        /**
        * shares an IP from an existing server in the specified shared IP group to
        * another specified server in the same group. By default, the operation
        * modifies cloud network restrictions to allow IP traffic for the given
        * IP to/from the server specified, but does not bind the IP to the server itself.
        * 
        * @name  shareIpAddress
        * @param mixed $serverId
        * @param mixed $sharedIpGroupId
        */
        public function shareIpAddress($serverId, $sharedIpGroupId, $address)
        {

			$args = array('shareIp' => array(
				'sharedIpGroupId' 	=> $sharedIpGroupId,
				'configureServer' 	=> true)
			); 
			
			$this->request("PUT", "servers/{$serverId}/ips/public/{$address}", $args);
        }
        
        /**
        * Removes a shared IP address from the specified server
        * 
        * @name  unshareIpAddress
        * @param mixed $serverId
        */
        public function unshareIpAddress($serverId)
        {
			$this->request("DELETE", "servers/{$serverId}/ips/public/address");
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
        */
        public function updateServerName($serverId, $name, $adminPass)
        {
			$args = array('server' => array('name' => $name));
			
			if($adminPass)
				$args['adminPass'] = $adminPass;
				
			$this->request("PUT", "servers/{$serverId}", $args);
        }
	}
?>
