<?php
	require_once(dirname(__FILE__).'/../../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrMigrate = new ConsolidateAccounts();
	$ScalrMigrate->run(5665, array(6022));
	
	class ConsolidateAccounts
	{
		function run($mainAccountId, array $accounts)
		{
			global $db;
			
			foreach ($accounts as $accountId)
			{
				//Update clients
				$db->Execute("UPDATE clients SET status='CONSOLIDATED->{$mainAccountId}' WHERE id=?", array($accountId));
				$name = $db->GetOne("SELECT name FROM clients WHERE id=?", array($accountId));
				$envId = $db->GetOne("SELECT id FROM client_environments WHERE client_id=?", array($accountId));
				
				// Update environment
				$db->Execute("UPDATE client_environments SET name=?, client_id=? WHERE id=?", array($name, $mainAccountId, $envId));
				
				$user = $db->GetRow("SELECT id, email FROM account_users WHERE `account_id`=? AND `type`=?", array($accountId, Scalr_Account_User::TYPE_ACCOUNT_OWNER));
				$userId = $user['id'];
				$userEmail = $user['email'];
				
				// Update user
				$db->Execute("UPDATE account_users SET `account_id` = ?, `type` = ? WHERE `id` = ?", array($mainAccountId, Scalr_Account_User::TYPE_TEAM_USER, $userId));
				
				// Create team
				$db->Execute("INSERT INTO account_teams SET `account_id` = ?, `name` = ?", array($mainAccountId, $userEmail));
				$teamId = $db->Insert_ID();
			
				
				// Associate env and user with new team
				$db->Execute("INSERT INTO account_team_users SET team_id = ?, user_id = ?, `permissions` = 'full'", array($teamId, $userId));
				$db->Execute("INSERT INTO account_team_envs SET team_id = ?, env_id = ?", array($teamId, $envId));
				
				
				//Update client_id objects
				$tables = array('apache_vhosts','bundle_tasks','dns_zones','ec2_ebs','roles','scaling_metrics','servers','servers_history','service_config_presets','ssh_keys','storage_snapshots','storage_volumes', 'scheduler_tasks');
				foreach ($tables as $table)
					$db->Execute("UPDATE {$table} SET client_id = ? WHERE client_id = ?", array($mainAccountId, $accountId));
				
				//Update clientid objects
				$tables = array('autosnap_settings','elastic_ips','farms','scripts');
				foreach ($tables as $table)
					$db->Execute("UPDATE {$table} SET clientid = ? WHERE clientid = ?", array($mainAccountId, $accountId));
					
				//Update account_id objects
				$tables = array('scheduler');
				foreach ($tables as $table)
					$db->Execute("UPDATE {$table} SET account_id = ? WHERE account_id = ?", array($mainAccountId, $accountId));
			}
		}
	}
?>