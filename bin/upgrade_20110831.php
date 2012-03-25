<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20110831();
	$ScalrUpdate->Run();
	
	class Update20110831
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			/*

CREATE TABLE IF NOT EXISTS `dm_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `env_id` int(11) DEFAULT NULL,
  `dm_source_id` int(11) DEFAULT NULL,
  `pre_deploy_script` text,
  `post_deploy_script` text,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=62 ;



CREATE TABLE IF NOT EXISTS `dm_deployment_tasks` (
  `id` varchar(12) NOT NULL,
  `env_id` int(11) DEFAULT NULL,
  `farm_role_id` int(11) DEFAULT NULL,
  `dm_application_id` int(11) DEFAULT NULL,
  `remote_path` varchar(255) DEFAULT NULL,
  `server_id` varchar(36) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `type` varchar(25) DEFAULT NULL,
  `dtdeployed` datetime DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `last_error` text,
  UNIQUE KEY `id` (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;



CREATE TABLE IF NOT EXISTS `dm_deployment_task_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dm_deployment_task_id` varchar(12) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `message` tinytext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=936 ;



CREATE TABLE IF NOT EXISTS `dm_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `url` text,
  `env_id` int(11) DEFAULT NULL,
  `auth_type` enum('password','certificate') DEFAULT NULL,
  `auth_info` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=88 ;
			 */

			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_users` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `account_id` INT NULL ,
			  `status` VARCHAR(45) NULL ,
			  `email` VARCHAR(100) NULL ,
			  `fullname` VARCHAR(100) NULL ,
			  `password` VARCHAR(64) NULL ,
			  `dtcreated` DATETIME NULL ,
			  `dtlastlogin` DATETIME NULL ,
			  `type` VARCHAR(45) NULL ,
			  `comments` TEXT NULL ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_users_clients1` (`account_id` ASC) )
			ENGINE = InnoDB;
			");
			
			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_limits` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `account_id` INT NULL ,
			  `limit_name` VARCHAR(45) NULL ,
			  `limit_value` INT NULL ,
			  `limit_type` ENUM('soft','hard') NULL DEFAULT 'hard' ,
			  `limit_type_value` INT NULL ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_limits_clients` (`account_id` ASC) )
			ENGINE = InnoDB;
			");
			
			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_audit` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `account_id` INT NULL ,
			  `user_id` INT NULL ,
			  `user_email` VARCHAR(100) NULL ,
			  `date` DATETIME NULL ,
			  `action` VARCHAR(45) NULL ,
			  `ipaddress` VARCHAR(15) NULL ,
			  `comments` VARCHAR(255) NULL ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_audit_clients1` (`account_id` ASC) ,
			  INDEX `fk_account_audit_account_users1` (`user_id` ASC) )
			ENGINE = MyISAM;
			");
			
			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_teams` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `account_id` INT NULL ,
			  `name` VARCHAR(45) NULL ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_teams_clients1` (`account_id` ASC) )
			ENGINE = InnoDB;
			");
			
			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_groups` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `team_id` INT NOT NULL ,
			  `name` VARCHAR(45) NULL ,
			  `isactive` TINYINT NULL DEFAULT 1 ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_groups_account_teams1` (`team_id` ASC) )
			ENGINE = InnoDB;
			");
			
			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_group_permissions` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `group_id` INT NULL ,
			  `controller` VARCHAR(45) NULL ,
			  `permissions` VARCHAR(255) NULL ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_group_permissions_account_groups1` (`group_id` ASC) )
			ENGINE = InnoDB;
			");
			
			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_team_envs` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `env_id` INT NULL ,
			  `team_id` INT NOT NULL ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_team_envs_account_teams1` (`team_id` ASC) )
			ENGINE = InnoDB;
			");
			
			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_user_groups` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `user_id` INT NOT NULL ,
			  `group_id` INT NOT NULL ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_user_groups_account_users1` (`user_id` ASC) ,
			  INDEX `fk_account_user_groups_account_groups1` (`group_id` ASC) )
			ENGINE = InnoDB;
			");
			
			$db->Execute("CREATE  TABLE IF NOT EXISTS `account_team_users` (
			  `id` INT NOT NULL AUTO_INCREMENT ,
			  `team_id` INT NOT NULL ,
			  `user_id` INT NOT NULL ,
			  `is_owner` TINYINT NULL DEFAULT 0 ,
			  PRIMARY KEY (`id`) ,
			  INDEX `fk_account_team_users_account_teams1` (`team_id` ASC) ,
			  INDEX `fk_account_team_users_account_users1` (`user_id` ASC) )
			ENGINE = InnoDB;
			");

			$db->Execute("ALTER TABLE  `clients` ADD  `name` VARCHAR( 50 ) NULL AFTER  `id`");
			$db->Execute("ALTER TABLE  `clients` ADD  `status` VARCHAR( 50 ) NULL AFTER  `name`");

			$clients = $db->Execute("SELECT * FROM clients");
			while ($client = $clients->FetchRow())
			{
				
				$db->Execute("INSERT INTO account_limits SET
					limit_name  = ?,
					limit_value = ?,
					limit_type  = ?,
					limit_type_value = ?,
					account_id = ?
				", array(
					Scalr_Limits::ACCOUNT_ENVIRONMENTS,
					1,
					Scalr_Limits::TYPE_HARD,
					0,
					$client['id']
				));
				
				$db->Execute("INSERT INTO account_limits SET
					limit_name  = ?,
					limit_value = ?,
					limit_type  = ?,
					limit_type_value = ?,
					account_id = ?
				", array(
					Scalr_Limits::ACCOUNT_USERS,
					1,
					Scalr_Limits::TYPE_HARD,
					0,
					$client['id']
				));
					
				//Add account_user record
				$db->Execute("INSERT INTO account_users SET
					`account_id`	= ?,
					`status`		= ?,
					`email`			= ?,
					`password`		= ?,
					`dtcreated`		= ?,
					`dtlastlogin`	= ?,
					`type`			= ?,
					`fullname`		= ?
				", array(
					$client['id'],
					($client['isactive'] == 1) ? Scalr_Account_User::STATUS_ACTIVE : Scalr_Account_User::STATUS_INACTIVE,
					$client['email'],
					$client['password'],
					$client['dtadded'],
					$client['dtlastloginattempt'],
					Scalr_Account_User::TYPE_ACCOUNT_OWNER,
					$client['fullname']
				));
				
				if ($client['farms_limit'] != 0) {
					$db->Execute("INSERT INTO account_limits SET
						limit_name  = ?,
						limit_value = ?,
						limit_type  = ?,
						limit_type_value = ?,
						account_id = ?
					", array(
						Scalr_Limits::ACCOUNT_FARMS,
						$client['farms_limit'],
						Scalr_Limits::TYPE_HARD,
						0,
						$client['id']
					));
				}
					
				$features = array(Scalr_Limits::FEATURE_API, Scalr_Limits::FEATURE_CUSTOM_SCALING_METRICS, Scalr_Limits::FEATURE_SCRIPTING);
				foreach ($features as $feature) {
					$db->Execute("INSERT INTO account_limits SET
						limit_name  = ?,
						limit_value = ?,
						limit_type  = ?,
						limit_type_value = ?,
						account_id = ?
					", array(
						$feature,
						1,
						Scalr_Limits::TYPE_HARD,
						0,
						$client['id']
					));
				}
				
				$name = ($client['org']) ? $client['org'] : $client['email'];
				
				$db->Execute("UPDATE clients SET `name` = ? WHERE id = ?", array(
					$name, $client['id']
				));
			}
			
			//MIGRATE ADMIN:
			$db->Execute("INSERT INTO account_users SET
				`account_id`	= ?,
				`status`		= ?,
				`email`			= ?,
				`password`		= ?,
				`dtcreated`		= NOW(),
				`dtlastlogin`	= NOW(),
				`type`			= ?,
				`fullname`		= ?
			", array(
				0,
				Scalr_Account_User::STATUS_ACTIVE,
				CONFIG::$ADMIN_LOGIN,
				CONFIG::$ADMIN_PASSWORD,
				Scalr_Account_User::TYPE_SCALR_ADMIN,
				'Scalr Admin'
			));
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>