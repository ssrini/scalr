<?php

	interface IModules_Platforms_Adapters_Status
	{
		public function getName();
		
		public function isRunning();
				
		public function isPending();
		
		public function isTerminated();
		
		public function isSuspended();
		
		public function isPendingSuspend();
		
		public function isPendingRestore();
	}
?>