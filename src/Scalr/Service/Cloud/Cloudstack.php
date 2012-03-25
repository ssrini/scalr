<?php
  
	class Scalr_Service_Cloud_Cloudstack
	{
		public static function newCloudstack($endpoint, $apiKey, $secretKey)
		{
			return new Scalr_Service_Cloud_Cloudstack_Client($endpoint, $apiKey, $secretKey);
		}
	}
?>
