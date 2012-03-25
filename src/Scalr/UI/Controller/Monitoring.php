<?php

class Scalr_UI_Controller_Monitoring extends Scalr_UI_Controller
{
	public function viewAction() 
	{		
		$farms = self::loadController('Farms')->getList(array('status' => FARM_STATUS::RUNNING));		
		$children = array();
		$hasServers = false;
		$hasRoles = false;
		foreach ($farms as $farm) {
			
			$this->request->setParam('farmId', $farm['id']);
			$farm['roles'] = self::loadController('Roles', 'Scalr_UI_Controller_Farms')->getList();
			
			if(!empty($farm['roles'])) 
				$hasRoles = true;
			
			$childrenRoles = array();
			foreach ($farm['roles'] as $role) {
				
				$this->request->setParam('farmRoleId', $role['id']);
				$role['servers'] = self::loadController('Servers')->getList(array(SERVER_STATUS::RUNNING, SERVER_STATUS::INIT));
				
				if(count($role['servers'])) 
					$hasServers = true;
				
				$servers = array();
				foreach($role['servers'] as $serv) {
					$servers[] = array(
						'text' => '#'.$serv['index']. ' ('.$serv['remote_ip'].')',
						'leaf' => true,
						'checked' => false,
						'itemId' => 'INSTANCE_'.$serv['farm_roleid'].'_'.$serv['index'],
						'value' => '#'.$serv['index'],
						'icon' => '/ui/images/space.gif'
					);
				}
				
				$ritem = array(
					'text' => 'role: '.$role['name'],
					'leaf' => true,
					'checked' => false,
					'itemId' => $role['id'],
					'value' => $role['name'],
					'icon' => '/ui/images/space.gif'
				);
				
				if ($hasServers) {
					$ritem['expanded'] = true;
					$ritem['leaf'] = false;
					$ritem['children'] = $servers;
				}
				
				$childrenRoles[] = $ritem;

				$hasServers = false;
			}
			
			$item = array(
				'text' => 'Farm: '.$farm['name'],
            	'itemId' => $farm['id'],
            	'value' => $farm['name'],
            	'checked' => false,
            	'leaf' => true,
				'icon' => '/ui/images/space.gif'
			);
			
			if ($hasRoles) {
				$item['expanded'] = true;
				$item['leaf'] = false;
				$item['children'] = $childrenRoles;
			}
			
			$children[] = $item;
			$hasRoles = false;
		}
		$this->response->page('ui/monitoring/view.js', array('children' => $children));
	}
}

