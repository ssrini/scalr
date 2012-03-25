<?php
	
	class DBFarmRole
	{				
		const SETTING_EXCLUDE_FROM_DNS					= 	'dns.exclude_role';
		const SETTING_DNS_INT_RECORD_ALIAS				= 	'dns.int_record_alias';
		const SETTING_DNS_EXT_RECORD_ALIAS				= 	'dns.ext_record_alias';
		
		const SETTING_TERMINATE_IF_SNMP_FAILS			=	'health.terminate_if_snmp_fails';
		const SETTING_TERMINATE_ACTION_IF_SNMP_FAILS	= 	'health.terminate_action_if_snmp_fails'; // helps to reboot or terminate nonresponsable farm
				
		const SETTING_SCALING_ENABLED					=	'scaling.enabled';
		const SETTING_SCALING_MIN_INSTANCES				= 	'scaling.min_instances';
		const SETTING_SCALING_MAX_INSTANCES				= 	'scaling.max_instances';
		const SETTING_SCALING_POLLING_INTERVAL			= 	'scaling.polling_interval';
		const SETTING_SCALING_LAST_POLLING_TIME			= 	'scaling.last_polling_time';
		const SETTING_SCALING_KEEP_OLDEST				= 	'scaling.keep_oldest';
		const SETTING_SCALING_SAFE_SHUTDOWN				=	'scaling.safe_shutdown';
		const SETTING_SCALING_EXCLUDE_DBMSR_MASTER      =   'scaling.exclude_dbmsr_master';
		const SETTING_SCALING_ONE_BY_ONE				=   'scaling.one_by_one';
		
		//advanced timeout limits for scaling
		const SETTING_SCALING_UPSCALE_TIMEOUT			=	'scaling.upscale.timeout';
		const SETTING_SCALING_DOWNSCALE_TIMEOUT			=   'scaling.downscale.timeout';
		const SETTING_SCALING_UPSCALE_TIMEOUT_ENABLED	=	'scaling.upscale.timeout_enabled';
		const SETTING_SCALING_DOWNSCALE_TIMEOUT_ENABLED =	'scaling.downscale.timeout_enabled';
		const SETTING_SCALING_UPSCALE_DATETIME			=	'scaling.upscale.datetime';
		const SETTING_SCALING_DOWNSCALE_DATETIME		=	'scaling.downscale.datetime';
		
		//CloudFoundry Settings
		const SETTING_CF_STORAGE_ENGINE			=		'cf.data_storage.engine';
		const SETTING_CF_STORAGE_EBS_SIZE		= 		'cf.data_storage.ebs.size';
		const SETTING_CF_STORAGE_VOLUME_ID		= 		'cf.data_storage.volume_id';
		
		const SETTING_BALANCING_USE_ELB 		= 		'lb.use_elb';
		const SETTING_BALANCING_HOSTNAME 		= 		'lb.hostname';
		const SETTING_BALANCING_NAME 			= 		'lb.name';
		const SETTING_BALANCING_HC_TIMEOUT 		= 		'lb.healthcheck.timeout';
		const SETTING_BALANCING_HC_TARGET 		= 		'lb.healthcheck.target';
		const SETTING_BALANCING_HC_INTERVAL		= 		'lb.healthcheck.interval';
		const SETTING_BALANCING_HC_UTH 			= 		'lb.healthcheck.unhealthythreshold';
		const SETTING_BALANCING_HC_HTH 			= 		'lb.healthcheck.healthythreshold';
		const SETTING_BALANCING_HC_HASH 		= 		'lb.healthcheck.hash';
		const SETTING_BALANCING_AZ_HASH 		= 		'lb.avail_zones.hash';		
		
		/** AWS RDS Settings **/
		const SETTING_RDS_INSTANCE_CLASS 		= 		'rds.instance_class';
		const SETTING_RDS_AVAIL_ZONE			= 		'rds.availability_zone';
		const SETTING_RDS_STORAGE				=       'rds.storage';
		const SETTING_RDS_INSTANCE_ENGINE		= 		'rds.engine';
		const SETTING_RDS_MASTER_USER			= 		'rds.master-user';
		const SETTING_RDS_MASTER_PASS			= 		'rds.master-pass';
		const SETTING_RDS_MULTI_AZ				= 		'rds.multi-az';
		const SETTING_RDS_PORT					= 		'rds.port';
		
		/** RACKSPACE Settings **/
		const SETTING_RS_FLAVOR_ID				= 		'rs.flavor-id';
		
		/** OPENSTACK Settings **/
		const SETTING_OPENSTACK_FLAVOR_ID		= 		'openstack.flavor-id';
		
		/** NIMBULA Settings **/
		const SETTING_NIMBULA_SHAPE				=		'nimbula.shape';
		
		/** Cloudstack Settings **/
		const SETTING_CLOUDSTACK_SERVICE_OFFERING_ID		=		'cloudstack.service_offering_id';
		const SETTING_CLOUDSTACK_NETWORK_OFFERING_ID		=		'cloudstack.network_offering_id';
		const SETTING_CLOUDSTACK_NETWORK_ID					=		'cloudstack.network_id';
		const SETTING_CLOUDSTACK_NETWORK_TYPE				=		'cloudstack.network_type';
		
		/** EUCA Settings **/
		const SETTING_EUCA_INSTANCE_TYPE 		= 		'euca.instance_type';
		const SETTING_EUCA_AVAIL_ZONE	 		= 		'euca.availability_zone';
		
		/** AWS EC2 Settings **/
		const SETTING_AWS_INSTANCE_TYPE 		= 		'aws.instance_type';
		const SETTING_AWS_AVAIL_ZONE			= 		'aws.availability_zone';
		const SETTING_AWS_USE_ELASIC_IPS		= 		'aws.use_elastic_ips';
		const SETTING_AWS_USE_EBS				=		'aws.use_ebs';
		const SETTING_AWS_EBS_SIZE				=		'aws.ebs_size';
		const SETTING_AWS_EBS_SNAPID			=		'aws.ebs_snapid';
		const SETTING_AWS_EBS_MOUNT				=		'aws.ebs_mount';
		const SETTING_AWS_EBS_MOUNTPOINT		=		'aws.ebs_mountpoint';
		const SETTING_AWS_AKI_ID				= 		'aws.aki_id';
		const SETTING_AWS_ARI_ID				= 		'aws.ari_id';
		const SETTING_AWS_ENABLE_CW_MONITORING	= 		'aws.enable_cw_monitoring';
		const SETTING_AWS_SECURITY_GROUPS_LIST  = 		'aws.security_groups.list';
		const SETTING_AWS_S3_BUCKET				= 		'aws.s3_bucket';	
		const SETTING_AWS_SECURITY_GROUP		= 		'aws.security_group';
		const SETTING_AWS_CLUSTER_PG			= 		'aws.cluster_pg';
		
		const SETTING_AWS_SG_LIST				=		'aws.additional_security_groups';
		
		const SETTING_AWS_VPC_PRIVATE_IP		=		'aws.vpc.privateIpAddress';
		const SETTING_AWS_VPC_SUBNET_ID			=		'aws.vpc.subnetId';
		
		/** MySQL options **/
		const SETTING_MYSQL_PMA_USER			=		'mysql.pma.username';
		const SETTING_MYSQL_PMA_PASS			=		'mysql.pma.password';
		const SETTING_MYSQL_PMA_REQUEST_TIME	=		'mysql.pma.request_time';
		const SETTING_MYSQL_PMA_REQUEST_ERROR	=		'mysql.pma.request_error';
		
		const SETTING_MYSQL_BUNDLE_WINDOW_START = 		'mysql.bundle_window.start';
		const SETTING_MYSQL_BUNDLE_WINDOW_END	= 		'mysql.bundle_window.end';
		
		const SETTING_MYSQL_BUNDLE_WINDOW_START_HH = 'mysql.pbw1_hh';
		const SETTING_MYSQL_BUNDLE_WINDOW_START_MM = 'mysql.pbw1_mm';
		
		const SETTING_MYSQL_BUNDLE_WINDOW_END_HH = 'mysql.pbw2_hh';
		const SETTING_MYSQL_BUNDLE_WINDOW_END_MM = 'mysql.pbw2_mm';
		
		const SETTING_MYSQL_EBS_SNAPS_ROTATE			= 'mysql.ebs.rotate';
		const SETTING_MYSQL_EBS_SNAPS_ROTATION_ENABLED	= 'mysql.ebs.rotate_snaps';
		
		const SETTING_MYSQL_BCP_ENABLED 				= 'mysql.enable_bcp';
		const SETTING_MYSQL_BCP_EVERY 					= 'mysql.bcp_every';
		const SETTING_MYSQL_BUNDLE_ENABLED 				= 'mysql.enable_bundle';
		const SETTING_MYSQL_BUNDLE_EVERY 				= 'mysql.bundle_every';
		const SETTING_MYSQL_LAST_BCP_TS 				= 'mysql.dt_last_bcp';
		const SETTING_MYSQL_LAST_BUNDLE_TS 				= 'mysql.dt_last_bundle';
		const SETTING_MYSQL_IS_BCP_RUNNING 				= 'mysql.isbcprunning';
		const SETTING_MYSQL_IS_BUNDLE_RUNNING 			= 'mysql.isbundlerunning';
		const SETTING_MYSQL_BCP_SERVER_ID 				= 'mysql.bcp_server_id';
		const SETTING_MYSQL_BUNDLE_SERVER_ID 			= 'mysql.bundle_server_id';
		/*Scalr_Db_Msr*/ const SETTING_MYSQL_DATA_STORAGE_ENGINE 		= 'mysql.data_storage_engine';
		const SETTING_MYSQL_SLAVE_TO_MASTER 			= 'mysql.slave_to_master';
	
		/* MySQL users credentials */
		const SETTING_MYSQL_ROOT_PASSWORD				= 'mysql.root_password';
		const SETTING_MYSQL_REPL_PASSWORD				= 'mysql.repl_password';		
		const SETTING_MYSQL_STAT_PASSWORD				= 'mysql.stat_password';
		
		const SETTING_MYSQL_LOG_FILE					= 'mysql.log_file';		
		const SETTING_MYSQL_LOG_POS						= 'mysql.log_pos';
		
		/*Scalr_Db_Msr*/ const SETTING_MYSQL_SCALR_SNAPSHOT_ID			= 'mysql.scalr.snapshot_id';
		/*Scalr_Db_Msr*/ const SETTING_MYSQL_SCALR_VOLUME_ID				= 'mysql.scalr.volume_id';
		
		/*
		 * @deprecated
		 */
		const SETTING_MYSQL_SNAPSHOT_ID			= 'mysql.snapshot_id';
		const SETTING_MYSQL_MASTER_EBS_VOLUME_ID= 'mysql.master_ebs_volume_id';
		const SETTING_MYSQL_EBS_VOLUME_SIZE 	= 'mysql.ebs_volume_size';
		
		/////////////////////////////////////////////////
		
		const SETTING_MTA_PROXY_GMAIL			=		'mta.proxy.gmail'; // settings for mail transfer on Google mail
		const SETTING_MTA_GMAIL_LOGIN			=		'mta.gmail.login';
		const SETTING_MTA_GMAIL_PASSWORD		=		'mta.gmail.password';
		
		const SETTING_SYSTEM_REBOOT_TIMEOUT		=		'system.timeouts.reboot';
		const SETTING_SYSTEM_LAUNCH_TIMEOUT		= 		'system.timeouts.launch';
		
		
		
		
		public 
			$ID,
			$FarmID,
			$LaunchIndex,
			$RoleID,
			$NewRoleID,
			$CloudLocation,
			$Platform;
		
		private $DB,
				$dbRole,
				$SettingsCache;
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'farmid'		=> 'FarmID',
			'role_id'		=> 'RoleID',
			'new_role_id'	=> 'NewRoleID',
			'launch_index'	=> 'LaunchIndex',
			'platform'		=> 'Platform',
			'cloud_location'=> 'CloudLocation'
		);
		
		/**
		 * Constructor
		 * @param $instance_id
		 * @return void
		 */
		public function __construct($farm_roleid)
		{
			$this->DB = Core::GetDBInstance();
			
			$this->ID = $farm_roleid;
			
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		public function __sleep()
		{
			$retval = array("ID", "FarmID", "RoleID");
			
			return $retval;
		}
		
		public function __wakeup()
		{
			$this->DB = Core::GetDBInstance();
			$this->Logger = Logger::getLogger(__CLASS__);
		}
		
		public function applyDefinition($definition, $reset = false)
		{
			$resetSettings = array(
				DBFarmRole::SETTING_BALANCING_HOSTNAME,
				DBFarmRole::SETTING_BALANCING_NAME,
				DBFarmRole::SETTING_AWS_S3_BUCKET,
				DBFarmRole::SETTING_MYSQL_PMA_USER,
				DBFarmRole::SETTING_MYSQL_PMA_PASS,
				DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR,
				DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME,
				DBFarmRole::SETTING_MYSQL_LAST_BCP_TS,
				DBFarmRole::SETTING_MYSQL_LAST_BUNDLE_TS,
				DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING,
				DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING,
				DBFarmRole::SETTING_MYSQL_BCP_SERVER_ID,
				DBFarmRole::SETTING_MYSQL_BUNDLE_SERVER_ID,
				DBFarmRole::SETTING_MYSQL_SLAVE_TO_MASTER,
				DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD,
				DBFarmRole::SETTING_MYSQL_REPL_PASSWORD,
				DBFarmRole::SETTING_MYSQL_STAT_PASSWORD,
				DBFarmRole::SETTING_MYSQL_LOG_FILE,
				DBFarmRole::SETTING_MYSQL_LOG_POS,
				DBFarmRole::SETTING_MYSQL_SCALR_SNAPSHOT_ID,
				DBFarmRole::SETTING_MYSQL_SCALR_VOLUME_ID,
				DBFarmRole::SETTING_MYSQL_SNAPSHOT_ID,
				DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID,
				
				Scalr_Db_Msr::DATA_BACKUP_IS_RUNNING,
				Scalr_Db_Msr::DATA_BACKUP_LAST_TS,
				Scalr_Db_Msr::DATA_BACKUP_SERVER_ID,
				Scalr_Db_Msr::DATA_BUNDLE_IS_RUNNING,
				Scalr_Db_Msr::DATA_BUNDLE_LAST_TS,
				Scalr_Db_Msr::DATA_BUNDLE_SERVER_ID,
				Scalr_Db_Msr::SLAVE_TO_MASTER,
				Scalr_Db_Msr::SNAPSHOT_ID,
				Scalr_Db_Msr::VOLUME_ID
			);
			
			// Set settings
			foreach ($definition->settings as $key => $value) {
				if ($reset && in_array($key, $resetSettings))
					continue;
				$this->SetSetting($key, $value);
			}
			
			// Presets
			$presets = array();
			foreach ($definition->presets as $preset)
				$presets[$preset->behavior] = $preset->presetId;
			$this->SetServiceConfigPresets($presets);
			
			
			// Scripts
			$scripts = array();
			foreach ($definition->scripts as $script) {
				$scripts[] = array(
					'params' => $script->params,
					'target' => $script->target,
					'order_index' => $script->orderIndex,
					'version' => $script->version,
					'issync' => $script->isSync,
					'timeout' => $script->timeout,
					'event' => $script->event,
					'script_id' => $script->scriptId
				);
			}
			$this->SetScripts($scripts);
			
			// Scaling times
			$this->DB->Execute("DELETE FROM farm_role_scaling_times WHERE farm_roleid=?",
				array($this->ID)
			);

			foreach ($definition->scalingTimes as $scalingPeriod) {
				$this->DB->Execute("INSERT INTO farm_role_scaling_times SET
					farm_roleid		= ?,
					start_time		= ?,
					end_time		= ?,
					days_of_week	= ?,
					instances_count	= ?
				", array(
					$this->ID,
					$scalingPeriod->startTime,
					$scalingPeriod->endTime,
					$scalingPeriod->daysOfWeek,
					$scalingPeriod->instanceCount
				));
			}
			
			// metrics
			$scalingManager = new Scalr_Scaling_Manager($this);
			$metrics = array();
			foreach ($definition->scalingMetrics as $metric)
				$metrics[$metric->metricId] = $metric->settings;
				
			$scalingManager->setFarmRoleMetrics($metrics);
			
			// params
			$params = array();
			foreach ($definition->parameters as $param)
				$params[$param->name] = $param->value;
			
			$this->SetParameters($params);
			
			return true;
		}
		
		public function getDefinition() 
		{
			$roleDefinition = new stdClass();
			$roleDefinition->roleId = $this->RoleID;
			$roleDefinition->platform = $this->Platform;
			$roleDefinition->cloudLocation = $this->CloudLocation;
			
			// Settings
			$roleDefinition->settings = array();
			foreach ($this->GetAllSettings() as $k=>$v) {
				$roleDefinition->settings[$k] = $v;
			}
			
			// Presets
			$presets = $this->DB->GetAll("SELECT * FROM farm_role_service_config_presets WHERE farm_roleid = ?", array($this->ID));
			$roleDefinition->presets = array();
			foreach ($presets as $preset) {
				$itm = new stdClass();
				$itm->behavior = $preset['behavior'];
				$itm->presetId = $preset['preset_id'];
				$itm->restartServise = $preset['restart_service'];
				$roleDefinition->presets[] = $itm;
			}
			
			// Scripts
			$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farm_roleid=? AND issystem='1'", array($this->ID));
			$roleDefinition->scripts = array();
			foreach ($scripts as $script) {
				$itm = new stdClass();
				$itm->event = $script['event_name'];
				$itm->scriptId = $script['scriptid'];
				$itm->params = unserialize($script['params']);
				$itm->target = $script['target'];
				$itm->version = $script['version'];
				$itm->timeout = $script['timeout'];
				$itm->isSync = $script['issync'];
				$itm->isMenuItem = $script['ismenuitem'];
				$itm->orderIndex = $script['order_index'];
				
				$roleDefinition->scripts[] = $itm;
			}
			
			// Scaling times
			$scalingTimes = $this->DB->GetAll("SELECT * FROM farm_role_scaling_times WHERE farm_roleid = ?", array($this->ID));
			$roleDefinition->scalingTimes = array();
			foreach ($scalingTimes as $time) {
				$itm = new stdClass();
				$itm->startTime = $time['start_time'];
				$itm->endTime = $time['end_time'];
				$itm->daysOfWeek = $time['days_of_week'];
				$itm->instanceCount = $time['instances_count'];
				
				$roleDefinition->scalingTimes[] = $itm;
			}
			
			// Scaling metrics
			$scalingMetrics = $this->DB->GetAll("SELECT * FROM farm_role_scaling_metrics WHERE farm_roleid = ?", array($this->ID));
			$roleDefinition->scalingMetrics = array();
			foreach ($scalingMetrics as $metric) {
				$itm = new stdClass();
				$itm->metricId = $metric['metric_id'];
				$itm->settings = unserialize($metric['settings']);
				
				$roleDefinition->scalingMetrics[] = $itm;
			}
			
			// params
			$roleParams = $this->DB->GetAll("SELECT * FROM farm_role_options WHERE farm_roleid=?", array($this->ID));
			$roleDefinition->parameters = array();
			foreach ($roleParams as $param) {
				$itm = new stdClass();
				$itm->name = $param['name'];
				$itm->value = $param['value'];
			}
			
			return $roleDefinition;
		}
		
		/**
		 * 
		 * Returns DBFarmRole object by id
		 * @param $id
		 * @return DBFarmRole
		 */
		static public function LoadByID($id)
		{
			$db = Core::GetDBInstance();
			
			$farm_role_info = $db->GetRow("SELECT * FROM farm_roles WHERE id=?", array($id));
			if (!$farm_role_info)
				throw new Exception(sprintf(_("Farm Role ID #%s not found"), $id));
				
			$DBFarmRole = new DBFarmRole($farm_role_info['id']);
			foreach (self::$FieldPropertyMap as $k=>$v)
				$DBFarmRole->{$v} = $farm_role_info[$k];
				
			return $DBFarmRole;
		}
		
		/**
		 * Load DBInstance by database id
		 * @param $id
		 * @return DBFarmRole
		 */
		static public function Load($farmid, $roleid, $cloudLocation)
		{
			$db = Core::GetDBInstance();
			
			$farm_role_info = $db->GetRow("SELECT * FROM farm_roles WHERE farmid=? AND (role_id=? OR new_role_id=?) AND cloud_location=?", array($farmid, $roleid, $roleid, $cloudLocation));
			if (!$farm_role_info)
				throw new Exception(sprintf(_("Role #%s is not assigned to farm #%s"), $roleid, $farmid));
				
			$DBFarmRole = new DBFarmRole($farm_role_info['id']);
			foreach (self::$FieldPropertyMap as $k=>$v)
				$DBFarmRole->{$v} = $farm_role_info[$k];
				
			return $DBFarmRole;
		}
		
		/**
		 * Returns DBFarm Object
		 * @return DBFarm
		 */
		public function GetFarmObject()
		{
			if (!$this->DBFarm)
				$this->DBFarm = DBFarm::LoadByID($this->FarmID);
				
			return $this->DBFarm;
		}
		
		/**
		 * 
		 * @return DBRole
		 */
		public function GetRoleObject()
		{
			if (!$this->dbRole)
				$this->dbRole = DBRole::loadById($this->RoleID);
			
			return $this->dbRole;
		}
		
		/**
		 * Returns role prototype
		 * @return string
		 */
		public function GetRoleID()
		{
			return $this->RoleID;
		}
		
		/**
		 * Delete role from farm
		 * @return void
		 */
		public function Delete()
		{
			foreach ($this->GetServersByFilter() as $DBServer)
			{
				if ($DBServer->status != SERVER_STATUS::TERMINATED)
                {
                	try
					{
						PlatformFactory::NewPlatform($DBServer->platform)->TerminateServer($DBServer);
                           
						$this->DB->Execute("UPDATE servers_history SET
							dtterminated	= NOW(),
							terminate_reason	= ?
							WHERE server_id = ?
						", array(
							sprintf("Role removed from farm"),
							$DBServer->serverId
						));
					}
					catch(Exception $e){}
					
					$DBServer->status = SERVER_STATUS::TERMINATED;
			
					if (defined("SCALR_SERVER_TZ"))
					{
						$tz = date_default_timezone_get();
						date_default_timezone_set(SCALR_SERVER_TZ);
					}
				
					$DBServer->dateShutdownScheduled = date("Y-m-d H:i:s");
			
					if ($tz)
						date_default_timezone_set($tz);
			
					$event = new HostDownEvent($DBServer);
					Scalr::FireEvent($DBServer->farmId, $event);
						
					$DBServer->Save();
                }
			}

			//
			$this->DB->Execute("DELETE FROM farm_roles WHERE id=?", array($this->ID));
                           
            // Clear farm role options & scripts
			$this->DB->Execute("DELETE FROM farm_role_options WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("DELETE FROM farm_role_scripts WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("DELETE FROM farm_role_service_config_presets WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("DELETE FROM farm_role_scaling_metrics WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("DELETE FROM farm_role_scaling_times WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("DELETE FROM farm_role_service_config_presets WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("DELETE FROM farm_role_settings WHERE farm_roleid=?", array($this->ID));
			
			$this->DB->Execute("DELETE FROM ec2_ebs WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("DELETE FROM elastic_ips WHERE farm_roleid=?", array($this->ID));
			
			$this->DB->Execute("DELETE FROM storage_volumes WHERE farm_roleid=?", array($this->ID));
			
			// Clear apache vhosts and update DNS zones
			$this->DB->Execute("UPDATE apache_vhosts SET farm_roleid='0' WHERE farm_roleid=?", array($this->ID));
			$this->DB->Execute("UPDATE dns_zones SET farm_roleid='0' WHERE farm_roleid=?", array($this->ID));

			// Clear scheduler tasks
			$this->DB->Execute("DELETE FROM scheduler WHERE target_id = ? AND target_type = ?", array(
				$this->ID,
				Scalr_SchedulerTask::TARGET_ROLE
			));
			$this->DB->Execute("DELETE FROM scheduler WHERE target_id LIKE '" . $this->ID . ":%' AND target_type = ?", array(
				Scalr_SchedulerTask::TARGET_INSTANCE
			));
		}
		
		public function GetServiceConfiguration($behavior)
		{
			$preset_id = $this->DB->GetOne("SELECT preset_id FROM farm_role_service_config_presets WHERE farm_roleid=? AND behavior=?", array(
				$this->ID,
				$behavior
			));
			
			if ($preset_id)
				return Scalr_Model::init(Scalr_Model::SERVICE_CONFIGURATION)->loadById($preset_id);
			else
				return null;
		}
		
		public function GetPendingInstancesCount()
		{
			return $this->DB->GetOne("SELECT COUNT(*) FROM servers WHERE status IN(?,?,?) AND farm_roleid=?",
            	array(SERVER_STATUS::INIT, SERVER_STATUS::PENDING, SERVER_STATUS::PENDING_LAUNCH, $this->ID)
            );
		}
		
		public function GetRunningInstancesCount()
		{
			return $this->DB->GetOne("SELECT COUNT(*) FROM servers WHERE status = ? AND farm_roleid=?",
            	array(SERVER_STATUS::RUNNING, $this->ID)
            );
		}
		
		public function GetServersByFilter($filter_args = array(), $ufilter_args = array())
		{
			$sql = "SELECT server_id FROM servers WHERE `farm_roleid`=?";
			$args = array($this->ID);
			foreach ((array)$filter_args as $k=>$v)
			{
				if (is_array($v))
				{	
					foreach ($v as $vv)
						array_push($args, $vv);
					
					$sql .= " AND `{$k}` IN (".implode(",", array_fill(0, count($v), "?")).")";
				}
				else
				{
					$sql .= " AND `{$k}`=?";
					array_push($args, $v);
				}
			}
			
			foreach ((array)$ufilter_args as $k=>$v)
			{
				if (is_array($v))
				{	
					foreach ($v as $vv)
						array_push($args, $vv);
					
					$sql .= " AND `{$k}` NOT IN (".implode(",", array_fill(0, count($v), "?")).")";	
				}
				else
				{
					$sql .= " AND `{$k}`!=?";
					array_push($args, $v);
				}
			}
			
			$res = $this->DB->GetAll($sql, $args);
			
			$retval = array();
			foreach ((array)$res as $i)
			{
				if ($i['server_id'])
					$retval[] = DBServer::LoadByID($i['server_id']);
			}
			
			return $retval;
		}
		
		public function SetServiceConfigPresets(array $presets)
		{
			foreach ($this->GetRoleObject()->getBehaviors() as $behavior) {
				$farm_preset_id = $this->DB->GetOne("SELECT preset_id FROM farm_role_service_config_presets WHERE farm_roleid=? AND behavior=?", array(
					$this->ID,
					$behavior
				));
				
				$send_message = false;
				$msg = false;
				
				if ($presets[$behavior]) {
					if (!$farm_preset_id) {
						$this->DB->Execute("INSERT INTO farm_role_service_config_presets SET
							preset_id	= ?,
							farm_roleid	= ?,
							behavior	= ?,
							restart_service	= '1'
						", array(
							$presets[$behavior],
							$this->ID,
							$behavior
						));

						$send_message = true;
					}
					elseif ($farm_preset_id != $presets[$behavior]) {
						$this->DB->Execute("UPDATE farm_role_service_config_presets SET
							preset_id	= ?
						WHERE farm_roleid = ? AND behavior = ?
						", array(
							$presets[$behavior],
							$this->ID,
							$behavior
						));
						
						$send_message = true;
					}
					
					if ($send_message) {
						$msg = new Scalr_Messaging_Msg_UpdateServiceConfiguration(
							$behavior,
							0,
							1
						);
					}
				}
				else {
					if ($farm_preset_id) {
						$this->DB->Execute("DELETE FROM farm_role_service_config_presets WHERE farm_roleid=? AND behavior=?", array($this->ID, $behavior));
						$msg = new Scalr_Messaging_Msg_UpdateServiceConfiguration(
							$behavior,
							1,
							1
						);
					}
				}
				
				if ($msg)
				{
					foreach ($this->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $dbServer)
					{
						if ($dbServer->IsSupported("0.6"))
							$dbServer->SendMessage($msg);
					}
				}
			}
		}
		
		public function SetScripts(array $scripts)
		{
			$this->DB->Execute("DELETE FROM farm_role_scripts WHERE farm_roleid=?", array($this->ID));
			
			if (count($scripts) > 0)
			{						
				foreach ($scripts as $script)
				{							
					$config = $script['params'];
					
					$target = $script['target'];
					$order_index = (int)$script['order_index'];
					
					$version = $script['version'];
					$issync = $script['issync'];
					$timeout = (int)$script['timeout'];
					if (!$timeout)
						$timeout = CONFIG::$SYNCHRONOUS_SCRIPT_TIMEOUT;
					
					$event_name = $script['event'];
					$scriptid = $script['script_id'];
					if ($event_name && $scriptid)
					{
						$this->DB->Execute("INSERT INTO farm_role_scripts SET
							scriptid	= ?,
							farmid		= ?,
							farm_roleid	= ?,
							params		= ?,
							event_name	= ?,
							target		= ?,
							version		= ?,
							timeout		= ?,
							issync		= ?,
							order_index = ?,
							issystem	= '1'
						", array(
							$scriptid,
							$this->FarmID,
							$this->ID,
							serialize($config),
							$event_name,
							$target,
							$version,
							$timeout,
							$issync,
							$order_index
						));
					}
				}
			}
		}
		
		public function SetParameters(array $p_params)
		{
			if (count($p_params) > 0)
			{						
				$current_role_options = $this->DB->GetAll("SELECT * FROM farm_role_options WHERE farm_roleid=?", array($this->ID));
				$role_opts = array();
				foreach ($current_role_options as $cro)
					$role_opts[$cro['hash']] = md5($cro['value']);
										
				$params = array();
				foreach ($p_params as $name => $value)
				{
					if (preg_match("/^(.*?)\[(.*?)\]$/", $name, $matches))
					{
						$params[$matches[1]] = array();
						
						if ($matches[2] != '' && $value == 1)
							$params[$matches[1]][] = $matches[2];
	
						continue;
					}
					else
						$params[$name] = $value;
				}
	
				$saved_opts = array();
				foreach($params as $name => $value)
				{
					if ($name)
					{
						$val = (is_array($value)) ? implode(',', $value) : $value;
						$hash = preg_replace("/[^A-Za-z0-9]+/", "_", strtolower($name));
						
						if (!$role_opts[$hash])
						{
							$this->DB->Execute("INSERT INTO farm_role_options SET
								farmid		= ?,
								farm_roleid	= ?,
								name		= ?,
								value		= ?,
								hash	 	= ? 
								ON DUPLICATE KEY UPDATE name = ?
							", array(
								$this->FarmID,
								$this->ID,
								$name,
								$val,
								$hash,
								$name
							));
							
							$fire_event = true;
						}
						else
						{
							if (md5($val) != $role_opts[$hash])
							{
								$this->DB->Execute("UPDATE farm_role_options SET value = ? WHERE
									farm_roleid	= ? AND hash = ?	
								", array(
									$val,
									$this->ID,
									$hash
								));
								
								$fire_event = true;
							}
						}
						
						// Submit event only for existing farm. 
						// If we create a new farm, no need to fire this event.
						if ($fire_event)
						{
							Scalr::FireEvent($this->FarmID, new RoleOptionChangedEvent(
								$this, $hash
							));
							
							$fire_event = false;
						}
						
						$saved_opts[] = $hash;
					}
				}	
	
				foreach ($role_opts as $k=>$v)
				{
					if (!in_array($k, array_values($saved_opts)))
					{
						$this->DB->Execute("DELETE FROM farm_role_options WHERE farm_roleid = ? AND hash = ?",
							array($this->ID, $k)
						);
					}
				}
			}
		}
		
		/**
		 * Returns all role settings
		 * @return unknown_type
		 */
		public function GetAllSettings()
		{				
			return $this->GetSettingsByFilter();
		}
		
		/**
		 * Set farm role setting
		 * @param string $name
		 * @param mixed $value
		 * @return void
		 */
		public function SetSetting($name, $value)
		{
			if ($value === "" || $value === null)
			{
				$this->DB->Execute("DELETE FROM farm_role_settings WHERE name=? AND farm_roleid=?", array(
					$name, $this->ID
				));
			}
			else
			{
				$this->DB->Execute("INSERT INTO farm_role_settings SET name=?, value=?, farm_roleid=? ON DUPLICATE KEY UPDATE value=?",
					array($name, $value, $this->ID, $value)
				);
			}
			
			$this->SettingsCache[$name] = $value;
			
			return true;
		}
		
		/**
		 * Get Role setting by name
		 * @param string $name
		 * @return mixed
		 */
		public function GetSetting($name)
		{
			if (!$this->SettingsCache[$name])
			{
				$this->SettingsCache[$name] = $this->DB->GetOne("SELECT value FROM farm_role_settings WHERE name=? AND farm_roleid=?",
					array($name, $this->ID)
				);
			}
			
			return $this->SettingsCache[$name];
		}
		
		public function GetSettingsByFilter($filter = "")
		{
			$settings = $this->DB->GetAll("SELECT * FROM farm_role_settings WHERE farm_roleid=? AND name LIKE '%{$filter}%'", array($this->ID));
			$retval = array();
			foreach ($settings as $setting)
				$retval[$setting['name']] = $setting['value']; 
			
			$this->SettingsCache = array_merge($this->SettingsCache, $retval);
				
			return $retval;
		}
		
		public function ClearSettings($filter = "")
		{
			$this->DB->Execute("DELETE FROM farm_role_settings WHERE name LIKE '%{$filter}%' AND farm_roleid=?",
				array($this->ID)
			);
			
			$this->SettingsCache = array();
		}
		
		private function Unbind () {
			$row = array();
			foreach (self::$FieldPropertyMap as $field => $property) {
				$row[$field] = $this->{$property};
			}
			
			return $row;		
		}
		
		function Save () {
				
			$row = $this->Unbind();
			unset($row['id']);
			
			// Prepare SQL statement
			$set = array();
			$bind = array();
			foreach ($row as $field => $value) {
				$set[] = "`$field` = ?";
				$bind[] = $value;
			}
			$set = join(', ', $set);
	
			try	{
				// Perform Update
				$bind[] = $this->ID;
				$this->DB->Execute("UPDATE farm_roles SET $set WHERE id = ?", $bind);
				
			} catch (Exception $e) {
				throw new Exception ("Cannot save farm role. Error: " . $e->getMessage(), $e->getCode());			
			}
		}
	}
?>