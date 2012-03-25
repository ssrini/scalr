<?php

class Scalr_UI_Controller_Services_Chef extends Scalr_UI_Controller
{
	public function xListRunListAction ()
	{
		$sql = 'SELECT id, name, description, attributes, chef_environment as chefEnv FROM services_chef_runlists WHERE `env_id` = '.$this->getEnvironmentId();

		$response = $this->buildResponseFromSql($sql);
		$this->response->data($response);
	}
	
	public function xListAllRecipesAction()
	{
		$servParams = $this->db->GetRow('SELECT url, username, auth_key FROM services_chef_servers WHERE id = ?', array($this->getParam('servId')));
		$chef = Scalr_Service_Chef_Client::getChef($servParams['url'], $servParams['username'], $this->getCrypto()->decrypt($servParams['auth_key'], $this->cryptoKey));
		if (!$this->getParam('chefEnv') || $this->getParam('chefEnv') == '_default')
            $response = $chef->listCookbooks();
		else
            $response = $chef->listCookbooks($this->getParam('chefEnv'));
		if ($response instanceof stdClass)
			$response = (array)$response;
		
		$recipes = array();
		foreach ($response as $key => $value) {
			$recipeList = $chef->listRecipes($key, '_latest');
			
			if ($recipeList instanceof stdClass)
				$recipeList = (array)$recipeList;
			
			foreach ($recipeList as $name => $recipeValue) {
				if ($name == 'recipes') {
					foreach ($recipeValue as $recipe) {
						$recipes[] = array(
							'cookbook' => $key,
							'name' => substr($recipe->name, 0, (strlen($recipe->name)-3))
						);
					}
				}
			}
		}
		sort($recipes);
		$this->response->data(array('data'=>$recipes));
	}
	
	public function xListRolesAction()
	{
		$servParams = $this->db->GetRow('SELECT url, username, auth_key FROM services_chef_servers WHERE id = ?', array($this->getParam('servId')));
		$chef = Scalr_Service_Chef_Client::getChef($servParams['url'], $servParams['username'], $this->getCrypto()->decrypt($servParams['auth_key'], $this->cryptoKey));
		$response = $chef->listRoles();
		
		if ($response instanceof stdClass)
		$response = (array)$response;
		
		$roles = array();
		foreach ($response as $key => $value) {
			$role = $chef->getRole($key);
			$roles[] = array(
				'name' => $role->name,
				'chef_type' => $role->chef_type
			);
		}
		sort($roles);

		$this->response->data(array('data'=>$roles));
	}
/*	public function xListRecipesAction()
	{
		$chef = Scalr_Service_Chef_Client::getChef($url, $username, $privateKey);
		$response = $chef->listRecipes($this->getParam('cbName'), $this->getParam('version'));
		
		if ($response instanceof stdClass)
		$response = (array)$response;
	
		$recipes = array();
		foreach ($response as $key => $value) {
			if($key == 'recipes'){
				foreach ($value as $recipe){
					$recipes[] = array(
						'cookbook' => $this->getParam('cbName'),
						'name' => substr($recipe->name, 0, (strlen($recipe->name)-3)),
						'specificity' => $recipe->specificity,
						'path' => $recipe->path
					);
				}
			}
		}
		$this->buildResponseFromData($recipes, 'name');
		$this->response->data(array('data'=>$recipes));
	}*/

/*public function xListCookbooksAction()
	{
		$chef = Scalr_Service_Chef_Client::getChef($url, $username, $privateKey);
		$response = $chef->listCookbooks();
		
		if ($response instanceof stdClass)
		$response = (array)$response;
		
		$cookbook = array();
		foreach ($response as $key => $value) {
			$versions = array();
			foreach($value->versions as $version){
				$versions[] = array(
					'version' => $version->version
				);
			}
			$cookbook[] = array(
				'name' => $key,
				'url' => $value->url,
				'version' => $versions[0]['version'],
				'versions' => $versions
			);
		}
		$this->buildResponseFromData($cookbook, 'name');
		$this->response->data(array('data'=>$cookbook));	
	}*/
}