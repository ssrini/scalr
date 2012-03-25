<?php

class Scalr_UI_Controller_Bundletasks extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'bundleTaskId';

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
		$this->response->page('ui/bundletasks/view.js');
	}

	public function xCancelAction()
	{
		$this->request->defineParams(array(
			'bundleTaskId' => array('type' => 'int')
		));

		$task = BundleTask::LoadById($this->getParam('bundleTaskId'));
		$this->user->getPermissions()->validate($task);

		if (in_array($task->status, array(
			SERVER_SNAPSHOT_CREATION_STATUS::CANCELLED,
			SERVER_SNAPSHOT_CREATION_STATUS::FAILED,
			SERVER_SNAPSHOT_CREATION_STATUS::SUCCESS)
		))
			throw new Exception('Selected task cannot be cancelled');

		$task->SnapshotCreationFailed('Cancelled by client');
		$this->response->success(_('Bundle task successfully cancelled.'));
	}

	public function logsAction()
	{
		$this->request->defineParams(array(
			'bundleTaskId' => array('type' => 'int')
		));

		$task = BundleTask::LoadById($this->getParam('bundleTaskId'));
		$this->user->getPermissions()->validate($task);

		$this->response->page('ui/bundletasks/logs.js');
	}

	public function xListLogsAction()
	{
		$this->request->defineParams(array(
			'bundleTaskId' => array('type' => 'int'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'DESC')
		));

		$task = BundleTask::LoadById($this->getParam('bundleTaskId'));
		$this->user->getPermissions()->validate($task);

		$sql = "SELECT * FROM bundle_task_log WHERE bundle_task_id = " . $this->db->qstr($this->getParam('bundleTaskId'));
		$response = $this->buildResponseFromSql($sql, array("message"));
		foreach ($response["data"] as &$row) {
			$row['dtadded'] = Scalr_Util_DateTime::convertTz($row['dtadded']);
		}
		
		$this->response->data($response);
	}

	public function failureDetailsAction()
	{
		$this->request->defineParams(array(
			'bundleTaskId' => array('type' => 'int')
		));

		$task = BundleTask::LoadById($this->getParam('bundleTaskId'));
		$this->user->getPermissions()->validate($task);

		$this->response->page('ui/bundletasks/failuredetails.js', array(
			'failureReason' => $task->failureReason
		));
	}

	public function xListTasksAction()
	{
		$this->request->defineParams(array(
			'bundleTaskId' => array('type' => 'int'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'DESC')
		));

		$sql = "SELECT * FROM bundle_tasks WHERE env_id = '" . $this->getEnvironmentId() . "'";

		if ($this->getParam('id') > 0)
			$sql .= " AND id = " . $this->db->qstr($this->getParam('bundleTaskId'));

		$response = $this->buildResponseFromSql($sql, array("server_id", "rolename", "failure_reason", "snapshot_id", "id"));

		foreach ($response["data"] as &$row) {
			$row['server_exists'] = DBServer::IsExists($row['server_id']);
			
			$row['dtadded'] = Scalr_Util_DateTime::convertTz($row['dtadded']);
			$row['dtstarted'] = Scalr_Util_DateTime::convertTz($row['dtstarted']);
			$row['dtfinished'] = Scalr_Util_DateTime::convertTz($row['dtfinished']);
		}

		$this->response->data($response);
	}
}
