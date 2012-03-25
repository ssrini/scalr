<?php

class Scalr_UI_Controller_Platforms_Eucalyptus extends Scalr_UI_Controller
{
	public function xGetAvailZonesAction()
	{
		$client = Scalr_Service_Cloud_Eucalyptus::newCloud(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Eucalyptus::SECRET_KEY, true, $this->getParam('cloudLocation')),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Eucalyptus::ACCESS_KEY, true, $this->getParam('cloudLocation')),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Eucalyptus::EC2_URL, true, $this->getParam('cloudLocation'))
		);

		$result = $client->describeAvailabilityZones();
		$data = array();

		foreach ($result->availabilityZoneInfo->item as $zone)
			$data[] = array('id' => (string) $zone->zoneName, 'name' => (string) $zone->zoneName);

		$this->response->data(array('data' => $data));
	}
}
