<?php

	class AWSRegions
	{
		const US_EAST_1 		= 'us-east-1';
		const US_WEST_1 		= 'us-west-1';
		const US_WEST_2 		= 'us-west-2';
		const EU_WEST_1 		= 'eu-west-1';		
		const SA_EAST_1			= 'sa-east-1';
		const AP_SOUTHEAST_1 	= 'ap-southeast-1';
		const AP_NORTHEAST_1	= 'ap-northeast-1';
				
		private static $Regions = array(
			"us-east-1"	=> array(
				"api_url" => "https://us-east-1.ec2.amazonaws.com",				
				"name"	  => "EC2 / US East 1"
			),
			"us-west-1"	=> array(
				"api_url" => "https://us-west-1.ec2.amazonaws.com",				
				"name"	  => "EC2 / US West 1"
			),
			"us-west-2"	=> array(
				"api_url" => "https://us-west-2.ec2.amazonaws.com",				
				"name"	  => "EC2 / US West 2"
			),
			"eu-west-1"	=> array(
				"api_url" => "https://eu-west-1.ec2.amazonaws.com",				
				"name"	  => "EC2 / EU West 1"
			),
			"ap-southeast-1" => array(
				"api_url" => "https://ec2.ap-southeast-1.amazonaws.com",				
				"name"	  => "EC2 / Asia Pacific 1"
			),
			"ap-northeast-1" => array(
				"api_url" => "https://ec2.ap-northeast-1.amazonaws.com",				
				"name"	  => "EC2 / Asia Pacific 1"
			),
			"sa-east-1"		=> array(
				"api_url" => "https://ec2.sa-east-1.amazonaws.com",				
				"name"	  => "EC2 / South America 1"
			)
		);
		
		public static function GetList()
		{
			return array_keys(self::$Regions);
		}
		
		public static function GetAPIURL($region)
		{
			if (self::$Regions[$region])
				return self::$Regions[$region]['api_url'];
			else
				throw new Exception(sprintf(_("Region %s not supported by Scalr"), $region)); 
		}
		
		public static function GetName($region)
		{
			if (self::$Regions[$region])
				return self::$Regions[$region]['name'];
			else
				throw new Exception(sprintf(_("Region %s not supported by Scalr"), $region)); 
		}
	}
	
?>