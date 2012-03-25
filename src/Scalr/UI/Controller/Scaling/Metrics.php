<?php

class Scalr_UI_Controller_Scaling_Metrics extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'metricId';

	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function getList()
	{
		$dbmetrics = $this->db->Execute("SELECT * FROM scaling_metrics WHERE env_id=0 OR env_id=?",
			array($this->getEnvironmentId())
		);

		$metrics = array();
		while ($metric = $dbmetrics->FetchRow())
		{
			$metrics[$metric['id']] = array(
				'id'	=> $metric['id'],
				'name'	=> $metric['name'],
				'alias'	=> $metric['alias']
			);
		}

		return $metrics;
	}

	public function xGetListAction()
	{
		$this->response->data(array('metrics' => $this->getList()));
	}

	public function getListAction()
	{
		$this->response->data(array('metrics' => $this->getList()));
	}

	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'metricId' => array('type' => 'int'),
			'name', 'filePath', 'retrieveMethod', 'calcFunction'
		));

		$metric = Scalr_Scaling_Metric::init();

		if ($this->getParam('metricId')) {
			$metric->loadById($this->getParam('metricId'));
			$this->user->getPermissions()->validate($metric);
		} else {
			$metric->clientId = $this->user->getAccountId();
			$metric->envId = $this->getEnvironmentId();
			$metric->alias = 'custom';
			$metric->algorithm = Scalr_Scaling_Algorithm::SENSOR_ALGO;
		}

		if (!preg_match("/^[A-Za-z0-9]{6,}/", $this->getParam('name')))
			throw new Exception("Metric name should me alphanumeric and greater than 5 chars");

		$metric->name = $this->getParam('name');
		$metric->filePath = $this->getParam('filePath');
		$metric->retrieveMethod = $this->getParam('retrieveMethod');
		$metric->calcFunction = $this->getParam('calcFunction');

		$metric->save();
		$this->response->success('Scaling metric successfully saved');
	}

	public function xRemoveAction()
	{
		$this->request->defineParams(array(
			'metrics' => array('type' => 'json')
		));

		foreach ($this->getParam('metrics') as $metricId) {
			if (!$this->db->GetOne("SELECT id FROM farm_role_scaling_metrics WHERE metric_id=?", array($metricId)))
				$this->db->Execute("DELETE FROM scaling_metrics WHERE id=? AND env_id=?", array($metricId, $this->getEnvironmentId()));
			else
				$err[] = sprintf(_("Metric #%s is used and cannot be removed"), $metricId);
		}

		if (count($err) == 0)
			$this->response->success('Selected metric(s) successfully removed');
		else
			$this->response->warning(implode('<br>', $err));
	}

	public function createAction()
	{
		$this->response->page('ui/scaling/metrics/create.js', array(
			'name' => '',
			'filePath' => '',
			'retrieveMethod' => '',
			'calcFunction' => ''
		));
	}

	public function editAction()
	{
		$this->request->defineParams(array(
			'metricId' => array('type' => 'int')
		));

		$metric = Scalr_Scaling_Metric::init()->loadById($this->getParam('metricId'));
		$this->user->getPermissions()->validate($metric);

		$this->response->page('ui/scaling/metrics/create.js', array(
			'name' => $metric->name,
			'filePath' => $metric->filePath,
			'retrieveMethod' => $metric->retrieveMethod,
			'calcFunction' => $metric->calcFunction
		));
	}

	public function viewAction()
	{
		$this->response->page('ui/scaling/metrics/view.js');
	}

	public function xListMetricsAction()
	{
		$this->request->defineParams(array(
			'metricId' => array('type' => 'int'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC')
		));

		$sql = "select * FROM scaling_metrics WHERE 1=1";
		$sql .= " AND (env_id='". $this->getEnvironmentId()."' OR env_id='0')";

		$response = $this->buildResponseFromSql($sql, array("name", "file_path"));
		$this->response->data($response);
	}
}
