<?php
	class Scalr_Service_Cloud_Eucalyptus
	{
		/**
		 * 
		 * Enter description here ...
		 * @param unknown_type $accessKey
		 * @param unknown_type $accessKeyId
		 * @param unknown_type $serviceUrl
		 * @param unknown_type $serviceUriPrefix
		 * @param unknown_type $serviceProtocol
		 * @return Scalr_Service_Cloud_Eucalyptus_Client
		 */
		public static function newCloud($accessKey, $accessKeyId, $ec2Url)
		{
			return new Scalr_Service_Cloud_Eucalyptus_Client($accessKey, $accessKeyId, $ec2Url);
		}
	} 
?>