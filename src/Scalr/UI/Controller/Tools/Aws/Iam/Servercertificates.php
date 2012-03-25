<?php

class Scalr_UI_Controller_Tools_Aws_Iam_ServerCertificates extends Scalr_UI_Controller
{
	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function createAction()
	{
		$this->response->page('ui/tools/aws/iam/serverCertificates/create.js');
	}

	public function viewAction()
	{
		$this->response->page('ui/tools/aws/iam/serverCertificates/view.js');
	}

	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'name' => array('type' => 'string')
		));

		$iamClient = Scalr_Service_Cloud_Aws::newIam(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);

		$iamClient->uploadServerCertificate(
			@file_get_contents($_FILES['certificate']['tmp_name']),
			@file_get_contents($_FILES['privateKey']['tmp_name']),
			$this->getParam('name'),
			($_FILES['certificateChain']['tmp_name']) ? @file_get_contents($_FILES['certificateChain']['tmp_name']) : null
		);

		$this->response->success('Certificate successfully uploaded');
	}

	public function xListCertificatesAction()
	{
		$iamClient = Scalr_Service_Cloud_Aws::newIam(
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
		);

		$rowz = array();
		$certs = $iamClient->listServerCertificates();

		foreach ($certs->ServerCertificateMetadataList as $item) {
			$rowz[] = array(
				'id'			=> $item->ServerCertificateId,
				'name'			=> $item->ServerCertificateName,
				'path'			=> $item->Path,
				'arn'			=> $item->Arn,
				'upload_date'	=> $item->UploadDate
			);
		}

		$this->response->data(array('data' => $rowz));
	}
}
