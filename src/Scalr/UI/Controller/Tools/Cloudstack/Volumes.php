<?php

class Scalr_UI_Controller_Tools_Cloudstack_Volumes extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'volumeId';

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function viewAction()
	{
		$this->response->page('ui/tools/cloudstack/volumes/view.js', array(
			'locations'	=> self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::CLOUDSTACK, false)
		));
	}

	public function xRemoveAction()
	{
		$this->request->defineParams(array(
			'volumeId' => array('type' => 'json'),
			'cloudLocation'
		));

		$amazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$this->getParam('cloudLocation'),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);

		foreach ($this->getParam('volumeId') as $volumeId) {
			$amazonEC2Client->DeleteVolume($volumeId);
			$this->db->Execute("DELETE FROM ec2_ebs WHERE volume_id=?", array($volumeId));
		}

		$this->response->success('Volume(s) successfully removed');
	}

	public function xListVolumesAction()
	{
		$this->request->defineParams(array(
			'sort' => array('type' => 'json', 'default' => array('property' => 'volumeId', 'direction' => 'ASC')),
			'volumeId'
		));

		$csClient = Scalr_Service_Cloud_Cloudstack::newCloudstack(
			$this->environment->getPlatformConfigValue(Modules_Platforms_Cloudstack::API_URL),
			$this->environment->getPlatformConfigValue(Modules_Platforms_Cloudstack::API_KEY),
			$this->environment->getPlatformConfigValue(Modules_Platforms_Cloudstack::SECRET_KEY)
		);

		$volumes = $csClient->listVolumes($this->getParam('cloudLocation'));
		
		$vols = array();
		foreach ($volumes as $pk=>$pv)
		{
			if ($this->getParam('volumeId') && $this->getParam('volumeId') != $pv->id)
				continue;

			$item = array(
				'volumeId'	=> $pv->id,
				'size'	=> round($pv->size / 1024 / 1024 / 1024, 2),
				'status' => $pv->state,
				'attachmentStatus' => ($pv->virtualmachineid) ? 'attached' : 'available',
				'device'	=> $pv->deviceid,
				'instanceId' => $pv->virtualmachineid,
				'type' 			=> $pv->type ." ({$pv->storagetype})",
				'storage'		=> $pv->storage
			);

			$item['autoSnaps'] = ($this->db->GetOne("SELECT id FROM autosnap_settings WHERE objectid=? AND object_type=?",
				 array($pv->id, AUTOSNAPSHOT_TYPE::CSVOL))) ? true : false;

			
			if ($item['instanceId']) {
				try {
					$dbServer = DBServer::LoadByPropertyValue(CLOUDSTACK_SERVER_PROPERTIES::SERVER_ID, $item['instanceId']);

					$item['farmId'] = $dbServer->farmId;
					$item['farmRoleId'] = $dbServer->farmRoleId;
					$item['serverIndex'] = $dbServer->index;
					$item['serverId'] = $dbServer->serverId;
					$item['farmName'] = $dbServer->GetFarmObject()->Name;
					$item['mountStatus'] = false;
					$item['roleName'] = $dbServer->GetFarmRoleObject()->GetRoleObject()->name;

				} catch (Exception $e) {}
			}

			$vols[] = $item;
		}

		$response = $this->buildResponseFromData($vols, array('serverId', 'volumeId','farmId', 'farmRoleId', 'storage'));

		$this->response->data($response);
	}
}
