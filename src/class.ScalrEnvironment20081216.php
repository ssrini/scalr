<?

	class ScalrEnvironment20081216 extends ScalrEnvironment20081125
    {    	
    	protected function GetLatestVersion()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		$VersionDOMNode = $ResponseDOMDocument->createElement("version", ScalrEnvironment::LATEST_VERSION);
    		$ResponseDOMDocument->documentElement->appendChild($VersionDOMNode);
    		
    		return $ResponseDOMDocument;
    	}

    	protected function ListEBSMountpoints()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$MountpointsDOMNode = $ResponseDOMDocument->createElement("mountpoints");
    		
    		//
    		// List EBS Volumes
    		//
    		if ($this->DBServer->IsSupported("0.7.36")) {
    			$volumes = $this->DB->GetAll("SELECT id FROM ec2_ebs WHERE farm_roleid=? AND server_index=?",
	    		array(
	    			$this->DBServer->farmRoleId,
	    			$this->DBServer->index
	    		));
    		} else {
	    		$volumes = $this->DB->GetAll("SELECT id FROM ec2_ebs WHERE server_id=? AND attachment_status = ? AND mount_status IN (?,?)",
	    		array(
	    			$this->DBServer->serverId,
	    			EC2_EBS_ATTACH_STATUS::ATTACHED,
	    			EC2_EBS_MOUNT_STATUS::MOUNTED,
	    			EC2_EBS_MOUNT_STATUS::MOUNTING
	    		));
    		}
    		
    		$DBFarmRole = $this->DBServer->GetFarmRoleObject();
    		
    		foreach ($volumes as $volume)
    		{
    			$DBEBSVolume = DBEBSVolume::loadById($volume['id']);
    			
    			$mountpoint = ($DBEBSVolume->mountPoint) ? $DBEBSVolume->mountPoint : "";
    			
    			if (!$DBEBSVolume->isManual && $mountpoint)
					$createfs = $DBEBSVolume->isFsExists ? 0 : 1;
				else
					$createfs = 0;
    			
				if ($mountpoint || $this->DBServer->IsSupported("0.7.36"))
				{
	    			$mountpoints[] = array(
						'name'		=> ($DBEBSVolume->attachmentStatus == EC2_EBS_ATTACH_STATUS::CREATING) ? "vol-creating" : $DBEBSVolume->volumeId,
						'dir'		=> $mountpoint,
	    				'createfs' 	=> $createfs,
	    				'volumes'	=> array($DBEBSVolume),
	    				'isarray'	=> 0
					);
				}
    		}
    		
    		//
    		// Create response
    		//
    		
    		foreach ($mountpoints as $mountpoint)
    		{
    			$MountpointDOMNode = $ResponseDOMDocument->createElement("mountpoint");
				
				$MountpointDOMNode->setAttribute("name", $mountpoint['name']);
				$MountpointDOMNode->setAttribute("dir", $mountpoint['dir']);
				$MountpointDOMNode->setAttribute("createfs", $mountpoint['createfs']);
				$MountpointDOMNode->setAttribute("isarray", $mountpoint['isarray']);
				
				$VolumesDOMNode = $ResponseDOMDocument->createElement("volumes");
				
				foreach ($mountpoint['volumes'] as $DBEBSVolume)
				{
					$VolumeDOMNode = $ResponseDOMDocument->createElement("volume");
					$VolumeDOMNode->setAttribute("device", $DBEBSVolume->deviceName);
					$VolumeDOMNode->setAttribute("volume-id", $DBEBSVolume->volumeId);
					
					$VolumesDOMNode->appendChild($VolumeDOMNode);
				}
				
				$MountpointDOMNode->appendChild($VolumesDOMNode);
				$MountpointsDOMNode->appendChild($MountpointDOMNode);
    		}
    		
    		$ResponseDOMDocument->documentElement->appendChild($MountpointsDOMNode);
    		
    		return $ResponseDOMDocument;
    	}
    }
?>