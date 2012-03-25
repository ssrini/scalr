<?php

class Scalr_Messaging_Msg_MountPointsReconfigure extends Scalr_Messaging_Msg {
	public $device;
	public $mountPoint;
	public $createFs;	
	
	function __construct ($device=null, $mountPoint=null, $createFs=null) {
		parent::__construct();
		$this->device = $device;
		$this->mountPoint = $mountPoint;
		$this->createFs = $createFs;
	}
}