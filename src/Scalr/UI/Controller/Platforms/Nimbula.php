<?php

class Scalr_UI_Controller_Platforms_Nimbula extends Scalr_UI_Controller
{
	public function xGetShapesAction()
	{
		$nimbula =  Scalr_Service_Cloud_Nimbula::newNimbula(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Nimbula::API_URL),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Nimbula::USERNAME),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Nimbula::PASSWORD)
		);

		$shapes = $nimbula->listShapes();
		$data = array();
		foreach ($shapes as $shape)
			$data[] = array(
				'id' => $shape->name,
				'name' => "{$shape->name} (CPUs: {$shape->cpus} RAM: {$shape->ram})"
			);

		$this->response->data(array('data' => $data));
	}
}
