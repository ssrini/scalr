<?php
	define("NO_TEMPLATES",1);
	define("NO_SESSIONS", 1);

	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20100820();
	$ScalrUpdate->Run();

	class Update20100820
	{
		function updateAPIKeys()
		{
			global $db;
			
			$clients = $db->Execute("SELECT * FROM clients");
			while ($client = $clients->FetchRow())
			{
				$envid = $db->GetOne("SELECT id FROM client_environments WHERE client_id=? AND is_system='1'", array($client['id']));
				
				$props = array(
					ENVIRONMENT_SETTINGS::API_ENABLED => $db->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?", array($client['id'], CLIENT_SETTINGS::API_ENABLED)),
					ENVIRONMENT_SETTINGS::API_KEYID => $client['scalr_api_keyid'],
					ENVIRONMENT_SETTINGS::API_ACCESS_KEY => $client['scalr_api_key'],
					ENVIRONMENT_SETTINGS::API_ALLOWED_IPS => $db->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?", array($client['id'], CLIENT_SETTINGS::API_ALLOWED_IPS))
				);
				
				if (!$props[ENVIRONMENT_SETTINGS::API_KEYID])
				{
					$keys = Scalr::GenerateAPIKeys();
					
					$props[ENVIRONMENT_SETTINGS::API_KEYID] = $keys['id'];
					$props[ENVIRONMENT_SETTINGS::API_ACCESS_KEY] = $keys['key'];
				}
				
				foreach ($props as $k=>$v) {
					$db->Execute("INSERT INTO client_environment_properties SET env_id=?, name=?, value=?", array(
						$envid, $k, $v
					));
				}
			}
		}
		
		function createSshKeysStorage()
		{
			global $db;
			
			$db->Execute("create table `ssh_keys`(     `id` int(11) NOT NULL AUTO_INCREMENT ,     `client_id` int(11) ,     `env_id` int(11) ,     `type` varchar(10) ,     `private_key` text ,     `public_key` text ,     `cloud_location` varchar(255) ,     `farm_id` int(11) ,     PRIMARY KEY (`id`)  )  Engine=InnoDB comment='' row_format=Default  ;");
			$db->Execute("alter table `ssh_keys`     add column `cloud_key_name` varchar(255) NULL after `farm_id`;");
			
			$farms = $db->Execute("SELECT * FROM farms");
			while ($farminfo = $farms->FetchRow())
			{
				$key_name = $db->GetOne("SELECT value FROM farm_settings WHERE farmid=? AND `name`=?", array($farminfo['id'], "aws.keypair_name"));
				$ssh_priv = $db->GetOne("SELECT value FROM farm_settings WHERE farmid=? AND `name`=?", array($farminfo['id'], "aws.ssh_private_key"));
				$ssh_pub = $db->GetOne("SELECT value FROM farm_settings WHERE farmid=? AND `name`=?", array($farminfo['id'], "aws.ssh_public_key"));
				
				$sshKey = Scalr_Model::init(Scalr_Model::SSH_KEY);
					
				$sshKey->farmId = $farminfo['id'];
				$sshKey->clientId = $farminfo['clientid'];
				$sshKey->envId = $farminfo['env_id'];
				$sshKey->type = Scalr_SshKey::TYPE_GLOBAL;
				$sshKey->cloudLocation = $farminfo['region'];
				$sshKey->cloudKeyName = $key_name;
				$sshKey->platfrom = 'ec2';
				
				$sshKey->setPrivate($ssh_priv);
				$sshKey->setPublic($ssh_pub);
				
				$sshKey->save();
			}
		}
		
		function createCustomScalingMetrics()
		{
			global $db;
			
			$metrics = array(
				"la"	=> 1,
				"ram"	=> 2,
				"http"	=> 3,
				"sqs"	=> 4,
				"time"	=> 5,
				"bw"	=> 6
			);
			
			$db->Execute("create table `scaling_metrics`(     `id` int(11) NOT NULL AUTO_INCREMENT ,     `client_id` int(11) ,     `env_id` int(11) ,     `name` varchar(50) ,     `file_path` varchar(255) ,     `retrieve_method` varchar(20) ,     `calc_function` varchar(20) ,     PRIMARY KEY (`id`)  );");
			$db->Execute("alter table `scaling_metrics` add index `NewIndex1` (`client_id`);");
			$db->Execute("alter table `scaling_metrics` add index `NewIndex2` (`env_id`);");
			$db->Execute("alter table `scaling_metrics` add unique `NewIndex3` (`client_id`, `name`);");
			$db->Execute("alter table `scaling_metrics` engine = InnoDB;");
			
			$db->Execute("alter table `scaling_metrics`     add column `algorithm` varchar(15) NULL after `calc_function`;");
			$db->Execute("alter table `scaling_metrics`     add column `alias` varchar(25) NULL after `algorithm`;");
			
			
			$db->Execute("create table `farm_role_scaling_metrics`(     `id` int(11) NOT NULL AUTO_INCREMENT ,     `farm_roleid` int(11) ,     `metric_name` varchar(255) ,     `metric_id` int(11) ,     `dtlastpolled` datetime ,     `last_value` varchar(255) ,     PRIMARY KEY (`id`)  );");
			$db->Execute("alter table `farm_role_scaling_metrics` add index `NewIndex1` (`farm_roleid`);");
			$db->Execute("alter table `farm_role_scaling_metrics` add index `NewIndex2` (`metric_id`);");
			$db->Execute("alter table `farm_role_scaling_metrics` add unique `NewIndex4` (`farm_roleid`, `metric_id`);");
			
			$db->Execute("alter table `farm_role_scaling_metrics` drop column `metric_name`,    add column `settings` text NULL after `last_value`;");
			
			$db->Execute("insert  into `scaling_metrics`(`id`,`client_id`,`env_id`,`name`,`file_path`,`retrieve_method`,`calc_function`,`algorithm`,`alias`) values (1,0,0,'LoadAverages',NULL,NULL,'avg','Sensor','la'),(2,0,0,'FreeRam',NULL,NULL,'avg','Sensor','ram'),(3,0,0,'URLResponseTime',NULL,NULL,NULL,'Sensor','http'),(4,0,0,'SQSQueueSize',NULL,NULL,NULL,'Sensor','sqs'),(5,0,0,'DateAndTime',NULL,NULL,NULL,'DateTime','time'),(6,0,0,'BandWidth',NULL,NULL,NULL,'Sensor','bw');");
			
			//UPDATE Scaling options
			$farmRoles = $db->Execute("SELECT id FROM farm_roles");
			while ($farmRole = $farmRoles->FetchRow())
			{
				$metrics = array();
				$dbsettings = $db->Execute("SELECT * FROM farm_role_settings WHERE farm_roleid=?", array($farmRole['id']));
				$settings = array();
				while ($setting = $dbsettings->FetchRow())
					$settings[$setting['name']] = $setting['value'];
				
				if ($settings['scaling.la.enabled'] == 1)
				{
					$metrics[] = array('metric_id' => 1, "settings" => serialize(
						array('min' => $settings['scaling.la.min'], 'max' => $settings['scaling.la.max'], "period" => 15)
					));
				}
				
				if ($settings['scaling.ram.enabled'] == 1)
				{
					$metrics[] = array('metric_id' => 2, "settings" => serialize(
						array('min' => $settings['scaling.ram.min'], 'max' => $settings['scaling.ram.max'])
					));
				}
				
				if ($settings['scaling.httpresponsetime.enabled'] == 1)
				{
					$metrics[] = array('metric_id' => 3, "settings" => serialize(
						array('min' => $settings['scaling.httpresponsetime.min'], 'max' => $settings['scaling.httpresponsetime.max'], 'url' => $settings['scaling.httpresponsetime.url'])
					));
				}
				
				if ($settings['scaling.sqs.enabled'] == 1)
				{
					$metrics[] = array('metric_id' => 4, "settings" => serialize(
						array('min' => $settings['scaling.sqs.min'], 'max' => $settings['scaling.sqs.max'], 'queue_name' => $settings['scaling.sqs.queue_name'])
					));
				}
				
				if ($settings['scaling.bw.enabled'] == 1)
				{
					$metrics[] = array('metric_id' => 6, "settings" => serialize(
						array('min' => $settings['scaling.bw.min'], 'max' => $settings['scaling.bw.max'])
					));
				}
				
				if ($settings['scaling.time.enabled'] == 1)
				{
					$metrics[] = array('metric_id' => 5, "settings" => serialize(
						array()
					));
				}
				
				foreach ($metrics as $metric)
				{
					$db->Execute("INSERT INTO farm_role_scaling_metrics SET
						farm_roleid	= ?,
						metric_id	= ?,
						settings	= ?
					", array(
						$farmRole['id'],
						$metric['metric_id'],
						$metric['settings']
					));
				}
			}
		}
		
		function createServiceConfigurations()
		{
			global $db;
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `service_config_presets` (
				  `id` INT NOT NULL AUTO_INCREMENT ,
				  `env_id` INT NULL ,
				  `client_id` INT NULL ,
				  `name` VARCHAR(45) NULL ,
				  `role_behavior` VARCHAR(20) NULL ,
				  `dtadded` DATETIME NULL ,
				  `dtlastmodified` DATETIME NULL ,
				  PRIMARY KEY (`id`) ,
				  INDEX `env_id` (`env_id` ASC) ,
				  INDEX `client_id` (`client_id` ASC) ,
				  INDEX `name` (`name`(45) ASC) 
				) ENGINE = InnoDB;
			");
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `service_config_preset_data` (
				  `id` INT NOT NULL AUTO_INCREMENT ,
				  `preset_id` INT NOT NULL ,
				  `key` VARCHAR(45) NULL ,
				  `value` TEXT NULL ,
				  PRIMARY KEY (`id`)
				) ENGINE = InnoDB;	
			");
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `farm_role_service_config_presets` (
  					`id` INT NOT NULL AUTO_INCREMENT ,
  					`preset_id` INT NOT NULL ,
  					`farm_roleid` INT NULL ,
  					`behavior` VARCHAR(25) NULL ,
  					PRIMARY KEY (`id`) ,
  					INDEX `fk_farm_role_service_config_presets_service_config_presets1` (`preset_id` ASC) ,
  					INDEX `farm_roleid` (`farm_roleid` ASC) ,
  					INDEX `preset_id` (`preset_id` ASC)
  				) ENGINE = InnoDB;
			");
			
			$db->Execute("alter table `farm_role_service_config_presets`     add column `restart_service` tinyint(1) DEFAULT '1' NULL after `behavior`;");
		}
		
		protected function getCrypto()
		{
			if (! $this->crypto) {
				$this->crypto = new Scalr_Util_CryptoTool(MCRYPT_TRIPLEDES, MCRYPT_MODE_CFB, 24, 8);
				$this->cryptoKey = @file_get_contents(dirname(__FILE__)."/../etc/.cryptokey");
			}

			return $this->crypto;
		}

		protected function encryptValue($value)
		{
			return $this->getCrypto()->encrypt($value, $this->cryptoKey);
		}
		
		function CreateEnvironments()
		{
			global $db;

			$key = file_get_contents('/dev/urandom', null, null, 0, 512);
			if (! $key)
				throw new Exception("Null key generated");

			if (!file_exists(dirname(__FILE__)."/../etc/.cryptokey"))
			{
				$key = substr(base64_encode($key), 0, 512);
				file_put_contents(dirname(__FILE__)."/../etc/.cryptokey", $key);
			}

			$db->Execute("
				CREATE TABLE IF NOT EXISTS `client_environments` (
					`id` int(11) NOT NULL auto_increment,
					`name` varchar(255) NOT NULL,
					`client_id` int(11) NOT NULL,
					`dt_added` datetime NOT NULL,
					`is_system` tinyint(1) NOT NULL default '0',
					PRIMARY KEY  (`id`),
					KEY `client_id` (`client_id`)
				) ENGINE=InnoDB ;
			");

			$db->Execute("
				CREATE TABLE IF NOT EXISTS `client_environment_properties` (
					`id` int(11) NOT NULL auto_increment,
					`env_id` int(11) NOT NULL,
					`name` varchar(255) NOT NULL,
					`value` text NOT NULL,
					PRIMARY KEY  (`id`),
					UNIQUE KEY `env_id_2` (`env_id`,`name`),
					KEY `env_id` (`env_id`)
				) ENGINE=InnoDB ;
			");

			$db->Execute("ALTER TABLE `apache_vhosts` ADD `env_id` INT( 11 ) NOT NULL AFTER `client_id`, ADD INDEX ( `env_id` ) ");
			$db->Execute("ALTER TABLE `dns_zones` ADD `env_id` INT( 11 ) NOT NULL AFTER `client_id`, ADD INDEX ( `env_id` ) ");
			$db->Execute("ALTER TABLE `ec2_ebs` ADD `env_id` INT( 11 ) NOT NULL AFTER `client_id`, ADD INDEX ( `env_id` ) ");
			$db->Execute("ALTER TABLE `elastic_ips` ADD `env_id` INT( 11 ) NOT NULL AFTER `clientid`, ADD INDEX ( `env_id` ) ");
			$db->Execute("ALTER TABLE `farms` ADD `env_id` INT( 11 ) NOT NULL AFTER `clientid`, ADD INDEX ( `env_id` ) ");
			$db->Execute("ALTER TABLE `roles` ADD `env_id` INT( 11 ) NOT NULL AFTER `clientid`, ADD INDEX ( `env_id` ) ");
			$db->Execute("ALTER TABLE `servers` ADD `env_id` INT( 11 ) NOT NULL AFTER `client_id`, ADD INDEX ( `env_id` ) ");
			$db->Execute("ALTER TABLE `bundle_tasks` ADD `env_id` INT( 11 ) NOT NULL AFTER `client_id`, ADD INDEX ( `env_id` ) ");
			$db->Execute("ALTER TABLE `autosnap_settings` ADD `env_id` INT( 11 ) NOT NULL AFTER `clientid`, ADD INDEX ( `env_id` ) ");
			$db->Execute("alter table `scheduler_tasks`     add column `env_id` int(11) NULL after `status`;");

			$Crypto = Core::GetInstance("Crypto", CONFIG::$CRYPTOKEY);
			$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));

			$clients = $db->Execute("SELECT * FROM clients");
			while ($client = $clients->FetchRow()) {
				
				$db->Execute("INSERT INTO client_environments SET
					name		= ?,
					client_id	= ?,
					dt_added	= NOW(),
					is_system	= '1'
				", array("default", $client['id']));
				$env_id = $db->Insert_Id();

				$config = array();

				$config_n[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT] = $db->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?", array($client['id'], CLIENT_SETTINGS::MAX_INSTANCES_LIMIT));
				if (!$config_n[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT])
					$config_n[ENVIRONMENT_SETTINGS::MAX_INSTANCES_LIMIT] = 20;
					
				$config_n[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT] = $db->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?", array($client['id'], CLIENT_SETTINGS::MAX_EIPS_LIMIT));
				if (!$config_n[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT])
					$config_n[ENVIRONMENT_SETTINGS::MAX_EIPS_LIMIT] = 5;
				
				$config_n[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT] = $db->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?", array($client['id'], CLIENT_SETTINGS::SYNC_TIMEOUT));
				if (!$config_n[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT])
					$config_n[ENVIRONMENT_SETTINGS::SYNC_TIMEOUT] = 86400;
				
				$config_n[ENVIRONMENT_SETTINGS::TIMEZONE] = $db->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?", array($client['id'], CLIENT_SETTINGS::TIMEZONE));

				if ($client['aws_accountid'])
				{
					if ($client['aws_accountid']) {
						$config[Modules_Platforms_Ec2::ACCOUNT_ID] = $client['aws_accountid'];
						$config[Modules_Platforms_Rds::ACCOUNT_ID] = $client['aws_accountid'];
					}
	
					if ($client['aws_private_key_enc']) {
						$config[Modules_Platforms_Ec2::PRIVATE_KEY] = $Crypto->Decrypt($client["aws_private_key_enc"], $cpwd);
						$config[Modules_Platforms_Rds::PRIVATE_KEY] = $Crypto->Decrypt($client["aws_private_key_enc"], $cpwd);
					}
	
					if ($client['aws_certificate_enc']) {
						$config[Modules_Platforms_Ec2::CERTIFICATE] = $Crypto->Decrypt($client["aws_certificate_enc"], $cpwd);
						$config[Modules_Platforms_Rds::CERTIFICATE] = $Crypto->Decrypt($client["aws_certificate_enc"], $cpwd);
					}
	
					if ($client['aws_accesskeyid']) {
						$config[Modules_Platforms_Ec2::ACCESS_KEY] = $Crypto->Decrypt($client["aws_accesskeyid"], $cpwd);
						$config[Modules_Platforms_Rds::ACCESS_KEY] = $Crypto->Decrypt($client["aws_accesskeyid"], $cpwd);
					}
	
					if ($client['aws_accesskey']) {
						$config[Modules_Platforms_Ec2::SECRET_KEY] = $Crypto->Decrypt($client["aws_accesskey"], $cpwd);
						$config[Modules_Platforms_Rds::SECRET_KEY] = $Crypto->Decrypt($client["aws_accesskey"], $cpwd);
					}
	
					$config_n[SERVER_PLATFORMS::EC2 . '.is_enabled'] = 1;
					$config_n[SERVER_PLATFORMS::RDS . '.is_enabled'] = 1;
				
					if (count($config)) {
						foreach ($config as $key => $value) {
							$value = $this->encryptValue($value);
							$db->Execute("INSERT INTO client_environment_properties SET env_id = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?", 
								array($env_id, $key, $value, $value));
						}
					}
				}
				
				foreach ($config_n as $key => $value) {
					$db->Execute("INSERT INTO client_environment_properties SET env_id = ?, name = ?, value = ? ON DUPLICATE KEY UPDATE value = ?", 
						array($env_id, $key, $value, $value));
				}

				$db->Execute("UPDATE `apache_vhosts` SET env_id = ? WHERE client_id = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `dns_zones` SET env_id = ? WHERE client_id = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `ec2_ebs` SET env_id = ? WHERE client_id = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `elastic_ips` SET env_id = ? WHERE clientid = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `farms` SET env_id = ? WHERE clientid = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `roles` SET env_id = ? WHERE clientid = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `servers` SET env_id = ? WHERE client_id = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `bundle_tasks` SET env_id = ? WHERE client_id = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `autosnap_settings` SET env_id = ? WHERE clientid = ?", array($env_id, $client['id']));
				$db->Execute("UPDATE `scheduler_tasks` SET env_id = ? WHERE client_id = ?", array($env_id, $client['id']));
			}
			
			$db->Execute("alter table `farm_role_scripts`     add column `issystem` tinyint(1) DEFAULT '0' NULL after `farm_roleid`;");
			$db->Execute("UPDATE farm_role_scripts SET issystem='1' WHERE event_name NOT LIKE 'CustomEvent%' AND event_name NOT LIKE 'APIEvent%'");
		}

		function AlterTables()
		{
			global $db;

			$db->Execute("alter table `farm_role_settings` change `value` `value` text NULL ;");
			$db->Execute("alter table `bundle_tasks`     add column `cloud_location` varchar(50) NULL after `farm_id`;");
			$db->Execute("alter table `bundle_tasks`     change `failure_reason` `failure_reason` text NULL ;");
			
			$db->Execute("drop table `zones`");
			$db->Execute("drop table `vhosts`");
			$db->Execute("drop table `farm_instances`");
			$db->Execute("drop table `farm_ebs`");
			
			$db->Execute("alter table `bundle_tasks`     add column `meta_data` text NULL after `cloud_location`;");
			
			$db->Execute("alter table `api_log`     add column `env_id` int(11) NULL after `clientid`;");
		}


		function UpdateFarmRoles()
		{
			global $db;
			
			$farm_roles = $db->Execute("SELECT * FROM farm_roles");
			while ($farm_role = $farm_roles->FetchRow())
			{
				$db->Execute("INSERT INTO farm_role_settings SET farm_roleid=?, `name`=?, `value`=?", array(
					$farm_role['id'],
					DBFarmRole::SETTING_SYSTEM_LAUNCH_TIMEOUT,
					$farm_role['launch_timeout']
				));
				
				$db->Execute("INSERT INTO farm_role_settings SET farm_roleid=?, `name`=?, `value`=?", array(
					$farm_role['id'],
					DBFarmRole::SETTING_SYSTEM_REBOOT_TIMEOUT,
					$farm_role['reboot_timeout']
				));
				
				$farminfo = $db->GetRow("SELECT id, region FROM farms WHERE id=?", array($farm_role['farmid']));
				if ($farminfo)
				{
					$db->Execute("INSERT INTO farm_role_settings SET farm_roleid=?, `name`=?, `value`=?", array(
						$farm_role['id'], DBFarmRole::SETTING_CLOUD_LOCATION, $farminfo['region']
					));

					$s3_bucket = $db->GetOne("SELECT value FROM farm_settings WHERE farmid=? AND `name`=?", array($farminfo['id'], DBFarm::SETTING_AWS_S3_BUCKET_NAME));
					if ($s3_bucket)
					{
						$db->Execute("INSERT INTO farm_role_settings SET farm_roleid=?, `name`=?, `value`=?", array(
							$farm_role['id'],
							DBFarmRole::SETTING_AWS_S3_BUCKET,
							$s3_bucket
						));
					}
				}
			}
		}

		function rebuildRoles()
		{
			global $db;
			
			$db->Execute("rename table `roles` to `_roles`;");
			$db->Execute("rename table `role_options` to `_role_options`;");
			$db->Execute("rename table `security_rules` to `_security_rules`;");
			
			$db->Execute("create table `role_behaviors`(     `id` int(11) NOT NULL AUTO_INCREMENT ,     `role_id` int(11) ,     `behavior` varchar(15) ,     PRIMARY KEY (`id`)  )  Engine=InnoDB;");
			$db->Execute("alter table `role_behaviors` add index `role_id` (`role_id`);");
			$db->Execute("alter table `role_behaviors` add unique `role_id_behavior` (`role_id`, `behavior`);");
			
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `roles` (
  					`id` INT NOT NULL AUTO_INCREMENT ,
  					`name` VARCHAR(100) NULL ,
  					`origin` ENUM('SHARED','CUSTOM') NULL ,
  					`client_id` INT NULL ,
  					`env_id` INT NULL ,
  					`description` TEXT NULL ,
  					`behaviors` VARCHAR(90) NULL ,
  					`architecture` ENUM('i386','x86_64') NULL ,
  					`is_stable` TINYINT(1) NULL DEFAULT 1 ,
  					`history` TEXT NULL ,
  					`approval_state` VARCHAR(20) NULL ,
  					`generation` TINYINT NULL DEFAULT 1 ,
  					`os` VARCHAR(20) NULL ,
  				PRIMARY KEY (`id`) )
				ENGINE = InnoDB;
			");
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `role_properties` (
  					`id` INT NOT NULL AUTO_INCREMENT ,
  					`role_id` INT NOT NULL ,
  					`name` VARCHAR(255) NULL ,
  					`value` TEXT NULL ,
  				PRIMARY KEY (`id`) ,
				INDEX `role_id` (`role_id` ASC))
				ENGINE = InnoDB;
			");
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `role_software` (
  					`id` INT NOT NULL AUTO_INCREMENT ,
  					`role_id` INT NOT NULL ,
  					`software_name` VARCHAR(45) NULL ,
  					`software_version` VARCHAR(20) NULL ,
  					`software_key` VARCHAR(20) NULL ,
  				PRIMARY KEY (`id`) ,
  				INDEX `role_id` (`role_id` ASC))
				ENGINE = InnoDB;
			");
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `role_parameters` (
  					`id` INT NOT NULL AUTO_INCREMENT ,
  					`role_id` INT NOT NULL ,
  					`name` VARCHAR(45) NULL ,
  					`type` VARCHAR(45) NULL ,
  					`isrequired` TINYINT(1) NULL ,
  					`defval` TEXT NULL ,
  					`allow_multiple_choice` TINYINT(1) NULL ,
  					`options` TEXT NULL ,
  					`hash` VARCHAR(45) NULL ,
  					`issystem` TINYINT(1) NULL ,
  				PRIMARY KEY (`id`) ,
  				INDEX `role_id` (`role_id` ASC))
				ENGINE = InnoDB;
			");
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `role_security_rules` (
  					`id` INT NOT NULL AUTO_INCREMENT ,
  					`role_id` INT NOT NULL ,
  					`rule` VARCHAR(90) NULL ,
  				PRIMARY KEY (`id`) ,
  				INDEX `role_id` (`role_id` ASC))
				ENGINE = InnoDB;
			");
			
			$db->Execute("
				CREATE  TABLE IF NOT EXISTS `role_images` (
  					`id` INT NOT NULL AUTO_INCREMENT ,
  					`role_id` INT NOT NULL ,
  					`cloud_location` VARCHAR(25) NULL ,
  					`image_id` VARCHAR(25) NULL ,
  					`platform` VARCHAR(25) NULL ,
  				PRIMARY KEY (`id`) ,
  				INDEX `role_id` (`role_id` ASC))
				ENGINE = InnoDB;
			");
			
			$db->Execute("alter table `role_images` add index `NewIndex1` (`platform`(25));");
			$db->Execute("alter table `role_images` add index `NewIndex2` (`cloud_location`(25));");
			$db->Execute("alter table `roles` add index `NewIndex1` (`origin`);");
			$db->Execute("alter table `roles` add index `NewIndex2` (`client_id`);");
			$db->Execute("alter table `roles` add index `NewIndex3` (`env_id`);");
			$db->Execute("alter table `role_properties` add unique `NewIndex1` (`role_id`, `name`);");
			
			
			$roles = $db->Execute("SELECT * FROM _roles WHERE clientid != 0");
			while ($role = $roles->FetchRow())
			{
				if (!$role['alias'])
					continue;
				
				if (stristr($role['name'], 'ubuntu-10-04'))
				{
					$os = 'Ubuntu 10.04';
					$generation = '1';
				}
				elseif (stristr($role['name'], 'centos'))
				{
					$os = 'CentOS 5.4';
					$generation = '2';
				}
				else
				{
					$os = 'Unknown';
					$generation = '1';
				}
				
				$db->Execute("INSERT INTO roles SET
					id			= ?,
					name		= ?,
					origin		= ?,
					client_id	= ?,
					env_id		= ?,
					description	= ?,
					behaviors	= ?,
					architecture= ?,
					is_stable	= ?,
					history		= ?,
					approval_state	= ?,
					generation	= ?,
					os			= ?
				", array(
					$role['id'],
					$role['name'],
					$role['roletype'],
					$role['clientid'],
					$role['env_id'],
					$role['description'],
					$role['alias'],
					$role['architecture'],
					$role['isstable'],
					'',
					$role['approval_state'],
					$generation,
					$os
				));
				
				$db->Execute("INSERT INTO role_properties SET
					role_id			= ?,
					name			= ?,
					value			= ?
				", array(
					$role['id'],
					DBRole::PROPERTY_SSH_PORT,
					$role['default_ssh_port']
				));
				
				$db->Execute("INSERT INTO role_behaviors SET
					role_id			= ?,
					behavior		= ?
				", array(
					$role['id'],
					$role['alias']
				));
				
				$db->Execute("INSERT INTO role_images SET
					role_id			= ?,
					cloud_location	= ?,
					image_id		= ?,
					platform		= ?
				", array(
					$role['id'],
					$role['region'],
					$role['ami_id'],
					$role['platform']
				));
				
				$options = $db->Execute("SELECT * FROM _role_options WHERE ami_id=?", array($role['ami_id']));
				while ($option = $options->FetchRow())
				{
					$db->Execute("INSERT INTO role_parameters SET
						role_id		= ?,
						name		= ?,
						type		= ?,
						isrequired	= ?,
						defval		= ?,
						allow_multiple_choice	= ?,
						options		= ?,
						hash		= ?,
						issystem	= ?
					", array(
						$role['id'],
						$option['name'],
						$option['type'],
						$option['isrequired'],
						$option['defval'],
						$option['allow_multiple_choice'],
						$option['options'],
						$option['hash'],
						$option['issystem']
					));
				}
				
				$rules = $db->Execute("SELECT * FROM _security_rules WHERE roleid=?", array($role['id']));
				while ($rule = $rules->FetchRow())
				{
					$db->Execute("INSERT INTO role_security_rules SET
						role_id	= ?,
						rule	= ?
					", array(
						$role['id'],
						$rule['rule']
					));
				}
			}
		}
		
		public function rebuildRoles2()
		{
			global $db;
			
			
			$roles = $db->Execute("SELECT * FROM _roles WHERE clientid = 0 GROUP BY name");
			while ($role = $roles->FetchRow())
			{	
				if (stristr($role['name'], 'ubuntu-10-04'))
				{
					$os = 'Ubuntu 10.04';
					$generation = '1';
				}
				elseif (stristr($role['name'], 'centos'))
				{
					$os = 'CentOS 5.4';
					$generation = '2';
				}
				else
				{
					$os = 'Ubuntu 8.04';
					$generation = '1';
				}
				
				$db->Execute("INSERT INTO roles SET
					id			= ?,
					name		= ?,
					origin		= ?,
					client_id	= ?,
					env_id		= ?,
					description	= ?,
					behaviors	= ?,
					architecture= ?,
					is_stable	= ?,
					history		= ?,
					approval_state	= ?,
					generation	= ?,
					os			= ?
				", array(
					$role['id'],
					$role['name'],
					$role['roletype'],
					$role['clientid'],
					$role['env_id'],
					$role['description'],
					$role['alias'],
					$role['architecture'],
					$role['isstable'],
					'',
					$role['approval_state'],
					$generation,
					$os
				));
				
				$db->Execute("INSERT INTO role_behaviors SET
					role_id			= ?,
					behavior		= ?
				", array(
					$role['id'],
					$role['alias']
				));
				
				$db->Execute("INSERT INTO role_properties SET
					role_id			= ?,
					name			= ?,
					value			= ?
				", array(
					$role['id'],
					DBRole::PROPERTY_SSH_PORT,
					$role['default_ssh_port']
				));
				
				$images = $db->Execute("SELECT * FROM _roles WHERE name='{$role['name']}'");
				foreach ($images as $image)
				{
					$db->Execute("INSERT INTO role_images SET
						role_id			= ?,
						cloud_location	= ?,
						image_id		= ?,
						platform		= ?
					", array(
						$role['id'],
						$image['region'],
						$image['ami_id'],
						$image['platform']
					));
				}
				
				$options = $db->Execute("SELECT * FROM _role_options WHERE ami_id=?", array($role['ami_id']));
				while ($option = $options->FetchRow())
				{
					$db->Execute("INSERT INTO role_parameters SET
						role_id		= ?,
						name		= ?,
						type		= ?,
						isrequired	= ?,
						defval		= ?,
						allow_multiple_choice	= ?,
						options		= ?,
						hash		= ?,
						issystem	= ?
					", array(
						$role['id'],
						$option['name'],
						$option['type'],
						$option['isrequired'],
						$option['defval'],
						$option['allow_multiple_choice'],
						$option['options'],
						$option['hash'],
						$option['issystem']
					));
				}
				
				$rules = $db->Execute("SELECT * FROM _security_rules WHERE roleid=?", array($role['id']));
				while ($rule = $rules->FetchRow())
				{
					$db->Execute("INSERT INTO role_security_rules SET
						role_id	= ?,
						rule	= ?
					", array(
						$role['id'],
						$rule['rule']
					));
				}
			}
		}
		
		public function rebuildRoles3()
		{
			global $db;
			
			$dbfarmroles = $db->Execute("SELECT * FROM farm_roles");
			while ($farmRole = $dbfarmroles->FetchRow())
			{
				$role_id = $db->GetOne("SELECT id FROM roles WHERE id=?", array($farmRole['role_id']));
				if (!$role_id)
				{
					print "\nFound zomby farm role...";
					$ami_id = $db->GetOne("SELECT ami_id FROM scalr20101020.roles WHERE id=?", array($farmRole['role_id']));
					print "AMI-ID: {$ami_id}";
					$role_id = $db->GetOne("SELECT role_id FROM role_images WHERE image_id=?", array($ami_id));
					print "Role-ID: {$role_id}";
					if ($role_id)
					{
						$db->Execute("UPDATE farm_roles SET role_id = ? WHERE role_id = ?", array($role_id, $farmRole['role_id']));
						$db->Execute("UPDATE servers SET role_id = ? WHERE role_id = ?", array($role_id, $farmRole['role_id']));
						print "Fixed.";
					}
					else
						print "Failed.";
				}
			}
			
			return;
		}
		
		public function fixELB()
		{
			global $db;
			$dbfarmroles = $db->Execute("SELECT id FROM farm_roles");
			while ($farmRole = $dbfarmroles->FetchRow())
			{
				$DBFarmRole = DBFarmRole::LoadByID($farmRole['id']);
				if ($DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB) == 1)
				{
					if (!$DBFarmRole->GetSetting(DBFarmRole::SETTING_BALANCING_HOSTNAME))
					{
						print "Fixing Farm RoleID #{$DBFarmRole->ID}";
						$DBFarmRole->SetSetting(DBFarmRole::SETTING_BALANCING_USE_ELB, 0);
						print " Done. \n";
					}
				}
			}
		}
		
		function Run()
		{
			global $db;

			$time = microtime(true);
			//$db->BeginTrans();

			try
			{
				/*
				print "Building environments...";
				$this->CreateEnvironments();
				print " Done.\n";
				
				print "Altering tables...";
				$this->AlterTables();
				print " Done.\n";
				
				print "Updating farm roles...";
				$this->UpdateFarmRoles();
				print " Done.\n";
				
				print "Updating roles (step 1 of 2)...";
				$this->rebuildRoles();
				print " Done.\n";
				
				print "Updating roles (step 2 of 3)...";
				$this->rebuildRoles2();
				print " Done.\n";
				
				print "Creating service configuration presets...";
				$this->createServiceConfigurations();
				print " Done.\n";
				
				print "Creating scaling metrics...";
				$this->createCustomScalingMetrics();
				print " Done.\n";
				
				print "Updating API keys...";
				$this->updateAPIKeys();
				print " Done.\n";
				
				print "Building secure SSH keys storage...";
				$this->createSshKeysStorage();
				print " Done.\n";
				
				print "Updating roles (step 3 of 3)...";
				$this->rebuildRoles3();
				print " Done.\n";
				*/
				
				print "Fixing ELB...";
				$this->fixELB();
				print " Done.\n";
			}
			catch(Exception $e)
			{
				//$db->RollbackTrans();
				var_dump($e->getMessage());
				exit();
			}

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