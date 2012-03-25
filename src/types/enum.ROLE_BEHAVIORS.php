<?
	final class ROLE_BEHAVIORS
	{
		const BASE 		= "base";
		const CUSTOM 	= "custom";
		const MYSQL 	= "mysql";
		const MYSQL2 	= "mysql2";
		const NGINX	 	= "www";
		const APACHE 	= "app";
		const MEMCACHED = "memcached";
		const CASSANDRA = "cassandra";
		const POSTGRESQL= "postgresql";
		const REDIS		= "redis";
		const RABBITMQ  = "rabbitmq";
		const MONGODB  	= "mongodb";
		const CHEF		= "chef";
		const MYSQLPROXY = "mysqlproxy";
		const HAPROXY	= "haproxy";
		
		/** CloudFoundry behaviors */
		const CF_ROUTER				= 'cf_router';
		const CF_CLOUD_CONTROLLER 	= 'cf_cloud_controller';
		const CF_HEALTH_MANAGER 	= 'cf_health_manager';
		const CF_DEA 				= 'cf_dea';
		const CF_SERVICE 			= 'cf_service';
		
		static public function GetName($const = null, $all = false)
		{
			$types = array(
				self::BASE	 => _("Base"),
				self::CUSTOM => _("Custom"),
				self::MYSQL	 => _("MySQL"),
				self::MYSQL2	 => _("MySQL"),
				self::APACHE => _("Apache"),
				self::NGINX	 => _("Nginx"),
				self::HAPROXY	 => _("HAProxy"),
				self::MEMCACHED  => _("Memcached"),
				self::CASSANDRA	 => _("Cassandra"),
				self::POSTGRESQL => _("PostgreSQL"),
				self::REDIS 	=> _("Redis"),
				self::RABBITMQ 	=> _("RabbitMQ"),
				self::MONGODB 	=> _("MongoDB"),
				self::CHEF 		=> _("Chef"),
				self::MYSQLPROXY => _("MySQL Proxy"),
				
				self::CF_ROUTER => _("CloudFoundry Router"),
				self::CF_CLOUD_CONTROLLER => _("CloudFoundry Controller"),
				self::CF_HEALTH_MANAGER => _("CloudFoundry Health Manager"),
				self::CF_DEA => _("CloudFoundry DEA"),
				self::CF_SERVICE => _("CloudFoundry Service"),
			);
			
			return ($all) ? $types : $types[$const];
		}
	}
?>