<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20101207();
	$ScalrUpdate->Run();
	
	class Update20101207
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->Execute("CREATE TABLE `storage_snapshots` (
				  `id` varchar(20) NOT NULL,
				  `client_id` int(11) default NULL,
				  `env_id` int(11) default NULL,
				  `name` varchar(255) default NULL,
				  `platform` varchar(50) default NULL,
				  `type` varchar(20) default NULL,
				  `config` text,
				  `description` text,
				  `ismysql` tinyint(1) default '0',
				  `dtcreated` datetime default NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;
			");

			$db->Execute("CREATE TABLE `storage_volumes` (
				  `id` varchar(50) NOT NULL,
				  `client_id` int(11) default NULL,
				  `env_id` int(11) default NULL,
				  `name` varchar(255) default NULL,
				  `attachment_status` varchar(255) default NULL,
				  `mount_status` varchar(255) default NULL,
				  `config` text,
				  `type` varchar(20) default NULL,
				  `dtcreated` datetime default NULL,
				  `platform` varchar(20) default NULL,
				  `size` varchar(20) default NULL,
				  `fstype` varchar(255) default NULL,
				  PRIMARY KEY  (`id`)
				) ENGINE=MyISAM DEFAULT CHARSET=latin1;
			");
			
			$db->Execute("alter table `ssh_keys`     add column `platform` varchar(20) NULL after `cloud_key_name`;");
			$db->Execute("update `ssh_keys` SET `platform`='ec2'");
			
			$db->Execute("alter table `client_environment_properties` add column `group` varchar(20) NOT NULL after `value`;");
			$db->Execute("ALTER TABLE `client_environment_properties` DROP INDEX `env_id_2` , ADD UNIQUE `env_id_2` ( `env_id` , `name` , `group` );");
			$db->Execute("UPDATE client_environment_properties SET `group`='euca-default' WHERE `name` LIKE 'eucalyptus%'");
			
			$db->Execute("create table `role_tags`(     `id` int(11) NOT NULL AUTO_INCREMENT ,     `role_id` int(11) ,     `tag` varchar(25) ,     PRIMARY KEY (`id`)  );");
			$db->Execute("alter table `role_tags` add unique `NewIndex1` (`role_id`, `tag`);");
			$db->Execute("alter table `role_tags` add index `NewIndex2` (`role_id`);");
			
			$db->Execute("alter table `roles`     add column `szr_version` varchar(10) NULL after `os`;");
			$db->Execute("UPDATE roles SET szr_version='0.6.21' WHERE generation = '2'");
			
			$db->Execute("alter table `bundle_tasks`     change `status` `status` varchar(30) character set latin1 collate latin1_swedish_ci NULL ;");
			
			$db->Execute("CREATE TABLE `roles_queue`(     `id` INT(11) ,     `role_id` INT(11) ,     `dtadded` DATETIME   );");
			$db->Execute("ALTER TABLE `roles_queue`     ADD COLUMN `action` VARCHAR(50) NULL AFTER `dtadded`;");
			
			$db->Execute("ALTER TABLE `dns_zones`     ADD COLUMN `allowed_accounts` TEXT NULL AFTER `iszoneconfigmodified`;");
			
			$db->Execute("ALTER TABLE `servers_history`     ADD COLUMN `type` VARCHAR(25) NULL AFTER `platform`;");
			
			$db->Execute("ALTER TABLE `storage_snapshots`     ADD COLUMN `farm_id` INT NULL AFTER `dtcreated`,     ADD COLUMN `farm_roleid` INT NULL AFTER `farm_id`;");
			
			$roles = $db->Execute("SELECT id FROM roles WHERE generation='2'");
			while ($role = $roles->FetchRow())
				$db->Execute("INSERT INTO role_tags SET `role_id` = ?, `tag` = ?", array($role['id'], ROLE_TAGS::EC2_EBS));
			
			//$db->RollbackTrans();
			
			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>