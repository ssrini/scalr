<?php

class Scalr_UI_Controller_Services_Chef_Runlists extends Scalr_UI_Controller
{
	public function defaultAction()
	{
		$this->viewAction();
	}
	
	public function viewAction()
	{
		$res = $this->db->getAll('SELECT distinct(chef_environment) AS env FROM services_chef_runlists WHERE env_id = ?', $this->getEnvironmentId());
		$envs = array();
		foreach($res as $value) {
			$envs[] = $value['env'];
		}
		$this->response->page('ui/services/chef/runlists/view.js', array('envs'=>$envs));
	}
	
	public function createAction()
	{
		$this->response->page('ui/services/chef/runlists/create.js');
	}
	
	public function editAction()
	{
		$retval = $this->db->GetRow('SELECT runlist, name, description, chef_server_id, attributes, chef_environment as chefEnv FROM services_chef_runlists WHERE `id` = ?', array(
			$this->getParam('runlistId')
		));
		$list = json_decode($retval['runlist']);
		$results = array();
		foreach ($list as $key=>$value) {
			$results[] = array(
				'name' => $value
			);
		}
		
		$runList  = array (
			'runlist' => $results,
			'runListName' => $retval['name'],
			'runListDescription' => $retval['description'],
			'runListAttrib' => $retval['attributes'],
			'chefServer' => $retval['chef_server_id'],
            'chefEnv' => $retval['chefEnv']
		);
		$this->response->page('ui/services/chef/runlists/create.js', array('runlistParams' => $runList));
	}
	
	public function xListRunlistsAction()
	{
		$sql = 'SELECT id, name, description, chef_server_id as servId, chef_environment as chefEnv FROM services_chef_runlists WHERE env_id = '.$this->getEnvironmentId();

		$response = $this->buildResponseFromSql($sql);
		$this->response->data($response);
	}
	
	public function xDeleteRunlistAction()
	{
		$sql = 'SELECT id FROM farm_role_settings WHERE name = "chef.runlist_id" AND value = '.$this->db->qstr($this->getParam('runlistId'));
		$result = $this->buildResponseFromSql($sql);
		if($result['total'])
			$this->response->failure('This runlist is in use. It can\'t be deleted');
		else {
			$servParams = $this->db->GetRow('SELECT url, username, auth_key FROM services_chef_servers WHERE id = ?', array(
				$this->getParam('servId')
			));
			$chef = Scalr_Service_Chef_Client::getChef($servParams['url'], $servParams['username'], $this->getCrypto()->decrypt($servParams['auth_key'], $this->cryptoKey));
			$role = $chef->getRole($this->getParam('runlistName'));
			if ($role instanceof stdClass)
				$role = (array)$role;
			$envRunlist = array();
			foreach ($role['env_run_lists'] as $key=>$value) {
				if($key != $this->getParam('chefEnv'))
				$envRunlist[$key] = $value;
			}
			$chef->updateRole(
				$this->getParam('runlistName'),
				$role['description'],
                $role['run_list'],
				$role['default_attributes'],
                $envRunlist
			);
			if($this->getParam('chefEnv') == '_default' && empty($envRunlist))
				$chef->removeRole($this->getParam('runlistName'));
				
			$this->db->Execute('DELETE FROM services_chef_runlists WHERE id = ?', array($this->getParam('runlistId')));
			$this->response->success('Runlist successfully deleted');
		}
	}
	
