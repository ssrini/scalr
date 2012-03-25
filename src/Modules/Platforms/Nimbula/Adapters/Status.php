<?php
	class Modules_Platforms_Nimbula_Adapters_Status implements IModules_Platforms_Adapters_Status
	{
		private $platformStatus;
		
		public static function load($status)
		{
			return new Modules_Platforms_Nimbula_Adapters_Status($status);
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
			return $this->platformStatus == 'running' ? true : false;
		}
		
		public function isPending()
		{
			return $this->platformStatus == 'queued' || $this->platformStatus == 'starting' || $this->platformStatus == 'initializing' ? true : false;
		}
		
		public function isTerminated()
		{
			return $this->platformStatus == 'terminating' || $this->platformStatus == 'not-found'  ? true : false;
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