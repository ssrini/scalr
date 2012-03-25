<?php

class Scalr_UI_Controller_Platforms_Cloudstack extends Scalr_UI_Controller
{
	public function xGetOfferingsListAction()
	{
		$cs = Scalr_Service_Cloud_Cloudstack::newCloudstack(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Cloudstack::API_URL),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Cloudstack::API_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Cloudstack::SECRET_KEY)
		);

		$data = array();
		foreach ($cs->listServiceOfferings() as $offering) {
			
			$data['serviceOfferings'][] = array(
				'id' => $offering->id,
				'name' => $offering->displaytext
			);
		}

		$accountName = $this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Cloudstack::ACCOUNT_NAME, false);
		$domainId = $this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Cloudstack::DOMAIN_ID, false);
		
		foreach ($cs->listNetworks("", $accountName, $domainId) as $network) {
			$data['networks'][] = array(
				'id' => $network->id,
				'name' => "{$network->id}: {$network->name} ({$network->networkdomain})"
			);
		}

		$this->response->data(array('data' => $data));
	}
}
