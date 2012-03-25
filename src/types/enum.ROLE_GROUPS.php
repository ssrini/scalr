<?
	final class ROLE_GROUPS
	{
		const BASE			= "base";
		const DB  			= "database";
		const APP			= "app";
		const LB			= "lb";
		const MQ			= "mq";
		const CACHE			= "cache";
		const MIXED			= "mixed";
		const CLOUDFOUNDRY  = "cloudfoundry";
		
		private static $utilityBehaviors = array(ROLE_BEHAVIORS::CHEF, ROLE_BEHAVIORS::MYSQLPROXY);
		
		static public function GetConstByBehavior($behavior)
		{
			if (is_array($behavior) && count($behavior) > 1) {
				
				$cfRole = false;
				foreach ($behavior as $b) {
					if (stristr($b, "cf_")) {
						$cfRole = true;
						break;
					}
					
					if (in_array($b, self::$utilityBehaviors))
						continue;
						
					$rBehavior .= "{$b},";
				}
				
				if ($cfRole)
					return self::CLOUDFOUNDRY;
				
				$rBehavior  = trim($rBehavior, ",");
			} else
				$rBehavior = $behavior[0];
			
				
			switch($rBehavior)
			{
				case ROLE_BEHAVIORS::RABBITMQ:
					return self::MQ;
				break;
				
				case ROLE_BEHAVIORS::APACHE:
					return self::APP;
				break;
				
				case ROLE_BEHAVIORS::CUSTOM:
				case ROLE_BEHAVIORS::BASE:
					return self::BASE;
				break;
				
				case ROLE_BEHAVIORS::HAPROXY:
				case ROLE_BEHAVIORS::NGINX:
					return self::LB;
				break;
				
				case ROLE_BEHAVIORS::MEMCACHED:
					return self::CACHE;
				break;
				
				case ROLE_BEHAVIORS::MYSQL:
				case ROLE_BEHAVIORS::MYSQL2:
				case ROLE_BEHAVIORS::MONGODB:
				case ROLE_BEHAVIORS::CASSANDRA:
				case ROLE_BEHAVIORS::POSTGRESQL:				
				case ROLE_BEHAVIORS::REDIS:
					return self::DB;
				break;
				
				case ROLE_BEHAVIORS::CF_CLOUD_CONTROLLER:
				case ROLE_BEHAVIORS::CF_DEA:
				case ROLE_BEHAVIORS::CF_HEALTH_MANAGER:
				case ROLE_BEHAVIORS::CF_ROUTER:
				case ROLE_BEHAVIORS::CF_SERVICE:
					return self::CLOUDFOUNDRY;
				break;
				
				default:
					return self::MIXED;
				break;
			}
		}
		
		static public function GetNameByBehavior($behavior)
		{
			return self::GetName(self::GetConstByBehavior($behavior));
		}
		
		static public function GetName($const = null, $all = false)
		{
			$types = array(
				self::BASE	 => _("Base images"),
				self::DB	 => _("Database servers"),
				self::APP	 => _("Application servers"),
				self::LB	 => _("Load balancers"),
				self::CACHE  => _("Caching servers"),
				self::MIXED	 => _("Mixed images"),
				self::MQ	 => _("MQ servers"),
				self::CLOUDFOUNDRY => _("CloudFoundry images")
			);
						
			return ($all) ? $types : $types[$const];
		}
	}
?>