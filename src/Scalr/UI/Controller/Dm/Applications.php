<?php
class Scalr_UI_Controller_Dm_Applications extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'applicationId';

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function saveAction()
	{
		$this->request->defineParams(array(
			'applicationId' => array('type' => 'int'),
			'sourceId' => array('type' => 'int'),
			'name', 'pre_deploy_script', 'post_deploy_script'
		));

		$application = Scalr_Dm_Application::init();
		if ($this->getParam('applicationId')) {
			$application->loadById($this->getParam('applicationId'));
			$this->user->getPermissions()->validate($application);
		}
		else {
			$application->envId = $this->getEnvironmentId();
		}

		$chkId = Scalr_Dm_Application::getIdByNameAndSource($this->getParam('name'), $this->getParam('sourceId'));
		if ((!$this->getParam('applicationId') && $chkId) || ($chkId && $chkId != $this->getParam('applicationId')))
			throw new Exception("Application already exists in database");

		$application->name = $this->getParam('name');
		$application->sourceId = $this->getParam('sourceId');

		$application->setPreDeployScript($this->getParam('pre_deploy_script'));
		$application->setPostDeployScript($this->getParam('post_deploy_script'));

		$application->save();

		$this->response->success('Application successfully saved');
        $this->response->data(array('app' => $application));
	}

	private function getSources()
	{
		$sources = self::loadController('Sources', 'Scalr_UI_Controller_Dm')->getList();

		if (count($sources) == 0)
			throw new Exception('You need to create application source first');

		return $sources;
	}

	public function xDeployAction()
	{
		$this->request->defineParams(array(
			'applicationId' => array('type' => 'int'),
			'farmId' 	   => array('type' => 'int'),
			'farmRoleId'   => array('type' => 'int'),
			'remotePath'
		));

		$application = Scalr_Dm_Application::init()->loadById($this->getParam('applicationId'));
		$this->user->getPermissions()->validate($application);

		$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
		$this->user->getPermissions()->validate($dbFarmRole);

		$servers = $dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));

		if (!$this->getParam('remotePath'))
			throw new Exception("Remote path reuired for deployment");
		
		if (count($servers) == 0)
			throw new Exception("There is no running servers on selected farm/role");

		foreach ($servers as $dbServer) {
			$deploymentTask = Scalr_Dm_DeploymentTask::init();

			$deploymentTask->create(
				$this->getParam('farmRoleId'),
				$this->getParam('applicationId'),
				$dbServer->serverId,
				Scalr_Dm_DeploymentTask::TYPE_MANUAL,
				$this->getParam('remotePath')
			);
		}

		$this->response->success('Deployment task created');
	}

	public function deployAction()
	{
		$application = Scalr_Dm_Application::init()->loadById($this->getParam('applicationId'));
		$this->user->getPermissions()->validate($application);

		$this->response->page('ui/dm/applications/deploy.js', array(
			'application_name'	=> $application->name,
			'farms'				=> self::loadController('Farms')->getList(),
			'farmRoles'			=> array()
		));
	}

	public function createAction()
	{
		$this->response->page('ui/dm/applications/create.js', array(
			'sources'	=> $this->getSources()
		));
	}

	public function editAction()
	{
		$this->request->defineParams(array(
			'applicationId' => array('type' => 'int')
		));

		$application = Scalr_Dm_Application::init()->loadById($this->getParam('applicationId'));
		$this->user->getPermissions()->validate($application);

		$this->response->page('ui/dm/applications/create.js', array(
			'sources'	=> $this->getSources(),
			'application' => array(
				'name'		=> $application->name,
				'id'		=> $application->id,
				'source_id'	=> $application->sourceId,
				'pre_deploy_script'	=> $application->getPreDeployScript(),
				'post_deploy_script'=> $application->getPostDeployScript()
			)
		));
	}

	public function viewAction()
	{
		$this->response->page('ui/dm/applications/view.js');
	}

	public function xRemoveApplicationsAction()
	{
		$this->request->defineParams(array(
			'applicationId' => array('type' => 'int')
		));

		$application = Scalr_Dm_Application::init()->loadById($this->getParam('applicationId'));
		$this->user->getPermissions()->validate($application);

		$application->delete();

		$this->response->success();
	}

	public function xGetApplicationsAction()
	{
		$sql = "SELECT id, name FROM dm_applications WHERE env_id = '{$this->getEnvironmentId()}'";

		if ($this->getParam('applicationId'))
			$sql .= ' AND id='.$this->db->qstr($this->getParam('applicationId'));

		$response = $this->buildResponseFromSql($sql, array("name"));

		foreach ($response["data"] as &$row) {
			//$row['source_url'] = $this->db->getOne("SELECT url FROM dm_sources WHERE id=?", array($row['source_id']));
			//$row['used_on'] = $this->db->getOne("SELECT COUNT(*) FROM dm_deployment_tasks WHERE dm_application_id=?", array($row['id']));
		}

		$this->response->data($response);
	}
	
	public function xListApplicationsAction()
	{
		$this->request->defineParams(array(
			'sort' => array('type' => 'json')
		));

		$sql = "SELECT name, dm_source_id as source_id, env_id, id FROM dm_applications WHERE env_id = '{$this->getEnvironmentId()}'";

		if ($this->getParam('applicationId'))
			$sql .= ' AND id='.$this->db->qstr($this->getParam('applicationId'));

		$response = $this->buildResponseFromSql($sql, array("name"));

		foreach ($response["data"] as &$row) {
			$row['source_url'] = $this->db->getOne("SELECT url FROM dm_sources WHERE id=?", array($row['source_id']));
			$row['used_on'] = $this->db->getOne("SELECT COUNT(*) FROM dm_deployment_tasks WHERE dm_application_id=?", array($row['id']));
		}

		$this->response->data($response);
	}
}
