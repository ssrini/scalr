<?php

class Scalr_UI_Controller_Tools_Aws extends Scalr_UI_Controller
{
	static public function getAwsLocations()
	{
		$locations = array();

		foreach (PlatformFactory::NewPlatform(SERVER_PLATFORMS::EC2)->getLocations() as $key => $loc)
			$locations[] = array($key, $loc);

		return $locations;
	}
	public function autoSnapshotSettingsAction()
	{
		$object_type = '';
		if($this->getParam('type') == 'ebs')
			$object_type = AUTOSNAPSHOT_TYPE::EBSSnap;
		
		if($this->getParam('type') == 'rds')
			$object_type = AUTOSNAPSHOT_TYPE::RDSSnap;
		
		$infos = $this->db->GetRow("SELECT * FROM autosnap_settings WHERE objectid = ? AND object_type = ? AND env_id = ?", 
			array(
				$this->getParam('objectId'),
				$object_type,
	 			$this->getEnvironmentId()
			));
		$this->response->page('ui/tools/aws/autoSnapshotSettings.js', array('settings' => $infos));
	}
	
	public function xSaveAutoSnapshotSettingsAction()
	{
		$object_type = '';
		if($this->getParam('type') == 'ebs')
			$object_type = AUTOSNAPSHOT_TYPE::EBSSnap;
		
		if($this->getParam('type') == 'rds')
			$object_type = AUTOSNAPSHOT_TYPE::RDSSnap;
		
		if ($this->getParam('enabling'))
		{
			$infos = $this->db->GetRow("SELECT * FROM autosnap_settings WHERE objectid = ? AND object_type = ? AND env_id = ?", 
			array(
				$this->getParam('objectId'),
				$object_type,
	 			$this->getEnvironmentId()
			));
			if($infos)
			{
				$this->db->Execute("UPDATE autosnap_settings SET
					period		= ?,
					rotate		= ?
					WHERE clientid = ? AND objectid = ? AND object_type = ?", 
				array(
					$this->getParam('period'),
					$this->getParam('rotate'),
					$this->user->getAccountId(),
					$this->getParam('objectId'),
					$object_type
				));
				$this->response->success('Auto-snapshots successfully updated');
			}
			else 
			{
				$this->db->Execute("INSERT INTO autosnap_settings SET
					clientid 	= ?,						
					period		= ?,
					rotate		= ?,
					region		= ?,
					objectid	= ?,
					object_type	= ?,
					env_id		= ?", 
				array(
					$this->user->getAccountId(),
					$this->getParam('period'),
					$this->getParam('rotate'),
					$this->getParam('cloudLocation'),
					$this->getParam('objectId'),
					$object_type,
					$this->getEnvironmentId()
				));
				$this->response->success('Auto-snapshots successfully enabled');
			}
		}
		else 
		{
			$this->db->Execute("DELETE FROM autosnap_settings WHERE 
				objectid	= ? AND 
				object_type	= ? AND 
				env_id		= ?",
			 array(
			 	$this->getParam('objectId'),
			 	$object_type,
			 	$this->getEnvironmentId()
			));
			$this->response->success('Auto-snapshots successfully deleted');
		}
	}
}
