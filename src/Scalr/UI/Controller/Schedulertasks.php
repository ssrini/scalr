<?php

class Scalr_UI_Controller_Schedulertasks extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'schedulerTaskId';

	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function viewAction()
	{
		$this->response->page('ui/schedulertasks/view.js', array(
			'oldTasks' => $this->db->GetOne('SELECT COUNT(*) FROM scheduler_tasks WHERE client_id = ?', array($this->user->getAccountId())) > 0 ? true : false
		));
	}

	public function createAction()
	{
		$this->response->page('ui/schedulertasks/create.js', array(
			'farmRoles' => array(
				'farms' => self::loadController('Farms')->getList(),
				'farmRoles' => array(),
				'servers' => array()
			),
			'timezones' => Scalr_Util_DateTime::getTimezones(),
			'scripts' => self::loadController('Scripts')->getList(),
			'defaultTimezone' => $this->getEnvironment()->getPlatformConfigValue(Scalr_Environment::SETTING_TIMEZONE)
		));
	}

	public function editAction()
	{
		$this->request->defineParams(array(
			'schedulerTaskId' => array('type' => 'int')
		));

		//$DBFarmRole->FarmID;
		$task = Scalr_SchedulerTask::init();
		$task->loadById($this->getParam(self::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($task);

		$taskValues = array(
			'targetId' => $task->targetId,
			'targetType' => $task->targetType,
			'id' => $task->id,
			'name' => $task->name,
			'type' => $task->type,
			'config' => $task->config,
			'startTime' => $task->startTime ? Scalr_Util_DateTime::convertDateTime(new DateTime($task->startTime), $task->timezone)->format('Y-m-d H:i') : '',
			'endTime' => $task->endTime ? Scalr_Util_DateTime::convertDateTime(new DateTime($task->endTime), $task->timezone)->format('Y-m-d H:i') : '',
			'restartEvery' => $task->restartEvery,
			'timezone' => $task->timezone
		);

		$farmRoles = array(
			'farms' => self::loadController('Farms')->getList(),				
			'farmRoles' => array(),
			'servers' => array()
		);

		switch($task->targetType) {
			case Scalr_SchedulerTask::TARGET_FARM:
				try {
					$farmRoles['farmId'] = $task->targetId;
					$this->request->setParams(array('farmId' => $task->targetId));
					$farmRole = self::loadController('Roles', 'Scalr_UI_Controller_Farms')->getList();
					if (count($farmRole)) {
						$farmRole[0] = array('id' => '0', 'name' => 'On all roles');
						$farmRoles['farmRoleId'] = '0';
					}
					$farmRoles['farmRoles'] = $farmRole;
				} catch (Exception $e) {}
			break;

			case Scalr_SchedulerTask::TARGET_ROLE:
				try {
					$DBFarmRole = DBFarmRole::LoadByID($task->targetId);
					$farmRoles['farmId'] = $DBFarmRole->FarmID;
					$farmRoles['farmRoleId'] = $task->targetId;
					
					$this->request->setParams(array('farmId' => $DBFarmRole->FarmID));
					
					$farmRole = self::loadController('Roles', 'Scalr_UI_Controller_Farms')->getList();

					if (count($farmRole))
						$farmRole[0] = array('id' => '0', 'name' => 'On all roles');

					$farmRoles['farmRoles'] = $farmRole;

					$servers = array();
					foreach ($DBFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $key => $value)
						$servers[$value->serverId] = $value->remoteIp;

					$farmRoles['servers'] = $servers;
					if (count($servers)) {
						$farmRoles['servers'][0] = 'On all servers';
						$farmRoles['serverId'] = '0';
					}


				} catch (Exception $e) {}
				break;

			case Scalr_SchedulerTask::TARGET_INSTANCE:
				$serverArgs = explode(':', $task->targetId);
				try {
					$DBServer = DBServer::LoadByFarmRoleIDAndIndex($serverArgs[0], $serverArgs[1]);
					$farmRoles['serverId'] = $DBServer->serverId;
					$farmRoles['farmRoleId'] = $DBServer->farmRoleId;
					$farmRoles['farmId'] = $DBServer->farmId;
					
					$this->request->setParams(array('farmId' => $DBServer->farmId));
					
					$farmRole = self::loadController('Roles', 'Scalr_UI_Controller_Farms')->getList();
					if (count($farmRole))
						$farmRole[0] = array('id' => '0', 'name' => 'On all roles');
					$farmRoles['farmRoles'] = $farmRole;

					$servers = array();
					$dbFarmRole = DBFarmRole::LoadByID($DBServer->farmRoleId);
					foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $key => $value)
						$servers[$value->serverId] = $value->remoteIp;
					
					$farmRoles['servers'] = $servers;
					if (count($servers))
						$farmRoles['servers'][0] = 'On all servers';
					
					
				} catch(Exception $e) {}
				break;

			default: break;
		}

		$this->response->page('ui/schedulertasks/create.js', array(
			'farmRoles' => $farmRoles,
			'timezones' => Scalr_Util_DateTime::getTimezones(),
			'scripts' => self::loadController('Scripts')->getList(),
			'defaultTimezone' => $this->getEnvironment()->getPlatformConfigValue(Scalr_Environment::SETTING_TIMEZONE),
			'task' => $taskValues
		));
	}

	public function xListTasksAction()
	{
		$this->request->defineParams(array(
			'sort' => array('type' => 'json')
		));

		$sql = 'SELECT id, name, type, target_id as targetId, target_type as targetType, start_time as startTime,
			end_time as endTime, last_start_time as lastStartTime, restart_every as restartEvery, config, order_index as orderIndex,
			status, timezone FROM `scheduler` WHERE `env_id` = ? AND :FILTER:';

		$response = $this->buildResponseFromSql(
			$sql,
			array('id', 'name', 'type', 'startTime', 'endTime', 'lastStartTime', 'timezone', 'orderIndex', 'status', 'timezone'),
			array('id', 'name'),
			array($this->getEnvironmentId())
		);

		foreach ($response['data'] as &$row) {
			switch($row['targetType']) {
				case Scalr_SchedulerTask::TARGET_FARM:
					try {
						$DBFarm = DBFarm::LoadByID($row['targetId']);
						$row['targetName'] = $DBFarm->Name;
					} catch ( Exception  $e) {}
					break;

				case Scalr_SchedulerTask::TARGET_ROLE:
					try {
						$DBFarmRole = DBFarmRole::LoadByID($row['targetId']);
						$row['targetName'] = $DBFarmRole->GetRoleObject()->name;
						$row['targetFarmId'] = $DBFarmRole->FarmID;
						$row['targetFarmName'] = $DBFarmRole->GetFarmObject()->Name;
					} catch (Exception $e) {}
					break;

				case Scalr_SchedulerTask::TARGET_INSTANCE:
					$serverArgs = explode(':', $row['targetId']);
					try {
						$DBServer = DBServer::LoadByFarmRoleIDAndIndex($serverArgs[0],$serverArgs[1]);
						$row['targetName'] = "({$DBServer->remoteIp})";
						$DBFarmRole = $DBServer->GetFarmRoleObject();
						$row['targetFarmId'] = $DBServer->farmId;
						$row['targetFarmName'] = $DBFarmRole->GetFarmObject()->Name;
						$row['targetRoleId'] = $DBServer->farmRoleId;
						$row['targetRoleName'] = $DBFarmRole->GetRoleObject()->name;
					} catch(Exception $e) {}
					break;

				default: break;
			}

			$row['type'] = Scalr_SchedulerTask::getTypeByName($row['type']);
			$row['startTime'] = $row['startTime'] ? Scalr_Util_DateTime::convertDateTime(new DateTime($row['startTime']), $row['timezone'])->format('M j, Y H:i:s') : 'Now';
			$row['endTime'] = $row['endTime'] ? Scalr_Util_DateTime::convertDateTime(new DateTime($row['endTime']), $row['timezone'])->format('M j, Y H:i:s') : 'Never';
			$row['lastStartTime'] = $row['lastStartTime'] ? Scalr_Util_DateTime::convertDateTime(new DateTime($row['lastStartTime']), $row['timezone'])->format('M j, Y H:i:s') : '';

			$row['config'] = unserialize($row['config']);
			$row['config']['scriptName'] = $this->db->GetOne("SELECT name FROM scripts WHERE id=?", array($row['config']['scriptId']));
		}

		$this->response->data($response);
	}

	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'id' => array('type' => 'integer'),
			'name' => array('type' => 'string', 'validator' => array(
				Scalr_Validator::REQUIRED => true,
				Scalr_Validator::NOHTML => true
			)),
			'type' => array('type' => 'string', 'validator' => array(
				Scalr_Validator::RANGE => array(
					Scalr_SchedulerTask::SCRIPT_EXEC,
					Scalr_SchedulerTask::LAUNCH_FARM,
					Scalr_SchedulerTask::TERMINATE_FARM
				),
				Scalr_Validator::REQUIRED => true
			)),
			'startTime', 'endTime', 'restartEvery',
			'timezone' => array('type' => 'string', 'validator' => array(
				Scalr_Validator::REQUIRED => true
			)),
			'farmId' => array('type' => 'integer'),
			'farmRoleId' => array('type' => 'integer'),
			'serverId' => array('type' => 'string')
		));

		$task = Scalr_SchedulerTask::init();
		if ($this->getParam('id')) {
			$task->loadById($this->getParam('id'));
			$this->user->getPermissions()->validate($task);
		} else {
			$task->accountId = $this->user->getAccountId();
			$task->envId = $this->getEnvironmentId();
			$task->status = Scalr_SchedulerTask::STATUS_ACTIVE;
		}
		
		$this->request->validate();
		$params = array();

		$timezone = new DateTimeZone($this->getParam('timezone'));
		$startTm = $this->getParam('startTime') ? new DateTime($this->getParam('startTime')) : NULL;
		$endTm = $this->getParam('endTime') ? new DateTime($this->getParam('endTime')) : NULL;

		if ($startTm)
			Scalr_Util_DateTime::convertDateTime($startTm, NULL, $timezone);

		if ($endTm)
			Scalr_Util_DateTime::convertDateTime($endTm, NULL, $timezone);

		if ($startTm && $endTm && $endTm < $startTm)
			$this->request->addValidationErrors('endTimeDate', array('End time must be greater then start time'));

		$curTm = new DateTime();
		if ($startTm && $startTm < $curTm)
			$this->request->addValidationErrors('startTimeDate', array('Start time must be greater then current time'));

		switch ($this->getParam('type')) {
			case Scalr_SchedulerTask::SCRIPT_EXEC:
				if($this->getParam('serverId')) {
					$dbServer = DBServer::LoadByID($this->getParam('serverId'));
					$this->user->getPermissions()->validate($dbServer);
					$task->targetId = $this->getParam('farmRoleId').':'.$dbServer->index;
					$task->targetType = Scalr_SchedulerTask::TARGET_INSTANCE;
				}
				else {
					if($this->getParam('farmRoleId')) {
						$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
						$this->user->getPermissions()->validate($dbFarmRole);
						$task->targetId = $this->getParam('farmRoleId');
						$task->targetType = Scalr_SchedulerTask::TARGET_ROLE;
					}
					else {
						if ($this->getParam('farmId')) {
							$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
							$this->user->getPermissions()->validate($dbFarm);
							$task->targetId = $dbFarm->ID;
							$task->targetType = Scalr_SchedulerTask::TARGET_FARM;
						}
						else {
							$this->request->addValidationErrors('farmId', array('Farm ID is required'));
						}
					}
				}
				if ($this->getParam('scriptId')) {
					// TODO: check scriptId and other vars
					$params['scriptId'] = $this->getParam('scriptId');
					$params['scriptIsSync'] = $this->getParam('scriptIsSync');
					$params['scriptTimeout'] = $this->getParam('scriptTimeout');
					$params['scriptVersion'] = $this->getParam('scriptVersion');
					$params['scriptOptions'] = $this->getParam('scriptOptions');
				} else {
					$this->request->addValidationErrors('scriptId', array('Script ID is required'));
				}
				
				break;
				
			case Scalr_SchedulerTask::LAUNCH_FARM:
				if ($this->getParam('farmId')) {
					$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
					$this->user->getPermissions()->validate($dbFarm);
					$task->targetId = $dbFarm->ID;
					$task->targetType = Scalr_SchedulerTask::TARGET_FARM;
				} else {
					$this->request->addValidationErrors('farmId', array('Farm ID is required'));
				}
				break;
				
			case Scalr_SchedulerTask::TERMINATE_FARM:
				if ($this->getParam('farmId')) {
					$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
					$this->user->getPermissions()->validate($dbFarm);
					$task->targetId = $dbFarm->ID;
					$task->targetType = Scalr_SchedulerTask::TARGET_FARM;
				} else {
					$this->request->addValidationErrors('farmId', array('Farm ID is required'));
				}
				$params['deleteDNSZones'] = $this->getParam('deleteDNSZones');
				$params['deleteCloudObjects'] = $this->getParam('deleteCloudObjects');
				break;
		}

		if (! $this->request->isValid()) {
			$this->response->failure();
			$this->response->data($this->request->getValidationErrors());
			return;
		}
		
		$task->name = $this->getParam('name');
		$task->type = $this->getParam('type');
		$task->timezone = $this->getParam('timezone');
		$task->startTime = $startTm ? $startTm->format('Y-m-d H:i:s') : NULL;
		$task->endTime = $endTm ? $endTm->format('Y-m-d H:i:s') : NULL;
		$task->restartEvery = $this->getParam('restartEvery');
		$task->config = $params;
		
		$task->save();
		$this->response->success();
	}

	public function xActivateAction()
	{
		$this->request->defineParams(array(
			'tasks' => array('type' => 'json')
		));

		foreach ($this->getParam('tasks') as $taskId) {
			$task = Scalr_SchedulerTask::init()->loadById($taskId);
			$this->user->getPermissions()->validate($task);

			if ($task->status == Scalr_SchedulerTask::STATUS_FINISHED)
				continue;

			$task->status = Scalr_SchedulerTask::STATUS_ACTIVE;
			$task->save();
		}

		$this->response->success("Selected task(s) successfully activated");
	}

	public function xSuspendAction()
	{
		$this->request->defineParams(array(
			'tasks' => array('type' => 'json')
		));

		foreach ($this->getParam('tasks') as $taskId) {
			$task = Scalr_SchedulerTask::init()->loadById($taskId);
			$this->user->getPermissions()->validate($task);

			if ($task->status == Scalr_SchedulerTask::STATUS_FINISHED)
				continue;

			$task->status = Scalr_SchedulerTask::STATUS_SUSPENDED;
			$task->save();
		}

		$this->response->success("Selected task(s) successfully suspended");
	}

	public function xDeleteAction()
	{
		$this->request->defineParams(array(
			'tasks' => array('type' => 'json')
		));

		foreach ($this->getParam('tasks') as $taskId) {
			$task = Scalr_SchedulerTask::init()->loadById($taskId);
			$this->user->getPermissions()->validate($task);
			$task->delete();
		}

		$this->response->success("Selected task(s) successfully removed");
	}

	public function xTransferAction()
	{
		foreach ($this->db->GetAll('SELECT * FROM scheduler_tasks WHERE client_id = ?', array($this->user->getAccountId())) as $taskOld) {
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

		$this->db->Execute('DELETE FROM scheduler_tasks WHERE client_id = ?', array($this->user->getAccountId()));

		$this->response->success('All tasks transfered successfully');
	}
}
