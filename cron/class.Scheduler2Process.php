<?php
class Scheduler2Process implements IProcess
{
	public $ThreadArgs;
	public $ProcessDescription = "Schedule manager";
	public $Logger;

	public function __construct()
	{
		// Get Logger instance
		$this->Logger = Logger::getLogger(__CLASS__);
	}

	public function OnStartForking()
	{
		// start cron, which runs scripts by it's schedule queue 
		try {
			$db = Core::GetDBInstance(null, true);

			// set status to "finished" for active tasks, which ended or executed once
			$info = $db->Execute("UPDATE scheduler SET `status` = ? WHERE
				`status` = ? AND (`end_time` < NOW() OR (`last_start_time` < NOW() AND `restart_every` = 0))",
				array(Scalr_SchedulerTask::STATUS_FINISHED, Scalr_SchedulerTask::STATUS_ACTIVE)
			);

			// get active tasks: first run (condition and last_start_time is null), others (condition and last_start_time + interval * 0.9 < now())
			$taskList = $db->GetAll("SELECT *
				FROM scheduler
				WHERE `status` = ? AND (`end_time` > NOW() OR `end_time` IS NULL) AND (`start_time` <= NOW() OR `start_time` IS NULL) AND (
					`last_start_time` IS NULL OR
					`last_start_time` IS NOT NULL AND (last_start_time + INTERVAL (restart_every * 0.9 * 60) SECOND < NOW())
				)
				ORDER BY IF (last_start_time, last_start_time, start_time), order_index ASC
			", array(Scalr_SchedulerTask::STATUS_ACTIVE));

			if (!$taskList) {
				$this->Logger->info(_("There is no tasks to execute in scheduler table"));
				exit();
			}

			foreach ($taskList as $task) {
				// check account status (active or inactive)
				try {
					if (Scalr_Account::init()->loadById($task['account_id'])->status != Scalr_Account::STATUS_ACTIVE)
						continue;
				} catch (Exception $e) {
					$this->Logger->info("Invalid scheduler task #{$task['id']}: {$e->getMessage()}");
				}

				// check time
				$lastStartTime = $task['last_start_time'] ? strtotime($task['last_start_time']) : NULL;

				if ($lastStartTime) {
					if ($task['start_time'] == NULL) {
						// disallow execute earlier
						if (($lastStartTime + $task['restart_every'] * 60) > time())
							continue;
					} else {
						// try to auto-align time to start time
						$startTime = strtotime($task['start_time']);
						$num = (time() - $startTime) / ($task['restart_every'] * 60);

						/*file_put_contents("test.txt", print_r($task['name'], true), FILE_APPEND);
						file_put_contents("test.txt", " - ", FILE_APPEND);
						file_put_contents("test.txt", print_r(date('r', $lastStartTime), true), FILE_APPEND);
						file_put_contents("test.txt", " - ", FILE_APPEND);
						file_put_contents("test.txt", print_r(date('r'), true), FILE_APPEND);
						file_put_contents("test.txt", " - ", FILE_APPEND);
						file_put_contents("test.txt", print_r($num, true), FILE_APPEND);
						file_put_contents("test.txt", " - ", FILE_APPEND);
						file_put_contents("test.txt", print_r(floor($num), true), FILE_APPEND);
						file_put_contents("test.txt", " - ", FILE_APPEND);
						file_put_contents("test.txt", print_r(round($num, 0, PHP_ROUND_HALF_UP), true), FILE_APPEND);
						file_put_contents("test.txt", "\n", FILE_APPEND);*/

						// num sholud be less than 0.5
						if (floor($num) != round($num, 0, PHP_ROUND_HALF_UP))
							continue;
					}
				}

				// Terminate, Launch farm  or execute script
				$farmRoleNotFound = false;

				switch($task['type']) {
					case Scalr_SchedulerTask::LAUNCH_FARM:
						try {
							$farmId = $task['target_id'];
							$DBFarm = DBFarm::LoadByID($farmId);

							if ($DBFarm->Status == FARM_STATUS::TERMINATED) {
								// launch farm
								Scalr::FireEvent($farmId, new FarmLaunchedEvent(true));
								$this->Logger->info(sprintf("Farm #{$farmId} successfully launched"));
							} elseif($DBFarm->Status == FARM_STATUS::RUNNING) {
								// farm is running
								$this->Logger->info(sprintf("Farm #{$farmId} is already running"));
							} else {
								// farm can't be launched
								$this->Logger->info(sprintf("Farm #{$farmId} can't be launched because of it's status: {$DBFarm->Status}"));
							}
						} catch(Exception $e) {
							// farm  not found
							$farmRoleNotFound  = true;
							$this->Logger->info(sprintf("Farm #{$farmId} was not found and can't be launched"));
						}
						break;

					case SCHEDULE_TASK_TYPE::TERMINATE_FARM:
						try {
							// get config settings
							$farmId = $task['target_id'];

							$config = unserialize($task['config']);

							$deleteDNSZones = (int)$config['deleteDNSZones'];
							$deleteCloudObjects = (int)$config['deleteCloudObjects'];
							$keepCloudObjects = $deleteCloudObjects == 1 ? 0 : 1;

							$DBFarm = DBFarm::LoadByID($farmId);

							if($DBFarm->Status == FARM_STATUS::RUNNING) {
								// terminate farm
								$event = new FarmTerminatedEvent($deleteDNSZones, $keepCloudObjects, false, $keepCloudObjects);
								Scalr::FireEvent($farmId, $event);

								$this->Logger->info(sprintf("Farm successfully terminated"));
							} else {
								$this->Logger->info(sprintf("Farm #{$farmId} can't be terminated because of it's status"));
							}
						} catch(Exception $e) {
							// role not found
							$farmRoleNotFound  = true;		       	 					
							$this->Logger->info(sprintf("Farm #{$farmId} was not found and can't be terminated"));	  	
						}
						break;

					case SCHEDULE_TASK_TYPE::SCRIPT_EXEC:
						// generate event name
						$eventName = 'CustomEvent-' . date("YmdHi") . '-' . rand(1000,9999);
						$instances = array();

						try {
							// get variables for SQL INSERT or UPDATE
							$config = unserialize($task['config']);

							if (! $config['scriptId'])
								throw new Exception(_("Script %s is not existed"), $config['scriptId']);

							// check avaliable script version
							if (! $db->GetOne("SELECT id FROM script_revisions WHERE scriptid = ? AND revision = ? AND approval_state = ?",
								array($config['scriptId'], $config['scriptVersion'], APPROVAL_STATE::APPROVED))) {
								throw new Exception(_("Selected version is not approved or no longer available"));
							}

							// get executing object by target_type variable
							switch($task['target_type']) {
								case SCRIPTING_TARGET::FARM:
									$DBFarm = DBFarm::LoadByID($task['target_id']);
									$farmId = $DBFarm->ID;
									$farmRoleId = null;

									$servers = $db->GetAll("SELECT * FROM servers WHERE `status` IN (?,?) AND farm_id = ?",
										array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $farmId)
									);
									break;

								case SCRIPTING_TARGET::ROLE:
									$farmRoleId = $task['target_id'];

									$DBFarmRole = DBFarmRole::LoadByID($farmRoleId);
									$farmId = $DBFarmRole->GetFarmObject()->ID;

									$servers = $db->GetAll("SELECT * FROM servers WHERE `status` IN (?,?) AND farm_roleid = ?",
										array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $farmRoleId)
									);
									break;

								case SCRIPTING_TARGET::INSTANCE:
									$instanceArgs = explode(":", $task['target_id']);
									$farmRoleId = $instanceArgs[0];
									$DBFarmRole = DBFarmRole::LoadByID($farmRoleId);

									// target for instance looks like  "farm_roleid:index"
									// script gets farmid conformed to the roleid and index	
									$servers = $db->GetAll("SELECT * FROM servers WHERE `status` IN (?,?) AND farm_roleid = ? AND `index` = ? ",
										array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING, $farmRoleId, $instanceArgs[1])
									);
									$farmId = $servers[0]["farm_id"];
									break;
							}

							if ($servers) {
								$db->Execute("INSERT INTO farm_role_scripts SET
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
									ismenuitem	= ?
								", array(
									$config['scriptId'],
									$farmId,
									$farmRoleId,
									serialize($config['scriptOptions']),
									$eventName,
									$task['target_type'],
									$config['scriptVersion'],
									$config['scriptTimeout'],
									$config['scriptIsSync'],
									$task["order_index"],
									0
								));

								$farmRoleScriptId = $db->Insert_ID();

								// send message to start executing task (starts script)
								foreach ($servers as $server) {
									$DBServer = DBServer::LoadByID($server['server_id']);

									$msg = new Scalr_Messaging_Msg_ExecScript($eventName);
									$msg->meta[Scalr_Messaging_MsgMeta::EVENT_ID] = "FRSID-{$farmRoleScriptId}";
									$DBServer->SendMessage($msg);
								}
							}
						} catch (Exception $e) {
							// farm or role not found.
							$farmRoleNotFound  = true;
							$this->Logger->warn(sprintf("Farm, role or instances were not found, script can't be executed"));
						}
						break;
				}

				if ($farmRoleNotFound) {
					// delete task if farm or role not found.
					$db->Execute("DELETE FROM scheduler  WHERE id = ?", array($task['id']));
					$this->Logger->warn(sprintf("Task {$task['id']} was deleted, because of the farm or role was not found"));
				} else {
					$db->Execute("UPDATE  scheduler SET last_start_time = NOW() WHERE id = ?",
						array($task['id'])
					);

					$this->Logger->info(sprintf("Task {$task['id']} successfully sent"));
				}
			}
		} catch(Exception $e) {
			$this->Logger->warn(sprintf("Can't execute task {$task['id']}. Error message: %s",$e->getMessage()));		
		}
	}

	public function OnEndForking()
	{

	}
	public function StartThread($queue_name)
	{

	}
}
