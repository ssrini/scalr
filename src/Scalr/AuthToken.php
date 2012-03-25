<?php

	class Scalr_AuthToken
	{
		const SCALR_ADMIN = 0x1;
		const ACCOUNT_ADMIN = 0x2;
		const ACCOUNT_USER = 0x4;

		const MODULE_DNS = 'module.dns';
		const MODULE_ENVIRONMENTS = 'module.environments';
		const MODULE_VHOSTS	= 'module.vhosts';
		const MODULE_CONFIG_PRESETS = 'module.config_presets';

		protected $session;

		public function __construct(Scalr_Session $session)
		{
			$this->session = $session;
		}

		public function hasAccessEnvironment($envId)
		{
			return ($this->session->getEnvironmentId() == $envId || $this->hasAccess(self::SCALR_ADMIN));
		}

		public function hasAccessEnvironmentEx($envId)
		{
			if (! $this->hasAccessEnvironment($envId))
				throw new Exception('You have no permissions for viewing requested page');
		}

		public function hasAccess($group, $module = null, $action = null)
		{
			//TODO:

			// ACCOUNT_USER ~ ACCOUNT_ADMIN
			if ($group & self::ACCOUNT_USER)
				$group |= self::ACCOUNT_ADMIN;

			return (($this->session->getUserGroup() & $group) ? true : false);
		}

		public function hasAccessEx($group, $module, $action = null)
		{
			if (! $this->hasAccess($group, $module, $action))
				throw new Exception('You have no permissions for viewing requested page');
		}
	}
