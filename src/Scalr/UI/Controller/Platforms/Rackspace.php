<?php

class Scalr_UI_Controller_Platforms_Rackspace extends Scalr_UI_Controller
{
	public function xGetFlavorsAction()
	{
		$cs = Scalr_Service_Cloud_Rackspace::newRackspaceCS(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rackspace::USERNAME, true, $this->getParam('cloudLocation')),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Rackspace::API_KEY, true, $this->getParam('cloudLocation')),
			$this->getParam('cloudLocation')
		);

		$data = array();
		foreach ($cs->listFlavors(true)->flavors as $flavor) {
			$data[] = array(
				'id' => $flavor->id,
				'name' => sprintf('RAM: %s MB Disk: %s GB', $flavor->ram, $flavor->disk)
			);
		}

		$this->response->data(array('data' => $data));
	}
}
