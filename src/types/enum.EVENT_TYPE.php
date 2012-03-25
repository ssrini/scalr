<?
	final class EVENT_TYPE
	{
		const HOST_UP 	= "HostUp";
		const HOST_DOWN	= "HostDown";
		const HOST_CRASH	= "HostCrash";
		const HOST_INIT 	= "HostInit";
				
		const REBUNDLE_COMPLETE	= "RebundleComplete";
		const REBUNDLE_FAILED	= "RebundleFailed";
		
		const REBOOT_BEGIN	= "RebootBegin";
		const REBOOT_COMPLETE	= "RebootComplete";
		
		const FARM_TERMINATED = "FarmTerminated";
		const FARM_LAUNCHED = "FarmLaunched";
		
		const INSTANCE_IP_ADDRESS_CHANGED = "IPAddressChanged";
		
		const NEW_MYSQL_MASTER = "NewMysqlMasterUp";
		const MYSQL_BACKUP_COMPLETE = "MysqlBackupComplete";
		const MYSQL_BACKUP_FAIL = "MysqlBackupFail";
		
		const MYSQL_REPLICATION_FAIL = "MySQLReplicationFail";
		const MYSQL_REPLICATION_RECOVERED = "MySQLReplicationRecovered";
		
		const EBS_VOLUME_MOUNTED = "EBSVolumeMounted";
		const BEFORE_INSTANCE_LAUNCH = "BeforeInstanceLaunch";
		const BEFORE_HOST_TERMINATE = "BeforeHostTerminate";
		const BEFORE_HOST_UP = "BeforeHostUp";
		
		const DNS_ZONE_UPDATED = "DNSZoneUpdated";
		
		const EBS_VOLUME_ATTACHED = "EBSVolumeAttached";
		
		CONST ROLE_OPTION_CHANGED = "RoleOptionChanged";
		
		const SERVICE_CONFIGURATION_PRESET_CHANGED = "ServiceConfigurationPresetChanged";
		
		public static function GetEventDescription($event_type)
		{
			$descriptions = array(
				self::HOST_UP 			=> _("Instance started and configured."),
				self::BEFORE_HOST_UP 	=> _("Time for user-defined actions before instance will be added to DNS, LoadBalancer, etc."),
				self::HOST_DOWN 		=> _("Instance terminated."),
				self::HOST_CRASH 		=> _("Instance crashed inexpectedly."),
				self::REBUNDLE_COMPLETE => _("\"Synchronize to all\" or custom role creation competed successfully."),
				self::REBUNDLE_FAILED 	=> _("\"Synchronize to all\" or custom role creation failed."),
				self::REBOOT_BEGIN 		=> _("Instance being rebooted."),
				self::REBOOT_COMPLETE 	=> _("Instance came up after reboot."),
				self::FARM_LAUNCHED 	=> _("Farm has been launched."),
				self::FARM_TERMINATED 	=> _("Farm has been terminated."),
				self::HOST_INIT			=> _("Instance booted up, Scalr environment not configured and services not initialized yet."),
				self::NEW_MYSQL_MASTER	=> _("One of MySQL instances promoted as master on boot up, or one of mySQL slaves promoted as master."), // due to master failure.",
				self::MYSQL_BACKUP_COMPLETE 		=> _("MySQL backup completed successfully."),
				self::MYSQL_BACKUP_FAIL 			=> _("MySQL backup failed."),
				self::INSTANCE_IP_ADDRESS_CHANGED 	=> _("Public IP address of the instance was changed upon reboot or within Elastic IP assignments."),
				self::MYSQL_REPLICATION_FAIL 		=> _("MySQL replication failure."),
				self::MYSQL_REPLICATION_RECOVERED 	=> _("MySQL replication recovered after failure."),
				self::EBS_VOLUME_MOUNTED			=> _("Single EBS volume or array of EBS volumes attached and mounted to instance."),
				self::BEFORE_INSTANCE_LAUNCH		=> _("New instance will be launched in a few minutes"),
				self::BEFORE_HOST_TERMINATE			=> _("Instance will be terminated in 3 minutes"),
				self::DNS_ZONE_UPDATED				=> _("DNS zone updated"),
				self::EBS_VOLUME_ATTACHED			=> _("EBS volume attached to instance."),
				self::ROLE_OPTION_CHANGED			=> _("Role option/parameter was changed"),
				self::SERVICE_CONFIGURATION_PRESET_CHANGED => _("Service configuration preset was modified")
			);
			
			return $descriptions[$event_type];
		}
	}
?>