<?php

class Scalr_UI_Controller_Tools_Eucalyptus extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		$enabledPlatforms = $this->getEnvironment()->getEnabledPlatforms();
		if (!in_array(SERVER_PLATFORMS::EUCALYPTUS, $enabledPlatforms))
			throw new Exception("You need to enable Eucalyptus platform for current environment");

		return true;
	}
}
