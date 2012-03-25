<?php
class Scalr_UI_Controller_SshKeys extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'sshKeyId';

	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function viewAction()
	{
		$this->response->page('ui/sshkeys/view.js');
	}

	public function downloadPrivateAction()
	{
		$this->request->defineParams(array(
			'sshKeyId' => array('type' => 'int')
		));

		$sshKey = Scalr_SshKey::init()->loadById($this->getParam('sshKeyId'));
		$this->user->getPermissions()->validate($sshKey);

		$retval = $sshKey->getPrivate();

		$this->response->setHeader('Pragma', 'private');
		$this->response->setHeader('Cache-control', 'private, must-revalidate');
		$this->response->setHeader('Content-type', 'plain/text');
		$this->response->setHeader('Content-Disposition', 'attachment; filename="'.$sshKey->cloudKeyName.'.'.$sshKey->cloudLocation.'.private.pem"');
		$this->response->setHeader('Content-Length', strlen($retval));

		$this->response->setResponse($retval);
	}

	public function downloadPublicAction()
	{
		$this->request->defineParams(array(
			'sshKeyId' => array('type' => 'int')
		));

		$sshKey = Scalr_SshKey::init()->loadById($this->getParam('sshKeyId'));
		$this->user->getPermissions()->validate($sshKey);

		$retval = $sshKey->getPublic();
		if (!$retval)
			$retval = $sshKey->generatePublicKey();

		$this->response->setHeader('Pragma', 'private');
		$this->response->setHeader('Cache-control', 'private, must-revalidate');
		$this->response->setHeader('Content-type', 'plain/text');
		$this->response->setHeader('Content-Disposition', 'attachment; filename="'.$sshKey->cloudKeyName.'.'.$sshKey->cloudLocation.'.public.pem"');
		$this->response->setHeader('Content-Length', strlen($retval));

		$this->response->setResponse($retval);
	}

	public function regenerateAction()
	{
		$this->request->defineParams(array(
			'sshKeyId' => array('type' => 'int')
		));

		$sshKey = Scalr_SshKey::init()->loadById($this->getParam('sshKeyId'));
		$this->user->getPermissions()->validate($sshKey);

		if ($sshKey->type == Scalr_SshKey::TYPE_GLOBAL) {
			if ($sshKey->platform == 'ec2') {
				$AmazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
					$sshKey->cloudLocation,
					$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
					$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
				);

				$AmazonEC2Client->DeleteKeyPair($sshKey->cloudKeyName);

				$result = $AmazonEC2Client->CreateKeyPair($sshKey->cloudKeyName);
				if ($result->keyMaterial) {
					$sshKey->setPrivate($result->keyMaterial);

					$pubKey = $sshKey->generatePublicKey();
					if (!$pubKey)
						throw new Exception("Keypair generation failed");

					$oldKey = $sshKey->getPublic();

					$sshKey->setPublic($pubKey);

					$sshKey->save();

					$dbFarm = DBFarm::LoadByID($sshKey->farmId);
					$servers = $dbFarm->GetServersByFilter(array('platform' => SERVER_PLATFORMS::EC2, 'status' => array(SERVER_STATUS::RUNNING, SERVER_STATUS::INIT, SERVER_STATUS::PENDING)));
					foreach ($servers as $dbServer) {
						if ($dbServer->GetCloudLocation() == $sshKey->cloudLocation) {
							$msg = new Scalr_Messaging_Msg_UpdateSshAuthorizedKeys(array($pubKey), array($oldKey));
							$dbServer->SendMessage($msg);
						}
					}

					$this->response->success();
				}
			} else {
				//TODO:
			}
		} else {
			//TODO:
		}
	}

	/**
	 * Get list of roles for listView
	 */
	public function xListSshKeysAction()
	{
		$this->request->defineParams(array(
			'sshKeyId' => array('type' => 'int'),
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC')
		));

		$sql = "SELECT id from ssh_keys WHERE env_id='" . $this->getEnvironmentId()."'";

		if ($this->getParam('sshKeyId'))
			$sql .= " AND id='{$this->getParam('sshKeyId')}'";

		$response = $this->buildResponseFromSql($sql, array("cloud_key_name", "farm_id", "id"));
		foreach ($response["data"] as &$row) {
			$sshKey = Scalr_SshKey::init()->loadById($row['id']);

			$row = array(
				'id'				=> $sshKey->id,
				'type'				=> ($sshKey->type == Scalr_SshKey::TYPE_GLOBAL) ? "{$sshKey->type} ({$sshKey->platform})" : $sshKey->type,
				'cloud_key_name'	=> $sshKey->cloudKeyName,
				//'fingerprint'	=> $sshKey->getFingerprint(),
				'farm_id'		=> $sshKey->farmId,
				'cloud_location'=> $sshKey->cloudLocation
			);
		}

		$this->response->data($response);
	}
}
