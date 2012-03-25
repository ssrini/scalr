<?php

class Scalr_UI_Controller_Platforms_Ec2 extends Scalr_UI_Controller
{
	public function xGetAvailZonesAction()
	{
		$amazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$this->getParam('cloudLocation'),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);

		// Get Avail zones
		$response = $amazonEC2Client->DescribeAvailabilityZones();
		if ($response->availabilityZoneInfo->item instanceOf stdClass)
			$response->availabilityZoneInfo->item = array($response->availabilityZoneInfo->item);

		$data = array();
		foreach ($response->availabilityZoneInfo->item as $zone) {
			if (stristr($zone->zoneState,'available')) {
				$data[] = array(
					'id' => (string)$zone->zoneName,
					'name' => (string)$zone->zoneName
				);
			}
		}

		$this->response->data(array('data' => $data));
	}

	public function xGetSnapshotsAction()
	{
		$amazonEC2Client = Scalr_Service_Cloud_Aws::newEc2(
			$this->getParam('cloudLocation'),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::PRIVATE_KEY),
			$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::CERTIFICATE)
		);

		$response = $amazonEC2Client->DescribeSnapshots();
		if ($response->snapshotSet->item instanceOf stdClass)
			$response->snapshotSet->item = array($response->snapshotSet->item);

		$data = array();
		foreach ($response->snapshotSet->item as $pk => $pv) {
			if ($pv->ownerId != $this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCOUNT_ID))
				continue;

			if ($pv->status == 'completed')
				$data[] = array(
					'snapid' 	=> (string)$pv->snapshotId,
					'createdat'	=> Scalr_Util_DateTime::convertTz($pv->startTime),
					'size'		=> (string)$pv->volumeSize
				);
		}

		$this->response->data(array('data' => $data));
	}
}
