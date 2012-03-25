<?php
class Scalr_UI_Controller_Services_Configurations_Presets extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'presetId';

	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function xGetListAction()
	{
		$this->request->defineParams(array(
			'behaviors' => array('type' => 'json')
		));

		$data = array();

		foreach ($this->getParam('behaviors') as $behavior) {
			$presets = $this->db->Execute("SELECT id, name FROM service_config_presets WHERE env_id = ? AND role_behavior=?", array(
					$this->getEnvironmentId(),
					$behavior
			));

			$itm = array();
			while ($preset = $presets->FetchRow())
				$itm[] = array('name' => $preset['name'], 'id' => $preset['id']);

			$data[$behavior] = $itm;
		}

		$this->response->data(array('data' => $data));
	}

	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'presetId' => array('type' => 'int'),
			'config'	=> array('type' => 'array')
		));

		if (!$this->getParam('presetId'))
		{
			if (!in_array($this->getParam('roleBehavior'), array(
				ROLE_BEHAVIORS::MYSQL,
				ROLE_BEHAVIORS::APACHE, 
				ROLE_BEHAVIORS::MEMCACHED,
				ROLE_BEHAVIORS::NGINX,
				ROLE_BEHAVIORS::REDIS))
			) {
				$err['roleBehavior'] = _("Please select service name");
			}

			if (!$this->getParam('presetName'))
				$err['presetName'] = _("Preset name required");
			else
			{
				if (strlen($this->getParam('presetName')) < 5)
					$err['presetName'] = _("Preset name should be 5 chars or longer");
				elseif (!preg_match("/^[A-Za-z0-9-]+$/", $this->getParam('presetName')))
					$err['presetName'] = _("Preset name should be alpha-numeric");
				elseif (strtolower($this->getParam('presetName')) == "default")
					$err['presetName'] = _("default is reserverd name");
				elseif ($this->getParam('roleBehavior') && $this->db->GetOne("SELECT id FROM service_config_presets WHERE name = ? AND role_behavior = ? AND id != ? AND env_id = ?", array(
					$this->getParam('presetName'), $this->getParam('roleBehavior'), (int)$this->getParam('presetId'), $this->getEnvironmentId()
				)))
					$err['presetName'] = _("Preset with selected name already exists");
			}
		}

		if (count($err) == 0)
		{
			$serviceConfiguration = Scalr_ServiceConfiguration::init();

			if ($this->getParam('presetId')) {
				$serviceConfiguration->loadById($this->getParam('presetId'));
				$this->user->getPermissions()->validate($serviceConfiguration);
			} else {
				$serviceConfiguration->loadBy(array(
					'client_id'		=> $this->user->getAccountId(),
					'env_id'		=> $this->getEnvironmentId(),
					'name'			=> $this->getParam('presetName'),
					'role_behavior'	=> $this->getParam('roleBehavior')
				));
			}

			$config = $this->getParam('config');

			foreach ($config as $k=>$v) {
					$serviceConfiguration->setParameterValue($k, $v);
			}

			foreach ($serviceConfiguration->getParameters() as $param) {
				if ($param->getType() == 'boolean') {
					if (!$config[$param->getName()])
						$serviceConfiguration->setParameterValue($param->getName(), '0');
				}
			}

			$serviceConfiguration->name = $this->getParam('presetName');
			$serviceConfiguration->save();

			//TODO:
			$resetToDefaults = false;
			Scalr::FireEvent(null, new ServiceConfigurationPresetChangedEvent($serviceConfiguration, $resetToDefaults));

			$this->response->success('Preset successfully saved');
		}
		else {
			$this->response->failure();
			$this->response->data(array('errors' => $err));
		}
	}

	public function editAction()
	{
		$this->request->defineParams(array(
			'presetId' => array('type' => 'int')
		));

		$this->buildAction();
	}

	public function buildAction()
	{
		$moduleParams = array();
		if ($this->getParam('presetId')) {
			$serviceConfiguration = Scalr_ServiceConfiguration::init()->loadById($this->getParam('presetId'));
			$this->user->getPermissions()->validate($serviceConfiguration);

			$moduleParams = array(
				'presetId'		=> $serviceConfiguration->id,
				'presetName'	=> $serviceConfiguration->name,
				'roleBehavior'	=> $serviceConfiguration->roleBehavior
			);
		}
		else {
			$moduleParams = array(
				'presetId'		=> 0,
				'presetName'	=> '',
				'roleBehavior'	=> ''
			);
		}

		$this->response->page('ui/services/configurations/presets/build.js', $moduleParams);
	}

	public function xGetPresetOptionsAction()
	{
		$this->request->defineParams(array(
			'presetId' => array('type' => 'int')
		));

		$serviceConfiguration = Scalr_ServiceConfiguration::init();

		if ($this->getParam('presetId')) {
			$serviceConfiguration->loadById($this->getParam('presetId'));
			$this->user->getPermissions()->validate($serviceConfiguration);
		} else {
			$serviceConfiguration->loadBy(array(
				'client_id'		=> $this->user->getAccountId(),
				'env_id'		=> $this->getEnvironmentId(),
				'name'			=> $this->getParam('presetName'),
				'role_behavior'	=> $this->getParam('roleBehavior')
			));
		}

		$items = $serviceConfiguration->getJsParameters();
		$this->response->data(array('presetOptions' => $items));
	}

	public function xRemoveAction()
	{
		$this->request->defineParams(array(
			'presets' => array('type' => 'json')
		));

		foreach ($this->getParam('presets') as $presetId) {
			if (!$this->db->GetOne("SELECT id FROM farm_role_service_config_presets WHERE preset_id=?", array($presetId)))
			{
				try {
					$serviceConfiguration = Scalr_ServiceConfiguration::init()->loadById($presetId);
					$this->user->getPermissions()->validate($serviceConfiguration);
					$serviceConfiguration->delete();
				}
				catch (Exception $e) {}
			}
			else
				$err[] = sprintf(_("Preset id #%s assigned to role and cannot be removed."), $presetId);
		}

		if (count($err) == 0)
			$this->response->success();
		else
			$this->response->warning(implode('<br>', $err));
	}

	public function viewAction()
	{
		$this->response->page('ui/services/configurations/presets/view.js');
	}

	public function xListPresetsAction()
	{
		$this->request->defineParams(array(
			'presetId' => array('type' => 'int'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC')
		));

		$sql = "select * FROM service_config_presets WHERE 1=1";
		$sql .= " AND env_id='". $this->getEnvironmentId() ."'";

		if ($this->getParam('presetId'))
			$sql .= " AND id=".$this->db->qstr($this->getParam('presetId'));

		$response = $this->buildResponseFromSql($sql, array("name", "role_behavior"));

		$this->response->data($response);
	}
}
