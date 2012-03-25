<?php
	class Scalr_Service_Cloud_Aws
	{
		/**
		 * 
		 * Amazon IAM Client
		 * @param string $access_key
		 * @param string $secret_key
		 * @return Scalr_Service_Cloud_Aws_Iam_Client
		 */
		public static function newIam($access_key, $secret_key)
		{
			$iam = new Scalr_Service_Cloud_Aws_Iam_Client($secret_key, $access_key);
			return $iam;
		}
		
		
		
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
		public static function newEc2($region, $privateKey, $certificate)
		{
			$ec2 = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
			$ec2->SetAuthKeys($privateKey, $certificate);
			
			return $ec2;
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param unknown_type $access_key
		 * @param unknown_type $secret_key
		 * @param unknown_type $region
		 * @return AmazonRDS
		 */
		public static function newRds($access_key, $secret_key, $region)
		{
			$rds = AmazonRDS::GetInstance($access_key, $secret_key);
		    $rds->SetRegion($region);
		    
		    return $rds;
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param unknown_type $region
		 * @param unknown_type $access_key
		 * @param unknown_type $secret_key
		 * @return AmazonELB
		 */
		public static function newElb($region, $access_key, $secret_key)
		{
			$elb = AmazonELB::GetInstance($access_key, $secret_key);
			$elb->SetRegion($region);
			
			return $elb;
		}
		
		public static function newVpc($region, $privateKey, $certificate)
		{
			$vpc = AmazonVPC::GetInstance(AWSRegions::GetAPIURL($region)); 
			$vpc->SetAuthKeys($privateKey, $certificate);
			
			return $vpc;
		}
	}
?>
