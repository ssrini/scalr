<?php
  
	class Scalr_Service_Cloud_Rackspace
	{
		public static function newRackspaceCS($user, $key, $cloudLocation)
		{
			return new Scalr_Service_Cloud_Rackspace_CS($user, $key, $cloudLocation);
		}
	}
?>