	public function xSaveRunListAction()
	{
		$this->request->defineParams(array(
			'runList' => array('type' => 'json'),
			'runListAttrib' => array('type' => 'json')
		));	
		$servParams = $this->db->GetRow('SELECT url, username, auth_key FROM services_chef_servers WHERE id = ?', array(
			$this->getParam('chefServer')
		));
		$chef = Scalr_Service_Chef_Client::getChef($servParams['url'], $servParams['username'], $this->getCrypto()->decrypt($servParams['auth_key'], $this->cryptoKey));
		$attrib = array();
		foreach ($this->getParam('runListAttrib') as $value) {
			$attrib[$value['name']] = $value['value'];
		}
		$attrib = empty($attrib) ? '' : json_encode($attrib);
        $envRunlist = array();
        $runList = $this->getParam('runList');
		
        if($this->getParam('chefEnv') && $this->getParam('chefEnv')!='_default') {
            if($this->getParam('runlistId')) {
                $roleRes = $chef->getRole($this->getParam('runListName'));
                if ($roleRes instanceof stdClass)
                    $roleRes = (array)$roleRes;
                $runList = $roleRes['run_list'];
                $envRunlist = (array)$roleRes['env_run_lists'];
            }
            $envRunlist[$this->getParam('chefEnv')] = $this->getParam('runList');
        }
		
		if($this->getParam('runlistId')) {
			$response = $chef->updateRole(
				$this->getParam('runListName'),
				$this->getParam('runListDescription'),
                $runList,
				$attrib,
                $envRunlist
			);
			if ($response instanceof stdClass) {
				if($this->db->getRow('SELECT * FROM services_chef_runlists WHERE name = ? AND `chef_environment` = ?  AND env_id = ?', array(
					$this->getParam('runListName'),
					$this->getParam('chefEnv'),
					$this->getEnvironmentId()
				)))
				$this->db->Execute("UPDATE services_chef_runlists SET chef_server_id = ?, name = ?, description = ?, runlist = ?, attributes = ?, `chef_environment` = ? WHERE id = ?", array(
					$this->getParam('chefServer'),
					$this->getParam('runListName'),
					$this->getParam('runListDescription'),
					json_encode($this->getParam('runList')),
					$this->getParam('runListAttrib'),
                    $this->getParam('chefEnv'),
					$this->getParam('runlistId')
				));
				else 
					$this->db->Execute('INSERT INTO services_chef_runlists (`env_id`, `chef_server_id`, `name`, `description`, `runlist`, `attributes`, `chef_environment`) VALUES (?, ?, ?, ?, ?, ?, ?)', array(
						$this->getEnvironmentId(),
						$this->getParam('chefServer'),
						$this->getParam('runListName'),
						$this->getParam('runListDescription'),
						json_encode($this->getParam('runList')),
						$this->getParam('runListAttrib'),
	                    $this->getParam('chefEnv')
					));
				
				$runListId = $this->getParam('runlistId');
				$this->response->success('RunList was succesfully updated');
			}
		} else {
			$response = $chef->createRole(
				$this->getParam('runListName'),
				$this->getParam('runListDescription'),
                $runList,
				$attrib,
                $envRunlist
			);
			$this->response->data(array('data'=>json_decode($response)));
			if ($response instanceof stdClass) {
				$this->db->Execute('INSERT INTO services_chef_runlists (`env_id`, `chef_server_id`, `name`, `description`, `runlist`, `attributes`, `chef_environment`) VALUES (?, ?, ?, ?, ?, ?, ?)', array(
					$this->getEnvironmentId(),
					$this->getParam('chefServer'),
					$this->getParam('runListName'),
					$this->getParam('runListDescription'),
					json_encode($this->getParam('runList')),
					$this->getParam('runListAttrib'),
                    $this->getParam('chefEnv')
				));
	
				$runListId = $this->db->Insert_ID();
				$this->response->success('RunList was succesfully created');
			}
			
		}
		if ($response instanceof stdClass) {
			$this->response->data(array(
				'runlistParams' => array(
						'id'=>$runListId, 
						'name'=>$this->getParam('runListName'), 
						'description'=>$this->getParam('runListDescription'), 
						'attributes'=>$this->getParam('runListAttrib'),
						'chefEnv'=>$this->getParam('chefEnv')
				)
			));
		}
	}

	public function xUpdateAttribAction() 
	{
		$this->db->Execute("UPDATE services_chef_runlists SET attributes = ? WHERE id = ?", array(
			$this->getParam('attributes'),
			$this->getParam('runlistId')
		));
		$this->response->data(array('success'=>true));
	}
	
	public function sourceAction()
	{
		$retval = $this->db->GetRow('SELECT runlist FROM services_chef_runlists WHERE `id` = ?', array(
			$this->getParam('runlistId')
		));
		
		$this->response->page('ui/services/chef/runlists/viewsource.js', array('runlist' => $retval['runlist']));
	}
}