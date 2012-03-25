<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	define('UPDATE_FROM_DB', 'scalr_test_2');
	define('UPDATE_TO_DB', 'scalr_test_1');
	
	$ScalrUpdate = new Update20100601();
	$ScalrUpdate->Run();
	
	class Update20100601
	{
		private $servers;
		
		function copyTables()
		{
			global $db;
			
			try
			{		
				$copy_tables = array('billing_packages', 'autosnap_settings','clients','client_settings','comments','config','countries','default_records','distributions',
					'ebs_array_snaps','ebs_snaps_info','farm_event_observers','farm_event_observers_config','farm_role_scaling_times',
					'farm_settings','farm_stats','garbage_queue','ipaccess','nameservers','payments','payment_redirects',
					'rds_snaps_info','role_options','scheduler_tasks','scripts','script_revisions','security_rules','sensor_data','subscriptions','task_queue','wus_info'
				);
				
				foreach ($copy_tables as $copy_table)
				{
					print "Copying table {$copy_table}...";
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".{$copy_table} SELECT * FROM ".UPDATE_FROM_DB.".{$copy_table}");
					print "OK\n";
				}
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (COPY): {$e->getMessage()}");
			}
		}
		
		function importRoles()
		{
			global $db;
			
			try
			{
				print "Importing roles...";
				$db->Execute("INSERT INTO ".UPDATE_TO_DB.".roles (
					`id`,`ami_id`,`name`,`roletype`,`clientid`,`comments`,`dtbuilt`,`description`,`default_minLA`,`default_maxLA`,`alias`,
					`instance_type`,`architecture`,`isstable`,`prototype_role`,`approval_state`,`region`,`default_ssh_port`,`platform`
				) SELECT `id`,`ami_id`,`name`,`roletype`,`clientid`,`comments`,`dtbuilt`,`description`,`default_minLA`,`default_maxLA`,`alias`,
					`instance_type`,`architecture`,'1',`prototype_role`,`approval_state`,`region`,`default_ssh_port`,'ec2' FROM ".UPDATE_FROM_DB.".roles WHERE iscompleted='1' ");

				print "OK\n";
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (ROLES): {$e->getMessage()}");
			}
		}
		
		function importFarmRoles()
		{
			global $db;
			
			try
			{
				print "Importing farm_roles...";
				$farm_roles = $db->Execute("SELECT * FROM ".UPDATE_FROM_DB.".farm_roles");
				$zomby = 0;
				$n = 0;
				while ($farm_role = $farm_roles->FetchRow())
				{
					$role_id = $db->GetOne("SELECT id FROM ".UPDATE_FROM_DB.".roles WHERE ami_id=?", array($farm_role['ami_id']));
					
					if (!$role_id)
					{
						$zomby++;
						continue;
					}
					
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".farm_roles SET
						id				= ?,
						farmid			= ?,
						dtlastsync		= ?,
						reboot_timeout	= ?,
						launch_timeout	= ?,
						status_timeout	= ?,
						launch_index	= ?,
						role_id			= ?,
						new_role_id		= NULL,
						platform		= ?
					", array(
						$farm_role['id'],
						$farm_role['farmid'],
						$farm_role['dtlastsync'],
						$farm_role['reboot_timeout'],
						$farm_role['launch_timeout'],
						$farm_role['status_timeout'],
						$farm_role['launch_index'],
						$role_id,
						'ec2'
					));
					
					//OPTIMIZE:
					
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".farm_role_settings SELECT * FROM ".UPDATE_FROM_DB.".farm_role_settings WHERE farm_roleid=?", 
						array($farm_role['id'])
					);
					
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".farm_role_scripts SELECT * FROM ".UPDATE_FROM_DB.".farm_role_scripts WHERE farm_roleid=?", 
						array($farm_role['id'])
					);
					
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".farm_role_options SELECT * FROM ".UPDATE_FROM_DB.".farm_role_options WHERE farm_roleid=? AND `hash` NOT IN ('apache_https_vhost_template','apache_http_vhost_template')", 
						array($farm_role['id'])
					);
					
					$n++;
					if ($n % 20 == 0)
					{
						print ".";
					}
				}
				
				print "OK (Zombies: {$zomby})\n";
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (FARM_ROLES): {$e->getMessage()}");
			}
		}
		
		function importFarms()
		{
			global $db;
			
			try
			{
				print "Importing farms...";
				$db->Execute("INSERT INTO ".UPDATE_TO_DB.".farms (
					`id`,`clientid`,`name`,`iscompleted`,`hash`,`dtadded`,`status`,`dtlaunched`,`term_on_sync_fail`,`region`,`farm_roles_launch_order`,
					`comments`
				) SELECT `id`,`clientid`,`name`,`iscompleted`,`hash`,`dtadded`,`status`,`dtlaunched`,`term_on_sync_fail`,`region`,`farm_roles_launch_order`,
					`comments` FROM ".UPDATE_FROM_DB.".farms");

				print "OK\n";
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (FARMS): {$e->getMessage()}");
			}
		}
		
		function importInstances()
		{
			global $db;
			
			try
			{
				print "Importing instances...";
				$farm_instances = $db->Execute("SELECT * FROM ".UPDATE_FROM_DB.".farm_instances");
				$zomby = 0;
				$n = 0;
				while ($farm_instance = $farm_instances->FetchRow())
				{
					$role_id = $db->GetOne("SELECT id FROM ".UPDATE_FROM_DB.".roles WHERE ami_id=?", array($farm_instance['ami_id']));
					$client_id = $db->GetOne("SELECT clientid FROM ".UPDATE_FROM_DB.".farms WHERE id=?", array($farm_instance['farmid']));
					
					if (!$role_id)
					{
						$zomby++;
						continue;
					}
					
					$server_id = Scalr::GenerateUID();
					
					$this->servers[$farm_instance['instance_id']] = $server_id;
					
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".servers SET
						`id`			= ?,
						`server_id`		= ?,
						`farm_id`		= ?,
						`farm_roleid`	= ?,
						`client_id`		= ?,
						`role_id`		= ?,
						`platform`		= ?,
						`status`		= ?,
						`remote_ip`		= ?,
						`local_ip`		= ?,
						`dtadded`		= ?,
						`index`			= ?
					", array(
						$farm_instance['id'],
						$server_id,
						$farm_instance['farmid'],
						$farm_instance['farm_roleid'],
						$client_id,
						$role_id,
						SERVER_PLATFORMS::EC2,
						$farm_instance['state'],
						$farm_instance['external_ip'],
						$farm_instance['internal_ip'],
						$farm_instance['dtadded'],
						$farm_instance['index']
					));
					
					$props = array(
						EC2_SERVER_PROPERTIES::AMIID 			=> $farm_instance['ami_id'],
						EC2_SERVER_PROPERTIES::ARCHITECTURE 	=> '',
						EC2_SERVER_PROPERTIES::AVAIL_ZONE 		=> $farm_instance['avail_zone'],
						EC2_SERVER_PROPERTIES::DB_MYSQL_MASTER 	=> $farm_instance['isdbmaster'],
						EC2_SERVER_PROPERTIES::INSTANCE_ID		=> $farm_instance['instance_id'],
						EC2_SERVER_PROPERTIES::INSTANCE_TYPE	=> $db->GetOne("SELECT value FROM ".UPDATE_FROM_DB.".farm_role_settings WHERE `farm_roleid`=? AND `name`=?", 
							array($farm_instance['farm_roleid'], DBFarmRole::SETTING_AWS_INSTANCE_TYPE)
						),
						EC2_SERVER_PROPERTIES::REGION			=> $farm_instance['region'],
						EC2_SERVER_PROPERTIES::SZR_VESION		=> ($farm_instance['scalarizr_pkg_version']) ? $farm_instance['scalarizr_pkg_version'] : '0.2-50' 
					);
					
					$db->Execute("REPLACE INTO ".UPDATE_TO_DB.".farm_role_settings SET farm_roleid=?, `name`=?, value=?", 
						array($farm_instance['farm_roleid'], DBFarmRole::SETTING_MYSQL_STAT_PASSWORD, $farm_instance['mysql_stat_password'])
					);
					
					foreach ($props as $k=>$v)
					{
						$db->Execute("INSERT INTO ".UPDATE_TO_DB.".server_properties SET
							`server_id`	= ?,
							`name`		= ?,
							`value`		= ?
						", array(
							$server_id, $k, $v
						));
					}
					
					$n++;
					if ($n % 50 == 0)
					{
						print ".";
					}
				}

				print "OK (Zombies: {$zomby})\n";
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (INSTANCES): {$e->getMessage()}");
			}
		}
		
		function importVhosts()
		{
			global $db;
			
			try
			{
				print "Importing vhosts...";
				$vhosts = $db->Execute("SELECT * FROM ".UPDATE_FROM_DB.".vhosts");
				while ($vhost = $vhosts->FetchRow())
				{
					$hash = ($vhost['issslenabled']) ? 'apache_https_vhost_template' : 'apache_http_vhost_template';
					
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".apache_vhosts SET
						`name`				= ?,
						`is_ssl_enabled`	= ?,
						`farm_id`			= ?,
						`farm_roleid`		= ?,
						`ssl_cert`			= ?,
						`ssl_key`			= ?,
						`ca_cert`			= '',
						`client_id`			= ?,
						`httpd_conf`		= ?,
						`httpd_conf_vars`	= ?,
						`advanced_mode`		= '0' ON DUPLICATE KEY UPDATE `name` = ?
					", array(
						$vhost['name'],
						$vhost['issslenabled'],
						$vhost['farmid'],
						$vhost['farm_roleid'],
						$vhost['ssl_cert'],
						$vhost['ssl_pkey'],
						$db->GetOne("SELECT clientid FROM ".UPDATE_FROM_DB.".farms WHERE id=?", array($vhost['farmid'])),
						$db->GetOne("SELECT value FROM ".UPDATE_FROM_DB.".farm_role_options WHERE farmid=? AND farm_roleid=? AND hash=?", array(
							$vhost['farmid'], $vhost['farm_roleid'], $hash
						)),
						serialize(array(
							'document_root' => $vhost['document_root_dir'],
							'logs_dir'		=> $vhost['logs_dir'],
							'server_admin'	=> $vhost['server_admin'],
							'server_alias'	=> $vhost['aliases']
						)),
						$vhost['name']
					));
				}

				print "OK\n";
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (VHOSTS): {$e->getMessage()}");
			}
		}
		
		function importElasticIps()
		{
			global $db;
			
			try
			{
				print "Importing elastic IPs...";
				$ips = $db->Execute("SELECT * FROM ".UPDATE_FROM_DB.".elastic_ips");
				while ($ip = $ips->FetchRow())
				{
					$server_id = '';
					try {
						
						if ($ip['instance_id'])
						{
							$server_id = $this->servers[$volume['instance_id']];
						}
						
					} catch (Exception $e) {}
					
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".elastic_ips SET
						`farmid`		= ?,
						`ipaddress`		= ?,
						`state`			= ?,
						`clientid`		= ?,
						`instance_index`= ?,
						`farm_roleid`	= ?,
						`server_id`		= ?
					", array(
						$ip['farmid'],
						$ip['ipaddress'],
						$ip['state'],
						$ip['clientid'],
						$ip['instance_index'],
						$ip['farm_roleid'],
						$server_id
					));
				}

				print "OK\n";
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (EIPS): {$e->getMessage()}");
			}
		}
		
		function importEBS()
		{
			global $db;
			
			try
			{
				print "Importing EBS...";
				$volumes = $db->Execute("SELECT * FROM ".UPDATE_FROM_DB.".farm_ebs");
				while ($volume = $volumes->FetchRow())
				{
					$server_id = '';
					$client_id = '';
					try {
						
						if ($volume['farmid'])
						{
							$client_id = $db->GetOne("SELECT clientid FROM ".UPDATE_FROM_DB.".farms WHERE id=?", array($volume['farmid']));
						}
						
						if ($volume['instance_id'])
						{
							$server_id = $this->servers[$volume['instance_id']];
						}
						
					} catch (Exception $e) {}
					
					if ($volume['state'] == 'Available')
					{
						$a_status = EC2_EBS_ATTACH_STATUS::AVAILABLE;
						$m_status = EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
					}
					elseif ($volume['state'] == 'Attached')
					{
						$a_status = EC2_EBS_ATTACH_STATUS::ATTACHED;
						$m_status = EC2_EBS_MOUNT_STATUS::MOUNTED;
					}
					elseif ($volume['state'] == 'Attaching')
					{
						$a_status = EC2_EBS_ATTACH_STATUS::ATTACHING;
						$m_status = EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
					}
					elseif ($volume['state'] == 'Creating')
					{
						$a_status = EC2_EBS_ATTACH_STATUS::CREATING;
						$m_status = EC2_EBS_MOUNT_STATUS::NOT_MOUNTED;
					}
					elseif ($volume['state'] == 'Mounting')
					{
						$a_status = EC2_EBS_ATTACH_STATUS::ATTACHED;
						$m_status = EC2_EBS_MOUNT_STATUS::MOUNTED;
					}
					
					if ($client_id)
					{
						$db->Execute("INSERT INTO ".UPDATE_TO_DB.".ec2_ebs SET
							`farm_id`		= ?,
							`farm_roleid`	= ?,
							`volume_id`		= ?,
							`server_id`		= ?,
							`attachment_status`	= ?,
							`mount_status`	= ?,
							`device`		= ?,
							`server_index`	= ?,
							`mount`			= ?,
							`mountpoint`	= ?,
							`ec2_avail_zone`= ?,
							`ec2_region`	= ?,
							`isfsexist`		= ?,
							`ismanual`		= ?,
							`size`			= ?,
							`snap_id`		= ?,
							`ismysqlvolume`	= ?,
							`client_id`		= ?
						", array(
							$volume['farmid'],
							$volume['farm_roleid'],
							$volume['volumeid'],
							$server_id,
							$a_status,
							$m_status,
							$volume['device'],
							$volume['instance_index'],
							(int)$volume['mount'],
							$volume['mountpoint'],
							$volume['avail_zone'],
							$volume['region'],
							$volume['isfsexists'],
							$volume['ismanual'],
							0,
							'',
							0,
							$client_id
						));
					}
				}

				print "OK\n";
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (EIPS): {$e->getMessage()}");
			}
		}
		
		public function importZones()
		{
			global $db;
			
			try
			{
				print "Importing DNS zones...";
				$zones = $db->Execute("SELECT * FROM ".UPDATE_FROM_DB.".zones");
				while ($zone = $zones->FetchRow())
				{
					$farm_role_id = $db->GetOne("SELECT id FROM ".UPDATE_FROM_DB.".farm_roles WHERE ami_id=? AND farmid=?", array($zone['ami_id'], $zone['farmid']));
					if (!$farm_role_id)
					{
						//print "Unable to import zone '{$zone['zone']}'. Farm role with AMI: {$zone['ami_id']} not found on farm '{$zone['farmid']}'.\n";
						//continue;
						$farm_role_id = '';
					}					
					
					$db->Execute("INSERT INTO ".UPDATE_TO_DB.".dns_zones SET
						id			= ?,
						client_id	= ?,
						farm_id		= ?,
						farm_roleid	= ?,
						zone_name	= ?,
						status		= ?,
						soa_owner	= ?,
						soa_ttl		= ?,
						soa_parent	= ?,
						soa_serial	= ?,
						soa_refresh	= ?,
						soa_retry	= ?,
						soa_expire	= ?,
						soa_min_ttl	= ?,
						dtlastmodified	= NOW(),
						axfr_allowed_hosts	= ?,
						allow_manage_system_records	= ?,
						isonnsserver	= ?,
						iszoneconfigmodified	= ?
					", array(
						$zone['id'],
						$zone['clientid'],
						$zone['farmid'],
						$farm_role_id,
						$zone['zone'],
						($zone['status'] == 0) ? DNS_ZONE_STATUS::PENDING_UPDATE : DNS_ZONE_STATUS::INACTIVE,
						$zone['soa_owner'],
						$zone['soa_ttl'],
						$zone['soa_parent'],
						$zone['soa_serial'],
						$zone['soa_refresh'],
						$zone['soa_retry'],
						$zone['soa_expire'],
						$zone['min_ttl'],
						$zone['axfr_allowed_hosts'],
						$zone['allow_manage_system_records'],
						'0',
						'1'
					));
					
					//IMPORT RECORDS
					$records = $db->Execute("SELECT * FROM ".UPDATE_FROM_DB.".records WHERE zoneid=?", array($zone['id']));
					while ($record = $records->FetchRow())
					{
						if ($record['issystem'] == 1)
						{
							if ($record['rtype'] == 'A')
							{
								$instance_id = $db->GetOne("SELECT instance_id FROM ".UPDATE_FROM_DB.".farm_instances WHERE (internal_ip=? OR external_ip=?) AND farmid=?", 
									array($record['rvalue'], $record['rvalue'], $zone['farmid'])
								);
								if (!$instance_id)
									continue;
								
								$server_id = $this->servers[$instance_id];
							}
							else
							{
								$server_id = '';
							}
							
							$db->Execute("REPLACE INTO ".UPDATE_TO_DB.".dns_zone_records SET
								`zone_id`	= ?,
								`type`		= ?,
								`ttl`		= ?,
								`priority`	= ?,
								`value`		= ?,
								`name`		= ?,
								`issystem`	= ?,
								`weight`	= ?,
								`port`		= ?,
								`server_id`	= ?
							", array(
								$zone['id'],
								$record['rtype'],
								$record['ttl'],
								(int)$record['rpriority'],
								$record['rvalue'],
								$record['rkey'],
								1,
								(int)$record['rweight'],
								(int)$record['rport'],
								$server_id
							));
						}
						else
						{
							$db->Execute("INSERT INTO ".UPDATE_TO_DB.".dns_zone_records SET
								`zone_id`	= ?,
								`type`		= ?,
								`ttl`		= ?,
								`priority`	= ?,
								`value`		= ?,
								`name`		= ?,
								`issystem`	= ?,
								`weight`	= ?,
								`port`		= ?,
								`server_id`	= ?
							", array(
								$zone['id'],
								$record['rtype'],
								$record['ttl'],
								(int)$record['rpriority'],
								$record['rvalue'],
								$record['rkey'],
								0,
								(int)$record['rweight'],
								(int)$record['rport'],
								''
							));
						}
					}		
				}
				
				print "Zones imported.\n";
			}
			catch(Exception $e)
			{
				$db->RollbackTrans();
				die("ERROR (ZONES): {$e->getMessage()}");
			}
		}
		
		function fixEBSMountPoints()
		{
			global $db;
			
			print "Patching mount settings for EBS volumes...";
			
			$i = 0;
			$volumes = $db->Execute("SELECT * FROM ".UPDATE_TO_DB.".ec2_ebs WHERE ismanual='0'");
			while ($volume = $volumes->FetchRow())
			{
				$mount = $db->GetOne("SELECT value FROM ".UPDATE_FROM_DB.".farm_role_settings WHERE farm_roleid=? AND `name`=?", array(
					$volume['farm_roleid'], 'aws.ebs_mount'
				));
				
				$mountpoint = $db->GetOne("SELECT value FROM ".UPDATE_FROM_DB.".farm_role_settings WHERE farm_roleid=? AND `name`=?", array(
					$volume['farm_roleid'], 'aws.ebs_mountpoint'
				));
				
				if ($mount == 1 && $volume['mount'] != 1)
				{
					$db->Execute("UPDATE ".UPDATE_TO_DB.".ec2_ebs SET mount=?, mountpoint=? WHERE id=?", array(
						$mount, $mountpoint, $volume['id']
					));
					$i++;
				}
			}
			
			print "OK. Patched {$i} volumes.";
		}
		
		function fixVhostTemplates()
		{
			global $db;
			print "Fixing vhost templates:";
			
			$k = 0;
			$fixed = 0;
			$vhosts = $db->Execute("SELECT * FROM ".UPDATE_TO_DB.".apache_vhosts");
			while ($vhost = $vhosts->FetchRow())
			{
				$farm_role_info = $db->GetRow("SELECT * FROM ".UPDATE_TO_DB.".farm_roles WHERE id=?", array($vhost['farm_roleid']));
				$role_info = $db->GetRow("SELECT * FROM ".UPDATE_TO_DB.".roles WHERE id=?", array($farm_role_info['role_id']));
				if (!$farm_role_info || !$role_info)
				{
					print "Zomby apache virtualhost: {$vhost['name']}\n";
					$db->Execute("DELETE FROM ".UPDATE_TO_DB.".apache_vhosts WHERE id=?", array($vhost['id']));
				}
				else
				{
					if ($role_info['alias'] == 'www')
					{
						if (!$vhost['httpd_conf'])
						{
							//print "Virtualhost {$vhost['name']} assigned to WWW role (Config: ".strlen($vhost['httpd_conf'])."). Fixing...\n";
							$farm_id = $vhost['farm_id'];
							$farm_roles = $db->GetOne("SELECT COUNT(*) FROM ".UPDATE_FROM_DB.".farm_roles INNER JOIN ".UPDATE_FROM_DB.".roles 
								ON roles.ami_id = farm_roles.ami_id WHERE farmid='{$farm_id}' AND alias='app'");
							if ($farm_roles != 1)
							{
								print "Virtualhost {$vhost['name']} cannot be fixed. Found {$farm_roles} app roles on farm\n";
							}
							else
							{
								$app_farm_roleid = $db->GetOne("SELECT ".UPDATE_FROM_DB.".farm_roles.id FROM ".UPDATE_FROM_DB.".farm_roles INNER JOIN ".UPDATE_FROM_DB.".roles 
									ON roles.ami_id = farm_roles.ami_id WHERE farmid='{$farm_id}' AND alias='app'");
								
								$http_template = $db->GetOne("SELECT value FROM ".UPDATE_FROM_DB.".farm_role_options WHERE farm_roleid=? AND hash=?", array(
									$app_farm_roleid, 'apache_http_vhost_template'
								));
								$https_template = $db->GetOne("SELECT value FROM ".UPDATE_FROM_DB.".farm_role_options WHERE farm_roleid=? AND hash=?", array(
									$app_farm_roleid, 'apache_https_vhost_template'
								));
								
								if ($http_template && $https_template)
								{
									$db->Execute("UPDATE apache_vhosts SET httpd_conf=?, httpd_conf_ssl=?, farm_roleid=? WHERE id=?",
										array($http_template, $https_template, $app_farm_roleid, $vhost['id'])
									);
									$fixed++;
								}
								else
								{
									print "Virtualhost {$vhost['name']} cannot be fixed. No templates found.\n";
								}
							}
						}
					}
					else
					{
						if (!$vhost['httpd_conf_ssl'])
						{
							$http_template = $db->GetOne("SELECT value FROM ".UPDATE_FROM_DB.".farm_role_options WHERE farm_roleid=? AND hash=?", array(
								$vhost['farm_roleid'], 'apache_http_vhost_template'
							));
							$https_template = $db->GetOne("SELECT value FROM ".UPDATE_FROM_DB.".farm_role_options WHERE farm_roleid=? AND hash=?", array(
								$vhost['farm_roleid'], 'apache_https_vhost_template'
							));
							
							if ($http_template && $https_template)
							{
								$db->Execute("UPDATE apache_vhosts SET httpd_conf=?, httpd_conf_ssl=? WHERE id=?",
									array($http_template, $https_template, $vhost['id'])
								);
								$fixed++;
							}
							else
							{
								print "Virtualhost {$vhost['name']} cannot be fixed. No templates found.\n";
							}
						}
					}
				}
			}
			
			print "{$fixed} virtualhosts FIXED.\n";
		}
		
		public function fixMySQLSettings()
		{
			global $db;
			
			print "Restoring MySQL settings:\n";
			
			$mysql_farm_roles = $db->GetAll("SELECT id, farmid FROM ".UPDATE_TO_DB.".farm_roles WHERE role_id IN (SELECT id FROM roles WHERE alias=?)", 
            	array(ROLE_ALIAS::MYSQL)
            );
            $f = 0;
            $o = 0;
            foreach ($mysql_farm_roles as $farm_role)
            {
            	$n = $db->GetOne("SELECT COUNT(*) FROM ".UPDATE_TO_DB.".farm_role_settings WHERE 
            		name IN ('mysql.enable_bcp','mysql.enable_bundle', 'mysql.mysql.data_storage_engine') AND farm_roleid=?", 
            	array($farm_role['id']));
            	if ($n == 0)
            	{
            		$settings = array();
            		$farm_settings = $db->Execute("SELECT * FROM ".UPDATE_FROM_DB.".farm_settings WHERE farmid=?", array($farm_role['farmid']));
            		foreach ($farm_settings as $setting)
            		{	
            			switch($setting['name'])
            			{
            				case 'mysql.dt_last_bcp':
            					$settings[DBFarmRole::SETTING_MYSQL_LAST_BCP_TS] = $setting['value'];
            					break;
            					
            				case 'mysql.dt_last_bundle':
            					$settings[DBFarmRole::SETTING_MYSQL_LAST_BUNDLE_TS] = $setting['value'];
            					break;
            				
            				case 'mysql.data_storage_engine':
            					$settings[DBFarmRole::SETTING_MYSQL_DATA_STORAGE_ENGINE] = $setting['value'];
            					break;

            				case 'mysql.ebs_volume_size':
            					$settings[DBFarmRole::SETTING_MYSQL_EBS_VOLUME_SIZE] = $setting['value'];
            					break;
            					
            				case 'mysql.master_ebs_volume_id':
            					$settings[DBFarmRole::SETTING_MYSQL_MASTER_EBS_VOLUME_ID] = $setting['value'];
            					break;
            					
            				case 'mysql.enable_bcp':
            					$settings[DBFarmRole::SETTING_MYSQL_BCP_ENABLED] = $setting['value'];
            					break;
            					
            				case 'mysql.bcp_every':
            					$settings[DBFarmRole::SETTING_MYSQL_BCP_EVERY] = $setting['value'];
            					break;
            				case 'mysql.enable_bundle':
            					$settings[DBFarmRole::SETTING_MYSQL_BUNDLE_ENABLED] = $setting['value'];
            					break;
            				case 'mysql.bundle_every':
            					$settings[DBFarmRole::SETTING_MYSQL_BUNDLE_EVERY] = $setting['value'];
            					break;
            			}
            		}
            		
            		if (count($setting) > 0)
            		{
	            		foreach ($settings as $k=>$v)
	            		{
	            			$db->Execute("REPLACE INTO ".UPDATE_TO_DB.".farm_role_settings SET
	            				name 	= ?,
	            				value	= ?,
	            				farm_roleid	= ?
	            			", array($k, $v, $farm_role['id']));
	            		}
            		}
            		
            		$f++;
            	}
            	else
            	{
            		$o++;
            	}
            }
            
            print "Roles fixed: {$f}. Normal roles: {$o}\n";
		}
		
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			//$db->BeginTrans();
			
			/*
			$this->copyTables();
			$this->importRoles();
			$this->importFarmRoles();
			$this->importFarms();
			$this->importInstances();
			$this->importVhosts();
			$this->importElasticIps();
			$this->importEBS();
			$this->importZones();
			*/
			
			//$this->fixEBSMountPoints();
			//$this->fixVhostTemplates();
			$this->fixMySQLSettings();
			
			//$db->RollbackTrans();
			//$db->CommitTrans();
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>