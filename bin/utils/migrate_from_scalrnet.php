<?php
	require_once(dirname(__FILE__).'/../../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$cryptokey = 'ihGTk/dj9NVliCdUiUllkwgPMe3HeP4xtZeQQHPGhDBnId63LuZQiEc07Utmll3X55QT1k7KUTyyqIpi/CwMS3FARRJiuyL8HlaOMwBIlt4ffXQvH4+eVj+zcm3yz1O1OBEf9XQCiGTVNfgVUCCBvgZ6GR6EcK7NUElmdyix6kNJ0KKNWxjYtImQe6fiU6FvUQjTtGqBOGD5BtL2WxHHwo05Oomfv6PErOcgFrR1NNUMGuPI0S6zL6JD6sIcIl2wF4xPyFBBfOms9zUeqzyFU1/NUvavUegJ25qMvzc3ZoqXDdfL+eg4MvE9WhyH9N4Qb0fjgOXih5JNuisX7wSFVyLWNllwhLBXWBCdX5JPhhYikO82WTByol9zWweOI0EQyDtDmSiPbYwOr3sBZ9SQb7Tmdv8cmDWm1J3Ml8n5i9p9vDqYu2RUKmwinvZ8Sm55aOiCpscAWZZsMMGxh/HBQ3rEwYRcSzAbORZcW1oJyPc5/Ru30Il4EDF8apRpxwI1';
	
	$ScalrMigrate = new MigrateDB(4907);
	$ScalrMigrate->run(array("user" => "scalr", "password" => "1GJdKjOLx", "host" => "ext-mysql-master.cloudfarms.net", "name" => "scalr"), $cryptokey);
	//$ScalrMigrate->run(array("user" => "dicsydel", "password" => "1GJdKjOLxNxPZbMd6hlM", "host" => "192.168.1.200", "name" => "scalr_samsung_test"), $cryptokey);
	
	
	class MigrateDB
	{	
		private $localDb;
		private $remoteDb;
		private $accountId;
		
		private $remoteCryptoKey;
		private $localCryptoKey;
		
		private $farmId = 7299;
		
		function __construct($accountId)
		{
			$this->accountId = $accountId;
			$this->cryptoTool = new Scalr_Util_CryptoTool(MCRYPT_TRIPLEDES, MCRYPT_MODE_CFB, 24, 8);
		}
		
		function run($dbinfo, $remoteCryptokey)
		{
			$this->migrate($dbinfo, $remoteCryptokey);
			
		}
		
		function migrate($dbinfo, $remoteCryptokey)
		{
			global $db;

			$this->localDb = $db;
			$this->remoteCryptoKey = $remoteCryptokey;
			$this->localCryptoKey = file_get_contents("/var/www/my.scalr.net-production/etc/.cryptokey");
			
			try
			{
				$this->remoteDb = &NewADOConnection("mysqli://{$dbinfo['user']}:{$dbinfo['password']}@{$dbinfo['host']}/{$dbinfo['name']}");
				$this->remoteDb->debug = false;
                $this->remoteDb->cacheSecs = 0;
                $this->remoteDb->SetFetchMode(ADODB_FETCH_ASSOC); 
			}
			catch(Exception $e)
			{		
				die("Service is temporary not available. Please try again in a minute. ({$e->getMessage()})");
			}
			
			
			$this->migrateAccount();
		}
		
		
		private function migrateAccount()
		{
			print "Migrating account...";

			// Migrate account
			$account = $this->localDb->GetRow("SELECT * FROM clients WHERE id=?", array($this->accountId));
			$this->remoteDb->Execute("INSERT INTO clients SET
				id = ?,
				name = ?,
				status = ?
			", array($account['id'], $account['name'], 'Active'));
			
			//Migrate client settings
			$settings = $this->localDb->Execute("SELECT * FROM client_settings WHERE clientid=?", array($this->accountId));
			while ($setting = $settings->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO client_settings SET
					`clientid` = ?,
					`key` = ?,
					`value` = ?
				", array($this->accountId, $setting['key'], $setting['value']));
			}
			
			//Migrate users/teams/groups
			$rs = $this->localDb->Execute("SELECT * FROM account_audit WHERE account_id = ?", array($this->accountId));
			while ($audit = $rs->FetchRow())
			{
				$this->remoteDb->Execute("INSERT INTO account_audit SET
					`id`	= ?,
					`account_id` = ?,
					`user_id` = ?,
					`user_email` = ?,
					`date` = ?,
					`action` = ?,
					`ipaddress` = ?,
					`comments` = ?
				", array(
					$audit['id'],
					$audit['account_id'],
					$audit['user_id'],
					$audit['user_email'],
					$audit['date'],
					$audit['action'],
					$audit['ipaddress'],
					$audit['comments']
				));
			}
			
			$rs = $this->localDb->Execute("SELECT * FROM account_limits WHERE account_id = ?", array($this->accountId));
			while ($limit = $rs->FetchRow())
			{
				$this->remoteDb->Execute("INSERT INTO account_limits SET
					`account_id` = ?,
					`limit_name` = ?,
					`limit_value` = ?,
					`limit_type` = ?,
					`limit_type_value` = ?
				", array(
					$limit['account_id'],
					$limit['limit_name'],
					$limit['limit_value'],
					$limit['limit_type'],
					$limit['limit_type_value']
				));
			}
			
			$rsT = $this->localDb->Execute("SELECT * FROM account_teams WHERE account_id = ?", array($this->accountId));
			while ($team = $rsT->FetchRow())
			{
				// migrate teams
				
				$this->remoteDb->Execute("INSERT INTO account_teams SET
					`id` = ?,
					`account_id` = ?,
					`name` = ?
				", array(
					$team['id'],
					$team['account_id'],
					$team['name']
				));
				
				
				// migrate team users
				$rs1 = $this->localDb->Execute("SELECT * FROM account_team_users WHERE team_id = ?", array($team['id']));
				while ($tmu = $rs1->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO account_team_users SET
						`team_id` = ?,
						`user_id` = ?,
						`permissions` = ?
					", array(
						$tmu['team_id'], $tmu['user_id'], $tmu['permissions']
					));
				}
				
				// migrate team envs
				$rs2 = $this->localDb->Execute("SELECT * FROM account_team_envs WHERE team_id = ?", array($team['id']));
				while ($tme = $rs2->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO account_team_envs SET
						`team_id` = ?,
						`env_id` = ?
					", array(
						$tme['team_id'], $tme['env_id']
					));
				}
				
				
				//migrate groups + group permissions
				$rs3 = $this->localDb->Execute("SELECT * FROM account_groups WHERE team_id = ?", array($team['id']));
				while ($group = $rs3->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO account_groups SET
						`id` = ?,
						`team_id` = ?,
						`name` = ?,
						`is_active` = ?
					", array(
						$group['id'], $group['team_id'], $group['name'], $group['is_active']
					));
					
					$rs4 = $this->localDb->Execute("SELECT * FROM account_group_permissions WHERE group_id = ?", array($group['id']));
					while ($p = $rs4->FetchRow()) {
						$this->remoteDb->Execute("INSERT INTO account_group_permissions SET
							`group_id` = ?,
							`controller` = ?,
							`permissions` = ?
						", array(
							$p['group_id'], $p['controller'], $p['permissions']
						));
					}
				}
			}
			
			$rs5 = $this->localDb->Execute("SELECT * FROM account_users WHERE account_id = ?", array($this->accountId));
			while ($user = $rs5->FetchRow())
			{
				$this->remoteDb->Execute("INSERT INTO account_users SET
					`id` = ?,
					`account_id` = ?,
					`status` = ?,
					`email` = ?,
					`fullname` = ?,
					`password` = ?,
					`dtcreated` = ?,
					`dtlastlogin` = ?,
					`type` = ?,
					`comments` = ?
				", array(
					$user['id'],
					$user['account_id'],
					$user['status'],
					$user['email'],
					$user['fullname'],
					$user['password'],
					$user['dtcreated'],
					$user['dtlastlogin'],
					$user['type'],
					$user['comments']
				));
				
				$rs6 = $this->localDb->Execute("SELECT * FROM account_user_groups WHERE user_id = ?", array($user['id']));
				while ($p = $rs6->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO account_user_groups SET
						`group_id` = ?,
						`user_id` = ?
					", array(
						$p['group_id'], $p['user_id']
					));
				}
					
				$rs7 = $this->localDb->Execute("SELECT * FROM account_user_settings WHERE user_id = ?", array($user['id']));
				while ($p = $rs7->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO account_user_settings SET
						`user_id` = ?,
						`name` = ?,
						`value` = ?
					", array(
						$p['user_id'], $p['name'], $p['value']
					));
				}
			}
			
			//Migrate environemnts
			$envs = $this->localDb->Execute("SELECT * FROM client_environments WHERE client_id=?", array($this->accountId));
			while ($env = $envs->FetchRow())
			{
				$this->remoteDb->Execute("INSERT INTO client_environments SET
					`id` = ?,
					`client_id` = ?,
					`name` = ?,
					`dt_added` = ?,
					`is_system` = ?
				", array(
					$env['id'],
					$this->accountId,
					$env['name'],
					$env['dt_added'],
					$env['is_system']
				));
				
				// Migrate settings
				$settings = $this->localDb->Execute("SELECT * FROM client_environment_properties WHERE env_id=?", array($env['id']));
				while ($setting = $settings->FetchRow())
				{
					if (in_array($setting['name'], array('timezone','client_max_eips', 'client_max_instances', 'sync_timeout')) || stristr($setting['name'], "api."))
						$value = $setting['value'];
					else {
						
						$decryptedVal = trim($this->cryptoTool->decrypt($setting['value'], $this->localCryptoKey));
						$value = $this->cryptoTool->encrypt($decryptedVal, $this->remoteCryptoKey);
					}
					
					$this->remoteDb->Execute("INSERT INTO client_environment_properties SET
						`env_id` = ?,
						`name` = ?,
						`value` = ?,
						`group` = ?
					", array(
						$env['id'],
						$setting['name'],
						$value,
						$setting['group']
					));
				}	
				// Migrate environment
				$this->migrateEnvironment($env['id']);
			}
			
			$this->migrateScripts();
		}
		
		private function migrateEnvironment($envId)
		{
			//$this->migrateEvents($envId);
			//$this->migrateTasks($envId);
			//$this->migrateDm($envId);
			//$this->migratePresets($envId);
			
			//$this->migrateApacheVhosts($envId);
			//$this->migrateAutosnaps($envId);
			//$this->migrateBundleTasks($envId);
			//$this->migrateDns($envId);
			//$this->migrateEc2Objects($envId);
			//$this->migrateFarms($envId);
			
			$this->migrateRoles($envId);
			
			//$this->migrateSshKeys($envId);
			//$this->migrateStorage($envId);
		}
		
		private function migrateStorage($envId)
		{
			$rs23 = $this->localDb->Execute("SELECT * FROM storage_volumes WHERE env_id = ?", array($envId));
			while ($vol = $rs23->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO storage_volumes SET
				  `id` =?,
				  `client_id` =?,
				  `env_id` =?,
				  `name` =?,
				  `attachment_status` =?,
				  `mount_status` =?,
				  `config` =?,
				  `type` =?,
				  `dtcreated` =?,
				  `platform` =?,
				  `size` =?,
				  `fstype` =?
				", array(
					$vol['id'], $vol['client_id'], $vol['env_id'], $vol['name'], $vol['attachment_status'], $vol['mount_status'], 
					$vol['config'], $vol['type'], $vol['dtcreated'], $vol['platform'], $vol['size'], $vol['fstype']
				));
			}
			
			$rs24 = $this->localDb->Execute("SELECT * FROM storage_snapshots WHERE env_id = ?", array($envId));
			while ($snap = $rs24->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO storage_snapshots SET
				  `id` = ?,
				  `client_id` = ?,
				  `env_id` = ?,
				  `name` = ?,
				  `platform` = ?,
				  `type` = ?,
				  `config` = ?,
				  `description` = ?,
				  `ismysql` = ?,
				  `dtcreated` = ?,
				  `farm_id` = ?,
				  `farm_roleid` = ?,
				  `service` = ?
				", array(
					 $snap['id'], $snap['client_id'], $snap['env_id'], $snap['name'], $snap['platform'], $snap['type'], 
					 $snap['config'], $snap['description'], $snap['ismysql'], $snap['dtcreated'], $snap['farm_id'], $snap['farm_roleid'], $snap['service'] 
				));
			}
		}
		
		private function migrateSshKeys($envId)
		{
			$rs22 = $this->localDb->Execute("SELECT * FROM ssh_keys WHERE env_id = ?", array($envId));
			while ($key = $rs22->FetchRow()) {
				$decryptedPrivate = trim($this->cryptoTool->decrypt($key['private_key'], $this->localCryptoKey));
				$private = $this->cryptoTool->encrypt($decryptedPrivate, $this->remoteCryptoKey);

				$decryptedPublic = trim($this->cryptoTool->decrypt($key['public_key'], $this->localCryptoKey));
				$public = $this->cryptoTool->encrypt($decryptedPublic, $this->remoteCryptoKey);
				
				$this->remoteDb->Execute("INSERT INTO ssh_keys SET
					client_id = ?,
					env_id = ?,
					type = ?,
					private_key = ?,
					public_key = ?,
					cloud_location = ?,
					farm_id = ?,
					cloud_key_name = ?,
					platform = ?
				", array($key['client_id'], $key['env_id'], $key['type'], $private, $public, $key['cloud_location'], 
					$key['farm_id'], $key['cloud_key_name'], $key['platform']
				));
			}
		}
		
		private function migrateServers($envId, $farmId)
		{
			$rs22 = $this->localDb->Execute("SELECT * FROM servers WHERE env_id = ? AND farm_id = ?", array($envId, $farmId));
			while ($server = $rs22->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO servers SET
					`id` = ?,
					`server_id` = ?,
					`farm_id` = ?,
					`farm_roleid` = ?,
					`client_id` = ?,
					`env_id` = ?,
					`role_id` = ?,
					`platform` = ?,
					`status` = ?,
					`remote_ip` = ?,
					`local_ip` = ?,
					`dtadded` = ?,
					`index` = ?,
					`dtshutdownscheduled` = ?,
					`dtrebootstart` = ?,
					`replace_server_id` = ?,
					`dtlastsync` = ?
				", array(
					$server['id'],
					$server['server_id'],
					$server['farm_id'],
					$server['farm_roleid'],
					$server['client_id'],
					$server['env_id'],
					$server['role_id'],
					$server['platform'],
					$server['status'],
					$server['remote_ip'],
					$server['local_ip'],
					$server['dtadded'],
					$server['index'],
					$server['dtshutdownscheduled'],
					$server['dtrebootstart'],
					$server['replace_server_id'],
					$server['dtlastsync']
				));
				
				$rss1 = $this->localDb->Execute("SELECT * FROM server_properties WHERE server_id = ?", array($server['server_id']));
				while ($r1 = $rss1->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO server_properties SET
						`server_id` = ?,
						`name` = ?,
						`value` = ?
					", array($r1['server_id'], $r1['name'], $r1['value']));
				}
				
				$rss2 = $this->localDb->Execute("SELECT * FROM messages WHERE server_id = ?", array($server['server_id']));
				while ($r2 = $rss2->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO messages SET
						`server_id` = ?,
						`messageid` = ?,
						`status` = ?,
						`handle_attempts` = ?,
						`dtlasthandleattempt` = ?,
						`message` = ?,
						`type` = ?,
						`isszr` = ?
					", array($r2['server_id'], $r2['messageid'], $r2['status'], $r2['handle_attempts'], $r2['dtlasthandleattempt'], $r2['message'], $r2['type'], $r2['isszr']));
				}
			}
		}
		
		private function migrateScripts($envId)
		{
			$rs21 = $this->localDb->Execute("SELECT * FROM scripts WHERE clientid = ?", array($this->accountId));
			while ($script = $rs21->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO scripts SET
					`id` = ?,
					`name` = ?,
					`description` = ?,
					`origin` = ?,
					`dtadded` = ?,
					`issync` = ?,
					`clientid` = ?,
					`approval_state` = ?
				", array(
					$script['id'], $script['name'], $script['description'], $script['origin'], $script['dtadded'], 
					$script['issync'], $script['clientid'], $script['approval_state']
				));
				
				$rss1 = $this->localDb->Execute("SELECT * FROM script_revisions WHERE scriptid = ?", array($script['id']));
				while ($r1 = $rss1->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO script_revisions SET
						`scriptid` = ?,
						`revision` = ?,
						`script` = ?,
						`dtcreated` = ?,
						`approval_state` = ?
					", array($r1['scriptid'], $r1['revision'], $r1['script'], $r1['dtcreated'], $r1['approval_state']));
				}
			}
		}
		
		private function migrateRoles($envId)
		{
			$rs20 = $this->localDb->Execute("SELECT * FROM roles WHERE env_id = ?", array($envId));
			while ($role = $rs20->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO roles SET
					`id` = ?,
					`name` = ?,
					`origin` = ?,
					`client_id` = ?,
					`env_id` = ?,
					`description` = ?,
					`behaviors` = ?,
					`architecture` = ?,
					`is_stable` = ?,
					`history` = ?,
					`approval_state` = ?,
					`generation` = ?,
					`os` = ?,
					`szr_version` = ?
				", array(
					$role['id'], $role['name'], $role['origin'], $role['client_id'], $role['env_id'], $role['description'],
					$role['behaviors'], $role['architecture'], $role['is_stable'], $role['history'], $role['approval_state'],
					$role['generation'], $role['os'], $role['szr_version']
				));
				
				$rsr1 = $this->localDb->Execute("SELECT * FROM role_tags WHERE role_id = ?", array($role['id']));
				while ($r1 = $rsr1->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO role_tags SET
						`role_id` = ?,
						`tag` = ?
					", array($r1['role_id'], $r1['tag']));
				}
				
				$rsr2 = $this->localDb->Execute("SELECT * FROM role_software WHERE role_id = ?", array($role['id']));
				while ($r2 = $rsr2->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO role_software SET
						`role_id` = ?,
						`software_name` = ?,
						`software_version` = ?,
						`software_key` = ?
					", array($r2['role_id'], $r2['software_name'], $r2['software_version'], $r2['software_key']));
				}
				
				$rsr3 = $this->localDb->Execute("SELECT * FROM role_security_rules WHERE role_id = ?", array($role['id']));
				while ($r3 = $rsr3->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO role_security_rules SET
						`role_id` = ?,
						`rule` = ?
					", array($r3['role_id'], $r3['rule']));
				}
				
				$rsr5 = $this->localDb->Execute("SELECT * FROM role_properties WHERE role_id = ?", array($role['id']));
				while ($r5 = $rsr5->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO role_properties SET
						`role_id` = ?,
						`name` = ?,
						`value` = ?
					", array($r5['role_id'], $r5['name'], $r5['value']));
				}
				
				$rsr6 = $this->localDb->Execute("SELECT * FROM role_parameters WHERE role_id = ?", array($role['id']));
				while ($r6 = $rsr6->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO role_parameters SET
						`role_id` = ?,
						`name` = ?,
						`type` = ?,
						`isrequired` = ?,
						`defval` = ?,
						`allow_multiple_choice` = ?,
						`options` = ?,
						`hash` = ?,
						`issystem` = ?
					", array($r6['role_id'], $r6['name'], $r6['type'], $r6['isrequired'], $r6['defval'], $r6['allow_multiple_choice'], $r6['options'], $r6['hash'], $r6['issystem']));
				}
				
				$rsr7 = $this->localDb->Execute("SELECT * FROM role_images WHERE role_id = ?", array($role['id']));
				while ($r7 = $rsr7->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO role_images SET
						`role_id` = ?,
						`cloud_location` = ?,
						`image_id` = ?,
						`platform` = ?
					", array($r7['role_id'], $r7['cloud_location'], $r7['image_id'], $r7['platform']));
				}
				
				$rsr8 = $this->localDb->Execute("SELECT * FROM role_behaviors WHERE role_id = ?", array($role['id']));
				while ($r8 = $rsr8->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO role_behaviors SET
						`role_id` = ?,
						`behavior` = ?
					", array($r8['role_id'], $r8['behavior']));
				}
			}
		}
		
		private function migrateFarms($envId)
		{
			if ($this->farmId)
				$rs8 = $this->localDb->Execute("SELECT * FROM farms WHERE env_id = ? AND id = ?", array($envId, $this->farmId));
			else
				$rs8 = $this->localDb->Execute("SELECT * FROM farms WHERE env_id = ?", array($envId));
				
			while ($farm = $rs8->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO farms SET
					`id` = ?,
					`clientid` = ?,
					`env_id` = ?,
					`name` = ?,
					`iscompleted` = ?,
					`hash` = ?,
					`dtadded` = ?,
					`status` = ?,
					`dtlaunched` = ?,
					`term_on_sync_fail` = ?,
					`farm_roles_launch_order` = ?,
					`comments` = ?
				", array(
					$farm['id'],
					$farm['clientid'],
					$farm['env_id'],
					$farm['name'],
					$farm['iscompleted'],
					$farm['hash'],
					$farm['dtadded'],
					$farm['status'],
					$farm['dtlaunched'],
					$farm['term_on_sync_fail'],
					$farm['farm_roles_launch_order'],
					$farm['comments']
				));
				
				$rsf1 = $this->localDb->Execute("SELECT * FROM farm_settings WHERE farmid = ?", array($farm['id']));
				while ($s = $rsf1->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO farm_settings SET
						`farmid` = ?,
						`name` = ?,
						`value` = ?
					", array($s['farmid'], $s['name'], $s['value']));
				}
				
				$rsf2 = $this->localDb->Execute("SELECT * FROM farm_roles WHERE farmid = ?", array($farm['id']));
				while ($role = $rsf2->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO farm_roles SET
						`id` = ?,
						`farmid` = ?,
						`role_id` = ?,
						`new_role_id` = ?,
						`platform` = ?,
						`cloud_location` = ?
					", array($role['id'], $role['farmid'], $role['role_id'], $role['new_role_id'], $role['platform'], $role['cloud_location']));
					
					$rsfr1 = $this->localDb->Execute("SELECT * FROM farm_role_settings WHERE farm_roleid = ?", array($role['id']));
					while ($r = $rsfr1->FetchRow()) {
						$this->remoteDb->Execute("INSERT INTO farm_role_settings SET
							`farm_roleid` = ?,
							`name` = ?,
							`value` = ?
						", array(
							$r['farm_roleid'], $r['name'], $r['value']
						));
					}
					
					$rsfr2 = $this->localDb->Execute("SELECT * FROM farm_role_service_config_presets WHERE farm_roleid = ?", array($role['id']));
					while ($r2 = $rsfr2->FetchRow()) {
						$this->remoteDb->Execute("INSERT INTO farm_role_service_config_presets SET
							`farm_roleid` = ?,
							`preset_id` = ?,
							`behavior` = ?,
							`restart_service` = ?
						", array(
							$r2['farm_roleid'], $r2['preset_id'], $r2['behavior'], $r2['restart_service']
						));
					}
					
					$rsfr3 = $this->localDb->Execute("SELECT * FROM farm_role_scripts WHERE farm_roleid = ?", array($role['id']));
					while ($r3 = $rsfr3->FetchRow()) {
						$this->remoteDb->Execute("INSERT INTO farm_role_scripts SET
							`scriptid` = ?,
							`farmid` = ?,
							`params` = ?,
							`event_name` = ?,
							`target` = ?,
							`version` = ?,
							`timeout` = ?,
							`issync` = ?,
							`ismenuitem` = ?,
							`order_index` = ?,
							`farm_roleid` = ?,
							`issystem` = ?
						", array(
							$r3['scriptid'], $r3['farmid'], $r3['params'], $r3['event_name'], $r3['target'], $r3['version'],
							$r3['timeout'], $r3['issync'], $r3['ismenuitem'], $r3['order_index'], $r3['farm_roleid'], $r3['issystem']
						));
					}
					
					$rsfr4 = $this->localDb->Execute("SELECT * FROM farm_role_scaling_times WHERE farm_roleid = ?", array($role['id']));
					while ($r4 = $rsfr4->FetchRow()) {
						$this->remoteDb->Execute("INSERT INTO farm_role_scaling_times SET
							`farm_roleid` = ?,
							`start_time` = ?,
							`end_time` = ?,
							`days_of_week` = ?,
							`instances_count` = ?
						", array(
							$r4['farm_roleid'], $r4['start_time'], $r4['end_time'], $r4['days_of_week'], $r4['instances_count']
						));
					}
					
					$rsfr5 = $this->localDb->Execute("SELECT * FROM farm_role_scaling_metrics WHERE farm_roleid = ?", array($role['id']));
					while ($r5 = $rsfr5->FetchRow()) {
						$this->remoteDb->Execute("INSERT INTO farm_role_scaling_metrics SET
							`farm_roleid` = ?,
							`metric_id` = ?,
							`dtlastpolled` = ?,
							`last_value` = ?,
							`settings` = ?
						", array(
							$r5['farm_roleid'], $r5['metric_id'], $r5['dtlastpolled'], $r5['last_value'], $r5['settings']
						));
					}
					
					$rsfr6 = $this->localDb->Execute("SELECT * FROM farm_role_options WHERE farm_roleid = ?", array($role['id']));
					while ($r6 = $rsfr6->FetchRow()) {
						$this->remoteDb->Execute("INSERT INTO farm_role_options SET
							`farm_roleid` = ?,
							`name` = ?,
							`value` = ?,
							`hash` = ?,
							`farmid` = ?
						", array(
							$r6['farm_roleid'], $r6['name'], $r6['value'], $r6['hash'], $r6['farmid']
						));
					}
				}
				
				$this->migrateServers($envId, $farm['id']);
			}
		}
		
		private function migrateEc2Objects($envId)
		{
			$rs9 = $this->localDb->Execute("SELECT * FROM ec2_ebs WHERE env_id = ?", array($envId));
			while ($ebs = $rs9->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO ec2_ebs SET
					`farm_id` = ?,
					`farm_roleid` = ?,
					`volume_id` = ?,
					`server_id` = ?,
					`attachment_status` = ?,
					`mount_status` = ?,
					`device` = ?,
					`server_index` = ?,
					`mount` = ?,
					`mountpoint` = ?,
					`ec2_avail_zone` = ?,
					`ec2_region` = ?,
					`isfsexist` = ?,
					`ismanual` = ?,
					`size` = ?,
					`snap_id` = ?,
					`ismysqlvolume` = ?,
					`client_id` = ?,
					`env_id` = ?
				", array(
					$ebs['farm_id'],
					$ebs['farm_roleid'],
					$ebs['volume_id'],
					$ebs['server_id'],
					$ebs['attachment_status'],
					$ebs['mount_status'],
					$ebs['device'],
					$ebs['server_index'],
					$ebs['mount'],
					$ebs['mountpoint'],
					$ebs['ec2_avail_zone'],
					$ebs['ec2_region'],
					$ebs['isfsexist'],
					$ebs['ismanual'],
					$ebs['size'],
					$ebs['snap_id'],
					$ebs['ismysqlvolume'],
					$ebs['client_id'],
					$ebs['env_id']
				));
			}
			
			$rs10 = $this->localDb->Execute("SELECT * FROM elastic_ips WHERE env_id = ?", array($envId));
			while ($eip = $rs10->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO elastic_ips SET
					`farmid` = ?,
					`farm_roleid` = ?,
					`ipaddress` = ?,
					`state` = ?,
					`clientid` = ?,
					`env_id` = ?,
					`instance_index` = ?,
					`server_id` = ?
				", array(
					$eip['farmid'],
					$eip['farm_roleid'],
					$eip['ipaddress'],
					$eip['state'],
					$eip['clientid'],
					$eip['env_id'],
					$eip['instance_index'],
					$eip['server_id']
				));
			}
		}
		
		private function migrateDns($envId)
		{
			$rs11 = $this->localDb->Execute("SELECT * FROM dns_zones WHERE env_id = ?", array($envId));
			while ($z = $rs11->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO dns_zones SET
					`id` = ?,
					`client_id` = ?,
					`env_id` = ?,
					`farm_id` = ?,
					`farm_roleid` = ?,
					`zone_name` = ?,
					`status` = ?,
					`soa_owner` = ?,
					`soa_ttl` = ?,
					`soa_parent` = ?,
					`soa_serial` = ?,
					`soa_refresh` = ?,
					`soa_retry` = ?,
					`soa_expire` = ?,
					`soa_min_ttl` = ?,
					`dtlastmodified` = ?,
					`axfr_allowed_hosts` = ?,
					`isonnsserver` = '0',
					`iszoneconfigmodified` = '1'
				", array(
					$z['id'],
					$z['client_id'],
					$z['env_id'],
					$z['farm_id'],
					$z['farm_roleid'],
					$z['zone_name'],
					$z['status'] == 'Active' ? 'Pending create' : $z['status'],
					$z['soa_owner'],
					$z['soa_ttl'],
					$z['soa_parent'],
					$z['soa_serial'],
					$z['soa_refresh'],
					$z['soa_retry'],
					$z['soa_expire'],
					$z['soa_min_ttl'],
					$z['dtlastmodified'],
					$z['axfr_allowed_hosts']
				));
				
				$rs12 = $this->localDb->Execute("SELECT * FROM dns_zone_records WHERE zone_id = ?", array($z['id']));
				while ($r = $rs12->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO dns_zone_records SET
						`zone_id` = ?,
						`type` = ?,
						`ttl` = ?,
						`priority` = ?,
						`value` = ?,
						`name` = ?,
						`issystem` = ?,
						`weight` = ?,
						`port` = ?,
						`server_id` = ?
					", array(
						$r['zone_id'], $r['type'], $r['ttl'], $r['priority'], $r['value'], $r['name'], $r['issystem'], $r['weight'], $r['port'], $r['server_id']
					));
				}
			}
		}
		
		private function migrateDm($envId)
		{
			
		}
		
		private function migrateBundleTasks($envId)
		{
			$rs13 = $this->localDb->Execute("SELECT * FROM bundle_tasks WHERE env_id = ?", array($envId));
			while ($b = $rs13->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO bundle_tasks SET
					`id` = ?,
					`prototype_role_id` = ?,
					`client_id` = ?,
					`env_id` = ?,
					`server_id` = ?,
					`replace_type` = ?,
					`status` = ?,
					`platform` = ?,
					`rolename` = ?,
					`failure_reason` = ?,
					`bundle_type` = ?,
					`dtadded` = ?,
					`dtstarted` = ?,
					`dtfinished` = ?,
					`remove_proto_role` = ?,
					`snapshot_id` = ?,
					`platform_status` = ?,
					`description` = ?,
					`role_id` = ?,
					`farm_id` = ?,
					`cloud_location` = ?,
					`meta_data` = ?
				", array(
					$b['id'],
					$b['prototype_role_id'],
					$b['client_id'],
					$b['env_id'],
					$b['server_id'],
					$b['replace_type'],
					$b['status'],
					$b['platform'],
					$b['rolename'],
					$b['failure_reason'],
					$b['bundle_type'],
					$b['dtadded'],
					$b['dtstarted'],
					$b['dtfinished'],
					$b['remove_proto_role'],
					$b['snapshot_id'],
					$b['platform_status'],
					$b['description'],
					$b['role_id'],
					$b['farm_id'],
					$b['cloud_location'],
					$b['meta_data'],
				));
				
				$rs14 = $this->localDb->Execute("SELECT * FROM bundle_task_log WHERE bundle_task_id = ?", array($b['id']));
				while ($p = $rs14->FetchRow()) {
					$this->remoteDb->Execute("INSERT INTO bundle_task_log SET
						`bundle_task_id` = ?,
						`dtadded` = ?,
						`message` = ?
					", array(
						$p['bundle_task_id'], $p['dtadded'], $p['message']
					));
				}
			}
		}
		
		private function migrateAutosnaps($envId)
		{
			$rs15 = $this->localDb->Execute("SELECT * FROM autosnap_settings WHERE env_id = ?", array($envId));
			while ($s = $rs15->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO autosnap_settings SET
					`id` = ?,
					`clientid` = ?,
					`env_id` = ?,
					`period` = ?,
					`dtlastsnapshot` = ?,
					`rotate` = ?,
					`last_snapshotid` = ?,
					`region` = ?,
					`objectid` = ?,
					`object_type` = ?
				", array(
					$s['id'],
					$s['clientid'],
					$s['env_id'],
					$s['period'],
					$s['dtlastsnapshot'],
					$s['rotate'],
					$s['last_snapshotid'],
					$s['region'],
					$s['objectid'],
					$s['object_type']
				));
			}
		}
		
		private function migrateApacheVhosts($envId)
		{
			$rs16 = $this->localDb->Execute("SELECT * FROM apache_vhosts WHERE env_id = ?", array($envId));
			while ($vhost = $rs16->FetchRow()) {
				$this->remoteDb->Execute("INSERT INTO apache_vhosts SET
					`id` = ?,
					`name` = ?,
					`is_ssl_enabled` = ?,
					`farm_id` = ?,
					`farm_roleid` = ?,
					`ssl_cert` = ?,
					`ssl_key` = ?,
					`ca_cert` = ?,
					`last_modified` = ?,
					`client_id` = ?,
					`env_id` = ?,
					`httpd_conf` = ?,
					`httpd_conf_vars` = ?,
					`httpd_conf_ssl` = ?
				", array(
					$vhost['id'],
					$vhost['name'],
					$vhost['is_ssl_enabled'],
					$vhost['farm_id'],
					$vhost['farm_roleid'],
					$vhost['ssl_cert'],
					$vhost['ssl_key'],
					$vhost['ca_cert'],
					$vhost['last_modified'],
					$vhost['client_id'],
					$vhost['env_id'],
					$vhost['httpd_conf'],
					$vhost['httpd_conf_vars'],
					$vhost['httpd_conf_ssl']
				));
			}
		}
	}
?>
