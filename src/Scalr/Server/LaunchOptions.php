<?php 
	class Scalr_Server_LaunchOptions
	{
		/**
		 * 
		 * Image ID
		 * @var string
		 */
		public $imageId;
		
		/**
		 * 
		 * flavourId - RackSpace, instance_type - EC2 ...
		 * @var string
		 */
		public $serverType;
		
		/**
		 * 
		 * USer data that will be passed to server
		 * @var string
		 */
		public $userData = '';
		
		/**
		 * 
		 * Cloud location
		 * @var string
		 */
		public $cloudLocation;
		
		/**
		 * 
		 * Server architecture
		 * @var string
		 */
		public $architecture;
	}
?>