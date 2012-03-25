<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20111125();
	$ScalrUpdate->Run();
	
	class Update20111125
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);

			$db->Execute("
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
				  KEY `index` (`name`,`type`,`start_time`,`end_time`,`last_start_time`,`restart_every`,`order_index`,`status`,`account_id`,`env_id`)
				) ENGINE=MyISAM  DEFAULT CHARSET=latin1 AUTO_INCREMENT=1;
			");
			$cnt = 0;

			foreach ($db->GetAll('SELECT * FROM scheduler_tasks') as $taskOld) {
				$task = Scalr_SchedulerTask::init();
				$task->name = $taskOld['task_name'];
				$task->type = $taskOld['task_type'];
				$task->targetId = $taskOld['target_id'];
				$task->targetType = $taskOld['target_type'];
				$task->timezone = $taskOld['timezone'];

				$timezone = new DateTimeZone($taskOld['timezone']);
				$startTm = new DateTime($taskOld['start_time_date']);
				$endTm = new DateTime($taskOld['end_time_date']);
				$lastStartTm = new DateTime($taskOld['last_start_time']);

				// old time in timezone (from record) to server time (timezone leave for UI)
				Scalr_Util_DateTime::convertDateTime($startTm, null, $timezone);
				Scalr_Util_DateTime::convertDateTime($endTm, null, $timezone);
				Scalr_Util_DateTime::convertDateTime($lastStartTm, null, $timezone);

				$task->startTime = $startTm->format('Y-m-d H:i:s');
				$task->endTime = $endTm->format('Y-m-d H:i:s');
				$task->lastStartTime = $taskOld['last_start_time'] ? $lastStartTm->format('Y-m-d H:i:s') : NULL;

				switch($taskOld['target_type']) {
					case SCRIPTING_TARGET::FARM:
						try {
							$DBFarm = DBFarm::LoadByID($taskOld['target_id']);
						} catch ( Exception  $e) {
							continue 2;
						}
						break;

					case SCRIPTING_TARGET::ROLE:
						try {
							$DBFarmRole = DBFarmRole::LoadByID($taskOld['target_id']);
							$a = $DBFarmRole->GetRoleObject()->name;
							$a = $DBFarmRole->FarmID;
							$a = $DBFarmRole->GetFarmObject()->Name;
						} catch (Exception $e) {
							continue 2;
						}
						break;

					case SCRIPTING_TARGET::INSTANCE:
						$serverArgs = explode(':', $taskOld['target_id']);
						try {
							$DBServer = DBServer::LoadByFarmRoleIDAndIndex($serverArgs[0], $serverArgs[1]);
							$a = "({$DBServer->remoteIp})";
							$DBFarmRole = $DBServer->GetFarmRoleObject();
							$a = $DBServer->farmId;
							$a = $DBFarmRole->GetFarmObject()->Name;
							$a = $DBServer->farmRoleId;
							$a = $DBFarmRole->GetRoleObject()->name;
						} catch(Exception $e) {
							continue 2;
						}
						break;
				}

				$config = unserialize($taskOld['task_config']);
				$r = array();
				switch ($task->type) {
					case Scalr_SchedulerTask::SCRIPT_EXEC:
						$r['scriptId'] = (string)$config['script_id']; unset($config['script_id']);
						$r['scriptIsSync'] = (string)$config['issync']; unset($config['issync']);
						$r['scriptTimeout'] = (string)$config['timeout']; unset($config['timeout']);
						$r['scriptVersion'] = (string)$config['revision']; unset($config['revision']);
						$r['scriptOptions'] = $config;
						break;
					case Scalr_SchedulerTask::LAUNCH_FARM:
						break;
					case Scalr_SchedulerTask::TERMINATE_FARM:
						$r['deleteDNSZones'] = $config['deleteDNS'];
						$r['deleteCloudObjects'] = ($config['keep_elastic_ips'] == '1' || $config['keep_ebs'] == '1') ? NULL : '1';
						break;
				}
				$task->config = $r;

				$task->restartEvery = $taskOld['restart_every'];
				$task->orderIndex = $taskOld['order_index'];
				$task->status = $taskOld['status'];
				$task->accountId = $taskOld['client_id'];
				$task->envId = $taskOld['env_id'];

				$task->save();
			}

			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{

		}
	}
