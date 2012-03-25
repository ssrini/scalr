<?php
	require_once(dirname(__FILE__).'/../../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrMigrate = new SyncDB();
	$ScalrMigrate->run(array("user" => "scalr2", "password" => "1GJdKjOLx", "host" => "107.21.169.39", "name" => "scalr"));
	
	class SyncDB
	{
		private $localDb;
		private $remoteDb;
		
		function run($dbinfo)
		{
			global $db;

			$this->localDb = $db;
			
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
			
			$this->syncRoles();
			$this->syncScripts();
		}
		
		function syncRoles()
		{	
			$rs20 = $this->localDb->Execute("SELECT * FROM roles WHERE env_id = '0' AND client_id = '0'");
			while ($role = $rs20->FetchRow()) {
				
				$chk = $this->remoteDb->GetOne("SELECT id FROM roles WHERE name=? AND origin=?", array($role['name'], $role['origin']));
				if (!$chk) {					
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
				} else {
					$role['id'] = $chk;
					$this->remoteDb->Execute("DELETE FROM role_tags WHERE role_id = ?", array($role['id']));
					$this->remoteDb->Execute("DELETE FROM role_images WHERE role_id = ?", array($role['id']));
					$this->remoteDb->Execute("DELETE FROM role_software WHERE role_id =?", array($role['id']));
					$this->remoteDb->Execute("DELETE FROM role_security_rules WHERE role_id =?", array($role['id']));
					$this->remoteDb->Execute("DELETE FROM role_properties WHERE role_id =?", array($role['id']));
					$this->remoteDb->Execute("DELETE FROM role_parameters WHERE role_id = ?", array($role['id']));
					$this->remoteDb->Execute("DELETE FROM role_behaviors WHERE role_id =?", array($role['id']));
				}
				
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
					
					try {
					$this->remoteDb->Execute("INSERT INTO role_images SET
						`role_id` = ?,
						`cloud_location` = ?,
						`image_id` = ?,
						`platform` = ?
					", array($r7['role_id'], $r7['cloud_location'], $r7['image_id'], $r7['platform']));
					} catch (Exception $e) {}
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
		
		function syncScripts()
		{
			$this->remoteDb->Execute("DELETE FROM script_revisions WHERE scriptid IN (SELECT id FROM scripts WHERE clientid = '0')");
			$this->remoteDb->Execute("DELETE FROM scripts WHERE clientid = '0'");
			
			$rs21 = $this->localDb->Execute("SELECT * FROM scripts WHERE clientid = '0'");
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
	}
?>