<?php
	class Modules_Platforms_Rds_Adapters_Status implements IModules_Platforms_Adapters_Status
	{
		private $platformStatus;
		
		//available | backing-up | creating | deleted | deleting | failed | modifying | rebooting | resetting-mastercredentials| storage-full
		
		public static function load($status)
		{
			return new Modules_Platforms_Rds_Adapters_Status($status);
		}
		
		public function __construct($status)
		{
			$this->platformStatus = $status;
		}
		
		public function getName()
		{
			return $this->platformStatus;
		}
		
		public function isRunning()
		{
			$running_statuses = array('available', 'backing-up', 'modifying', 'rebooting', 'resetting-mastercredentials', 'storage-full');
			return in_array($this->platformStatus, $running_statuses);
		}
		
		public function isPending()
		{
			return $this->platformStatus == 'creating' ? true : false;
		}
		
		public function isTerminated()
		{
			$term_statuses = array('deleted', 'deleting', 'failed', 'not-found');
			return in_array($this->platformStatus, $term_statuses);
		}
		
		public function isSuspended()
		{
			//
		}
		
		public function isPendingSuspend()
		{
			//
		}
		
		public function isPendingRestore()
		{
			//
		}
	}