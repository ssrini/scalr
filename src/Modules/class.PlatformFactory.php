<?php

	class PlatformFactory
	{
		private static $cache = array();
		
		/**
		 * Create platform instance
		 * @param string $platform
		 * @return IPlatformModule
		 */
		public static function NewPlatform($platform)
		{
			if (!self::$cache[$platform])
			{
				if ($platform == SERVER_PLATFORMS::EC2)
					self::$cache[$platform] = new Modules_Platforms_Ec2();
				elseif ($platform == SERVER_PLATFORMS::RDS)
					self::$cache[$platform] = new Modules_Platforms_Rds();
				elseif ($platform == SERVER_PLATFORMS::EUCALYPTUS)
					self::$cache[$platform] = new Modules_Platforms_Eucalyptus();
				elseif ($platform == SERVER_PLATFORMS::RACKSPACE)
					self::$cache[$platform] = new Modules_Platforms_Rackspace();
				elseif ($platform == SERVER_PLATFORMS::NIMBULA)
					self::$cache[$platform] = new Modules_Platforms_Nimbula();
				elseif ($platform == SERVER_PLATFORMS::CLOUDSTACK)
					self::$cache[$platform] = new Modules_Platforms_Cloudstack();
				elseif ($platform == SERVER_PLATFORMS::OPENSTACK)
					self::$cache[$platform] = new Modules_Platforms_Openstack();
				else
					throw new Exception(sprintf("Platform %s not supported by Scalr", $platform));
			}
			
			return self::$cache[$platform];
		}
	}
?>