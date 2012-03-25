-- --------------------------------------------------------

--
-- Table structure for table `account_audit`
--

CREATE TABLE IF NOT EXISTS `account_audit` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `user_id` int(11) DEFAULT NULL,
  `user_email` varchar(100) DEFAULT NULL,
  `date` datetime DEFAULT NULL,
  `action` varchar(45) DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  `comments` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_account_audit_clients1` (`account_id`),
  KEY `fk_account_audit_account_users1` (`user_id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_groups`
--

CREATE TABLE IF NOT EXISTS `account_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  `is_active` tinyint(4) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_account_groups_account_teams1` (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_group_permissions`
--

CREATE TABLE IF NOT EXISTS `account_group_permissions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `group_id` int(11) DEFAULT NULL,
  `controller` varchar(45) DEFAULT NULL,
  `permissions` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_account_group_permissions_account_groups1` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_limits`
--

CREATE TABLE IF NOT EXISTS `account_limits` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `limit_name` varchar(45) DEFAULT NULL,
  `limit_value` int(11) DEFAULT NULL,
  `limit_type` enum('soft','hard') DEFAULT 'hard',
  `limit_type_value` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_account_limits_clients` (`account_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_teams`
--

CREATE TABLE IF NOT EXISTS `account_teams` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_account_teams_clients1` (`account_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_team_envs`
--

CREATE TABLE IF NOT EXISTS `account_team_envs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `env_id` int(11) DEFAULT NULL,
  `team_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_account_team_envs_account_teams1` (`team_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_team_users`
--

CREATE TABLE IF NOT EXISTS `account_team_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `team_id` int(11) NOT NULL,
  `user_id` int(11) NOT NULL,
  `permissions` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_account_team_users_account_teams1` (`team_id`),
  KEY `fk_account_team_users_account_users1` (`user_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_users`
--

CREATE TABLE IF NOT EXISTS `account_users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `account_id` int(11) DEFAULT NULL,
  `status` varchar(45) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `fullname` varchar(100) DEFAULT NULL,
  `password` varchar(64) DEFAULT NULL,
  `dtcreated` datetime DEFAULT NULL,
  `dtlastlogin` datetime DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `comments` text,
  PRIMARY KEY (`id`),
  KEY `fk_account_users_clients1` (`account_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_user_dashboard`
--

CREATE TABLE IF NOT EXISTS `account_user_dashboard` (
  `user_id` int(11) NOT NULL,
  `env_id` int(11) NOT NULL,
  `value` text NOT NULL,
  UNIQUE KEY `user_id` (`user_id`,`env_id`),
  KEY `env_id` (`env_id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `account_user_groups`
--

CREATE TABLE IF NOT EXISTS `account_user_groups` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `group_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `fk_account_user_groups_account_users1` (`user_id`),
  KEY `fk_account_user_groups_account_groups1` (`group_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `account_user_settings`
--

CREATE TABLE IF NOT EXISTS `account_user_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `userid_name` (`user_id`,`name`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `apache_vhosts`
--

CREATE TABLE IF NOT EXISTS `apache_vhosts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `is_ssl_enabled` tinyint(1) DEFAULT '0',
  `farm_id` int(11) DEFAULT NULL,
  `farm_roleid` int(11) DEFAULT NULL,
  `ssl_cert` text,
  `ssl_key` text,
  `ca_cert` text,
  `last_modified` datetime DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) NOT NULL,
  `httpd_conf` text,
  `httpd_conf_vars` text,
  `advanced_mode` tinyint(1) DEFAULT '0',
  `httpd_conf_ssl` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `ix_name` (`name`,`env_id`),
  KEY `clientid` (`client_id`),
  KEY `env_id` (`env_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `api_log`
--

CREATE TABLE IF NOT EXISTS `api_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transaction_id` varchar(36) DEFAULT NULL,
  `dtadded` int(11) DEFAULT NULL,
  `action` varchar(25) DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  `request` text,
  `response` text,
  `clientid` int(11) DEFAULT NULL,
  `env_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transaction_id` (`transaction_id`),
  KEY `client_index` (`clientid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `autosnap_settings`
--

CREATE TABLE IF NOT EXISTS `autosnap_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientid` int(11) DEFAULT NULL,
  `env_id` int(11) NOT NULL,
  `period` int(5) DEFAULT NULL,
  `dtlastsnapshot` datetime DEFAULT NULL,
  `rotate` int(11) DEFAULT NULL,
  `last_snapshotid` varchar(50) DEFAULT NULL,
  `region` varchar(50) DEFAULT 'us-east-1',
  `objectid` varchar(20) DEFAULT NULL,
  `object_type` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `env_id` (`env_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `aws_errors`
--

CREATE TABLE IF NOT EXISTS `aws_errors` (
  `guid` varchar(85) NOT NULL,
  `title` text,
  `pub_date` datetime DEFAULT NULL,
  `description` text,
  PRIMARY KEY (`guid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `aws_regions`
--

CREATE TABLE IF NOT EXISTS `aws_regions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `api_url` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `billing_packages`
--

CREATE TABLE IF NOT EXISTS `billing_packages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `cost` float(7,2) DEFAULT NULL,
  `group` tinyint(2) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `bundle_tasks`
--

CREATE TABLE IF NOT EXISTS `bundle_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `prototype_role_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) NOT NULL,
  `server_id` varchar(36) DEFAULT NULL,
  `replace_type` varchar(20) DEFAULT NULL,
  `status` varchar(30) DEFAULT NULL,
  `platform` varchar(20) DEFAULT NULL,
  `rolename` varchar(50) DEFAULT NULL,
  `failure_reason` text,
  `bundle_type` varchar(20) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `dtstarted` datetime DEFAULT NULL,
  `dtfinished` datetime DEFAULT NULL,
  `remove_proto_role` tinyint(1) DEFAULT '0',
  `snapshot_id` varchar(50) DEFAULT NULL,
  `platform_status` varchar(50) DEFAULT NULL,
  `description` text,
  `role_id` int(11) DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `cloud_location` varchar(50) DEFAULT NULL,
  `meta_data` text,
  PRIMARY KEY (`id`),
  KEY `clientid` (`client_id`),
  KEY `env_id` (`env_id`),
  KEY `server_id` (`server_id`),
  KEY `status` (`status`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `bundle_task_log`
--

CREATE TABLE IF NOT EXISTS `bundle_task_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `bundle_task_id` int(11) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`bundle_task_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `clients`
--

CREATE TABLE IF NOT EXISTS `clients` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `status` varchar(50) DEFAULT NULL,
  `isbilled` tinyint(1) DEFAULT '0',
  `dtdue` datetime DEFAULT NULL,
  `isactive` tinyint(1) DEFAULT '0',
  `fullname` varchar(60) DEFAULT NULL,
  `org` varchar(60) DEFAULT NULL,
  `country` varchar(60) DEFAULT NULL,
  `state` varchar(60) DEFAULT NULL,
  `city` varchar(60) DEFAULT NULL,
  `zipcode` varchar(60) DEFAULT NULL,
  `address1` varchar(60) DEFAULT NULL,
  `address2` varchar(60) DEFAULT NULL,
  `phone` varchar(60) DEFAULT NULL,
  `fax` varchar(60) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `iswelcomemailsent` tinyint(1) DEFAULT '0',
  `login_attempts` int(5) DEFAULT '0',
  `dtlastloginattempt` datetime DEFAULT NULL,
  `comments` text,
  `priority` int(4) NOT NULL DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `client_environments`
--

CREATE TABLE IF NOT EXISTS `client_environments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) NOT NULL,
  `client_id` int(11) NOT NULL,
  `dt_added` datetime NOT NULL,
  `is_system` tinyint(1) NOT NULL DEFAULT '0',
  `color` varchar(6) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `client_environment_properties`
--

CREATE TABLE IF NOT EXISTS `client_environment_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `env_id` int(11) NOT NULL,
  `name` varchar(255) NOT NULL,
  `value` text NOT NULL,
  `group` varchar(20) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `env_id_2` (`env_id`,`name`,`group`),
  KEY `env_id` (`env_id`),
  KEY `name_value` (`name`(100),`value`(100)),
  KEY `name` (`name`(100))
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `client_settings`
--

CREATE TABLE IF NOT EXISTS `client_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientid` int(11) DEFAULT NULL,
  `key` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex1` (`clientid`,`key`),
  KEY `settingskey` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `comments`
--

CREATE TABLE IF NOT EXISTS `comments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `env_id` int(11) NOT NULL,
  `rule` varchar(255) NOT NULL,
  `sg_name` varchar(255) NOT NULL,
  `comment` varchar(255) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `main` (`env_id`,`sg_name`,`rule`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `config`
--

CREATE TABLE IF NOT EXISTS `config` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `key` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `key` (`key`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `countries`
--

CREATE TABLE IF NOT EXISTS `countries` (
  `id` int(5) NOT NULL AUTO_INCREMENT,
  `name` varchar(64) NOT NULL DEFAULT '',
  `code` char(2) NOT NULL DEFAULT '',
  PRIMARY KEY (`id`),
  KEY `IDX_COUNTRIES_NAME` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `debug_pm`
--

CREATE TABLE IF NOT EXISTS `debug_pm` (
  `ip` varchar(16) NOT NULL,
  `cnt` int(11) NOT NULL,
  UNIQUE KEY `ip` (`ip`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `debug_rackspace`
--

CREATE TABLE IF NOT EXISTS `debug_rackspace` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(36) DEFAULT NULL,
  `info` text NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `default_records`
--

CREATE TABLE IF NOT EXISTS `default_records` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientid` int(11) DEFAULT '0',
  `type` enum('NS','MX','CNAME','A','TXT') DEFAULT NULL,
  `ttl` int(11) DEFAULT '14400',
  `priority` int(11) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `distributions`
--

CREATE TABLE IF NOT EXISTS `distributions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `cfid` varchar(25) DEFAULT NULL,
  `cfurl` varchar(255) DEFAULT NULL,
  `cname` varchar(255) DEFAULT NULL,
  `zone` varchar(255) DEFAULT NULL,
  `bucket` varchar(255) DEFAULT NULL,
  `clientid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dm_applications`
--

CREATE TABLE IF NOT EXISTS `dm_applications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `env_id` int(11) DEFAULT NULL,
  `dm_source_id` int(11) DEFAULT NULL,
  `pre_deploy_script` text,
  `post_deploy_script` text,
  `name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dm_deployment_tasks`
--

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
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `dm_deployment_task_logs`
--

CREATE TABLE IF NOT EXISTS `dm_deployment_task_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `dm_deployment_task_id` varchar(12) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `message` tinytext,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dm_sources`
--

CREATE TABLE IF NOT EXISTS `dm_sources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `type` varchar(50) DEFAULT NULL,
  `url` text,
  `env_id` int(11) DEFAULT NULL,
  `auth_type` enum('password','certificate') DEFAULT NULL,
  `auth_info` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dns_zones`
--

CREATE TABLE IF NOT EXISTS `dns_zones` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) NOT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `farm_roleid` int(11) DEFAULT NULL,
  `zone_name` varchar(255) DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  `soa_owner` varchar(100) DEFAULT NULL,
  `soa_ttl` int(10) unsigned DEFAULT NULL,
  `soa_parent` varchar(100) DEFAULT NULL,
  `soa_serial` int(10) unsigned DEFAULT NULL,
  `soa_refresh` int(10) unsigned DEFAULT NULL,
  `soa_retry` int(10) unsigned DEFAULT NULL,
  `soa_expire` int(10) unsigned DEFAULT NULL,
  `soa_min_ttl` int(10) unsigned DEFAULT NULL,
  `dtlastmodified` datetime DEFAULT NULL,
  `axfr_allowed_hosts` tinytext,
  `allow_manage_system_records` tinyint(1) DEFAULT '0',
  `isonnsserver` tinyint(1) DEFAULT '0',
  `iszoneconfigmodified` tinyint(1) DEFAULT '0',
  `allowed_accounts` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zones_index3945` (`zone_name`),
  KEY `farmid` (`farm_id`),
  KEY `clientid` (`client_id`),
  KEY `env_id` (`env_id`),
  KEY `iszoneconfigmodified` (`iszoneconfigmodified`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `dns_zone_records`
--

CREATE TABLE IF NOT EXISTS `dns_zone_records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `zone_id` int(10) unsigned NOT NULL DEFAULT '0',
  `type` varchar(6) DEFAULT NULL,
  `ttl` int(10) unsigned DEFAULT NULL,
  `priority` int(10) unsigned DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `issystem` tinyint(1) DEFAULT NULL,
  `weight` int(10) DEFAULT NULL,
  `port` int(10) DEFAULT NULL,
  `server_id` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zoneid` (`zone_id`,`type`(1),`value`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ebs_snaps_info`
--

CREATE TABLE IF NOT EXISTS `ebs_snaps_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `snapid` varchar(50) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `dtcreated` datetime DEFAULT NULL,
  `ebs_array_snapid` int(11) DEFAULT '0',
  `region` varchar(255) DEFAULT 'us-east-1',
  `autosnapshotid` int(11) DEFAULT '0',
  `is_autoebs_master_snap` tinyint(1) DEFAULT '0',
  `farm_roleid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `mainindex` (`farm_roleid`,`is_autoebs_master_snap`),
  KEY `autosnapid` (`autosnapshotid`),
  KEY `snapid` (`snapid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ec2_ebs`
--

CREATE TABLE IF NOT EXISTS `ec2_ebs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farm_id` int(11) DEFAULT NULL,
  `farm_roleid` int(11) DEFAULT NULL,
  `volume_id` varchar(15) DEFAULT NULL,
  `server_id` varchar(36) DEFAULT NULL,
  `attachment_status` varchar(30) DEFAULT NULL,
  `mount_status` varchar(20) DEFAULT NULL,
  `device` varchar(15) DEFAULT NULL,
  `server_index` int(3) DEFAULT NULL,
  `mount` tinyint(1) DEFAULT '0',
  `mountpoint` varchar(50) DEFAULT NULL,
  `ec2_avail_zone` varchar(30) DEFAULT NULL,
  `ec2_region` varchar(30) DEFAULT NULL,
  `isfsexist` tinyint(1) DEFAULT '0',
  `ismanual` tinyint(1) DEFAULT '0',
  `size` int(11) DEFAULT NULL,
  `snap_id` varchar(50) DEFAULT NULL,
  `ismysqlvolume` tinyint(1) DEFAULT '0',
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) NOT NULL,
  PRIMARY KEY (`id`),
  KEY `env_id` (`env_id`),
  KEY `server_id` (`server_id`),
  KEY `farm_roleid_index` (`farm_roleid`,`server_index`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `elastic_ips`
--

CREATE TABLE IF NOT EXISTS `elastic_ips` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `role_name` varchar(100) DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  `state` tinyint(1) DEFAULT '0',
  `instance_id` varchar(20) DEFAULT NULL,
  `clientid` int(11) DEFAULT NULL,
  `env_id` int(11) NOT NULL,
  `instance_index` int(11) DEFAULT '0',
  `farm_roleid` int(11) DEFAULT NULL,
  `server_id` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `farmid` (`farmid`),
  KEY `farm_roleid` (`farm_roleid`),
  KEY `env_id` (`env_id`),
  KEY `server_id` (`server_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `events`
--

CREATE TABLE IF NOT EXISTS `events` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `type` varchar(25) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  `ishandled` tinyint(1) DEFAULT '0',
  `short_message` varchar(255) DEFAULT NULL,
  `event_object` text,
  `event_id` varchar(36) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `event_id` (`event_id`),
  KEY `farmid` (`farmid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farms`
--

CREATE TABLE IF NOT EXISTS `farms` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientid` int(11) DEFAULT NULL,
  `env_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `iscompleted` tinyint(1) DEFAULT '0',
  `hash` varchar(25) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `status` tinyint(1) DEFAULT '1',
  `dtlaunched` datetime DEFAULT NULL,
  `term_on_sync_fail` tinyint(1) DEFAULT '1',
  `region` varchar(255) DEFAULT 'us-east-1',
  `farm_roles_launch_order` tinyint(1) DEFAULT '0',
  `comments` text,
  PRIMARY KEY (`id`),
  KEY `clientid` (`clientid`),
  KEY `env_id` (`env_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_event_observers`
--

CREATE TABLE IF NOT EXISTS `farm_event_observers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `event_observer_name` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`farmid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_event_observers_config`
--

CREATE TABLE IF NOT EXISTS `farm_event_observers_config` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `observerid` int(11) DEFAULT NULL,
  `key` varchar(255) DEFAULT NULL,
  `value` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`observerid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_roles`
--

CREATE TABLE IF NOT EXISTS `farm_roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `dtlastsync` datetime DEFAULT NULL,
  `reboot_timeout` int(10) DEFAULT '300',
  `launch_timeout` int(10) DEFAULT '300',
  `status_timeout` int(10) DEFAULT '20',
  `launch_index` int(5) DEFAULT '0',
  `role_id` int(11) DEFAULT NULL,
  `new_role_id` int(11) DEFAULT NULL,
  `platform` varchar(20) DEFAULT NULL,
  `cloud_location` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`),
  KEY `farmid` (`farmid`),
  KEY `platform` (`platform`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_role_options`
--

CREATE TABLE IF NOT EXISTS `farm_role_options` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `ami_id` varchar(255) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  `hash` varchar(255) DEFAULT NULL,
  `farm_roleid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `farmid` (`farmid`),
  KEY `farm_roleid` (`farm_roleid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_role_scaling_metrics`
--

CREATE TABLE IF NOT EXISTS `farm_role_scaling_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farm_roleid` int(11) DEFAULT NULL,
  `metric_id` int(11) DEFAULT NULL,
  `dtlastpolled` datetime DEFAULT NULL,
  `last_value` varchar(255) DEFAULT NULL,
  `settings` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex4` (`farm_roleid`,`metric_id`),
  KEY `NewIndex1` (`farm_roleid`),
  KEY `NewIndex2` (`metric_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_role_scaling_times`
--

CREATE TABLE IF NOT EXISTS `farm_role_scaling_times` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farm_roleid` int(11) DEFAULT NULL,
  `start_time` int(11) DEFAULT NULL,
  `end_time` int(11) DEFAULT NULL,
  `days_of_week` varchar(75) DEFAULT NULL,
  `instances_count` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `farmroleid` (`farm_roleid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_role_scripts`
--

CREATE TABLE IF NOT EXISTS `farm_role_scripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scriptid` int(11) DEFAULT NULL,
  `farmid` int(11) DEFAULT NULL,
  `ami_id` varchar(255) DEFAULT NULL,
  `params` text,
  `event_name` varchar(255) DEFAULT NULL,
  `target` varchar(50) DEFAULT NULL,
  `version` varchar(20) DEFAULT 'latest',
  `timeout` int(5) DEFAULT '120',
  `issync` tinyint(1) DEFAULT '0',
  `ismenuitem` tinyint(1) DEFAULT '0',
  `order_index` int(5) DEFAULT '0',
  `farm_roleid` int(11) DEFAULT NULL,
  `issystem` tinyint(1) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `farmid` (`farmid`),
  KEY `farm_roleid` (`farm_roleid`),
  KEY `event_name` (`event_name`),
  KEY `UniqueIndex` (`scriptid`,`farmid`,`event_name`,`farm_roleid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_role_service_config_presets`
--

CREATE TABLE IF NOT EXISTS `farm_role_service_config_presets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preset_id` int(11) NOT NULL,
  `farm_roleid` int(11) DEFAULT NULL,
  `behavior` varchar(25) DEFAULT NULL,
  `restart_service` tinyint(1) DEFAULT '1',
  PRIMARY KEY (`id`),
  KEY `fk_farm_role_service_config_presets_service_config_presets1` (`preset_id`),
  KEY `farm_roleid` (`farm_roleid`),
  KEY `preset_id` (`preset_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_role_settings`
--

CREATE TABLE IF NOT EXISTS `farm_role_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farm_roleid` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`farm_roleid`,`name`),
  KEY `name` (`name`(30))
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ROW_FORMAT=COMPACT AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_settings`
--

CREATE TABLE IF NOT EXISTS `farm_settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `farmid_name` (`farmid`,`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `farm_stats`
--

CREATE TABLE IF NOT EXISTS `farm_stats` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `bw_in` bigint(20) DEFAULT '0',
  `bw_out` bigint(20) DEFAULT '0',
  `bw_in_last` int(11) DEFAULT '0',
  `bw_out_last` int(11) DEFAULT '0',
  `month` int(2) DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `dtlastupdate` int(11) DEFAULT NULL,
  `m1_small` int(11) DEFAULT '0',
  `m1_large` int(11) DEFAULT '0',
  `m1_xlarge` int(11) DEFAULT '0',
  `c1_medium` int(11) DEFAULT '0',
  `c1_xlarge` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`month`,`year`),
  KEY `NewIndex2` (`farmid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `garbage_queue`
--

CREATE TABLE IF NOT EXISTS `garbage_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientid` int(11) DEFAULT NULL,
  `data` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex1` (`clientid`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `init_tokens`
--

CREATE TABLE IF NOT EXISTS `init_tokens` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` varchar(255) DEFAULT NULL,
  `token` varchar(255) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `instances_history`
--

CREATE TABLE IF NOT EXISTS `instances_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `instance_id` varchar(20) DEFAULT NULL,
  `dtlaunched` int(11) DEFAULT NULL,
  `dtterminated` int(11) DEFAULT NULL,
  `uptime` int(11) DEFAULT NULL,
  `instance_type` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ipaccess`
--

CREATE TABLE IF NOT EXISTS `ipaccess` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipaddress` varchar(255) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `logentries`
--

CREATE TABLE IF NOT EXISTS `logentries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `serverid` varchar(36) NOT NULL,
  `message` text NOT NULL,
  `severity` tinyint(1) DEFAULT '0',
  `time` int(11) NOT NULL,
  `source` varchar(255) DEFAULT NULL,
  `farmid` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`farmid`),
  KEY `NewIndex2` (`severity`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `messages`
--

CREATE TABLE IF NOT EXISTS `messages` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `messageid` varchar(75) DEFAULT NULL,
  `instance_id` varchar(15) DEFAULT NULL,
  `status` tinyint(1) DEFAULT '0',
  `handle_attempts` int(2) DEFAULT '1',
  `dtlasthandleattempt` datetime DEFAULT NULL,
  `message` text,
  `server_id` varchar(36) DEFAULT NULL,
  `type` enum('in','out') DEFAULT NULL,
  `isszr` tinyint(1) DEFAULT '0',
  `message_name` varchar(30) DEFAULT NULL,
  `message_version` int(2) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `server_message` (`messageid`(36),`server_id`),
  KEY `server_id` (`server_id`),
  KEY `serverid_isszr` (`server_id`,`isszr`),
  KEY `messageid` (`messageid`),
  KEY `status` (`status`,`type`),
  KEY `message_name` (`message_name`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `nameservers`
--

CREATE TABLE IF NOT EXISTS `nameservers` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `host` varchar(100) DEFAULT NULL,
  `port` int(10) unsigned DEFAULT NULL,
  `username` varchar(100) DEFAULT NULL,
  `password` text,
  `rndc_path` varchar(255) DEFAULT NULL,
  `named_path` varchar(255) DEFAULT NULL,
  `namedconf_path` varchar(255) DEFAULT NULL,
  `isproxy` tinyint(1) DEFAULT '0',
  `isbackup` tinyint(1) DEFAULT '0',
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `payments`
--

CREATE TABLE IF NOT EXISTS `payments` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientid` int(11) DEFAULT NULL,
  `transactionid` varchar(255) DEFAULT NULL,
  `subscriptionid` varchar(255) DEFAULT NULL,
  `dtpaid` datetime DEFAULT NULL,
  `amount` float(6,2) DEFAULT NULL,
  `payer_email` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `payment_redirects`
--

CREATE TABLE IF NOT EXISTS `payment_redirects` (
  `id` int(11) DEFAULT NULL,
  `from_clientid` int(11) DEFAULT NULL,
  `to_clientid` int(11) DEFAULT NULL,
  `subscription_id` varchar(255) DEFAULT NULL
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `rds_snaps_info`
--

CREATE TABLE IF NOT EXISTS `rds_snaps_info` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `snapid` varchar(50) DEFAULT NULL,
  `comment` varchar(255) DEFAULT NULL,
  `dtcreated` datetime DEFAULT NULL,
  `region` varchar(255) DEFAULT 'us-east-1',
  `autosnapshotid` int(11) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 CHECKSUM=1 DELAY_KEY_WRITE=1 ROW_FORMAT=DYNAMIC AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `real_servers`
--

CREATE TABLE IF NOT EXISTS `real_servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `ami_id` varchar(255) DEFAULT NULL,
  `ipaddress` varchar(15) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `rebundle_log`
--

CREATE TABLE IF NOT EXISTS `rebundle_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `roleid` int(11) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `message` text,
  `bundle_task_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `records`
--

CREATE TABLE IF NOT EXISTS `records` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `zoneid` int(10) unsigned NOT NULL DEFAULT '0',
  `rtype` varchar(6) DEFAULT NULL,
  `ttl` int(10) unsigned DEFAULT NULL,
  `rpriority` int(10) unsigned DEFAULT NULL,
  `rvalue` varchar(255) DEFAULT NULL,
  `rkey` varchar(255) DEFAULT NULL,
  `issystem` tinyint(1) DEFAULT NULL,
  `rweight` int(10) DEFAULT NULL,
  `rport` int(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `zoneid` (`zoneid`,`rtype`(1),`rvalue`,`rkey`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `roles`
--

CREATE TABLE IF NOT EXISTS `roles` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) DEFAULT NULL,
  `origin` enum('SHARED','CUSTOM') DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) DEFAULT NULL,
  `description` text,
  `behaviors` varchar(90) DEFAULT NULL,
  `architecture` enum('i386','x86_64') DEFAULT NULL,
  `is_stable` tinyint(1) DEFAULT '1',
  `history` text,
  `approval_state` varchar(20) DEFAULT NULL,
  `generation` tinyint(4) DEFAULT '1',
  `os` varchar(60) DEFAULT NULL,
  `szr_version` varchar(10) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`origin`),
  KEY `NewIndex2` (`client_id`),
  KEY `NewIndex3` (`env_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `roles_queue`
--

CREATE TABLE IF NOT EXISTS `roles_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `action` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `role_behaviors`
--

CREATE TABLE IF NOT EXISTS `role_behaviors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `behavior` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_id_behavior` (`role_id`,`behavior`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `role_images`
--

CREATE TABLE IF NOT EXISTS `role_images` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `cloud_location` varchar(25) DEFAULT NULL,
  `image_id` varchar(255) DEFAULT NULL,
  `platform` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`role_id`,`image_id`,`cloud_location`),
  UNIQUE KEY `role_id_location` (`role_id`,`cloud_location`),
  KEY `NewIndex1` (`platform`),
  KEY `location` (`cloud_location`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `role_parameters`
--

CREATE TABLE IF NOT EXISTS `role_parameters` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `name` varchar(45) DEFAULT NULL,
  `type` varchar(45) DEFAULT NULL,
  `isrequired` tinyint(1) DEFAULT NULL,
  `defval` text,
  `allow_multiple_choice` tinyint(1) DEFAULT NULL,
  `options` text,
  `hash` varchar(45) DEFAULT NULL,
  `issystem` tinyint(1) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `role_properties`
--

CREATE TABLE IF NOT EXISTS `role_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex1` (`role_id`,`name`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `role_security_rules`
--

CREATE TABLE IF NOT EXISTS `role_security_rules` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `rule` varchar(90) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `role_software`
--

CREATE TABLE IF NOT EXISTS `role_software` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) NOT NULL,
  `software_name` varchar(45) DEFAULT NULL,
  `software_version` varchar(20) DEFAULT NULL,
  `software_key` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `role_tags`
--

CREATE TABLE IF NOT EXISTS `role_tags` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `role_id` int(11) DEFAULT NULL,
  `tag` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `role_tag` (`role_id`,`tag`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scaling_metrics`
--

CREATE TABLE IF NOT EXISTS `scaling_metrics` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) DEFAULT NULL,
  `name` varchar(50) DEFAULT NULL,
  `file_path` varchar(255) DEFAULT NULL,
  `retrieve_method` varchar(20) DEFAULT NULL,
  `calc_function` varchar(20) DEFAULT NULL,
  `algorithm` varchar(15) DEFAULT NULL,
  `alias` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `NewIndex3` (`client_id`,`name`),
  KEY `NewIndex1` (`client_id`),
  KEY `NewIndex2` (`env_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scheduler`
--

CREATE TABLE IF NOT EXISTS `scheduler` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `type` varchar(255) DEFAULT NULL,
  `target_id` varchar(255) DEFAULT NULL COMMENT 'id of farm, farm_role or farm_role:index from other tables',
  `target_type` varchar(255) DEFAULT NULL COMMENT 'farm, role or instance type',
  `start_time` datetime DEFAULT NULL COMMENT 'start task''s time',
  `end_time` datetime DEFAULT NULL COMMENT 'end task by this time',
  `last_start_time` datetime DEFAULT NULL COMMENT 'the last time task was started',
  `restart_every` int(11) DEFAULT '0' COMMENT 'restart task every N minutes',
  `config` text COMMENT 'arguments for action',
  `order_index` int(11) DEFAULT NULL COMMENT 'task order',
  `timezone` varchar(100) DEFAULT NULL,
  `status` varchar(11) DEFAULT NULL COMMENT 'active, suspended, finished',
  `account_id` int(11) DEFAULT NULL COMMENT 'Task belongs to selected account',
  `env_id` int(11) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`),
  KEY `account_id` (`account_id`,`env_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scheduler_tasks`
--

CREATE TABLE IF NOT EXISTS `scheduler_tasks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `task_name` varchar(255) DEFAULT NULL,
  `task_type` varchar(255) DEFAULT NULL,
  `target_id` varchar(255) DEFAULT NULL COMMENT 'id of farm, farm_role or farm_role:index from other tables',
  `target_type` varchar(255) DEFAULT NULL COMMENT 'farm, role or instance type',
  `start_time_date` datetime DEFAULT NULL COMMENT 'start task''s time',
  `end_time_date` datetime DEFAULT NULL COMMENT 'end task by this time',
  `last_start_time` datetime DEFAULT NULL COMMENT 'the last time task was started',
  `restart_every` int(11) DEFAULT '0' COMMENT 'restart task every N minutes',
  `task_config` text COMMENT 'arguments for script',
  `order_index` int(11) DEFAULT NULL COMMENT 'task order',
  `client_id` int(11) DEFAULT NULL COMMENT 'Task belongs to selected client',
  `status` varchar(11) DEFAULT NULL COMMENT 'active, suspended, finished',
  `env_id` int(11) DEFAULT NULL,
  `timezone` varchar(100) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `status` (`status`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scripting_log`
--

CREATE TABLE IF NOT EXISTS `scripting_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farmid` int(11) DEFAULT NULL,
  `event` varchar(255) DEFAULT NULL,
  `server_id` varchar(36) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`id`),
  KEY `farmid` (`farmid`),
  KEY `server_id` (`server_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `scripts`
--

CREATE TABLE IF NOT EXISTS `scripts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(255) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `origin` varchar(50) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `issync` tinyint(1) DEFAULT '0',
  `clientid` int(11) DEFAULT '0',
  `approval_state` varchar(50) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `script_revisions`
--

CREATE TABLE IF NOT EXISTS `script_revisions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `scriptid` int(11) DEFAULT NULL,
  `revision` int(11) DEFAULT NULL,
  `script` longtext,
  `dtcreated` datetime DEFAULT NULL,
  `approval_state` varchar(255) DEFAULT NULL,
  `variables` text,
  PRIMARY KEY (`id`),
  KEY `scriptid_revision` (`scriptid`,`revision`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `sensor_data`
--

CREATE TABLE IF NOT EXISTS `sensor_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farm_roleid` int(11) DEFAULT NULL,
  `sensor_name` varchar(255) DEFAULT NULL,
  `sensor_value` varchar(255) DEFAULT NULL,
  `dtlastupdate` int(11) DEFAULT NULL,
  `raw_sensor_data` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique` (`farm_roleid`,`sensor_name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `servers`
--

CREATE TABLE IF NOT EXISTS `servers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(36) DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `farm_roleid` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) NOT NULL,
  `role_id` int(11) DEFAULT NULL,
  `platform` varchar(10) DEFAULT NULL,
  `status` varchar(25) DEFAULT NULL,
  `remote_ip` varchar(15) DEFAULT NULL,
  `local_ip` varchar(15) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `index` int(11) DEFAULT NULL,
  `dtshutdownscheduled` datetime DEFAULT NULL,
  `dtrebootstart` datetime DEFAULT NULL,
  `replace_server_id` varchar(36) DEFAULT NULL,
  `dtlastsync` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `serverid` (`server_id`),
  KEY `farm_roleid` (`farm_roleid`),
  KEY `farmid_status` (`farm_id`,`status`),
  KEY `local_ip` (`local_ip`),
  KEY `env_id` (`env_id`),
  KEY `role_id` (`role_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `servers_history`
--

CREATE TABLE IF NOT EXISTS `servers_history` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `server_id` varchar(36) DEFAULT NULL,
  `cloud_server_id` varchar(50) DEFAULT NULL,
  `dtlaunched` datetime DEFAULT NULL,
  `dtterminated` datetime DEFAULT NULL,
  `dtterminated_scalr` datetime DEFAULT NULL,
  `terminate_reason` varchar(255) DEFAULT NULL,
  `platform` varchar(20) DEFAULT NULL,
  `type` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `client_id` (`client_id`),
  KEY `server_id` (`server_id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `servers_stats`
--

CREATE TABLE IF NOT EXISTS `servers_stats` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `usage` int(11) DEFAULT NULL,
  `instance_type` varchar(15) DEFAULT NULL,
  `env_id` int(11) DEFAULT NULL,
  `month` int(2) DEFAULT NULL,
  `year` int(4) DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `cloud_location` varchar(25) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `main` (`instance_type`,`cloud_location`,`farm_id`,`env_id`,`month`,`year`),
  KEY `envid` (`env_id`),
  KEY `farm_id` (`farm_id`),
  KEY `year` (`year`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `server_operations`
--

CREATE TABLE IF NOT EXISTS `server_operations` (
  `id` varchar(36) NOT NULL DEFAULT '',
  `server_id` varchar(36) NOT NULL DEFAULT '',
  `name` varchar(50) DEFAULT NULL,
  `phases` text,
  UNIQUE KEY `id` (`id`),
  UNIQUE KEY `server_id` (`server_id`,`name`(20))
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `server_operation_progress`
--

CREATE TABLE IF NOT EXISTS `server_operation_progress` (
  `operation_id` varchar(36) NOT NULL,
  `timestamp` int(11) DEFAULT NULL,
  `phase` varchar(100) NOT NULL,
  `step` varchar(100) NOT NULL,
  `status` varchar(15) NOT NULL,
  `progress` int(11) DEFAULT NULL,
  `stepno` int(11) DEFAULT NULL,
  `message` text,
  `trace` text,
  `handler` varchar(255) DEFAULT NULL,
  UNIQUE KEY `unique` (`operation_id`,`phase`,`step`),
  KEY `operation_id` (`operation_id`),
  KEY `timestamp` (`timestamp`)
) ENGINE=InnoDB DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `server_properties`
--

CREATE TABLE IF NOT EXISTS `server_properties` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `server_id` varchar(36) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `serverid_name` (`server_id`,`name`),
  KEY `serverid` (`server_id`),
  KEY `name_value` (`name`(20),`value`(20))
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `services_chef_runlists`
--

CREATE TABLE IF NOT EXISTS `services_chef_runlists` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `env_id` int(11) DEFAULT NULL,
  `chef_server_id` int(11) DEFAULT NULL,
  `name` varchar(30) NOT NULL,
  `description` varchar(255) NOT NULL,
  `runlist` text,
  `attributes` text,
  `chef_environment` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `services_chef_servers`
--

CREATE TABLE IF NOT EXISTS `services_chef_servers` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `env_id` int(11) DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `username` varchar(50) DEFAULT NULL,
  `auth_key` text,
  `v_username` varchar(255) DEFAULT NULL,
  `v_auth_key` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `services_mongodb_cluster_log`
--

CREATE TABLE IF NOT EXISTS `services_mongodb_cluster_log` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `farm_roleid` int(11) DEFAULT NULL,
  `severity` enum('INFO','WARNING','ERROR') DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `message` text,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `services_mongodb_snapshots_map`
--

CREATE TABLE IF NOT EXISTS `services_mongodb_snapshots_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farm_roleid` int(11) NOT NULL,
  `shard_index` int(11) NOT NULL,
  `snapshot_id` varchar(25) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `main` (`farm_roleid`,`shard_index`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `services_mongodb_volumes_map`
--

CREATE TABLE IF NOT EXISTS `services_mongodb_volumes_map` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `farm_roleid` int(11) NOT NULL,
  `replica_set_index` int(11) NOT NULL,
  `shard_index` int(11) NOT NULL,
  `volume_id` varchar(25) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `main` (`farm_roleid`,`replica_set_index`,`shard_index`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `service_config_presets`
--

CREATE TABLE IF NOT EXISTS `service_config_presets` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `env_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `name` varchar(45) DEFAULT NULL,
  `role_behavior` varchar(20) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `dtlastmodified` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `env_id` (`env_id`),
  KEY `client_id` (`client_id`),
  KEY `name` (`name`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `service_config_preset_data`
--

CREATE TABLE IF NOT EXISTS `service_config_preset_data` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `preset_id` int(11) NOT NULL,
  `key` varchar(45) DEFAULT NULL,
  `value` text,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ssh_keys`
--

CREATE TABLE IF NOT EXISTS `ssh_keys` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) DEFAULT NULL,
  `type` varchar(10) DEFAULT NULL,
  `private_key` text,
  `public_key` text,
  `cloud_location` varchar(255) DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `cloud_key_name` varchar(255) DEFAULT NULL,
  `platform` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `farmid` (`farm_id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `storage_snapshots`
--

CREATE TABLE IF NOT EXISTS `storage_snapshots` (
  `id` varchar(20) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `platform` varchar(50) DEFAULT NULL,
  `type` varchar(20) DEFAULT NULL,
  `config` text,
  `description` text,
  `ismysql` tinyint(1) DEFAULT '0',
  `dtcreated` datetime DEFAULT NULL,
  `farm_id` int(11) DEFAULT NULL,
  `farm_roleid` int(11) DEFAULT NULL,
  `service` varchar(50) DEFAULT NULL,
  `cloud_location` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `farm_roleid` (`farm_roleid`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `storage_volumes`
--

CREATE TABLE IF NOT EXISTS `storage_volumes` (
  `id` varchar(50) NOT NULL,
  `client_id` int(11) DEFAULT NULL,
  `env_id` int(11) DEFAULT NULL,
  `name` varchar(255) DEFAULT NULL,
  `attachment_status` varchar(255) DEFAULT NULL,
  `mount_status` varchar(255) DEFAULT NULL,
  `config` text,
  `type` varchar(20) DEFAULT NULL,
  `dtcreated` datetime DEFAULT NULL,
  `platform` varchar(20) DEFAULT NULL,
  `size` varchar(20) DEFAULT NULL,
  `fstype` varchar(255) DEFAULT NULL,
  `farm_roleid` int(11) DEFAULT NULL,
  `server_index` int(11) DEFAULT NULL,
  `purpose` varchar(20) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;

-- --------------------------------------------------------

--
-- Table structure for table `subscriptions`
--

CREATE TABLE IF NOT EXISTS `subscriptions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `clientid` int(11) DEFAULT NULL,
  `subscriptionid` varchar(255) DEFAULT NULL,
  `dtstart` datetime DEFAULT NULL,
  `status` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=InnoDB  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `syslog`
--

CREATE TABLE IF NOT EXISTS `syslog` (
  `id` int(10) unsigned NOT NULL AUTO_INCREMENT,
  `dtadded` datetime DEFAULT NULL,
  `message` text,
  `severity` varchar(10) DEFAULT NULL,
  `dtadded_time` bigint(20) DEFAULT NULL,
  `transactionid` varchar(50) DEFAULT NULL,
  `backtrace` text,
  `caller` varchar(255) DEFAULT NULL,
  `path` varchar(255) DEFAULT NULL,
  `sub_transactionid` varchar(50) DEFAULT NULL,
  `farmid` varchar(20) DEFAULT '0',
  PRIMARY KEY (`id`),
  KEY `NewIndex1` (`transactionid`),
  KEY `NewIndex2` (`sub_transactionid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `syslog_metadata`
--

CREATE TABLE IF NOT EXISTS `syslog_metadata` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `transactionid` varchar(50) DEFAULT NULL,
  `errors` int(5) DEFAULT NULL,
  `warnings` int(5) DEFAULT NULL,
  `message` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `transid` (`transactionid`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `task_queue`
--

CREATE TABLE IF NOT EXISTS `task_queue` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `queue_name` varchar(255) DEFAULT NULL,
  `data` text,
  `dtadded` datetime DEFAULT NULL,
  `failed_attempts` int(3) DEFAULT '0',
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

-- --------------------------------------------------------

--
-- Table structure for table `ui_debug_log`
--

CREATE TABLE IF NOT EXISTS `ui_debug_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `ipaddress` varchar(15) DEFAULT NULL,
  `dtadded` datetime DEFAULT NULL,
  `url` varchar(255) DEFAULT NULL,
  `report` text,
  `env_id` int(11) DEFAULT NULL,
  `client_id` int(11) DEFAULT NULL,
  `watch_client_id` int(11) DEFAULT NULL,
  `watch_client_hash` char(16) NOT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1 ;

--
-- Constraints for dumped tables
--

--
-- Constraints for table `account_user_dashboard`
--
ALTER TABLE `account_user_dashboard`
  ADD CONSTRAINT `account_user_dashboard_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `account_users` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION,
  ADD CONSTRAINT `account_user_dashboard_ibfk_2` FOREIGN KEY (`env_id`) REFERENCES `client_environments` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `role_behaviors`
--
ALTER TABLE `role_behaviors`
  ADD CONSTRAINT `role_behaviors_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `role_images`
--
ALTER TABLE `role_images`
  ADD CONSTRAINT `role_images_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `role_parameters`
--
ALTER TABLE `role_parameters`
  ADD CONSTRAINT `role_parameters_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `role_properties`
--
ALTER TABLE `role_properties`
  ADD CONSTRAINT `role_properties_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `role_security_rules`
--
ALTER TABLE `role_security_rules`
  ADD CONSTRAINT `role_security_rules_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `role_software`
--
ALTER TABLE `role_software`
  ADD CONSTRAINT `role_software_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `role_tags`
--
ALTER TABLE `role_tags`
  ADD CONSTRAINT `role_tags_ibfk_1` FOREIGN KEY (`role_id`) REFERENCES `roles` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `server_operations`
--
ALTER TABLE `server_operations`
  ADD CONSTRAINT `server_operations_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`server_id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `server_operation_progress`
--
ALTER TABLE `server_operation_progress`
  ADD CONSTRAINT `server_operation_progress_ibfk_1` FOREIGN KEY (`operation_id`) REFERENCES `server_operations` (`id`) ON DELETE CASCADE ON UPDATE NO ACTION;

--
-- Constraints for table `server_properties`
--
ALTER TABLE `server_properties`
  ADD CONSTRAINT `server_properties_ibfk_1` FOREIGN KEY (`server_id`) REFERENCES `servers` (`server_id`) ON DELETE CASCADE ON UPDATE NO ACTION;




--
-- Dumping data for table `account_users`
--

INSERT INTO `account_users` VALUES
(1, 0, 'Active', 'admin', 'Scalr Admin', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918', '2011-09-08 08:06:53', '2012-03-15 03:11:14', 'ScalrAdmin', NULL);
--
-- Dumping data for table `config`
--

INSERT INTO `config` VALUES
(1, 'crypto_algo', 'SHA256'),
(2, 'admin_password', '8c6976e5b5410415bde908bd4dee15dfb167a9c873fc4bb8a81f6f2ab448a918'),
(3, 'namedconftpl', 'zone "{zone}" {\r\n   type master;\r\n   file "{db_filename}";\r\n   allow-transfer { {allow_transfer}; };\r\n   {also-notify}\r\n};'),
(4, 'snmptrap_path', '/usr/bin/snmptrap'),
(5, 'admin_login', 'admin'),
(6, 'email_address', 'admin@example.com'),
(7, 'email_name', 'Scalr'),
(8, 'email_dsn', ''),
(9, 'team_emails', ''),
(10, 'dynamic_a_rec_ttl', '90'),
(11, 'secgroup_prefix', 'scalr.'),
(12, 's3cfg_template', '[default]\r\naccess_key = [access_key]\r\nacl_public = False\r\nforce = False\r\nhuman_readable_sizes = False\r\nrecv_chunk = 4096\r\nsecret_key = [secret_key]\r\nsend_chunk = 4096\r\nverbosity = WARNING'),
(13, 'rrdtool_path', '/usr/local/rrdtool-1.3.0/bin/rrdtool'),
(14, 'rrd_default_font_path', '/usr/share/rrdtool/fonts/DejaVuSansMono-Roman.ttf'),
(15, 'rrd_db_dir', '/home/rrddata'),
(16, 'rrd_stats_url', 'http://example.net/graphics/%fid%/%rn%_%wn%.'),
(17, 'rrd_graph_storage_type', 'LOCAL'),
(18, 'rrd_graph_storage_path', '/home/graphics'),
(19, 'http_proto', 'http'),
(20, 'eventhandler_url', 'example.com'),
(21, 'cron_processes_number', '5'),
(22, 'app_sys_ipaddress', '192.168.1.1');

INSERT INTO `roles` VALUES
(16161, 'mysqllvm-centos-5-4', 'SHARED', 0, 0, 'MySQL (v5.0.77) role on CentOS 5.4 i386 EBS', 'mysql', 'i386', 1, '', NULL, 2, 'CentOS 5.4', '0.6.21'),
(16162, 'mysqllvm64-centos-5-4', 'SHARED', 0, 0, 'MySQL (v5.0.77) role on CentOS 5.4 x86_64 EBS', 'mysql', 'x86_64', 1, '', NULL, 2, 'CentOS 5.4', '0.6.21'),
(16163, 'base-centos-5-4', 'SHARED', 0, 0, 'Base role on CentOS 5.4 i386 EBS', 'base', 'i386', 1, '', NULL, 2, 'CentOS 5.4', '0.6.21'),
(16164, 'base64-centos-5-4', 'SHARED', 0, 0, 'Base role on CentOS 5.4 x86_64 EBS ', 'base', 'x86_64', 1, '', NULL, 2, 'CentOS 5.4', '0.6.21'),
(16374, 'mysqllvm-ubuntu-10-04', 'SHARED', 0, 0, 'MySQL (v5.1.41) on Ubuntu 10.04 i386 EBS-root', 'mysql', 'i386', 0, '', NULL, 2, 'Ubuntu 10.04', '0.6.21'),
(16375, 'mysqllvm64-ubuntu-10-04', 'SHARED', 0, 0, 'MySQL (v5.1.41) on Ubuntu 10.04 x86_64 EBS-root', 'mysql', 'x86_64', 0, '', NULL, 2, 'Ubuntu 10.04', '0.6.21'),
(18015, 'app-apache-centos-ebs', 'SHARED', 0, 0, 'Can act as a backend (if farm contains load balancer role) or frontend web server.', 'app', 'i386', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', '0.6.21'),
(18016, 'app-apache64-centos-ebs', 'SHARED', 0, 0, 'Can act as a backend (if farm contains load balancer role) or frontend web server.', 'app', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.5. Final', '0.6.21'),
(18017, 'lb-nginx-centos-ebs', 'SHARED', 0, 0, 'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of Application Server role.', 'www', 'i386', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', '0.6.21'),
(18018, 'lb-nginx64-centos-ebs', 'SHARED', 0, 0, 'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of Application Server role.', 'www', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.5. Final', '0.6.21'),
(18367, 'lamp-centos-ebs', 'SHARED', 0, 0, 'Clasic LAMP', 'mysql,app', 'i386', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', '0.6.21'),
(18368, 'lamp64-centos-ebs', 'SHARED', 0, 0, 'Classic LAMP', 'mysql,app', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', '0.6.21'),
(18615, 'base-ubuntu-ebs', 'SHARED', 0, 0, 'Base role', 'base', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18616, 'base64-ubuntu-ebs', 'SHARED', 0, 0, 'Base role', 'base', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18617, 'app-apache-ubuntu-ebs', 'SHARED', 0, 0, 'Can act as a backend (if farm contains load balancer role) or frontend web server. ', 'app', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18618, 'app-apache64-ubuntu-ebs', 'SHARED', 0, 0, 'Can act as a backend (if farm contains load balancer role) or frontend web server. ', 'app', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18619, 'lb-nginx-ubuntu-ebs', 'SHARED', 0, 0, 'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of Application Server role. ', 'www', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18620, 'lb-nginx64-ubuntu-ebs', 'SHARED', 0, 0, 'Frontend web server/load balancer, running nginx. Proxies all requests to all instances of Application Server role. ', 'www', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18621, 'mysqllvm-ubuntu-ebs', 'SHARED', 0, 0, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.', 'mysql', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18622, 'mysqllvm64-ubuntu-ebs', 'SHARED', 0, 0, 'MySQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.', 'mysql', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18623, 'lamp-ubuntu-ebs', 'SHARED', 0, 0, 'Classic LAMP (Linux Apache PHP MySQL)', 'mysql,app', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(18624, 'lamp64-ubuntu-ebs', 'SHARED', 0, 0, 'Classic LAMP (Linux Apache PHP MySQL)', 'mysql,app', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.6.21'),
(22461, 'base64-hvm-centos-5-4', 'SHARED', 0, 0, '', 'base', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.4', NULL),
(24151, 'percona64-ubuntu1004', 'SHARED', 0, 0, 'An enhanced replacement for MySQL with better performance, scalability, tunability, and instrumentation. Over 250,000 downloads.', 'mysql', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', NULL),
(24154, 'percona-ubuntu1004', 'SHARED', 0, 0, 'An enhanced replacement for MySQL with better performance, scalability, tunability, and instrumentation. Over 250,000 downloads.', 'mysql', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', NULL),
(24156, 'percona64-centos55', 'SHARED', 0, 0, 'An enhanced replacement for MySQL with better performance, scalability, tunability, and instrumentation. Over 250,000 downloads.', 'mysql', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', NULL),
(24159, 'percona-centos55', 'SHARED', 0, 0, 'An enhanced replacement for MySQL with better performance, scalability, tunability, and instrumentation. Over 250,000 downloads.', 'mysql', 'i386', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', NULL),
(25052, 'base64-windows2008', 'SHARED', 0, 0, '', 'base', 'x86_64', 1, NULL, 'Approved', 2, 'Windows 2008 Server', '0.8.0'),
(25056, 'base-windows2008', 'SHARED', 0, 0, '', 'base', 'i386', 1, NULL, 'Approved', 2, 'Windows 2008 Server', '0.8.0'),
(25162, 'base-windows2003', 'SHARED', 0, 0, '', 'base', 'i386', 1, NULL, 'Approved', 2, 'Windows 2003 Server', NULL),
(25163, 'base64-windows2003', 'SHARED', 0, 0, '', 'base', 'x86_64', 1, NULL, 'Approved', 2, 'Windows 2003 Server', NULL),
(25952, 'memcached64-ubuntu1004', 'SHARED', 0, 0, '', 'memcached', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', NULL),
(25955, 'memcached-ubuntu1004', 'SHARED', 0, 0, '', 'memcached', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', NULL),
(25957, 'memcached64-centos55', 'SHARED', 0, 0, '', 'memcached', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', NULL),
(25958, 'memcached-centos55', 'SHARED', 0, 0, '', 'memcached', 'i386', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', NULL),
(26539, 'postgresql-ubuntu1004', 'SHARED', 0, 0, 'PostgreSQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.', 'postgresql', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.7.74'),
(26542, 'postgresql64-ubuntu1004', 'SHARED', 0, 0, 'PostgreSQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.', 'postgresql', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04', '0.7.74'),
(26543, 'postgresql-centos55', 'SHARED', 0, 0, 'PostgreSQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.', 'postgresql', 'i386', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', '0.7.74'),
(26544, 'postgresql64-centos55', 'SHARED', 0, 0, 'PostgreSQL database server. Scalr automatically assigns master and slave roles, if multiple instances launched, and re-assigns during scaling or curing.', 'postgresql', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', '0.7.74'),
(27481, 'redis64-ubuntu1004', 'SHARED', 0, 0, 'Redis is an open source, advanced key-value store. It is often referred to as a data structure server since keys can contain strings, hashes, lists, sets and sorted sets.', 'redis', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.95'),
(27482, 'redis64-centos55', 'SHARED', 0, 0, 'Redis is an open source, advanced key-value store. It is often referred to as a data structure server since keys can contain strings, hashes, lists, sets and sorted sets.', 'redis', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', '0.7.95'),
(27506, 'redis-ubuntu1004', 'SHARED', 0, 0, 'Redis is an open source, advanced key-value store. It is often referred to as a data structure server since keys can contain strings, hashes, lists, sets and sorted sets.', 'redis', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.95'),
(27507, 'redis-centos55', 'SHARED', 0, 0, 'Redis is an open source, advanced key-value store. It is often referred to as a data structure server since keys can contain strings, hashes, lists, sets and sorted sets.', 'redis', 'i386', 1, NULL, 'Approved', 2, 'CentOS 5.5 Final', '0.7.95'),
(28917, 'cf-all64-ubuntu1004', 'SHARED', 0, 0, 'CloudFoundry all in one role\n* Nginx\n* Router\n* CloudController\n* Health Manager\n* DEA', 'www,cf_router,cf_cloud_controller,cf_health_manager,cf_dea,cf_service', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.100'),
(28921, 'cf-router64-ubuntu1004', 'SHARED', 0, 0, 'CloudFoundry Router component', 'cf_router', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.100'),
(28922, 'cf-dea64-ubuntu1004', 'SHARED', 0, 0, 'CloudFoundry DEA component', 'cf_dea', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.100'),
(28925, 'cf-cchm64-ubuntu1004', 'SHARED', 0, 0, 'CloudFoundry cloud controller component\n\n* Cloud controller\n* Health manager', 'cf_cloud_controller,cf_health_manager', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.100'),
(31011, 'rabbitmq64-ubuntu1004', 'SHARED', 0, 0, 'RabbitMQ is a complete and highly reliable enterprise messaging system based on the emerging AMQP standard', 'rabbitmq', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.130'),
(31024, 'rabbitmq-ubuntu1004', 'SHARED', 0, 0, 'RabbitMQ is a complete and highly reliable enterprise messaging system based on the emerging AMQP standard', 'rabbitmq', 'i386', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.130'),
(33205, 'mongodb64-ubuntu1004', 'SHARED', 0, 0, 'MongoDB (from "humongous") is a scalable, high-performance, open source NoSQL database. ', 'mongodb', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 10.04 lucid', '0.7.150'),
(33206, 'mongodb64-centos57', 'SHARED', 0, 0, 'MongoDB (from "humongous") is a scalable, high-performance, open source NoSQL database. ', 'mongodb', 'x86_64', 1, NULL, 'Approved', 2, 'CentOS 5.7', '0.7.150'),
(35144, 'base64-hvm-ubuntu1110', 'SHARED', 0, 0, '', 'base', 'x86_64', 1, NULL, 'Approved', 2, 'Ubuntu 11.10 oneiric', '0.7.178');


INSERT INTO `role_behaviors` (`id`, `role_id`, `behavior`) VALUES
(9491, 16161, 'mysql'),
(23668, 16162, 'mysql'),
(9298, 16163, 'base'),
(9299, 16164, 'base'),
(28991, 16374, 'mysql'),
(28992, 16375, 'mysql'),
(29811, 18015, 'app'),
(30158, 18016, 'app'),
(29806, 18017, 'www'),
(29798, 18018, 'www'),
(30177, 18367, 'app'),
(30176, 18367, 'mysql'),
(30188, 18368, 'app'),
(30187, 18368, 'mysql'),
(34191, 18615, 'base'),
(29062, 18616, 'base'),
(29042, 18617, 'app'),
(29068, 18618, 'app'),
(29040, 18619, 'www'),
(29065, 18620, 'www'),
(29039, 18621, 'mysql'),
(29071, 18622, 'mysql'),
(29053, 18623, 'app'),
(29052, 18623, 'mysql'),
(29074, 18624, 'app'),
(29073, 18624, 'mysql'),
(11671, 22461, 'base'),
(29547, 24151, 'mysql'),
(29055, 24154, 'mysql'),
(30184, 24156, 'mysql'),
(30171, 24159, 'mysql'),
(15780, 25052, 'base'),
(15787, 25056, 'base'),
(15965, 25162, 'base'),
(15967, 25163, 'base'),
(29075, 25952, 'memcached'),
(29050, 25955, 'memcached'),
(34199, 25957, 'memcached'),
(30172, 25958, 'memcached'),
(29057, 26539, 'postgresql'),
(29077, 26542, 'postgresql'),
(30173, 26543, 'postgresql'),
(29797, 26544, 'postgresql'),
(29160, 27481, 'redis'),
(29801, 27482, 'redis'),
(29164, 27506, 'redis'),
(30159, 27507, 'redis'),
(21704, 28917, 'cf_cloud_controller'),
(21706, 28917, 'cf_dea'),
(21705, 28917, 'cf_health_manager'),
(21703, 28917, 'cf_router'),
(21707, 28917, 'cf_service'),
(21702, 28917, 'www'),
(21713, 28921, 'cf_router'),
(21715, 28922, 'cf_dea'),
(21720, 28925, 'cf_cloud_controller'),
(21721, 28925, 'cf_health_manager'),
(29892, 31011, 'rabbitmq'),
(29891, 31024, 'rabbitmq'),
(29890, 33205, 'mongodb'),
(29803, 33206, 'mongodb'),
(32797, 35144, 'base');


INSERT INTO `role_images` (`id`, `role_id`, `cloud_location`, `image_id`, `platform`) VALUES
(4504, 16163, 'us-east-1', 'ami-2002f749', 'ec2'),
(4505, 16163, 'us-west-1', 'ami-8c1747c9', 'ec2'),
(4506, 16163, 'eu-west-1', 'ami-146d5860', 'ec2'),
(4507, 16163, 'ap-southeast-1', 'ami-f81967aa', 'ec2'),
(4515, 16164, 'us-east-1', 'ami-1402f77d', 'ec2'),
(4516, 16164, 'us-west-1', 'ami-31fba674', 'ec2'),
(4517, 16164, 'eu-west-1', 'ami-449caf30', 'ec2'),
(4518, 16164, 'ap-southeast-1', 'ami-72f38820', 'ec2'),
(4551, 16161, 'us-east-1', 'ami-fc02f795', 'ec2'),
(4552, 16161, 'us-west-1', 'ami-941747d1', 'ec2'),
(4553, 16161, 'eu-west-1', 'ami-fe6c598a', 'ec2'),
(4554, 16161, 'ap-southeast-1', 'ami-5a166808', 'ec2'),
(4560, 16162, 'us-east-1', 'ami-a002f7c9', 'ec2'),
(4561, 16162, 'us-west-1', 'ami-79faa73c', 'ec2'),
(4563, 16162, 'ap-southeast-1', 'ami-aaf388f8', 'ec2'),
(7857, 16163, 'ap-northeast-1', 'ami-6400ab65', 'ec2'),
(7862, 16164, 'ap-northeast-1', 'ami-6e01b46f', 'ec2'),
(8065, 16161, 'ap-northeast-1', 'ami-f207acf3', 'ec2'),
(8070, 16162, 'ap-northeast-1', 'ami-f001b4f1', 'ec2'),
(9476, 22461, 'us-east-1', 'ami-08bd4261', 'ec2'),
(12108, 25052, 'us-east-1', 'ami-e0669d89', 'ec2'),
(12112, 25056, 'us-east-1', 'ami-12669d7b', 'ec2'),
(12195, 25056, 'us-west-1', 'ami-5588da10', 'ec2'),
(12196, 25056, 'eu-west-1', 'ami-c8a091bc', 'ec2'),
(12197, 25056, 'ap-northeast-1', 'ami-52f74253', 'ec2'),
(12198, 25056, 'ap-southeast-1', 'ami-feb6ceac', 'ec2'),
(12199, 25052, 'us-west-1', 'ami-b78bd9f2', 'ec2'),
(12200, 25052, 'eu-west-1', 'ami-aea091da', 'ec2'),
(12201, 25052, 'ap-northeast-1', 'ami-56f74257', 'ec2'),
(12202, 25052, 'ap-southeast-1', 'ami-ceb6ce9c', 'ec2'),
(12227, 25162, 'us-east-1', 'ami-7ea85217', 'ec2'),
(12228, 25162, 'us-west-1', 'ami-ff8cdeba', 'ec2'),
(12229, 25162, 'eu-west-1', 'ami-d4a899a0', 'ec2'),
(12230, 25162, 'ap-southeast-1', 'ami-7cabd32e', 'ec2'),
(12231, 25162, 'ap-northeast-1', 'ami-26f04527', 'ec2'),
(12232, 25163, 'us-east-1', 'ami-44a8522d', 'ec2'),
(12233, 25163, 'us-west-1', 'ami-fb8cdebe', 'ec2'),
(12234, 25163, 'eu-west-1', 'ami-d6a899a2', 'ec2'),
(12235, 25163, 'ap-southeast-1', 'ami-66abd334', 'ec2'),
(12236, 25163, 'ap-northeast-1', 'ami-30f04531', 'ec2'),
(16117, 28917, 'us-east-1', 'ami-7595591c', 'ec2'),
(16121, 28921, 'us-east-1', 'ami-65905c0c', 'ec2'),
(16122, 28922, 'us-east-1', 'ami-63905c0a', 'ec2'),
(16125, 28925, 'us-east-1', 'ami-69905c00', 'ec2'),
(17593, 16162, 'eu-west-1', 'ami-8a9caffe', 'ec2'),
(21189, 16374, 'us-east-1', 'ami-9205effb', 'ec2'),
(21190, 16375, 'us-east-1', 'ami-6a1af003', 'ec2'),
(21313, 18621, 'us-west-2', 'ami-04f47934', 'ec2'),
(21314, 18621, 'ap-southeast-1', 'ami-0c6a145e', 'ec2'),
(21315, 18621, 'us-west-1', 'ami-12510157', 'ec2'),
(21316, 18621, 'eu-west-1', 'ami-f3fbce87', 'ec2'),
(21317, 18621, 'us-east-1', 'ami-741bec1d', 'ec2'),
(21318, 18621, 'ap-northeast-1', 'ami-bc04afbd', 'ec2'),
(21319, 18621, 'sa-east-1', 'ami-845f8099', 'ec2'),
(21320, 18619, 'eu-west-1', 'ami-6a2d1e1e', 'ec2'),
(21321, 18619, 'us-west-1', 'ami-8fd28eca', 'ec2'),
(21322, 18619, 'us-west-2', 'ami-1ef4792e', 'ec2'),
(21323, 18619, 'ap-northeast-1', 'ami-a604afa7', 'ec2'),
(21324, 18619, 'ap-southeast-1', 'ami-d8b7cd8a', 'ec2'),
(21325, 18619, 'us-east-1', 'ami-9c13e4f5', 'ec2'),
(21326, 18619, 'sa-east-1', 'ami-8a5f8097', 'ec2'),
(21328, 18617, 'ap-northeast-1', 'ami-8e04af8f', 'ec2'),
(21329, 18617, 'us-west-2', 'ami-06f47936', 'ec2'),
(21330, 18617, 'us-west-1', 'ami-ddd28e98', 'ec2'),
(21331, 18617, 'ap-southeast-1', 'ami-34b7cd66', 'ec2'),
(21332, 18617, 'us-east-1', 'ami-c61cebaf', 'ec2'),
(21333, 18617, 'eu-west-1', 'ami-fffbce8b', 'ec2'),
(21334, 18617, 'sa-east-1', 'ami-865f809b', 'ec2'),
(21341, 25955, 'ap-southeast-1', 'ami-383e456a', 'ec2'),
(21342, 25955, 'us-west-2', 'ami-54f47964', 'ec2'),
(21343, 25955, 'eu-west-1', 'ami-a4feced0', 'ec2'),
(21344, 25955, 'ap-northeast-1', 'ami-aeb90caf', 'ec2'),
(21345, 25955, 'us-west-1', 'ami-dd0d5098', 'ec2'),
(21346, 25955, 'us-east-1', 'ami-e920e680', 'ec2'),
(21347, 25955, 'sa-east-1', 'ami-825f809f', 'ec2'),
(21349, 18623, 'eu-west-1', 'ami-911a27e5', 'ec2'),
(21350, 18623, 'ap-southeast-1', 'ami-7ea7dd2c', 'ec2'),
(21351, 18623, 'us-west-1', 'ami-f5f8a4b0', 'ec2'),
(21352, 18623, 'us-west-2', 'ami-74f47944', 'ec2'),
(21353, 18623, 'ap-northeast-1', 'ami-c004afc1', 'ec2'),
(21354, 18623, 'us-east-1', 'ami-dff836b6', 'ec2'),
(21355, 18623, 'sa-east-1', 'ami-be5f80a3', 'ec2'),
(21357, 24154, 'us-west-1', 'ami-c5c39f80', 'ec2'),
(21358, 24154, 'us-east-1', 'ami-ec778e85', 'ec2'),
(21359, 24154, 'eu-west-1', 'ami-9d1d20e9', 'ec2'),
(21360, 24154, 'ap-northeast-1', 'ami-be4bffbf', 'ec2'),
(21361, 24154, 'us-west-2', 'ami-78f47948', 'ec2'),
(21362, 24154, 'ap-southeast-1', 'ami-d82d558a', 'ec2'),
(21363, 24154, 'sa-east-1', 'ami-bc5f80a1', 'ec2'),
(21365, 26539, 'ap-southeast-1', 'ami-34a4df66', 'ec2'),
(21366, 26539, 'us-west-1', 'ami-39a5f87c', 'ec2'),
(21367, 26539, 'ap-northeast-1', 'ami-54398c55', 'ec2'),
(21368, 26539, 'us-east-1', 'ami-713dfc18', 'ec2'),
(21369, 26539, 'us-west-2', 'ami-7cf4794c', 'ec2'),
(21370, 26539, 'eu-west-1', 'ami-a4d5e6d0', 'ec2'),
(21371, 26539, 'sa-east-1', 'ami-b85f80a5', 'ec2'),
(21377, 18616, 'us-west-2', 'ami-16f47926', 'ec2'),
(21378, 18616, 'us-west-1', 'ami-49c39f0c', 'ec2'),
(21379, 18616, 'ap-southeast-1', 'ami-64a3d936', 'ec2'),
(21380, 18616, 'eu-west-1', 'ami-fbfbce8f', 'ec2'),
(21381, 18616, 'ap-northeast-1', 'ami-a004afa1', 'ec2'),
(21382, 18616, 'us-east-1', 'ami-f9ef2190', 'ec2'),
(21383, 18616, 'sa-east-1', 'ami-b65f80ab', 'ec2'),
(21387, 18620, 'us-east-1', 'ami-ac13e4c5', 'ec2'),
(21388, 18620, 'eu-west-1', 'ami-e7fbce93', 'ec2'),
(21389, 18620, 'us-west-2', 'ami-1af4792a', 'ec2'),
(21390, 18620, 'us-west-1', 'ami-8bd28ece', 'ec2'),
(21391, 18620, 'ap-northeast-1', 'ami-b804afb9', 'ec2'),
(21392, 18620, 'ap-southeast-1', 'ami-c2b7cd90', 'ec2'),
(21393, 18620, 'sa-east-1', 'ami-ac5f80b1', 'ec2'),
(21395, 18618, 'ap-northeast-1', 'ami-9c04af9d', 'ec2'),
(21396, 18618, 'us-west-2', 'ami-02f47932', 'ec2'),
(21397, 18618, 'us-west-1', 'ami-d1d28e94', 'ec2'),
(21398, 18618, 'ap-southeast-1', 'ami-3a6a1468', 'ec2'),
(21399, 18618, 'eu-west-1', 'ami-e5fbce91', 'ec2'),
(21400, 18618, 'us-east-1', 'ami-a81cebc1', 'ec2'),
(21401, 18618, 'sa-east-1', 'ami-ae5f80b3', 'ec2'),
(21403, 18622, 'ap-southeast-1', 'ami-3e6a146c', 'ec2'),
(21404, 18622, 'us-west-1', 'ami-2c510169', 'ec2'),
(21405, 18622, 'us-east-1', 'ami-5dec2234', 'ec2'),
(21406, 18622, 'eu-west-1', 'ami-e1fbce95', 'ec2'),
(21407, 18622, 'us-west-2', 'ami-1cf4792c', 'ec2'),
(21408, 18622, 'ap-northeast-1', 'ami-be04afbf', 'ec2'),
(21409, 18622, 'sa-east-1', 'ami-a85f80b5', 'ec2'),
(21411, 18624, 'us-west-1', 'ami-ebf8a4ae', 'ec2'),
(21412, 18624, 'ap-southeast-1', 'ami-7aa7dd28', 'ec2'),
(21413, 18624, 'ap-northeast-1', 'ami-7a37837b', 'ec2'),
(21414, 18624, 'us-west-2', 'ami-76f47946', 'ec2'),
(21415, 18624, 'eu-west-1', 'ami-e3fbce97', 'ec2'),
(21416, 18624, 'us-east-1', 'ami-fc13e495', 'ec2'),
(21417, 18624, 'sa-east-1', 'ami-a45f80b9', 'ec2'),
(21418, 25952, 'us-east-1', 'ami-1920e670', 'ec2'),
(21419, 25952, 'ap-southeast-1', 'ami-1c3e454e', 'ec2'),
(21420, 25952, 'us-west-2', 'ami-62f47952', 'ec2'),
(21421, 25952, 'ap-northeast-1', 'ami-a6b90ca7', 'ec2'),
(21422, 25952, 'eu-west-1', 'ami-bcfecec8', 'ec2'),
(21423, 25952, 'us-west-1', 'ami-d50d5090', 'ec2'),
(21424, 25952, 'sa-east-1', 'ami-a65f80bb', 'ec2'),
(21426, 26542, 'ap-southeast-1', 'ami-0ca4df5e', 'ec2'),
(21427, 26542, 'us-west-1', 'ami-3da5f878', 'ec2'),
(21428, 26542, 'ap-northeast-1', 'ami-42398c43', 'ec2'),
(21429, 26542, 'us-west-2', 'ami-72f47942', 'ec2'),
(21430, 26542, 'eu-west-1', 'ami-b0d5e6c4', 'ec2'),
(21431, 26542, 'us-east-1', 'ami-d93dfcb0', 'ec2'),
(21432, 26542, 'sa-east-1', 'ami-a05f80bd', 'ec2'),
(21580, 27481, 'eu-west-1', 'ami-43d5ea37', 'ec2'),
(21581, 27481, 'ap-northeast-1', 'ami-86e75187', 'ec2'),
(21582, 27481, 'sa-east-1', 'ami-945f8089', 'ec2'),
(21583, 27481, 'us-east-1', 'ami-aded2ec4', 'ec2'),
(21584, 27481, 'ap-southeast-1', 'ami-d2c88d80', 'ec2'),
(21585, 27481, 'us-west-1', 'ami-fd8bd5b8', 'ec2'),
(21586, 27481, 'us-west-2', 'ami-fcb23fcc', 'ec2'),
(21589, 18615, 'us-west-1', 'ami-55c39f10', 'ec2'),
(21590, 18615, 'eu-west-1', 'ami-f9fbce8d', 'ec2'),
(21591, 18615, 'ap-southeast-1', 'ami-366a1464', 'ec2'),
(21592, 18615, 'ap-northeast-1', 'ami-5c06ad5d', 'ec2'),
(21593, 18615, 'us-east-1', 'ami-dc1bc7b5', 'ec2'),
(21594, 18615, 'sa-east-1', 'ami-8e5f8093', 'ec2'),
(21595, 18615, 'us-west-2', 'ami-e4b23fd4', 'ec2'),
(21596, 27506, 'eu-west-1', 'ami-0bdae57f', 'ec2'),
(21597, 27506, 'ap-northeast-1', 'ami-82e75183', 'ec2'),
(21598, 27506, 'us-east-1', 'ami-8bf330e2', 'ec2'),
(21599, 27506, 'sa-east-1', 'ami-b45f80a9', 'ec2'),
(21600, 27506, 'us-west-1', 'ami-b98bd5fc', 'ec2'),
(21601, 27506, 'ap-southeast-1', 'ami-d0c88d82', 'ec2'),
(21602, 27506, 'us-west-2', 'ami-e6b23fd6', 'ec2'),
(21798, 18016, 'eu-west-1', 'ami-3fab944b', 'ec2'),
(21810, 18018, 'eu-west-1', 'ami-39ab944d', 'ec2'),
(21818, 25957, 'eu-west-1', 'ami-09ab947d', 'ec2'),
(21826, 26544, 'eu-west-1', 'ami-0fab947b', 'ec2'),
(21851, 25957, 'us-west-1', 'ami-83aaf4c6', 'ec2'),
(21854, 18018, 'us-west-1', 'ami-87aaf4c2', 'ec2'),
(21862, 26544, 'us-west-1', 'ami-81aaf4c4', 'ec2'),
(21872, 24159, 'eu-west-1', 'ami-1daa9569', 'ec2'),
(21879, 18015, 'eu-west-1', 'ami-1faa956b', 'ec2'),
(21889, 18017, 'eu-west-1', 'ami-19aa956d', 'ec2'),
(21896, 18367, 'eu-west-1', 'ami-0daa9579', 'ec2'),
(21923, 18017, 'us-west-1', 'ami-0fabf54a', 'ec2'),
(21930, 18367, 'us-west-1', 'ami-0dabf548', 'ec2'),
(21934, 24159, 'us-west-1', 'ami-03abf546', 'ec2'),
(21940, 25958, 'us-west-1', 'ami-17abf552', 'ec2'),
(21949, 26543, 'us-west-1', 'ami-1babf55e', 'ec2'),
(21954, 24151, 'us-west-1', 'ami-c7c39f82', 'ec2'),
(21955, 24151, 'us-east-1', 'ami-68778e01', 'ec2'),
(21956, 24151, 'ap-northeast-1', 'ami-bc4bffbd', 'ec2'),
(21957, 24151, 'eu-west-1', 'ami-911d20e5', 'ec2'),
(21958, 24151, 'us-west-2', 'ami-7af4794a', 'ec2'),
(21959, 24151, 'ap-southeast-1', 'ami-dea3d98c', 'ec2'),
(21960, 24151, 'sa-east-1', 'ami-1659860b', 'ec2'),
(21968, 25958, 'us-east-1', 'ami-df67b0b6', 'ec2'),
(21984, 18017, 'us-east-1', 'ami-af67b0c6', 'ec2'),
(21999, 27482, 'us-east-1', 'ami-0d66b164', 'ec2'),
(22003, 26544, 'us-east-1', 'ami-0f66b166', 'ec2'),
(22011, 25957, 'us-east-1', 'ami-0166b168', 'ec2'),
(22022, 24156, 'us-east-1', 'ami-8f66b1e6', 'ec2'),
(22088, 18018, 'us-east-1', 'ami-399e4850', 'ec2'),
(22100, 18016, 'us-east-1', 'ami-3f9e4856', 'ec2'),
(22106, 24159, 'us-east-1', 'ami-099e4860', 'ec2'),
(22173, 18017, 'ap-southeast-1', 'ami-72f4b120', 'ec2'),
(22194, 24156, 'ap-southeast-1', 'ami-90f4b1c2', 'ec2'),
(22232, 26544, 'ap-southeast-1', 'ami-8cf4b1de', 'ec2'),
(22233, 26544, 'ap-northeast-1', 'ami-76ca7c77', 'ec2'),
(22238, 18018, 'ap-southeast-1', 'ami-e2f4b1b0', 'ec2'),
(22239, 18018, 'ap-northeast-1', 'ami-78ca7c79', 'ec2'),
(22243, 27482, 'ap-southeast-1', 'ami-b2f4b1e0', 'ec2'),
(22244, 27482, 'ap-northeast-1', 'ami-74ca7c75', 'ec2'),
(22245, 27482, 'us-west-1', 'ami-85aaf4c0', 'ec2'),
(22246, 27482, 'eu-west-1', 'ami-e7ab9493', 'ec2'),
(22248, 33206, 'us-east-1', 'ami-0366b16a', 'ec2'),
(22249, 33206, 'ap-northeast-1', 'ami-84ca7c85', 'ec2'),
(22250, 33206, 'us-west-1', 'ami-8baaf4ce', 'ec2'),
(22251, 33206, 'ap-southeast-1', 'ami-b4f4b1e6', 'ec2'),
(22252, 33206, 'eu-west-1', 'ami-e3ab9497', 'ec2'),
(22256, 18017, 'ap-northeast-1', 'ami-8cca7c8d', 'ec2'),
(22264, 18367, 'ap-northeast-1', 'ami-92ca7c93', 'ec2'),
(22267, 18015, 'us-west-1', 'ami-79abf53c', 'ec2'),
(22268, 18015, 'ap-southeast-1', 'ami-4cf4b11e', 'ec2'),
(22269, 18015, 'us-east-1', 'ami-d367b0ba', 'ec2'),
(22270, 18015, 'ap-northeast-1', 'ami-9eca7c9f', 'ec2'),
(22280, 25958, 'ap-northeast-1', 'ami-a2ca7ca3', 'ec2'),
(22291, 27507, 'ap-northeast-1', 'ami-a6ca7ca7', 'ec2'),
(22310, 18368, 'eu-west-1', 'ami-4392ad37', 'ec2'),
(22335, 33205, 'us-east-1', 'ami-2dd80f44', 'ec2'),
(22336, 33205, 'eu-west-1', 'ami-37d5ea43', 'ec2'),
(22337, 33205, 'ap-southeast-1', 'ami-54cb8e06', 'ec2'),
(22338, 33205, 'us-west-1', 'ami-8d8bd5c8', 'ec2'),
(22339, 33205, 'sa-east-1', 'ami-dc5f80c1', 'ec2'),
(22340, 33205, 'us-west-2', 'ami-fab23fca', 'ec2'),
(22341, 33205, 'ap-northeast-1', 'ami-fee751ff', 'ec2'),
(22342, 31024, 'ap-southeast-1', 'ami-06d59054', 'ec2'),
(22343, 31024, 'eu-west-1', 'ami-0bd9e67f', 'ec2'),
(22344, 31024, 'us-east-1', 'ami-9377befa', 'ec2'),
(22345, 31024, 'sa-east-1', 'ami-b05f80ad', 'ec2'),
(22346, 31024, 'us-west-1', 'ami-bf8bd5fa', 'ec2'),
(22347, 31024, 'us-west-2', 'ami-eab23fda', 'ec2'),
(22348, 31024, 'ap-northeast-1', 'ami-fae751fb', 'ec2'),
(22349, 31011, 'eu-west-1', 'ami-09dae57d', 'ec2'),
(22350, 31011, 'ap-southeast-1', 'ami-52cb8e00', 'ec2'),
(22351, 31011, 'us-west-1', 'ami-838bd5c6', 'ec2'),
(22352, 31011, 'sa-east-1', 'ami-a25f80bf', 'ec2'),
(22353, 31011, 'us-west-2', 'ami-ccb23ffc', 'ec2'),
(22354, 31011, 'us-east-1', 'ami-db7eb7b2', 'ec2'),
(22355, 31011, 'ap-northeast-1', 'ami-fce751fd', 'ec2'),
(22502, 18016, 'us-west-1', 'ami-ebaaf4ae', 'ec2'),
(22505, 18016, 'ap-southeast-1', 'ami-8ef4b1dc', 'ec2'),
(22506, 18016, 'ap-northeast-1', 'ami-2cae182d', 'ec2'),
(22507, 27507, 'us-west-1', 'ami-25abf560', 'ec2'),
(22508, 27507, 'us-east-1', 'ami-9767b0fe', 'ec2'),
(22510, 27507, 'eu-west-1', 'ami-f3aa9587', 'ec2'),
(22511, 27507, 'ap-southeast-1', 'ami-e60d49b4', 'ec2'),
(22514, 25957, 'ap-southeast-1', 'ami-02fcb850', 'ec2'),
(22516, 25957, 'ap-northeast-1', 'ami-2eae182f', 'ec2'),
(22529, 24159, 'ap-northeast-1', 'ami-a0ca7ca1', 'ec2'),
(22532, 24159, 'ap-southeast-1', 'ami-940d49c6', 'ec2'),
(22534, 25958, 'ap-southeast-1', 'ami-9a0d49c8', 'ec2'),
(22535, 25958, 'eu-west-1', 'ami-ffaa958b', 'ec2'),
(22539, 26543, 'ap-southeast-1', 'ami-980d49ca', 'ec2'),
(22540, 26543, 'ap-northeast-1', 'ami-a4ca7ca5', 'ec2'),
(22541, 26543, 'us-east-1', 'ami-dd67b0b4', 'ec2'),
(22542, 26543, 'eu-west-1', 'ami-fdaa9589', 'ec2'),
(22546, 18367, 'us-east-1', 'ami-3d9e4854', 'ec2'),
(22547, 18367, 'ap-southeast-1', 'ami-9e0d49cc', 'ec2'),
(22552, 24156, 'ap-northeast-1', 'ami-72ca7c73', 'ec2'),
(22553, 24156, 'eu-west-1', 'ami-4d92ad39', 'ec2'),
(22554, 24156, 'us-west-1', 'ami-b9f9a7fc', 'ec2'),
(22558, 18368, 'ap-southeast-1', 'ami-e0f4b1b2', 'ec2'),
(22559, 18368, 'us-east-1', 'ami-8d66b1e4', 'ec2'),
(22560, 18368, 'ap-northeast-1', 'ami-32ae1833', 'ec2'),
(22562, 18368, 'us-west-1', 'ami-bbf9a7fe', 'ec2'),
(23877, 35144, 'us-east-1', 'ami-ac8c5ec5', 'ec2');


INSERT INTO `role_parameters` (`id`, `role_id`, `name`, `type`, `isrequired`, `defval`, `allow_multiple_choice`, `options`, `hash`, `issystem`) VALUES
(8088, 28917, 'Nginx HTTPS Vhost Template', 'textarea', 1, '{literal}server { {/literal}\r\n	listen       443;\r\n	server_name  {$host} www.{$host} {$server_alias};\r\n	\r\n	ssl                  on;\r\n	ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n	ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n	ssl_session_timeout  10m;\r\n	ssl_session_cache    shared:SSL:10m;\r\n\r\n	ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n	ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n	ssl_prefer_server_ciphers   on;\r\n{literal}\r\n	location / {\r\n		proxy_pass         http://backend;\r\n		proxy_set_header   Host             $host;\r\n		proxy_set_header   X-Real-IP        $remote_addr;\r\n		proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n		client_max_body_size       10m;\r\n		client_body_buffer_size    128k;\r\n  \r\n		proxy_buffering on;\r\n		proxy_connect_timeout 15;\r\n		proxy_intercept_errors on;  \r\n    }\r\n} {/literal}', 0, '', 'nginx_https_vhost_template', 1),
(8557, 18017, 'Nginx HTTPS Vhost Template', 'textarea', 1, '{literal}server { {/literal}\r\n	listen       443;\r\n	server_name  {$host} www.{$host} {$server_alias};\r\n	\r\n	ssl                  on;\r\n	ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n	ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n	ssl_session_timeout  10m;\r\n	ssl_session_cache    shared:SSL:10m;\r\n\r\n	ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n	ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n	ssl_prefer_server_ciphers   on;\r\n{literal}\r\n	location / {\r\n		proxy_pass         http://backend;\r\n		proxy_set_header   Host             $host;\r\n		proxy_set_header   X-Real-IP        $remote_addr;\r\n		proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n		client_max_body_size       10m;\r\n		client_body_buffer_size    128k;\r\n  \r\n		proxy_buffering on;\r\n		proxy_connect_timeout 15;\r\n		proxy_intercept_errors on;  \r\n    }\r\n} {/literal}', 0, '', 'nginx_https_vhost_template', 1),
(8558, 18018, 'Nginx HTTPS Vhost Template', 'textarea', 1, '{literal}server { {/literal}\r\n	listen       443;\r\n	server_name  {$host} www.{$host} {$server_alias};\r\n	\r\n	ssl                  on;\r\n	ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n	ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n	ssl_session_timeout  10m;\r\n	ssl_session_cache    shared:SSL:10m;\r\n\r\n	ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n	ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n	ssl_prefer_server_ciphers   on;\r\n{literal}\r\n	location / {\r\n		proxy_pass         http://backend;\r\n		proxy_set_header   Host             $host;\r\n		proxy_set_header   X-Real-IP        $remote_addr;\r\n		proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n		client_max_body_size       10m;\r\n		client_body_buffer_size    128k;\r\n  \r\n		proxy_buffering on;\r\n		proxy_connect_timeout 15;\r\n		proxy_intercept_errors on;  \r\n    }\r\n} {/literal}', 0, '', 'nginx_https_vhost_template', 1),
(8559, 18619, 'Nginx HTTPS Vhost Template', 'textarea', 1, '{literal}server { {/literal}\r\n	listen       443;\r\n	server_name  {$host} www.{$host} {$server_alias};\r\n	\r\n	ssl                  on;\r\n	ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n	ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n	ssl_session_timeout  10m;\r\n	ssl_session_cache    shared:SSL:10m;\r\n\r\n	ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n	ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n	ssl_prefer_server_ciphers   on;\r\n{literal}\r\n	location / {\r\n		proxy_pass         http://backend;\r\n		proxy_set_header   Host             $host;\r\n		proxy_set_header   X-Real-IP        $remote_addr;\r\n		proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n		client_max_body_size       10m;\r\n		client_body_buffer_size    128k;\r\n  \r\n		proxy_buffering on;\r\n		proxy_connect_timeout 15;\r\n		proxy_intercept_errors on;  \r\n    }\r\n} {/literal}', 0, '', 'nginx_https_vhost_template', 1),
(8560, 18620, 'Nginx HTTPS Vhost Template', 'textarea', 1, '{literal}server { {/literal}\r\n	listen       443;\r\n	server_name  {$host} www.{$host} {$server_alias};\r\n	\r\n	ssl                  on;\r\n	ssl_certificate      /etc/aws/keys/ssl/https.crt;\r\n	ssl_certificate_key  /etc/aws/keys/ssl/https.key;\r\n\r\n	ssl_session_timeout  10m;\r\n	ssl_session_cache    shared:SSL:10m;\r\n\r\n	ssl_protocols  SSLv2 SSLv3 TLSv1;\r\n	ssl_ciphers  ALL:!ADH:!EXPORT56:RC4+RSA:+HIGH:+MEDIUM:+LOW:+SSLv2:+EXP;\r\n	ssl_prefer_server_ciphers   on;\r\n{literal}\r\n	location / {\r\n		proxy_pass         http://backend;\r\n		proxy_set_header   Host             $host;\r\n		proxy_set_header   X-Real-IP        $remote_addr;\r\n		proxy_set_header   X-Forwarded-For  $proxy_add_x_forwarded_for;\r\n\r\n		client_max_body_size       10m;\r\n		client_body_buffer_size    128k;\r\n  \r\n		proxy_buffering on;\r\n		proxy_connect_timeout 15;\r\n		proxy_intercept_errors on;  \r\n    }\r\n} {/literal}', 0, '', 'nginx_https_vhost_template', 1);


INSERT INTO `role_properties` (`id`, `role_id`, `name`, `value`) VALUES
(6953, 16163, 'system.ssh-port', '22'),
(6954, 16164, 'system.ssh-port', '22'),
(7076, 16161, 'system.ssh-port', '22'),
(8025, 22461, 'system.ssh-port', '22'),
(9557, 25052, 'system.ssh-port', '22'),
(9560, 25056, 'system.ssh-port', '22'),
(9629, 25162, 'system.ssh-port', '22'),
(9630, 25163, 'system.ssh-port', '22'),
(12054, 28917, 'system.ssh-port', '22'),
(12057, 28921, 'system.ssh-port', '22'),
(12058, 28922, 'system.ssh-port', '22'),
(12061, 28925, 'system.ssh-port', '22'),
(12862, 16162, 'system.ssh-port', '22'),
(14877, 16374, 'system.ssh-port', '22'),
(14878, 16375, 'system.ssh-port', '22'),
(14908, 18621, 'system.ssh-port', '22'),
(14909, 18619, 'system.ssh-port', '22'),
(14910, 18617, 'system.ssh-port', '22'),
(14913, 25955, 'system.ssh-port', '22'),
(14914, 18623, 'system.ssh-port', '22'),
(14915, 24154, 'system.ssh-port', '22'),
(14916, 26539, 'system.ssh-port', '22'),
(14918, 18616, 'system.ssh-port', '22'),
(14920, 18620, 'system.ssh-port', '22'),
(14922, 18618, 'system.ssh-port', '22'),
(14923, 18622, 'system.ssh-port', '22'),
(14924, 18624, 'system.ssh-port', '22'),
(14925, 25952, 'system.ssh-port', '22'),
(14926, 26542, 'system.ssh-port', '22'),
(14962, 27481, 'system.ssh-port', '22'),
(14964, 27506, 'system.ssh-port', '22'),
(15129, 24151, 'system.ssh-port', '22'),
(15239, 26544, 'system.ssh-port', '22'),
(15240, 18018, 'system.ssh-port', '22'),
(15242, 27482, 'system.ssh-port', '22'),
(15243, 33206, 'system.ssh-port', '22'),
(15244, 18017, 'system.ssh-port', '22'),
(15246, 18015, 'system.ssh-port', '22'),
(15271, 33205, 'system.ssh-port', '22'),
(15272, 31024, 'system.ssh-port', '22'),
(15273, 31011, 'system.ssh-port', '22'),
(15349, 18016, 'system.ssh-port', '22'),
(15350, 27507, 'system.ssh-port', '22'),
(15355, 24159, 'system.ssh-port', '22'),
(15356, 25958, 'system.ssh-port', '22'),
(15357, 26543, 'system.ssh-port', '22'),
(15358, 18367, 'system.ssh-port', '22'),
(15360, 24156, 'system.ssh-port', '22'),
(15361, 18368, 'system.ssh-port', '22'),
(16269, 35144, 'system.ssh-port', '22'),
(16615, 18615, 'system.ssh-port', '22'),
(16618, 25957, 'system.ssh-port', '22');


INSERT INTO `role_security_rules` (`id`, `role_id`, `rule`) VALUES
(74, 16163, 'icmp:-1:-1:0.0.0.0/0'),
(75, 16163, 'tcp:22:22:0.0.0.0/0'),
(76, 16163, 'udp:161:162:0.0.0.0/0'),
(77, 16163, 'tcp:8013:8013:0.0.0.0/0'),
(78, 16163, 'udp:8014:8014:0.0.0.0/0'),
(85, 16164, 'icmp:-1:-1:0.0.0.0/0'),
(86, 16164, 'tcp:22:22:0.0.0.0/0'),
(87, 16164, 'udp:161:162:0.0.0.0/0'),
(88, 16164, 'tcp:8013:8013:0.0.0.0/0'),
(89, 16164, 'udp:8014:8014:0.0.0.0/0'),
(129, 16161, 'icmp:-1:-1:0.0.0.0/0'),
(130, 16161, 'tcp:22:22:0.0.0.0/0'),
(131, 16161, 'udp:161:162:0.0.0.0/0'),
(132, 16161, 'tcp:3306:3306:0.0.0.0/0'),
(133, 16161, 'tcp:8013:8013:0.0.0.0/0'),
(134, 16161, 'udp:8014:8014:0.0.0.0/0'),
(135, 16374, 'icmp:-1:-1:0.0.0.0/0'),
(136, 16374, 'tcp:22:22:0.0.0.0/0'),
(137, 16374, 'udp:161:162:0.0.0.0/0'),
(138, 16374, 'tcp:3306:3306:0.0.0.0/0'),
(139, 16374, 'tcp:8013:8013:0.0.0.0/0'),
(140, 16374, 'udp:8014:8014:0.0.0.0/0'),
(145, 16162, 'icmp:-1:-1:0.0.0.0/0'),
(146, 16162, 'tcp:22:22:0.0.0.0/0'),
(147, 16162, 'udp:161:162:0.0.0.0/0'),
(148, 16162, 'tcp:3306:3306:0.0.0.0/0'),
(149, 16162, 'tcp:8013:8013:0.0.0.0/0'),
(150, 16162, 'udp:8014:8014:0.0.0.0/0'),
(151, 16375, 'icmp:-1:-1:0.0.0.0/0'),
(152, 16375, 'tcp:22:22:0.0.0.0/0'),
(153, 16375, 'udp:161:162:0.0.0.0/0'),
(154, 16375, 'tcp:3306:3306:0.0.0.0/0'),
(155, 16375, 'tcp:8013:8013:0.0.0.0/0'),
(156, 16375, 'udp:8014:8014:0.0.0.0/0'),
(423, 18015, 'icmp:-1:-1:0.0.0.0/0'),
(424, 18015, 'tcp:22:22:0.0.0.0/0'),
(425, 18015, 'tcp:8013:8013:0.0.0.0/0'),
(426, 18015, 'udp:8014:8014:0.0.0.0/0'),
(427, 18015, 'udp:161:162:0.0.0.0/0'),
(428, 18015, 'tcp:80:80:0.0.0.0/0'),
(429, 18015, 'tcp:443:443:0.0.0.0/0'),
(430, 18016, 'icmp:-1:-1:0.0.0.0/0'),
(431, 18016, 'tcp:22:22:0.0.0.0/0'),
(432, 18016, 'tcp:8013:8013:0.0.0.0/0'),
(433, 18016, 'udp:8014:8014:0.0.0.0/0'),
(434, 18016, 'udp:161:162:0.0.0.0/0'),
(435, 18016, 'tcp:80:80:0.0.0.0/0'),
(436, 18016, 'tcp:443:443:0.0.0.0/0'),
(437, 18017, 'icmp:-1:-1:0.0.0.0/0'),
(438, 18017, 'tcp:22:22:0.0.0.0/0'),
(439, 18017, 'tcp:8013:8013:0.0.0.0/0'),
(440, 18017, 'udp:8014:8014:0.0.0.0/0'),
(441, 18017, 'udp:161:162:0.0.0.0/0'),
(442, 18017, 'tcp:80:80:0.0.0.0/0'),
(443, 18017, 'tcp:443:443:0.0.0.0/0'),
(444, 18018, 'icmp:-1:-1:0.0.0.0/0'),
(445, 18018, 'tcp:22:22:0.0.0.0/0'),
(446, 18018, 'tcp:8013:8013:0.0.0.0/0'),
(447, 18018, 'udp:8014:8014:0.0.0.0/0'),
(448, 18018, 'udp:161:162:0.0.0.0/0'),
(449, 18018, 'tcp:80:80:0.0.0.0/0'),
(450, 18018, 'tcp:443:443:0.0.0.0/0'),
(991, 18367, 'icmp:-1:-1:0.0.0.0/0'),
(992, 18367, 'tcp:22:22:0.0.0.0/0'),
(993, 18367, 'tcp:8013:8013:0.0.0.0/0'),
(994, 18367, 'udp:8014:8014:0.0.0.0/0'),
(995, 18367, 'udp:161:162:0.0.0.0/0'),
(996, 18367, 'tcp:3306:3306:0.0.0.0/0'),
(997, 18367, 'tcp:80:80:0.0.0.0/0'),
(998, 18367, 'tcp:443:443:0.0.0.0/0'),
(999, 18368, 'icmp:-1:-1:0.0.0.0/0'),
(1000, 18368, 'tcp:22:22:0.0.0.0/0'),
(1001, 18368, 'tcp:8013:8013:0.0.0.0/0'),
(1002, 18368, 'udp:8014:8014:0.0.0.0/0'),
(1003, 18368, 'udp:161:162:0.0.0.0/0'),
(1004, 18368, 'tcp:3306:3306:0.0.0.0/0'),
(1005, 18368, 'tcp:80:80:0.0.0.0/0'),
(1006, 18368, 'tcp:443:443:0.0.0.0/0'),
(1387, 18615, 'icmp:-1:-1:0.0.0.0/0'),
(1388, 18615, 'tcp:22:22:0.0.0.0/0'),
(1389, 18615, 'tcp:8013:8013:0.0.0.0/0'),
(1390, 18615, 'udp:8014:8014:0.0.0.0/0'),
(1391, 18615, 'udp:161:162:0.0.0.0/0'),
(1392, 18616, 'icmp:-1:-1:0.0.0.0/0'),
(1393, 18616, 'tcp:22:22:0.0.0.0/0'),
(1394, 18616, 'tcp:8013:8013:0.0.0.0/0'),
(1395, 18616, 'udp:8014:8014:0.0.0.0/0'),
(1396, 18616, 'udp:161:162:0.0.0.0/0'),
(1397, 18617, 'icmp:-1:-1:0.0.0.0/0'),
(1398, 18617, 'tcp:22:22:0.0.0.0/0'),
(1399, 18617, 'tcp:8013:8013:0.0.0.0/0'),
(1400, 18617, 'udp:8014:8014:0.0.0.0/0'),
(1401, 18617, 'udp:161:162:0.0.0.0/0'),
(1402, 18617, 'tcp:80:80:0.0.0.0/0'),
(1403, 18617, 'tcp:443:443:0.0.0.0/0'),
(1404, 18618, 'icmp:-1:-1:0.0.0.0/0'),
(1405, 18618, 'tcp:22:22:0.0.0.0/0'),
(1406, 18618, 'tcp:8013:8013:0.0.0.0/0'),
(1407, 18618, 'udp:8014:8014:0.0.0.0/0'),
(1408, 18618, 'udp:161:162:0.0.0.0/0'),
(1409, 18618, 'tcp:80:80:0.0.0.0/0'),
(1410, 18618, 'tcp:443:443:0.0.0.0/0'),
(1411, 18619, 'icmp:-1:-1:0.0.0.0/0'),
(1412, 18619, 'tcp:22:22:0.0.0.0/0'),
(1413, 18619, 'tcp:8013:8013:0.0.0.0/0'),
(1414, 18619, 'udp:8014:8014:0.0.0.0/0'),
(1415, 18619, 'udp:161:162:0.0.0.0/0'),
(1416, 18619, 'tcp:80:80:0.0.0.0/0'),
(1417, 18619, 'tcp:443:443:0.0.0.0/0'),
(1418, 18620, 'icmp:-1:-1:0.0.0.0/0'),
(1419, 18620, 'tcp:22:22:0.0.0.0/0'),
(1420, 18620, 'tcp:8013:8013:0.0.0.0/0'),
(1421, 18620, 'udp:8014:8014:0.0.0.0/0'),
(1422, 18620, 'udp:161:162:0.0.0.0/0'),
(1423, 18620, 'tcp:80:80:0.0.0.0/0'),
(1424, 18620, 'tcp:443:443:0.0.0.0/0'),
(1425, 18621, 'icmp:-1:-1:0.0.0.0/0'),
(1426, 18621, 'tcp:22:22:0.0.0.0/0'),
(1427, 18621, 'tcp:8013:8013:0.0.0.0/0'),
(1428, 18621, 'udp:8014:8014:0.0.0.0/0'),
(1429, 18621, 'udp:161:162:0.0.0.0/0'),
(1430, 18621, 'tcp:3306:3306:0.0.0.0/0'),
(1431, 18622, 'icmp:-1:-1:0.0.0.0/0'),
(1432, 18622, 'tcp:22:22:0.0.0.0/0'),
(1433, 18622, 'tcp:8013:8013:0.0.0.0/0'),
(1434, 18622, 'udp:8014:8014:0.0.0.0/0'),
(1435, 18622, 'udp:161:162:0.0.0.0/0'),
(1436, 18622, 'tcp:3306:3306:0.0.0.0/0'),
(1437, 18623, 'icmp:-1:-1:0.0.0.0/0'),
(1438, 18623, 'tcp:22:22:0.0.0.0/0'),
(1439, 18623, 'tcp:8013:8013:0.0.0.0/0'),
(1440, 18623, 'udp:8014:8014:0.0.0.0/0'),
(1441, 18623, 'udp:161:162:0.0.0.0/0'),
(1442, 18623, 'tcp:3306:3306:0.0.0.0/0'),
(1443, 18623, 'tcp:80:80:0.0.0.0/0'),
(1444, 18623, 'tcp:443:443:0.0.0.0/0'),
(1445, 18624, 'icmp:-1:-1:0.0.0.0/0'),
(1446, 18624, 'tcp:22:22:0.0.0.0/0'),
(1447, 18624, 'tcp:8013:8013:0.0.0.0/0'),
(1448, 18624, 'udp:8014:8014:0.0.0.0/0'),
(1449, 18624, 'udp:161:162:0.0.0.0/0'),
(1450, 18624, 'tcp:3306:3306:0.0.0.0/0'),
(1451, 18624, 'tcp:80:80:0.0.0.0/0'),
(1452, 18624, 'tcp:443:443:0.0.0.0/0'),
(11309, 22461, 'icmp:-1:-1:0.0.0.0/0'),
(11310, 22461, 'tcp:22:22:0.0.0.0/0'),
(11311, 22461, 'tcp:8013:8013:0.0.0.0/0'),
(11312, 22461, 'udp:8014:8014:0.0.0.0/0'),
(11313, 22461, 'udp:161:162:0.0.0.0/0'),
(16165, 24151, 'icmp:-1:-1:0.0.0.0/0'),
(16166, 24151, 'tcp:22:22:0.0.0.0/0'),
(16167, 24151, 'tcp:8013:8013:0.0.0.0/0'),
(16168, 24151, 'udp:8014:8014:0.0.0.0/0'),
(16169, 24151, 'udp:161:162:0.0.0.0/0'),
(16170, 24151, 'tcp:3306:3306:0.0.0.0/0'),
(16177, 24154, 'icmp:-1:-1:0.0.0.0/0'),
(16178, 24154, 'tcp:22:22:0.0.0.0/0'),
(16179, 24154, 'tcp:8013:8013:0.0.0.0/0'),
(16180, 24154, 'udp:8014:8014:0.0.0.0/0'),
(16181, 24154, 'udp:161:162:0.0.0.0/0'),
(16182, 24154, 'tcp:3306:3306:0.0.0.0/0'),
(16183, 24156, 'icmp:-1:-1:0.0.0.0/0'),
(16184, 24156, 'tcp:22:22:0.0.0.0/0'),
(16185, 24156, 'tcp:8013:8013:0.0.0.0/0'),
(16186, 24156, 'udp:8014:8014:0.0.0.0/0'),
(16187, 24156, 'udp:161:162:0.0.0.0/0'),
(16188, 24156, 'tcp:3306:3306:0.0.0.0/0'),
(16201, 24159, 'icmp:-1:-1:0.0.0.0/0'),
(16202, 24159, 'tcp:22:22:0.0.0.0/0'),
(16203, 24159, 'tcp:8013:8013:0.0.0.0/0'),
(16204, 24159, 'udp:8014:8014:0.0.0.0/0'),
(16205, 24159, 'udp:161:162:0.0.0.0/0'),
(16206, 24159, 'tcp:3306:3306:0.0.0.0/0'),
(18921, 25052, 'icmp:-1:-1:0.0.0.0/0'),
(18922, 25052, 'tcp:22:22:0.0.0.0/0'),
(18923, 25052, 'tcp:8013:8013:0.0.0.0/0'),
(18924, 25052, 'udp:8014:8014:0.0.0.0/0'),
(18925, 25052, 'udp:161:162:0.0.0.0/0'),
(18933, 25056, 'icmp:-1:-1:0.0.0.0/0'),
(18934, 25056, 'tcp:22:22:0.0.0.0/0'),
(18935, 25056, 'tcp:8013:8013:0.0.0.0/0'),
(18936, 25056, 'udp:8014:8014:0.0.0.0/0'),
(18937, 25056, 'udp:161:162:0.0.0.0/0'),
(19301, 25162, 'icmp:-1:-1:0.0.0.0/0'),
(19302, 25162, 'tcp:22:22:0.0.0.0/0'),
(19303, 25162, 'tcp:8013:8013:0.0.0.0/0'),
(19304, 25162, 'udp:8014:8014:0.0.0.0/0'),
(19305, 25162, 'udp:161:162:0.0.0.0/0'),
(19306, 25163, 'icmp:-1:-1:0.0.0.0/0'),
(19307, 25163, 'tcp:22:22:0.0.0.0/0'),
(19308, 25163, 'tcp:8013:8013:0.0.0.0/0'),
(19309, 25163, 'udp:8014:8014:0.0.0.0/0'),
(19310, 25163, 'udp:161:162:0.0.0.0/0'),
(21869, 25952, 'icmp:-1:-1:0.0.0.0/0'),
(21870, 25952, 'tcp:22:22:0.0.0.0/0'),
(21871, 25952, 'tcp:8013:8013:0.0.0.0/0'),
(21872, 25952, 'udp:8014:8014:0.0.0.0/0'),
(21873, 25952, 'udp:161:162:0.0.0.0/0'),
(21888, 25955, 'icmp:-1:-1:0.0.0.0/0'),
(21889, 25955, 'tcp:22:22:0.0.0.0/0'),
(21890, 25955, 'tcp:8013:8013:0.0.0.0/0'),
(21891, 25955, 'udp:8014:8014:0.0.0.0/0'),
(21892, 25955, 'udp:161:162:0.0.0.0/0'),
(21893, 25957, 'icmp:-1:-1:0.0.0.0/0'),
(21894, 25957, 'tcp:22:22:0.0.0.0/0'),
(21895, 25957, 'tcp:8013:8013:0.0.0.0/0'),
(21896, 25957, 'udp:8014:8014:0.0.0.0/0'),
(21897, 25957, 'udp:161:162:0.0.0.0/0'),
(21898, 25958, 'icmp:-1:-1:0.0.0.0/0'),
(21899, 25958, 'tcp:22:22:0.0.0.0/0'),
(21900, 25958, 'tcp:8013:8013:0.0.0.0/0'),
(21901, 25958, 'udp:8014:8014:0.0.0.0/0'),
(21902, 25958, 'udp:161:162:0.0.0.0/0'),
(23581, 26539, 'icmp:-1:-1:0.0.0.0/0'),
(23582, 26539, 'tcp:22:22:0.0.0.0/0'),
(23583, 26539, 'tcp:8013:8013:0.0.0.0/0'),
(23584, 26539, 'udp:8014:8014:0.0.0.0/0'),
(23585, 26539, 'udp:161:162:0.0.0.0/0'),
(23586, 26539, 'tcp:5432:5432:0.0.0.0/0'),
(23598, 26542, 'icmp:-1:-1:0.0.0.0/0'),
(23599, 26542, 'tcp:22:22:0.0.0.0/0'),
(23600, 26542, 'tcp:8013:8013:0.0.0.0/0'),
(23601, 26542, 'udp:8014:8014:0.0.0.0/0'),
(23602, 26542, 'udp:161:162:0.0.0.0/0'),
(23603, 26542, 'tcp:5432:5432:0.0.0.0/0'),
(23604, 26543, 'icmp:-1:-1:0.0.0.0/0'),
(23605, 26543, 'tcp:22:22:0.0.0.0/0'),
(23606, 26543, 'tcp:8013:8013:0.0.0.0/0'),
(23607, 26543, 'udp:8014:8014:0.0.0.0/0'),
(23608, 26543, 'udp:161:162:0.0.0.0/0'),
(23609, 26543, 'tcp:5432:5432:0.0.0.0/0'),
(23610, 26544, 'icmp:-1:-1:0.0.0.0/0'),
(23611, 26544, 'tcp:22:22:0.0.0.0/0'),
(23612, 26544, 'tcp:8013:8013:0.0.0.0/0'),
(23613, 26544, 'udp:8014:8014:0.0.0.0/0'),
(23614, 26544, 'udp:161:162:0.0.0.0/0'),
(23615, 26544, 'tcp:5432:5432:0.0.0.0/0'),
(26791, 27481, 'icmp:-1:-1:0.0.0.0/0'),
(26792, 27481, 'tcp:22:22:0.0.0.0/0'),
(26793, 27481, 'tcp:8013:8013:0.0.0.0/0'),
(26794, 27481, 'udp:8014:8014:0.0.0.0/0'),
(26795, 27481, 'udp:161:162:0.0.0.0/0'),
(26796, 27481, 'tcp:6379:6379:0.0.0.0/0'),
(26797, 27482, 'icmp:-1:-1:0.0.0.0/0'),
(26798, 27482, 'tcp:22:22:0.0.0.0/0'),
(26799, 27482, 'tcp:8013:8013:0.0.0.0/0'),
(26800, 27482, 'udp:8014:8014:0.0.0.0/0'),
(26801, 27482, 'udp:161:162:0.0.0.0/0'),
(26802, 27482, 'tcp:6379:6379:0.0.0.0/0'),
(26803, 27506, 'icmp:-1:-1:0.0.0.0/0'),
(26804, 27506, 'tcp:22:22:0.0.0.0/0'),
(26805, 27506, 'tcp:8013:8013:0.0.0.0/0'),
(26806, 27506, 'udp:8014:8014:0.0.0.0/0'),
(26807, 27506, 'udp:161:162:0.0.0.0/0'),
(26808, 27506, 'tcp:6379:6379:0.0.0.0/0'),
(26809, 27507, 'icmp:-1:-1:0.0.0.0/0'),
(26810, 27507, 'tcp:22:22:0.0.0.0/0'),
(26811, 27507, 'tcp:8013:8013:0.0.0.0/0'),
(26812, 27507, 'udp:8014:8014:0.0.0.0/0'),
(26813, 27507, 'udp:161:162:0.0.0.0/0'),
(26814, 27507, 'tcp:6379:6379:0.0.0.0/0'),
(38710, 31011, 'icmp:-1:-1:0.0.0.0/0'),
(38711, 31011, 'tcp:22:22:0.0.0.0/0'),
(38712, 31011, 'tcp:8013:8013:0.0.0.0/0'),
(38713, 31011, 'udp:8014:8014:0.0.0.0/0'),
(38714, 31011, 'udp:161:162:0.0.0.0/0'),
(38715, 31011, 'tcp:5672:5672:0.0.0.0/0'),
(38716, 31011, 'tcp:55672:55672:0.0.0.0/0'),
(38732, 31024, 'icmp:-1:-1:0.0.0.0/0'),
(38733, 31024, 'tcp:22:22:0.0.0.0/0'),
(38734, 31024, 'tcp:8013:8013:0.0.0.0/0'),
(38735, 31024, 'udp:8014:8014:0.0.0.0/0'),
(38736, 31024, 'udp:161:162:0.0.0.0/0'),
(38737, 31024, 'tcp:5672:5672:0.0.0.0/0'),
(38738, 31024, 'tcp:55672:55672:0.0.0.0/0'),
(45791, 33205, 'icmp:-1:-1:0.0.0.0/0'),
(45792, 33205, 'tcp:22:22:0.0.0.0/0'),
(45793, 33205, 'tcp:8013:8013:0.0.0.0/0'),
(45794, 33205, 'udp:8014:8014:0.0.0.0/0'),
(45795, 33205, 'udp:161:162:0.0.0.0/0'),
(45796, 33205, 'tcp:27017:27020:0.0.0.0/0'),
(45797, 33205, 'tcp:28018:28018:0.0.0.0/0'),
(45798, 33206, 'icmp:-1:-1:0.0.0.0/0'),
(45799, 33206, 'tcp:22:22:0.0.0.0/0'),
(45800, 33206, 'tcp:8013:8013:0.0.0.0/0'),
(45801, 33206, 'udp:8014:8014:0.0.0.0/0'),
(45802, 33206, 'udp:161:162:0.0.0.0/0'),
(45803, 33206, 'tcp:27017:27020:0.0.0.0/0'),
(45804, 33206, 'tcp:28018:28018:0.0.0.0/0'),
(51772, 35144, 'icmp:-1:-1:0.0.0.0/0'),
(51773, 35144, 'tcp:22:22:0.0.0.0/0'),
(51774, 35144, 'tcp:8013:8013:0.0.0.0/0'),
(51775, 35144, 'udp:8014:8014:0.0.0.0/0'),
(51776, 35144, 'udp:161:162:0.0.0.0/0');


INSERT INTO `role_software` (`id`, `role_id`, `software_name`, `software_version`, `software_key`) VALUES
(61, 18015, 'apache', '2.2.3', 'apache'),
(62, 18015, 'php', '5.1.6', 'php'),
(63, 18016, 'apache', '2.2.3', 'apache'),
(64, 18016, 'php', '5.1.6', 'php'),
(65, 18017, 'nginx', '0.6.39', 'nginx'),
(66, 18018, 'nginx', '0.6.39', 'nginx'),
(245, 18367, 'apache', '2.2.3', 'apache'),
(246, 18367, 'php', '5.1.6', 'php'),
(247, 18367, 'mysql', '5.0.77', 'mysql'),
(248, 18368, 'apache', '2.2.3', 'apache'),
(249, 18368, 'php', '5.1.6', 'php'),
(250, 18368, 'mysql', '5.0.77', 'mysql'),
(377, 18615, 'python 2.6.5', '', 'python 2.6.5'),
(378, 18616, 'python 2.6.5', '', 'python 2.6.5'),
(379, 18617, 'Apache 2.2.14, PHP 5.3.2', '', 'Apache 2.2.14, PHP 5'),
(380, 18618, 'Apache 2.2.14, PHP 5.3.2', '', 'Apache 2.2.14, PHP 5'),
(381, 18619, 'Nginx 0.7.65', '', 'Nginx 0.7.65'),
(382, 18620, 'Nginx 0.7.65', '', 'Nginx 0.7.65'),
(383, 18621, 'MySQL 5.1.41', '', 'MySQL 5.1.41'),
(384, 18622, 'MySQL 5.1.41', '', 'MySQL 5.1.41'),
(385, 18623, 'Apache 2.2.14, PHP 5.3.2, MySQL 5.1.41', '', 'Apache 2.2.14, PHP 5'),
(386, 18624, 'Apache 2.2.14, PHP 5.3.2, MySQL 5.1.41', '', 'Apache 2.2.14, PHP 5'),
(12101, 25952, 'Memcached 1.4.2', '', 'Memcached 1.4.2'),
(12107, 25955, 'Memcached 1.4.2', '', 'Memcached 1.4.2'),
(12112, 25957, 'Memcached 1.4.5', '', 'Memcached 1.4.5'),
(12113, 25958, 'Memcached 1.4.5', '', 'Memcached 1.4.5'),
(13373, 26539, 'PostgreSQL', '9.0.4', 'PostgreSQL'),
(13377, 26542, 'PostgreSQL', '9.0.4', 'PostgreSQL'),
(13378, 26543, 'PostgreSQL', '9.0.2', 'PostgreSQL'),
(13379, 26544, 'PostgreSQL', '9.0.2', 'PostgreSQL'),
(15595, 27481, 'Redis 2.2.11', '', 'Redis 2.2.11'),
(15596, 27482, 'Redis 2.2.5', '', 'Redis 2.2.5'),
(15631, 27506, 'Redis 2.2.11', '', 'Redis 2.2.11'),
(15632, 27507, 'Redis 2.2.5', '', 'Redis 2.2.5'),
(19057, 28917, 'CloudFoundry 0.999', '', 'CloudFoundry 0.999'),
(19060, 28921, 'CloudFoundry 0.999', '', 'CloudFoundry 0.999'),
(19061, 28922, 'CloudFoundry 0.999', '', 'CloudFoundry 0.999'),
(19063, 28925, 'CloudFoundry 0.999', '', 'CloudFoundry 0.999'),
(23856, 31011, '- RabbitMQ 2.7.0', '', '- RabbitMQ 2.7.0'),
(23857, 31011, '- Erlang 13.b.3', '', '- Erlang 13.b.3'),
(23878, 31024, '- RabbitMQ 2.7.0', '', '- RabbitMQ 2.7.0'),
(23879, 31024, '- Erlang 13.b.3', '', '- Erlang 13.b.3'),
(29677, 33205, 'MongoDB 2.0.x', '', 'MongoDB 2.0.x'),
(29678, 33206, 'MongoDB 2.0.x', '', 'MongoDB 2.0.x');

INSERT INTO `role_tags` (`id`, `role_id`, `tag`) VALUES
(917, 16161, 'ec2.ebs'),
(6564, 16162, 'ec2.ebs'),
(861, 16163, 'ec2.ebs'),
(862, 16164, 'ec2.ebs'),
(9121, 16374, 'ec2.ebs'),
(9122, 16375, 'ec2.ebs'),
(9647, 18015, 'ec2.ebs'),
(9795, 18016, 'ec2.ebs'),
(9643, 18017, 'ec2.ebs'),
(9636, 18018, 'ec2.ebs'),
(9807, 18367, 'ec2.ebs'),
(9812, 18368, 'ec2.ebs'),
(11458, 18615, 'ec2.ebs'),
(9180, 18616, 'ec2.ebs'),
(9163, 18617, 'ec2.ebs'),
(9185, 18618, 'ec2.ebs'),
(9161, 18619, 'ec2.ebs'),
(9183, 18620, 'ec2.ebs'),
(9160, 18621, 'ec2.ebs'),
(9187, 18622, 'ec2.ebs'),
(9171, 18623, 'ec2.ebs'),
(9189, 18624, 'ec2.ebs'),
(1589, 22461, 'ec2.ebs'),
(1590, 22461, 'ec2.hvm'),
(9482, 24151, 'ec2.ebs'),
(9173, 24154, 'ec2.ebs'),
(9810, 24156, 'ec2.ebs'),
(9803, 24159, 'ec2.ebs'),
(2612, 25052, 'ec2.ebs'),
(2615, 25056, 'ec2.ebs'),
(2684, 25162, 'ec2.ebs'),
(2685, 25163, 'ec2.ebs'),
(9190, 25952, 'ec2.ebs'),
(9169, 25955, 'ec2.ebs'),
(11464, 25957, 'ec2.ebs'),
(9804, 25958, 'ec2.ebs'),
(9175, 26539, 'ec2.ebs'),
(9192, 26542, 'ec2.ebs'),
(9805, 26543, 'ec2.ebs'),
(9635, 26544, 'ec2.ebs'),
(9251, 27481, 'ec2.ebs'),
(9639, 27482, 'ec2.ebs'),
(9255, 27506, 'ec2.ebs'),
(9796, 27507, 'ec2.ebs'),
(5607, 28917, 'ec2.ebs'),
(5609, 28921, 'ec2.ebs'),
(5610, 28922, 'ec2.ebs'),
(5612, 28925, 'ec2.ebs'),
(9683, 31011, 'ec2.ebs'),
(9682, 31024, 'ec2.ebs'),
(9681, 33205, 'ec2.ebs'),
(9641, 33206, 'ec2.ebs'),
(10909, 35144, 'ec2.ebs'),
(10910, 35144, 'ec2.hvm');


INSERT INTO `scripts` (`id`, `name`, `description`, `origin`, `dtadded`, `issync`, `clientid`, `approval_state`) VALUES
(1, 'SVN update', 'Update a working copy from SVN repository', 'Shared', '2008-10-23 19:41:07', 1, 0, 'Approved'),
(2, 'SVN export', 'Export SVN repository to local directory', 'Shared', '2008-10-22 15:30:46', 0, 0, 'Approved'),
(3, 'SVN checkout', 'Checkout from SVN repository', 'Shared', '2008-10-23 19:42:21', 1, 0, 'Approved'),
(4, 'Git clone', 'Clone a git repository', 'Shared', '2008-10-24 15:04:41', 1, 0, 'Approved'),
(39, 'Do not fetch MySQL snapshot', 'Create a flag that makes mysql master skip snapshot fetch upon init', 'Shared', '2008-12-13 08:31:20', 0, 0, 'Approved'),
(50, 'Set terminal prompt', 'Beautify shell prompt', 'Shared', '2008-12-17 10:24:46', 0, 0, 'Approved'),
(51, 'rebuild /etc/aws/hosts', 'Rebuild /etc/aws/hosts', 'Shared', '2008-12-19 05:47:25', 0, 0, 'Approved'),
(70, 'Enable essential Apache Modules', 'enable essential Apache modules', 'Shared', '2009-01-10 16:03:11', 0, 0, 'Approved'),
(71, 'Generate /etc/hosts for local resolve', 'Generate /etc/hosts from /etc/aws/hosts', 'Shared', '2009-01-12 16:34:26', 0, 0, 'Approved'),
(80, 'nginx backend reload', 'Reload nginx upstreams list', 'Shared', '2009-01-16 08:51:16', 0, 0, 'Approved'),
(90, 'Bundle MySQL data', 'Bundle MySQL data, split it into chunks and upload to S3', 'Shared', '2009-01-21 09:47:26', 0, 0, 'Approved'),
(1844, 'Simulate load', 'Simulate high Load Average with cpuburn', 'Shared', '2011-04-26 20:38:32', 0, 0, 'Approved'),
(1845, 'Stop load simulation', 'Stop any running cpuburn process', 'Shared', '2011-04-26 20:40:24', 0, 0, 'Approved'),
(2102, 'Scalarizr update', 'Updates scalarizr to the latest version', 'Shared', '2011-06-01 05:42:42', 0, 0, 'Approved'),
(2720, 'Strict iptables', 'Allow connections to this instance only from other farm instances, Scalr.net IP pool and whitelisted IPs (separated with space)', 'Shared', '2011-09-16 05:59:02', 0, 0, 'Approved');

INSERT INTO `script_revisions` (`id`, `scriptid`, `revision`, `script`, `dtcreated`, `approval_state`, `variables`) VALUES
(1, 1, 1, '#!/bin/bash\n\nSVN_PATH="/usr/bin/svn"\nSVN_USER="%svn_user%"\nSVN_PASS="%svn_password%"\nSVN_REV="%svn_revision%"\nSVN_UP_DIR="%svn_co_dir%"\n\n\nif [ -z "$SVN_UP_DIR" ]; then\n        echo "Working copy directory was not specified." >&2\n\n        exit 1\nfi\n\nif [ -z "$SVN_PATH" ] || [ ! -x "$SVN_PATH" ]; then\n        echo "SVN binary is not executable" >&2\n\n        exit 1\nfi\n\n[ "$SVN_USER" ] && SVN_USER_STR="--username $SVN_USER"\n[ "$SVN_PASS" ] && SVN_PASS_STR="--password $SVN_PASS"\n[ "$SVN_REV" ]  && SVN_REV_STR="-r $SVN_REV"\n\n\nif $SVN_PATH --non-interactive --no-auth-cache $SVN_USER_STR $SVN_PASS_STR info "$SVN_UP_DIR" >/dev/null 2>&1; then\n        $SVN_PATH --non-interactive $SVN_USER_STR $SVN_PASS_STR $SVN_REV_STR update "$SVN_UP_DIR"\nfi\n', '2008-12-02 07:03:36', 'Approved', 'a:4:{s:8:"svn_user";s:8:"Svn User";s:12:"svn_password";s:12:"Svn Password";s:12:"svn_revision";s:12:"Svn Revision";s:10:"svn_co_dir";s:10:"Svn Co Dir";}'),
(2, 2, 1, '#!/bin/bash\n\nSVN_PATH="/usr/bin/svn"\nSVN_REPO_URL="%svn_repo_url%"\nSVN_USER="%svn_user%"\nSVN_PASS="%svn_password%"\nSVN_REV="%svn_revision%"\nSVN_CO_DIR="%svn_co_dir%"\n\n\nif [ -z "$SVN_REPO_URL" ]; then \n        echo "SVN repository URL was not specified." >&2\n\n        exit 1\nfi\n\nif [ -z "$SVN_CO_DIR" ]; then\n        echo "Checkout directory was not specified." >&2\n\n        exit 1\nfi\n\nif [ -z "$SVN_PATH" ] || [ ! -x "$SVN_PATH" ]; then\n        echo "SVN binary is not executable" >&2\n\n        exit 1\nfi\n\n[ "$SVN_USER" ] && SVN_USER_STR="--username $SVN_USER"\n[ "$SVN_PASS" ] && SVN_PASS_STR="--password $SVN_PASS"\n[ "$SVN_REV" ]  && SVN_REV_STR="-r $SVN_REV"\n\n[ -d "$SVN_CO_DIR" ] || mkdir -p $SVN_CO_DIR\n\n$SVN_PATH --force --non-interactive $SVN_USER_STR $SVN_PASS_STR export $SVN_REV_STR "$SVN_REPO_URL" "$SVN_CO_DIR"\n', '2008-12-02 07:03:36', 'Approved', 'a:5:{s:12:"svn_repo_url";s:12:"Svn Repo Url";s:8:"svn_user";s:8:"Svn User";s:12:"svn_password";s:12:"Svn Password";s:12:"svn_revision";s:12:"Svn Revision";s:10:"svn_co_dir";s:10:"Svn Co Dir";}'),
(3, 3, 1, '#!/bin/bash\n\nSVN_PATH="/usr/bin/svn"\nSVN_REPO_URL="%svn_repo_url%"\nSVN_USER="%svn_user%"\nSVN_PASS="%svn_password%"\nSVN_REV="%svn_revision%"\nSVN_CO_DIR="%svn_co_dir%"\n\n\nif [ -z "$SVN_REPO_URL" ]; then\n        echo "SVN repository URL was not specified." >&2\n\n        exit 1\nfi\n\nif [ -z "$SVN_CO_DIR" ]; then\n        echo "Checkout directory was not specified." >&2\n\n        exit 1\nfi\n\nif [ -z "$SVN_PATH" ] || [ ! -x "$SVN_PATH" ]; then\n        echo "SVN binary is not executable" >&2\n\n        exit 1\nfi\n\n[ "$SVN_USER" ] && SVN_USER_STR="--username $SVN_USER"\n[ "$SVN_PASS" ] && SVN_PASS_STR="--password $SVN_PASS"\n[ "$SVN_REV" ]  && SVN_REV_STR="-r $SVN_REV"\n\n[ -d "$SVN_CO_DIR" ] || mkdir -p $SVN_CO_DIR\n\n$SVN_PATH --no-auth-cache --non-interactive $SVN_USER_STR $SVN_PASS_STR checkout $SVN_REV_STR "$SVN_REPO_URL" "$SVN_CO_DIR"\n', '2008-12-02 07:03:36', 'Approved', 'a:5:{s:12:"svn_repo_url";s:12:"Svn Repo Url";s:8:"svn_user";s:8:"Svn User";s:12:"svn_password";s:12:"Svn Password";s:12:"svn_revision";s:12:"Svn Revision";s:10:"svn_co_dir";s:10:"Svn Co Dir";}'),
(4, 4, 1, '#!/bin/bash\n\nGIT_PATH="/usr/bin/git"\nGIT_REPO_URL="%git_repo_url%"\nGIT_CL_DIR="%git_co_dir%"\n\n\nif [ -z "$GIT_REPO_URL" ]; then\n        echo "GIT repository URL was not specified." >&2\n\n        exit 1\nfi\n\nif [ -z "$GIT_CL_DIR" ]; then\n        echo "Destination directory was not specified." >&2\n\n        exit 1\nfi\n\nif [ ! -x "$GIT_PATH" ]; then\n        /usr/bin/apt-get -q -y install git-core\n\n        if [ ! -x "$GIT_PATH" ]; then\n                echo "GIT binary is not executable" >&2\n\n                exit 1\n        fi\nfi\n\n$GIT_PATH clone "$GIT_REPO_URL" "$GIT_CL_DIR"\n', '2008-12-02 07:03:36', 'Approved', 'a:2:{s:12:"git_repo_url";s:12:"Git Repo Url";s:10:"git_co_dir";s:10:"Git Co Dir";}'),
(41, 39, 1, '#!/bin/sh\n\ntouch /etc/aws/mysql_master_do_not_fetch_snapshot\n', '2008-12-13 08:31:20', 'Approved', 'a:0:{}'),
(56, 50, 1, '#!/bin/bash\n\nPROMPT_FORMAT="%prompt_format%"\n\nif [ -z "$PROMPT_FORMAT" ]; then\n        PROMPT_FORMAT="\\u@@ROLE@[@IP@]:\\w\\\\\\$ "\nfi\n\nEXT_IP=`curl -s http://169.254.169.254/latest/meta-data/public-ipv4`\n\nif [ -f /etc/aws/host.conf ]; then\n        . /etc/aws/host.conf\n\n        if [ "$USER_ROLE" ]; then\n                ROLE="$USER_ROLE"\n        elif [ "$SERVER_ROLE" ]; then\n                ROLE="$SERVER_ROLE"\n        else\n                ROLE="unknown"\n        fi\n\n        if [ "$MYSQL_ROLE" ]; then\n                ROLE="$ROLE-$MYSQL_ROLE"\n        fi\nelse\n        ROLE="unknown"\nfi\n\nPROMPT_FORMAT=`echo "$PROMPT_FORMAT" | sed -e "s/@IP@/$EXT_IP/g" -e "s/@ROLE@/$ROLE/g" -e ''s/\\\\\\$/\\\\\\\\\\$/g''`\n\nif [ "$PROMPT_FORMAT" ]; then\n        echo "PS1=''$PROMPT_FORMAT''" >> /root/.bashrc\nfi\n', '2008-12-17 10:24:46', 'Approved', 'a:1:{s:13:"prompt_format";s:13:"Prompt Format";}'),
(57, 51, 1, '#!/bin/bash\n\nif [ -f /usr/local/aws/lib/lib.sh ]; then\n        . /usr/local/aws/lib/lib.sh\nelse\n        echo "/usr/local/aws/lib/lib.sh does not exist. Exiting."\n        exit\nfi\n\nif [ -d /etc/aws/hosts ]; then\n        rm -r /etc/aws/hosts\n\n        mkdir -p /etc/aws/hosts\nfi\n\n# Fill /etc/aws/hosts with farm hosts IPs\n\nMASTER_IP=`ec2_get_option_val db.mysql.master.ip`\n\nMYSQL_CUSTOM_ROLE=""\n\nfor HOST_ROLE in `ec2_listroles`; do\n        [ -d /etc/aws/hosts/$HOST_ROLE ] || mkdir -p /etc/aws/hosts/$HOST_ROLE\n\n        for HOST_IP in `ec2_listhosts $HOST_ROLE`; do\n                [ -f /etc/aws/hosts/$HOST_ROLE/$HOST_IP ] || touch /etc/aws/hosts/$HOST_ROLE/$HOST_IP\n\n                if [ "$HOST_ROLE" = "mysql" ]; then\n                        if [ "$MASTER_IP" != "$HOST_IP" ]; then\n                                [ -d /etc/aws/hosts/mysql-slave ]  || mkdir -p /etc/aws/hosts/mysql-slave\n                                touch /etc/aws/hosts/mysql-slave/$HOST_IP\n                        else\n                                [ -d /etc/aws/hosts/mysql-master ] || mkdir -p /etc/aws/hosts/mysql-master\n                                touch /etc/aws/hosts/mysql-master/$HOST_IP\n                        fi\n                fi\n\n                if [ "$MASTER_IP" ] && [ "$HOST_IP" == "$MASTER_IP" ] && [ "$HOST_ROLE" != "mysql" ]; then\n                        MYSQL_CUSTOM_ROLE="$HOST_ROLE"\n                fi\n        done\ndone\n\nif [ "$MYSQL_CUSTOM_ROLE" ]; then\n        for HOST_IP in `ec2_listhosts $MYSQL_CUSTOM_ROLE`; do\n                if [ "$HOST_IP" != "$MASTER_IP" ]; then\n                        [ -d /etc/aws/hosts/$MYSQL_CUSTOM_ROLE-slave  ] || mkdir -p /etc/aws/hosts/$MYSQL_CUSTOM_ROLE-slave\n                        touch /etc/aws/hosts/$MYSQL_CUSTOM_ROLE-slave/$HOST_IP\n                else\n                        [ -d /etc/aws/hosts/$MYSQL_CUSTOM_ROLE-master ] || mkdir -p /etc/aws/hosts/$MYSQL_CUSTOM_ROLE-master\n                        touch /etc/aws/hosts/$MYSQL_CUSTOM_ROLE-master/$HOST_IP\n                fi\n        done\nfi\n', '2008-12-19 05:47:25', 'Approved', 'a:0:{}'),
(82, 70, 1, '#!/bin/bash\necho "Enabling mod_rewrite..."\ntest -f "/etc/apache2/mods-available/rewrite.load" && ln -s /etc/apache2/mods-available/rewrite.load /etc/apache2/mods-enabled/rewrite.load\n\necho "Restarting Apache..."\n/etc/init.d/apache2 restart\n\n\n', '2009-01-10 16:03:11', 'Approved', 'a:0:{}'),
(83, 71, 1, '#!/bin/bash\n\nif [ -s /etc/hosts ]; then\n        mv /etc/hosts /etc/hosts.save\nfi\n\ncat << EOLH > /etc/hosts\n127.0.0.1       localhost\n127.0.1.1       ubuntu\n\n# The following lines are desirable for IPv6 capable hosts\n::1     ip6-localhost ip6-loopback\nfe00::0 ip6-localnet\nff00::0 ip6-mcastprefix\nff02::1 ip6-allnodes\nff02::2 ip6-allrouters\nff02::3 ip6-allhosts\n\nEOLH\n\nfor ROLEDIR in /etc/aws/hosts/*; do\n        ROLE=`basename $ROLEDIR`\n\n        [ -z "$ROLE" ] && continue\n\n        for IPFILE in /etc/aws/hosts/$ROLE/*; do\n                IP=`basename $IPFILE`\n\n                [ -z "$IP" ] && continue\n\n                echo "$IP $ROLE int-$ROLE" >> /etc/hosts\n        done\ndone\n\n\n\n', '2009-01-12 16:34:26', 'Approved', 'a:0:{}'),
(98, 80, 1, '#!/bin/bash\n\nif [ -r /usr/local/aws/lib/lib.sh ]; then\n        . /usr/local/aws/lib/lib.sh\nelse\n        echo "Error: /usr/local/aws/lib/lib.sh does not exist."\n\n        exit 1\nfi\n\nif [ -r /usr/local/aws/lib/nginxlib.sh ]; then\n        . /usr/local/aws/lib/nginxlib.sh\nelse\n        echo "Error: /usr/local/aws/lib/nginxlib.sh does not exist."\n\n        exit 1\nfi\n\nif [ -r /etc/aws/host.conf ]; then\n        . /etc/aws/host.conf\nelse\n        echo "Error: /etc/aws/host.conf does not exist."\n\n        exit 1\nfi\n\nif [ "$SERVER_ROLE" ] && [ "$SERVER_ROLE" = "www" ]; then\n        if type nginx_upstream_reload > /dev/null 2>/dev/null; then\n                nginx_upstream_reload\n        elif type reload_nginx_upstream > /dev/null 2>/dev/null; then\n                reload_nginx_upstream\n        else\n                echo "Error: nginx backend reconfiguration routine not defined."\n\n                exit 1\n        fi\nelse\n        echo "Error: not a www instance."\n\n        exit 1\nfi\n', '2009-01-16 08:51:16', 'Approved', 'a:0:{}'),
(110, 90, 1, '#!/bin/bash\n\ntest -r /etc/aws/host.conf && . /etc/aws/host.conf\ntest -r /etc/aws/farmconfig && . /etc/aws/farmconfig\ntest -r /etc/aws/authconfig && . /etc/aws/authconfig\n\ntest -r /usr/local/aws/lib/lib.sh && . /usr/local/aws/lib/lib.sh\ntest -r /usr/local/aws/lib/mysqllib.sh && . /usr/local/aws/lib/mysqllib.sh\ntest -r /usr/local/aws/lib/s3lib.sh && . /usr/local/aws/lib/s3lib.sh\n\nMYSQLD_PID_FILE=`mysqld_get_param pid-file`\nMYSQLD_DATA_DIR=`mysqld_get_param datadir`\nBUCKET_NAME_MYSQL="$BUCKET_NAME/farm-mysql"\nMYSQL_CMD="/usr/bin/mysql --defaults-file=/etc/mysql/debian.cnf --batch"\nDATA_BUNDLE=""\n\nif lvm_enabled; then\n        . /etc/scalr_lvm.conf\nfi\n\n# Trying to lock\nif ! LPID=`mysql_data_bundle_lock`; then\n        loga "MySQL data bundle already in progress (PID: $LPID)."\n\n        exit 1\nfi\n\nlogq "Bundling MySQL data."\n\nif lvm_enabled; then\n        SNAP_DESTDIR="$LV_MISC_MPT/snapshot"\n        SNAP_SRCDIR="$LV_SNAP_MPT"\nelse\n        SNAP_DESTDIR=`mktemp -d -p /mnt`\n        SNAP_SRCDIR="/mnt/mysql-data-snapshot"\nfi\n\n[ -d $SNAP_DESTDIR ] || mkdir -p $SNAP_DESTDIR\n\nlogq "Locking tables and creating data snapshot."\n\n$MYSQL_CMD -e "FLUSH TABLES WITH READ LOCK; SYSTEM /usr/local/aws/bin/mysql-data-bundle.sh; UNLOCK TABLES"\n\nif [ -f $SNAP_SRCDIR/master-snap.conf ]; then\n        cp $SNAP_SRCDIR/master-snap.conf /etc/mysql/\n\n        if [ -r /etc/mysql/master-snap.conf ]; then\n                . /etc/mysql/master-snap.conf\n\n                if [ "$STATUS_USER" ] && [ "$STATUS_PASS" ]; then\n                        $MYSQL_CMD -e "GRANT REPLICATION CLIENT ON *.* TO ''$STATUS_USER''@''%'' identified by ''$STATUS_PASS''"\n                fi\n        fi\nelse\n        loga "Snapshot creation failed!"\n\n        if lvm_enabled; then\n                umount $LV_SNAP_MPT || echo -n\n                /sbin/lvremove -f /dev/$VG_NAME/$LV_SNAP_NAME >/dev/null 2>&1 || echo -n\n        fi\n\n        mysql_data_bundle_unlock\n\n        exit 1\nfi\n\n# InnoDB recovery\nlogq "Starting InnoDB recovery on the snapshot."\n\nif mysql_innodb_recover $SNAP_SRCDIR/mysql; then\n        logq "InnoDB recovery performed successfully."\nelse\n        loga "Failed to perform InnoDB recovery, snapshot corrupt!"\nfi\n\nlogq "Creating archive and splitting it into chunks."\n\n#\n# split snapshot into 150MB chunks (S3 allows files of up to 5G to be stored)\n#\ntar -cf - -C $SNAP_SRCDIR . | split -a 3 -b 150M - $SNAP_DESTDIR/mysql-snapshot.tar.\n\n# If RC!=0 or no files have been created\nif [ "$?" != "0" ] || ! ls $SNAP_DESTDIR/mysql-snapshot.tar.* >/dev/null 2>&1; then\n        loga "Failed to create snapshot."\n\n        mysql_data_bundle_unlock\n\n        exit 1\nfi\n\n[ -s $SNAP_DESTDIR/mysql-snapshot.index ] && rm $SNAP_DESTDIR/mysql-snapshot.index\n\n# Count snapshot chunks and their size\n\nSNAP_PARTS=0\nSNAP_SIZE=0\n\nfor CUR_VOLUME in $SNAP_DESTDIR/mysql-snapshot.tar.*; do\n        [ -r "$CUR_VOLUME" ] || continue\n\n        CHUNK_SIZE=`du -sm "$CUR_VOLUME" | awk ''{ print $1 }''`\n\n        SNAP_PARTS=$[ $SNAP_PARTS + 1 ]\n        SNAP_SIZE=$[ $SNAP_SIZE + $CHUNK_SIZE ]\n\n        echo "$CUR_VOLUME" >> $SNAP_DESTDIR/mysql-snapshot.index\ndone\n\nlogq "Snapshot created: $SNAP_PARTS chunks, total size: $SNAP_SIZE MB."\n\n[ -r $SNAP_DESTDIR/mysql-snapshot.index ] && DATA_BUNDLE="$SNAP_DESTDIR/mysql-snapshot.index"\n\nif lvm_enabled; then\n        logq "Unmounting LVM snapshot."\n\n        umount $LV_SNAP_MPT\n\n        /sbin/lvremove -f /dev/$VG_NAME/$LV_SNAP_NAME >/dev/null 2>&1\nelse\n        rm -r $SNAP_SRCDIR\nfi\n\nif [ -z "$DATA_BUNDLE" ] || [ ! -s "$DATA_BUNDLE" ]; then\n        loga "Failed to bundle MySQL data."\n        mysql_data_bundle_unlock\n\n        exit 1\nelse\n        log "Successfully bundled MySQL data to ''$DATA_BUNDLE''."\nfi\n\n# And upload it to S3\n\nif mysql_upload_bundle "$DATA_BUNDLE" "s3://$BUCKET_NAME_MYSQL"; then\n        SNAP_FNAME="`basename "$DATA_BUNDLE"`"\n        ec2_submit_event mysqlBckComplete "operation:bundle;snapurl:$BUCKET_NAME_MYSQL/$SNAP_FNAME"\n        log "Successfully uploaded MySQL data bundle to ''s3://$BUCKET_NAME_MYSQL/$SNAP_FNAME''."\n\n        mysql_delete_bundle "$DATA_BUNDLE"\n        mysql_data_bundle_unlock\nelse\n        loga "Failed to upload MySQL data bundle to S3."\n        mysql_data_bundle_unlock\n\n        exit 1\nfi\n', '2009-01-21 09:47:26', 'Approved', 'a:0:{}'),
(971, 51, 2, '#!/bin/bash\n\nif [ -f /usr/local/aws/lib/lib.sh ]; then\n        . /usr/local/aws/lib/lib.sh\nelse\n        echo "/usr/local/aws/lib/lib.sh does not exist. Exiting."\n        exit\nfi\n\nif [ -d /etc/aws/hosts ]; then\n        rm -r /etc/aws/hosts\n\n        mkdir -p /etc/aws/hosts\nfi\n\n# Fill /etc/aws/hosts with farm hosts IPs\n\nTMP_DIR=`mktemp -d`\nXMLSTARLET_PATH="/usr/bin/xmlstarlet"\n\nif [ ! -x $XMLSTARLET_PATH ]; then\n        loga "Cannot build /etc/aws/hosts: $XMLSTARLET_PATH not found."\n\n        exit 1\nfi\n\nif ! ec2_get_option_query_env list-roles > $TMP_DIR/roles.xml; then\n        loga "Cannot rertrieve farm hosts list from Scalr."\n\n        rm -r $TMP_DIR\nfi\n\nif [ ! -s $TMP_DIR/roles.xml ]; then\n        # logq "Farm hosts list empty."\n\n        exit 0\nfi\n\nif ! ROLES_COUNT=`$XMLSTARLET_PATH sel -t -v "count(/response/roles/role)" $TMP_DIR/roles.xml`; then\n        loga "Could not parse $TMP_DIR/roles.xml."\n\n        exit 1\nfi\n\nif [ -z "$ROLES_COUNT" ] || [ $ROLES_COUNT -lt 1 ]; then\n        # logq "Farm hosts list empty."\n\n        exit 0\nfi\n\nfor ROLE_ID in `seq 1 $ROLES_COUNT`; do\n        unset ROLE_NAME ROLE_BEHAV\n\n        ROLE_NAME=`$XMLSTARLET_PATH sel -t -v "/response/roles/role[$ROLE_ID]/@name" $TMP_DIR/roles.xml`\n        ROLE_BEHAV=`$XMLSTARLET_PATH sel -t -v "/response/roles/role[$ROLE_ID]/@behaviour" $TMP_DIR/roles.xml`\n\n        if [ -z "$ROLE_NAME" ] && [ -z "$ROLE_BEHAV" ]; then\n                continue\n        fi\n\n        HOSTS_COUNT=`$XMLSTARLET_PATH sel -t -v "count(/response/roles/role[$ROLE_ID]/hosts/host)" $TMP_DIR/roles.xml`\n\n        if [ -z "$HOSTS_COUNT" ] || [ $HOSTS_COUNT -lt 1 ]; then\n                continue\n        fi\n\n        for HOST_ID in `seq 1 $HOSTS_COUNT`; do\n                HOST_INT_IP=`$XMLSTARLET_PATH sel -t -v "/response/roles/role[$ROLE_ID]/hosts/host[$HOST_ID]/@internal-ip" $TMP_DIR/roles.xml`\n                HOST_REPL_MASTER=`$XMLSTARLET_PATH sel -t -v "/response/roles/role[$ROLE_ID]/hosts/host[$HOST_ID]/@replication-master" $TMP_DIR/roles.xml`\n\n                if [ "$HOST_INT_IP" ] && [ "$ROLE_NAME" ]; then\n                        [ -d /etc/aws/hosts/$ROLE_NAME ] || mkdir -p /etc/aws/hosts/$ROLE_NAME\n\n                        touch /etc/aws/hosts/$ROLE_NAME/$HOST_INT_IP\n                fi\n\n                if [ "$HOST_INT_IP" ] && [ "$ROLE_BEHAV" ]; then\n                        [ -d /etc/aws/hosts/$ROLE_BEHAV ] || mkdir -p /etc/aws/hosts/$ROLE_BEHAV\n\n                        touch /etc/aws/hosts/$ROLE_BEHAV/$HOST_INT_IP\n                fi\n\n                if [ "$HOST_REPL_MASTER" ] && [ $HOST_REPL_MASTER -eq 0 ]; then\n                        [ -d /etc/aws/hosts/$ROLE_NAME-slave ] || mkdir -p /etc/aws/hosts/$ROLE_NAME-slave\n                        [ -d /etc/aws/hosts/$ROLE_BEHAV-slave ] || mkdir -p /etc/aws/hosts/$ROLE_BEHAV-slave\n\n                        touch /etc/aws/hosts/$ROLE_NAME-slave/$HOST_INT_IP\n                        touch /etc/aws/hosts/$ROLE_BEHAV-slave/$HOST_INT_IP\n                fi\n\nif [ "$HOST_REPL_MASTER" ] && [ $HOST_REPL_MASTER -eq 1 ]; then\n                        [ -d /etc/aws/hosts/$ROLE_NAME-master ] || mkdir -p /etc/aws/hosts/$ROLE_NAME-master\n                        [ -d /etc/aws/hosts/$ROLE_BEHAV-master ] || mkdir -p /etc/aws/hosts/$ROLE_BEHAV-master\n\n                        touch /etc/aws/hosts/$ROLE_NAME-master/$HOST_INT_IP\n                        touch /etc/aws/hosts/$ROLE_BEHAV-master/$HOST_INT_IP\n                fi\n        done\ndone\n\nif [ -f $TMP_DIR/roles.xml ]; then\n        rm $TMP_DIR/roles.xml\nfi\n\nif [ -d $TMP_DIR ]; then\n        rmdir $TMP_DIR\nfi\n', '2010-01-13 13:02:14', 'Approved', 'a:0:{}'),
(2780, 1844, 1, '#!/bin/bash\n\nif ! dpkg -s cpuburn >/dev/null 2>&1; then\n    apt-get install -y -y -q cpuburn\nfi\n\nif [ ! -x /usr/bin/burnP6 ]; then\n    echo "cpuburn cannot be installed on this instance." >&2\n\n    exit\nfi\n\n/usr/bin/burnP6 &\n/usr/bin/burnMMX &\n/usr/bin/burnP5 &\n/usr/bin/burnBX &\n/usr/bin/burnK7 &\n/usr/bin/burnK6 &\n', '2011-04-26 20:38:32', 'Approved', 'a:0:{}'),
(2781, 1845, 1, '#!/bin/bash\n\nkillall -9 burnP6 >/dev/null 2>&1\nkillall -9 burnMMX >/dev/null 2>&1\nkillall -9 burnP5 >/dev/null 2>&1\nkillall -9 burnBX >/dev/null 2>&1\nkillall -9 burnK7 >/dev/null 2>&1\nkillall -9 burnK6 >/dev/null 2>&1\n', '2011-04-26 20:40:24', 'Approved', 'a:0:{}'),
(3070, 2102, 1, '#!/bin/bash\n\nset -x\n\nif [ -f /usr/bin/yum ]; then\n    yum -y update scalarizr || :\n    yum -y update scalarizr-ec2 || :\n    yum -y update scalarizr-rackspace || :\nelse\n    apt-get update\n    export DEBIAN_FRONTEND=noninteractive\n    export DEBIAN_PRIORITY=critical\n\n    version=$(scalarizr -v 2>/dev/null | awk ''{ print $2 }'')\n    if dpkg --compare-versions "$version" lt ''0.7.0''; then\n        apt-get -y install scalarizr\n    else\n         platform=$(cat /etc/scalr/public.d/config.ini | grep ''platform\\s*='' | cut -d ''='' -f 2 | sed -e ''s/^\\s*//'' | sed -e ''s/\\s*$//'')\n         apt-get install -y scalarizr scalarizr-${platform}\n    fi\nfi', '2011-06-01 05:42:42', 'Approved', 'a:0:{}'),
(4140, 2720, 1, '#!/bin/bash\n\nIPTABLES="/sbin/iptables"\n# Whitelisted IPs, separated by space\nIP_WHITELIST="%ip_whitelist%"\n\nSCALR_IPS=""\nFARM_IPS=""\n\nif [ ! -x $IPTABLES ]; then\n	echo "$IPTABLES not found." >&2\n\n	exit 1\nfi\n\nif [ -d /etc/scalr/private.d/hosts ]; then\n	DIRNAME="/etc/scalr/private.d/hosts"\nelif [ -d /etc/aws/hosts ]; then\n	DIRNAME="/etc/aws/hosts"\nelse\n	echo "Can''t find farm instances IP addresses in /etc/scalr/private.d/hosts or /etc/aws/hosts" >&2\n	echo "Not modifying firewall rules" >&2\n\n	exit 1\nfi\n\nfor FARM_IP in `find $DIRNAME -type f`; do\n	FARM_IP=`basename $FARM_IP`\n	FARM_IPS="$FARM_IPS $FARM_IP"\ndone\n\nif [ -z "$FARM_IPS" ]; then\n	echo "Can''t find farm instances IP addresses." >&2\n	echo "Not modifying firewall rules" >&2\n\n	exit 1\nfi\n\nif ! SCALR_IPS=`dig +short ip-pool.scalr.net` || [ -z "$SCALR_IPS" ]; then\n	echo "Can''t fetch Scalr IP addresses list. Check whether ''dig'' utility is installed." >&2\n	echo "Not modifying firewall rules" >&2\n\n	exit 1\nfi\n\n$IPTABLES -P INPUT ACCEPT\n\nif ! $IPTABLES -F FARM 2>/dev/null; then\n	$IPTABLES -N FARM\nfi\n\nfor IP2WHITELIST in $FARM_IPS; do\n	$IPTABLES -A FARM -p all -s $IP2WHITELIST -j ACCEPT\ndone\n\nif ! $IPTABLES -F SCALR 2>/dev/null; then\n	$IPTABLES -N SCALR\nfi\n\nfor IP2WHITELIST in $SCALR_IPS; do\n	$IPTABLES -A SCALR -p all -s $IP2WHITELIST -j ACCEPT\ndone\n\nif [ "$IP_WHITELIST" ]; then\n	for IP2WHITELIST in $IP_WHITELIST; do\n		$IPTABLES -A SCALR -p all -s $IP2WHITELIST -j ACCEPT \n	done\nfi\n\n$IPTABLES -D INPUT -i lo -j ACCEPT\n$IPTABLES -D INPUT -p ALL -m state --state ESTABLISHED,RELATED -j ACCEPT\n$IPTABLES -D INPUT -p ALL -j FARM\n$IPTABLES -D INPUT -p ALL -j SCALR\n\n$IPTABLES -I INPUT 1 -i lo -j ACCEPT\n$IPTABLES -I INPUT 2 -p ALL -m state --state ESTABLISHED,RELATED -j ACCEPT\n$IPTABLES -I INPUT 3 -p ALL -j FARM\n$IPTABLES -I INPUT 4 -p ALL -j SCALR\n$IPTABLES -P INPUT DROP', '2011-09-16 05:59:02', 'Approved', 'a:1:{s:12:"ip_whitelist";s:12:"Ip Whitelist";}'),
(5404, 2102, 2, '#!/bin/bash\n\nset -x\n\nrm -f /etc/scalr/private.d/.update && /etc/init.d/scalarizr_update start', '2011-12-07 10:39:12', 'Approved', 'a:0:{}'),
(6400, 2102, 3, '#!/usr/bin/python\n\nimport os\nimport re\nimport sys\nimport logging\nimport string\nimport platform\nimport subprocess\nimport urllib2\nimport pkgutil\nimport optparse\nimport time\nimport ConfigParser\n\nlogging.basicConfig(filename="/var/log/scalarizr_update.log", level=logging.DEBUG)\nLOG = logging.getLogger("update")\n\ndef daemonize():\n	# First fork\n	pid = os.fork()\n	if pid > 0:\n		sys.exit(0) 	\n	\n	os.chdir("/")\n	os.setsid()\n	os.umask(0)\n	\n	# Second fork\n	pid = os.fork()\n	if pid > 0:\n		sys.exit(0)\n		\n	# Redirect standard file descriptors\n	sys.stdout.flush()\n	sys.stderr.flush()\n	si = file(os.devnull, ''r'')\n	so = file(os.devnull, ''a+'')\n	se = file(os.devnull, ''a+'', 0)\n	os.dup2(si.fileno(), sys.stdin.fileno())\n	os.dup2(so.fileno(), sys.stdout.fileno())\n	os.dup2(se.fileno(), sys.stderr.fileno())\n\nclass ProcessExecutionError(Exception):\n	\n	def __str__(self):\n		if isinstance(self.cmd[0], basestring):\n			args = [self.cmd[0]]\n		else:\n			args = ['' ''.join(self.cmd[0])]\n		args += [self.returncode, self.out, self.err]\n\n		ret = ''Process execution failed.\\ncmd: %s\\nreturncode: %s\\nout: %s\\nerr: %s\\n'' % tuple(args)\n		return ret.strip()\n\n	@property\n	def cmd(self):\n		return self.args[0]\n\n	@property\n	def out(self):\n		return self.args[1]\n	\n	@property\n	def err(self):\n		return self.args[2]\n\n	@property\n	def returncode(self):\n		return self.args[3]\n\nclass process(object):\n	processes = {}\n	\n	PATH = ''/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games''.split('':'')\n	\n	@classmethod\n	def run(cls, *args, **kwds):\n		raise_exc = kwds.pop(''raise_exc'', False)\n		kwds.update(stdout=subprocess.PIPE, stderr=subprocess.PIPE)\n		args = list(args)\n		if isinstance(args[0], basestring) and kwds.get(''shell'') != True:\n			args[0] = args[0].split('' '')\n			binary = args[0][0]\n			if not binary.startswith(''/''):\n				for path in cls.PATH:\n					binary0 = os.path.join(path, binary)\n					if os.access(binary0, os.X_OK):\n						args[0][0] = binary0\n						break\n		\n		if isinstance(args[0], basestring):\n			cmd = args[0]\n		else:\n			cmd = '' ''.join(args[0])\n		LOG.debug(''process.run: %s'', cmd)\n		proc = subprocess.Popen(*args, **kwds)\n		cls.processes[proc.pid] = proc\n		try:\n			out, err = proc.communicate()\n			if LOG.isEnabledFor(logging.DEBUG):\n				LOG.debug(''returncode: %s'', proc.returncode)\n				for line in out.splitlines():\n					LOG.debug(''out: %s'', line)\n				for line in err.splitlines():\n					LOG.debug(''err: %s'', line)\n			\n			if proc.returncode and raise_exc:\n				raise ProcessExecutionError(args, out, err, proc.returncode)\n	\n			return out or '''', err or '''', proc.returncode\n		finally:\n			del cls.processes[proc.pid]\n	\n	@classmethod\n	def terminate_all(cls):\n		for proc in cls.processes.values():\n			try:\n				proc.terminate()\n			finally:\n				del cls.processes[proc.pid]\n\nclass dist(object):\n	UBUNTU = FEDORA = RHEL = None\n	\n	@classmethod\n	def linux_dist(cls):\n		if os.path.exists("/etc/lsb-release"):\n			fp = open("/etc/lsb-release")\n			lines = fp.readlines()\n			fp.close()\n			return tuple(map(lambda i: lines[i].split(''='')[1].strip(), range(3)))\n			\n		elif not process.run(''which lsb_release'')[2]:\n			lines = process.run(''lsb_release -a'')[0].splitlines()\n			lsb = dict(map(lambda i: map(string.strip, re.split(r''[=:]'', lines[i], 1)), range(len(lines))))\n			return (lsb[''Distributor ID''], lsb[''Release''], lsb[''Codename''])\n		\n		elif sys.version_info[0:2] > (2, 5):\n			return platform.linux_distribution()\n		\n		return platform.dist()\n	\n	@classmethod\n	def _init(cls):\n		d = map(string.lower, cls.linux_dist())\n		version = tuple(map(int, d[1].split(''.'')))\n		cls.UBUNTU = d[0] in (''ubuntu'', ''debian'') and version\n		cls.FEDORA = d[0] == ''fedora'' and version\n		cls.RHEL = (d[0] in (''redhat'', ''rhel'', ''centos'') or ''red hat'' in d[0] or ''enterprise linux'' in d[0] or cls.FEDORA) and version\n\ndist._init()\nclass PackageMgr(object):\n	def __init__(self):\n		self.proc = None\n	\n	def install(self, name, version, *args):\n		'''''' Installs a `version` of package `name` ''''''\n		raise NotImplemented()\n	\n	def _join_packages_str(self, sep, name, version, *args):\n		packages = [(name, version)]\n		if args:\n			for i in xrange(0, len(args), 2):\n				packages.append(args[i:i+2])\n		format = ''%s'' + sep +''%s''\n		return '' ''.join(format % p for p in packages)		\n	\n	def check_update(self, name):\n		'''''' Returns info for package `name` ''''''\n		raise NotImplemented()\n\n	def candidates(self, name):\n		'''''' Returns all available installation candidates for `name` ''''''\n		raise NotImplemented()\n\nclass AptPackageMgr(PackageMgr):\n	def apt_get_command(self, command, **kwds):\n		kwds.update(env={\n			''DEBIAN_FRONTEND'': ''noninteractive'', \n			''DEBIAN_PRIORITY'': ''critical'',\n			''PATH'': ''/usr/local/sbin:/usr/local/bin:/usr/sbin:/usr/bin:/sbin:/bin:/usr/games''\n		})\n		return process.run(''apt-get -q -y %s'' % command, **kwds)\n\n	def apt_cache_command(self, command, **kwds):\n		return process.run(''apt-cache %s'' % command, **kwds)\n\n	def candidates(self, name):\n		version_available_re = re.compile(r''^\\s{5}([^\\s]+)\\s{1}'')\n		version_installed_re = re.compile(r''^\\s{1}\\*\\*\\*|s{1}([^\\s]+)\\s{1}'')\n		\n		self.apt_get_command(''update'')\n		\n		versions = []\n		\n		for line in self.apt_cache_command(''policy %s'' % name)[0].splitlines():\n			m = version_available_re.match(line)\n			if m:\n				versions.append(m.group(1))\n			m = version_installed_re.match(line)\n			if m:\n				break\n\n		versions.reverse()\n		return versions\n\n	\n	def check_update(self, name):\n		installed_re = re.compile(r''^\\s{2}Installed: (.+)$'')\n		candidate_re = re.compile(r''^\\s{2}Candidate: (.+)$'')\n		installed = candidate = None\n		\n		self.apt_get_command(''update'')\n		\n		for line in self.apt_cache_command(''policy %s'' % name)[0].splitlines():\n			m = installed_re.match(line)\n			if m:\n				installed = m.group(1)\n				if installed == ''(none)'':\n					installed = None\n				continue\n			\n			m = candidate_re.match(line)\n			if m:\n				candidate = m.group(1)\n				continue\n			\n		if candidate and installed:\n			if not process.run(''dpkg --compare-versions %s gt %s'' % (candidate, installed))[2]:\n				return candidate\n	\n	def install(self, name, version, *args):\n		self.apt_get_command(''install %s'' % self._join_packages_str(''='', name, version, *args), raise_exc=True)\n\nclass YumPackageMgr(PackageMgr):\n\n	def yum_command(self, command, **kwds):\n		return process.run(''yum -d0 -y %s'' % command, **kwds)\n\n	def rpm_ver_cmp(self, v1, v2):\n		return cmp(RpmVersion(v1), RpmVersion(v2))\n	\n	def candidates(self, name):\n		self.yum_command(''clean expire-cache'')\n		out = self.yum_command(''list --showduplicates %s'' % name)[0].strip()\n		\n		version_re = re.compile(r''[^\\s]+\\s+([^\\s]+)'')\n		lines = map(string.strip, out.splitlines())\n		\n		try:\n			line = lines[lines.index(''Installed Packages'')+1]\n			installed = version_re.match(line).group(1)\n		except ValueError:\n			installed = None\n		\n		versions = [version_re.match(line).group(1) for line in lines[lines.index(''Available Packages'')+1:]]\n		if installed:\n			versions = [v for v in versions if self.rpm_ver_cmp(v, installed) > 0]\n		\n		return versions\n			\n	\n	def check_update(self, name):\n		self.yum_command(''clean expire-cache'')\n		out, _, code = self.yum_command(''check-update %s'' % name)\n		if code == 100:\n			return filter(None, out.strip().split('' ''))[1]\n			\n	def install(self, name, version, *args):\n		self.yum_command(''install %s'' %  self._join_packages_str(''-'', name, version, *args), raise_exc=True)\n\nclass RpmVersion(object):\n	\n	def __init__(self, version):\n		self.version = version\n		self._re_not_alphanum = re.compile(r''^[^a-zA-Z0-9]+'')\n		self._re_digits = re.compile(r''^(\\d+)'')\n		self._re_alpha = re.compile(r''^([a-zA-Z]+)'')\n	\n	def __iter__(self):\n		ver = self.version\n		while ver:\n			ver = self._re_not_alphanum.sub('''', ver)\n			if not ver:\n				break\n\n			if ver and ver[0].isdigit():\n				token = self._re_digits.match(ver).group(1)\n			else:\n				token = self._re_alpha.match(ver).group(1)\n			\n			yield token\n			ver = ver[len(token):]\n			\n		raise StopIteration()\n	\n	def __cmp__(self, other):\n		iter2 = iter(other)\n		\n		for tok1 in self:\n			try:\n				tok2 = iter2.next()\n			except StopIteration:\n				return 1\n		\n			if tok1.isdigit() and tok2.isdigit():\n				c = cmp(int(tok1), int(tok2))\n				if c != 0:\n					return c\n			elif tok1.isdigit() or tok2.isdigit():\n				if tok1.isdigit():\n					return 1\n				else:\n					return -1\n			else:\n				c = cmp(tok1, tok2)\n				if c != 0:\n					return c\n			\n		try:\n			iter2.next()\n			return -1\n		except StopIteration:\n			return 0\n\ndef get_scalarizr_option(option):\n	p = ConfigParser.ConfigParser()\n	p.read(''/etc/scalr/public.d/config.ini'')\n	return p.get(''general'', option)\n\ndef update_main():\n	daemonize()\n	time.sleep(5)\n	\n	package = "scalarizr-%s" % get_scalarizr_option(''platform'')\n	if dist.UBUNTU:\n		MgrClass = AptPackageMgr\n	else:\n		MgrClass = YumPackageMgr\n	mgr = MgrClass()\n	versions = mgr.candidates(package)\n	if len(versions):\n		LOG.info(''Installing %s=%s'', package, versions[-1])\n		mgr.install(''scalarizr-base'', versions[-1], package, versions[-1])\n		LOG.info(''Done'')\n\nupdate_main()', '2012-03-14 06:58:44', 'Approved', 'a:1:{s:2:"s=";s:2:"S=";}');




