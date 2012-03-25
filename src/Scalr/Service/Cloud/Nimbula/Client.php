<?php
	class Scalr_Service_Cloud_Nimbula_Client extends Scalr_Service_Cloud_Nimbula_Connection
	{
		protected $username;
		protected $password;
		protected $apiUrl;
		
		public function __construct($apiUrl, $username, $password)
		{
			$this->username = $username;
			$this->password = $password;
			$this->apiUrl = $apiUrl;
			
			@preg_match("/\/([^\/]+)\//", $this->username, $matches);
			$this->nimbulaContainer = $matches[1];
			
			$this->auth();
		}
		
		public function instanceDelete($instanceName)
		{
			$result = $this->request("/instance{$this->username}/{$instanceName}", HTTP_METH_DELETE);
		}
		
		public function instanceLaunch($shape, $imageList, $entry, $label = "", $userData = null)
		{
			if (!$userData)
				$userData = new stdClass();
			
			$instance = new stdClass();
			$instance->placement_requirements = array();
			$instance->tags = new stdClass();
			$instance->block_devices = new stdClass();
			$instance->network_devices = new stdClass();
			$instance->label = $label;
			$instance->shape = $shape;
			$instance->entry = (int)$entry;
			$instance->imagelist = $imageList;
			$instance->user_data = $userData;
			
			$result = $this->request("/launchplan/", HTTP_METH_POST, array(
				'relationships' => array(),
				'instances' => array(
					$instance			
				)
			));
			
			return $result;
		}
		
		public function instancesList($instanceName = null)
		{
			if ($instanceName)
				$instanceName = "/{$instanceName}";
			else
				$instanceName = "/";
			
			$result = $this->request("/instance{$this->username}{$instanceName}", HTTP_METH_GET);
			
			return ($instanceName == '/') ? $result->result : $result;
		}
		
		public function listShapes($container = null)
		{
			if (!$container)
				$container = $this->nimbulaContainer;
			
			$result = $this->request("/shape/{$container}/", HTTP_METH_GET);
			return $result->result;
		}
		
		public function imageListCreate($name, $description, $default = 0)
		{
			$result = $this->request("/imagelist/", HTTP_METH_POST, array(
				'name' => "{$this->username}/{$name}",
				'description' => $description,
				'default' => $default ,
				'uri' => '',
				'entries' => array()
			));
			
			return $result;
		}
		
		public function imageListAddEntry($name, $imageName, $version = 1)
		{
			$result = $this->request("/imagelist{$this->username}/{$name}/entry/", HTTP_METH_POST, array(
				'version' => $version,
				'machineimages' => array($imageName),
				'attributes' => array('type' => 'linux'),
				'uri' => null
			));
			
			return $result;
		}
		
		public function imageListList()
		{
			$result = $this->request("/imagelist{$this->username}/", HTTP_METH_GET);
			return $result->result;
		}
		
		public function listImages($container = '')
		{
			$uri = ($container) ? "/machineimage{$container}" : "/machineimage{$this->username}/"; 
			$result = $this->request($uri, HTTP_METH_GET);
			return $result->result;
		}
	}
