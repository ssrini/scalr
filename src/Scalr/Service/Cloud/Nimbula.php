<?php
  
	class Scalr_Service_Cloud_Nimbula
	{
		public static function newNimbula($apiUrl, $user, $password)
		{
			return new Scalr_Service_Cloud_Nimbula_Client($apiUrl, $user, $password);
		}
	}
?>
