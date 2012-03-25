<?
	function __autoload($class_name)
	{
    	$paths = array(
    		/****************************** Basic Objects ***********************/
    		'Client'				=> SRCPATH.'/class.Client.php',
    		'DBFarm'				=> SRCPATH.'/class.DBFarm.php',
    		'DBEBSVolume'			=> SRCPATH.'/class.DBEBSVolume.php',
    		'DBEBSArray'			=> SRCPATH.'/class.DBEBSArray.php',
    		'AWSRegions'			=> SRCPATH.'/class.AWSRegions.php',
    		'XMLMessageSerializer'	=> SRCPATH.'/class.XMLMessageSerializer.php',
    		'IMessageSerializer'	=> SRCPATH.'/interface.IMessageSerializer.php',
    		'DBFarmRole'			=> SRCPATH.'/class.DBFarmRole.php',
    		'DBServer'				=> SRCPATH.'/class.DBServer.php',
    		'ServerCreateInfo'		=> SRCPATH.'/class.ServerCreateInfo.php',
    		'ServerSnapshotCreateInfo'	=> SRCPATH.'/class.ServerSnapshotCreateInfo.php',
    		'BundleTask'			=> SRCPATH.'/class.BundleTask.php',
    		'DBRole'				=> SRCPATH.'/class.DBRole.php',
    		'DBDNSZone'				=> SRCPATH.'/class.DBDNSZone.php',

    		/********************** Service Configuration Modules ********************/
    		'ServiceConfigurationFactory'	=> SRCPATH.'/Modules/class.ServiceConfigurationFactory.php',
    	
    		/****************************** Modules **********************************/
    		'Modules_Platforms_Aws'		=> SRCPATH.'/Modules/Platforms/abstract.Aws.php', // Abstract

    		'Modules_Platforms_Ec2'		=> SRCPATH.'/Modules/Platforms/Ec2/Ec2.php',
    		'Modules_Platforms_Rds'		=> SRCPATH.'/Modules/Platforms/Rds/Rds.php',
    		'Modules_Platforms_Eucalyptus'		=> SRCPATH.'/Modules/Platforms/Eucalyptus/Eucalyptus.php',
    		'Modules_Platforms_Rackspace'		=> SRCPATH.'/Modules/Platforms/Rackspace/Rackspace.php',
    		'Modules_Platforms_Nimbula'		=> SRCPATH.'/Modules/Platforms/Nimbula/Nimbula.php',
			'Modules_Platforms_Cloudstack'		=> SRCPATH.'/Modules/Platforms/Cloudstack/Cloudstack.php',
    		'Modules_Platforms_Openstack'		=> SRCPATH.'/Modules/Platforms/Openstack/Openstack.php',
    	
    	
    		'IModules_Platforms_Adapters_Status' => SRCPATH.'/Modules/Platforms/interface.IModules_Platforms_Adapters_Status.php',
    		'IPlatformModule'		=> SRCPATH.'/Modules/interface.IPlatformModule.php',
    		'PlatformFactory'		=> SRCPATH.'/Modules/class.PlatformFactory.php',


    		'Modules_Platforms_Ec2_Helpers_Ebs'	=> SRCPATH.'/Modules/Platforms/Ec2/Helpers/Ebs.php',
    		'Modules_Platforms_Ec2_Helpers_Eip'	=> SRCPATH.'/Modules/Platforms/Ec2/Helpers/Eip.php',
    		'Modules_Platforms_Ec2_Helpers_Elb'	=> SRCPATH.'/Modules/Platforms/Ec2/Helpers/Elb.php',
    		'Modules_Platforms_Ec2_Observers_Ebs'	=> SRCPATH.'/Modules/Platforms/Ec2/Observers/Ebs.php',
    		'Modules_Platforms_Ec2_Observers_Ec2'	=> SRCPATH.'/Modules/Platforms/Ec2/Observers/Ec2.php',
    		'Modules_Platforms_Ec2_Adapters_Status'	=> SRCPATH.'/Modules/Platforms/Ec2/Adapters/Status.php',


    		'Modules_Platforms_Rackspace_Adapters_Status'	=> SRCPATH.'/Modules/Platforms/Rackspace/Adapters/Status.php',
    		'Modules_Platforms_Openstack_Adapters_Status'	=> SRCPATH.'/Modules/Platforms/Openstack/Adapters/Status.php',
    		'Modules_Platforms_Nimbula_Adapters_Status'	=> SRCPATH.'/Modules/Platforms/Nimbula/Adapters/Status.php',
    		'Modules_Platforms_Cloudstack_Adapters_Status'	=> SRCPATH.'/Modules/Platforms/Cloudstack/Adapters/Status.php',
    	


    		'Modules_Platforms_Rds_Helpers_Rds'	=> SRCPATH.'/Modules/Platforms/Rds/Helpers/Rds.php',
    		'Modules_Platforms_Rds_Observers_Rds'	=> SRCPATH.'/Modules/Platforms/Rds/Observers/Rds.php',
    		'Modules_Platforms_Rds_Adapters_Status'	=> SRCPATH.'/Modules/Platforms/Rds/Adapters/Status.php',


    		'Modules_Platforms_Eucalyptus_Helpers_Eucalyptus'	=> SRCPATH.'/Modules/Platforms/Eucalyptus/Helpers/Eucalyptus.php',
    	
    		'Modules_Platforms_Cloudstack_Helpers_Cloudstack'	=> SRCPATH.'/Modules/Platforms/Cloudstack/Helpers/Cloudstack.php',
    		'Modules_Platforms_Cloudstack_Observers_Cloudstack'	=> SRCPATH.'/Modules/Platforms/Cloudstack/Observers/Cloudstack.php',


    		/***************************** API ***********************************/
    		'ScalrAPICoreFactory'	=> SRCPATH.'/api/class.ScalrAPICoreFactory.php',
    		'ScalrAPICore'			=> SRCPATH.'/api/class.ScalrAPICore.php',


    		'ScalrAPI_2_0_0'		=> SRCPATH.'/api/class.ScalrAPI_2_0_0.php',
    		'ScalrAPI_2_1_0'		=> SRCPATH.'/api/class.ScalrAPI_2_1_0.php',
    		'ScalrAPI_2_2_0'		=> SRCPATH.'/api/class.ScalrAPI_2_2_0.php',
    		'ScalrAPI_2_3_0'		=> SRCPATH.'/api/class.ScalrAPI_2_3_0.php',

    		/****************************** Messaging  ***************************/
    		'ScalrMessagingService'				=> SRCPATH.'/class.ScalrMessagingService.php',

    		/******************* Environment objects ****************************/
    		'ScalrEnvironmentFactory'	=> SRCPATH.'/class.ScalrEnvironmentFactory.php',
    		'ScalrEnvironment'			=> SRCPATH.'/class.ScalrEnvironment.php',
    		'ScalrRESTService'			=> SRCPATH.'/class.ScalrRESTService.php',
    		'ScalarizrCallbackService'  => SRCPATH.'/class.ScalarizrCallbackService.php',

    		/****************************** Events ******************************/
    		'Event'					=> SRCPATH.'/events/abstract.Event.php',
    		'FarmLaunchedEvent' 	=> SRCPATH.'/events/class.FarmLaunchedEvent.php',
    		'FarmTerminatedEvent' 	=> SRCPATH.'/events/class.FarmTerminatedEvent.php',
    		'HostCrashEvent' 		=> SRCPATH.'/events/class.HostCrashEvent.php',
    		'HostDownEvent'			=> SRCPATH.'/events/class.HostDownEvent.php',
    		'HostInitEvent' 		=> SRCPATH.'/events/class.HostInitEvent.php',
    		'HostUpEvent'			=> SRCPATH.'/events/class.HostUpEvent.php',
    		'IPAddressChangedEvent'	=> SRCPATH.'/events/class.IPAddressChangedEvent.php',
    		'MysqlBackupCompleteEvent'		=> SRCPATH.'/events/class.MysqlBackupCompleteEvent.php',
    		'MysqlBackupFailEvent'			=> SRCPATH.'/events/class.MysqlBackupFailEvent.php',
    		'MySQLReplicationFailEvent'		=> SRCPATH.'/events/class.MySQLReplicationFailEvent.php',
    		'MySQLReplicationRecoveredEvent'=> SRCPATH.'/events/class.MySQLReplicationRecoveredEvent.php',
    		'NewMysqlMasterUpEvent'	=> SRCPATH.'/events/class.NewMysqlMasterUpEvent.php',
    		'NewDbMsrMasterUpEvent' => SRCPATH.'/events/class.NewDbMsrMasterUpEvent.php',
    		'RebootBeginEvent'		=> SRCPATH.'/events/class.RebootBeginEvent.php',
    		'RebootCompleteEvent'	=> SRCPATH.'/events/class.RebootCompleteEvent.php',
    		'RebundleCompleteEvent'	=> SRCPATH.'/events/class.RebundleCompleteEvent.php',
    		'RebundleFailedEvent'	=> SRCPATH.'/events/class.RebundleFailedEvent.php',
    		'EBSVolumeMountedEvent'	=> SRCPATH.'/events/class.EBSVolumeMountedEvent.php',
    		'BeforeInstanceLaunchEvent'		=> SRCPATH.'/events/class.BeforeInstanceLaunchEvent.php',
    		'BeforeHostTerminateEvent'		=> SRCPATH.'/events/class.BeforeHostTerminateEvent.php',
    		'DNSZoneUpdatedEvent'	=> SRCPATH.'/events/class.DNSZoneUpdatedEvent.php',
    		'RoleOptionChangedEvent'=> SRCPATH.'/events/class.RoleOptionChangedEvent.php',
    		'EBSVolumeAttachedEvent'	=> SRCPATH.'/events/class.EBSVolumeAttachedEvent.php',
    		'BeforeHostUpEvent'	=> SRCPATH.'/events/class.BeforeHostUpEvent.php',
    		'ServiceConfigurationPresetChangedEvent'	=> SRCPATH.'/events/class.ServiceConfigurationPresetChangedEvent.php',

    		/****************************** Structs ******************************/
    		'CONTEXTS'				=> SRCPATH."/structs/struct.CONTEXTS.php",
			'CONFIG'				=> SRCPATH."/structs/struct.CONFIG.php",

    		/****************************** ENUMS ******************************/
    		'APPCONTEXT'			=> SRCPATH."/types/enum.APPCONTEXT.php",
			'FORM_FIELD_TYPE'		=> SRCPATH."/types/enum.FORM_FIELD_TYPE.php",
			'SUBSCRIPTION_STATUS'	=> SRCPATH."/types/enum.SUBSCRIPTION_STATUS.php",
			'INSTANCE_FLAVOR'		=> SRCPATH."/types/enum.INSTANCE_FLAVOR.php",
    		'X86_64_TYPE'			=> SRCPATH."/types/enum.X86_64_TYPE.php",
    		'I386_TYPE'				=> SRCPATH."/types/enum.I386_TYPE.php",
			'INSTANCE_ARCHITECTURE'	=> SRCPATH."/types/enum.INSTANCE_ARCHITECTURE.php",
			'EVENT_TYPE'			=> SRCPATH."/types/enum.EVENT_TYPE.php",
			'RRD_STORAGE_TYPE'		=> SRCPATH."/types/enum.RRD_STORAGE_TYPE.php",
			'GRAPH_TYPE'			=> SRCPATH."/types/enum.GRAPH_TYPE.php",
			'MYSQL_BACKUP_TYPE'		=> SRCPATH."/types/enum.MYSQL_BACKUP_TYPE.php",
			'FARM_STATUS'			=> SRCPATH."/types/enum.FARM_STATUS.php",
			'INSTANCE_COST'			=> SRCPATH."/types/enum.INSTANCE_COST.php",
			'QUEUE_NAME'			=> SRCPATH."/types/enum.QUEUE_NAME.php",
			'ROLE_BEHAVIORS'		=> SRCPATH."/types/enum.ROLE_BEHAVIORS.php",
    		'ROLE_GROUPS'			=> SRCPATH."/types/enum.ROLE_GROUPS.php",
			'ROLE_TYPE'				=> SRCPATH."/types/enum.ROLE_TYPE.php",
    		'ROLE_TAGS'				=> SRCPATH."/types/enum.ROLE_TAGS.php",
    		'AWS_SCALR_EIP_STATE'	=> SRCPATH."/types/enum.AWS_SCALR_EIP_STATE.php",
			'AMAZON_EBS_STATE'		=> SRCPATH."/types/enum.AMAZON_EBS_STATE.php",
    		'SCRIPTING_TARGET'		=> SRCPATH."/types/enum.SCRIPTING_TARGET.php",
    		'APPROVAL_STATE'		=> SRCPATH."/types/enum.APPROVAL_STATE.php",
    		'SCRIPT_ORIGIN_TYPE'	=> SRCPATH."/types/enum.SCRIPT_ORIGIN_TYPE.php",
    		'COMMENTS_OBJECT_TYPE'	=> SRCPATH."/types/enum.COMMENTS_OBJECT_TYPE.php",
    		'EBS_ARRAY_STATUS'		=> SRCPATH."/types/enum.EBS_ARRAY_STATUS.php",
    		'EBS_ARRAY_SNAP_STATUS' => SRCPATH."/types/enum.EBS_ARRAY_SNAP_STATUS.php",
    		'MYSQL_STORAGE_ENGINE'	=> SRCPATH."/types/enum.MYSQL_STORAGE_ENGINE.php",
    		'CLIENT_SETTINGS'		=> SRCPATH."/types/enum.CLIENT_SETTINGS.php",
    		'BASIC_MESSAGE_NAMES'	=> SRCPATH."/types/enum.BASIC_MESSAGE_NAMES.php",
    		'RESERVED_ROLE_OPTIONS' => SRCPATH."/types/enum.RESERVED_ROLE_OPTIONS.php",
    		'AUTOSNAPSHOT_TYPE' 	=> SRCPATH."/types/enum.AUTOSNAPSHOT_TYPE.php",
    		'SCHEDULE_TASK_TYPE' 	=> SRCPATH."/types/enum.SCHEDULE_TASK_TYPE.php",
    		'TASK_STATUS' 			=> SRCPATH."/types/enum.TASK_STATUS.php",
    		'LOG_CATEGORY'			=> SRCPATH."/types/enum.LOG_CATEGORY.php",
    		'MONITORING_TYPE'		=> SRCPATH."/types/enum.MONITORING_TYPE.php",

    		'DNS_ZONE_STATUS'		=> SRCPATH."/types/enum.DNS_ZONE_STATUS.php",

    		'SERVER_TYPE'			=> SRCPATH."/types/enum.SERVER_TYPE.php",
    		'SERVER_STATUS'			=> SRCPATH."/types/enum.SERVER_STATUS.php",
    		'SERVER_PROPERTIES'		=> SRCPATH."/types/enum.SERVER_PROPERTIES.php",
    		'SERVER_PLATFORMS'		=> SRCPATH."/types/enum.SERVER_PLATFORMS.php",
    		'EC2_SERVER_PROPERTIES'	=> SRCPATH."/types/enum.EC2_SERVER_PROPERTIES.php",
    		'RDS_SERVER_PROPERTIES'	=> SRCPATH."/types/enum.RDS_SERVER_PROPERTIES.php",
    		'VPS_SERVER_PROPERTIES'	=> SRCPATH."/types/enum.VPS_SERVER_PROPERTIES.php",
    		'EUCA_SERVER_PROPERTIES'=> SRCPATH."/types/enum.EUCA_SERVER_PROPERTIES.php",
    		'RACKSPACE_SERVER_PROPERTIES'	=> SRCPATH."/types/enum.RACKSPACE_SERVER_PROPERTIES.php",
    		'OPENSTACK_SERVER_PROPERTIES'	=> SRCPATH."/types/enum.OPENSTACK_SERVER_PROPERTIES.php",
    		'CLOUDSTACK_SERVER_PROPERTIES'	=> SRCPATH."/types/enum.CLOUDSTACK_SERVER_PROPERTIES.php",
    		'NIMBULA_SERVER_PROPERTIES'	=> SRCPATH."/types/enum.NIMBULA_SERVER_PROPERTIES.php",
    		'SZR_KEY_TYPE'			=> SRCPATH."/types/enum.SZR_KEY_TYPE.php",
    		'SERVER_REPLACEMENT_TYPE'	=> SRCPATH."/types/enum.SERVER_REPLACEMENT_TYPE.php",
    		'SERVER_SNAPSHOT_CREATION_TYPE'	=> SRCPATH."/types/enum.SERVER_SNAPSHOT_CREATION_TYPE.php",
    		'SERVER_SNAPSHOT_CREATION_STATUS'	=> SRCPATH."/types/enum.SERVER_SNAPSHOT_CREATION_STATUS.php",
    		'MESSAGE_STATUS'		=> SRCPATH."/types/enum.MESSAGE_STATUS.php",
    		'ENVIRONMENT_SETTINGS'	=> SRCPATH."/types/enum.ENVIRONMENT_SETTINGS.php",

    		'EC2_EBS_ATTACH_STATUS'		=> SRCPATH."/types/enum.EC2_EBS_ATTACH_STATUS.php",
    		'EC2_EBS_MOUNT_STATUS'		=> SRCPATH."/types/enum.EC2_EBS_MOUNT_STATUS.php",

    		/****************************** Observers ***************************/
		    'EventObserver'			=> APPPATH.'/observers/abstract.EventObserver.php',
		    'DNSEventObserver'		=> APPPATH.'/observers/class.DNSEventObserver.php',
		    'DBEventObserver'		=> APPPATH.'/observers/class.DBEventObserver.php',
		    'ScriptingEventObserver'=> APPPATH.'/observers/class.ScriptingEventObserver.php',
		    'MessagingEventObserver'=> APPPATH.'/observers/class.MessagingEventObserver.php',
		    'SSHWorker'				=> APPPATH.'/observers/class.SSHWorker.php',
		    'ScalarizrEventObserver' => APPPATH.'/observers/class.ScalarizrEventObserver.php',
    		'BehaviorEventObserver' => APPPATH.'/observers/class.BehaviorEventObserver.php',

    		// Deferred observers
    		'MailEventObserver'		=> APPPATH.'/observers/class.MailEventObserver.php',
    		'RESTEventObserver'		=> APPPATH.'/observers/class.RESTEventObserver.php'
    	);

    	if (key_exists($class_name, $paths))
    	{
			require_once $paths[$class_name];
			return;
    	}

		// Load packaged classes
		if (strpos($class_name, "_") !== false) {
			$filename = str_replace("_", "/", $class_name) . ".php";
			require_once ($filename);
		}
	}
?>