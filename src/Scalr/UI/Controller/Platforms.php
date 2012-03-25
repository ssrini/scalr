<?php

class Scalr_UI_Controller_Platforms extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		return true;
	}

	public function getCloudLocations($platforms, $allowAll = true)
	{
		$ePlatforms = array();
		$locations = array();

		if (is_string($platforms))
			$platforms = explode(',', $platforms);

		if ($allowAll)
			$locations[''] = 'All';

		if ($this->getEnvironment())
			$ePlatforms = $this->getEnvironment()->getEnabledPlatforms();
		else
			$ePlatforms = array_keys(SERVER_PLATFORMS::GetList());

		if (implode('', $platforms) != 'all')
			$ePlatforms = array_intersect($ePlatforms, $platforms);

		foreach ($ePlatforms as $platform) {
			foreach (PlatformFactory::NewPlatform($platform)->getLocations() as $key => $loc)
				$locations[$key] = $loc;
		}

		return $locations;
	}

	public function getEnabledPlatforms($addLocations = false)
	{
		$ePlatforms = $this->getEnvironment()->getEnabledPlatforms();
		$lPlatforms = SERVER_PLATFORMS::GetList();
		$platforms = array();

		foreach ($ePlatforms as $platform)
			$platforms[$platform] = $addLocations ?
				array('id' => $platform, 'name' => $lPlatforms[$platform], 'locations' => PlatformFactory::NewPlatform($platform)->getLocations()) :
				$lPlatforms[$platform];

		return $platforms;
	}
}
