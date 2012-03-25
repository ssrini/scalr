<?php

class Scalr_Permissions
{
	protected
		$user,
		$envId;

	public function __construct($user)
	{
		$this->user = $user;
	}

	public function setEnvironmentId($envId)
	{
		$this->envId = $envId;
	}

	public function validate($object)
	{
		if (! $this->check($object))
			throw new Scalr_Exception_InsufficientPermissions('Access denied');
	}

	public function check($object)
	{
		$cls = get_class($object);

		switch ($cls) {
			case 'Scalr_Environment':
				$flag = false;
				foreach ($this->user->getEnvironments() as $env) {
					if ($env['id'] == $object->id) {
						$flag = true;
						break;
					}
				}
				return $flag;

			case 'DBFarm':
				return $this->hasAccessEnvironment($object->EnvID);
				
			case 'Scalr_Account_User':
				return ($object->getAccountId() == $this->user->getAccountId());

			case 'Scalr_Account_Team':
				return ($object->accountId == $this->user->getAccountId());

			case 'BundleTask':
			case 'DBRole': // проверять ли $dbRole->clientId == Scalr_Session::getInstance()->getClientId()) {
			case 'DBDNSZone':
			case 'DBServer':
			case 'Scalr_Dm_Application':
			case 'Scalr_Dm_Source':
			case 'Scalr_Dm_DeploymentTask':
			case 'Scalr_Scaling_Metric':
			case 'Scalr_ServiceConfiguration':
			case 'Scalr_Service_Apache_Vhost':
			case 'Scalr_SshKey':
			case 'Scalr_SchedulerTask':
				return $this->hasAccessEnvironment($object->envId);

			case 'DBFarmRole':
				return $this->hasAccessEnvironment($object->GetFarmObject()->EnvID);
		}
	}

	protected function hasAccessEnvironment($envId)
	{
		if (is_null($this->envId)) {
			throw new Scalr_Exception_Core('Environment not defined in permissions object');
		}
		
		return ($envId == $this->envId);
	}
}
