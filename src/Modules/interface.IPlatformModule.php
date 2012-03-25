<?php

	interface IPlatformModule
	{
		public function getRoleBuilderBaseImages();
		
		public function getLocations();
		
		public function LaunchServer(DBServer $DBServer, Scalr_Server_LaunchOptions $launchOptions = null);
				
		public function TerminateServer(DBServer $DBServer);
		
		public function RebootServer(DBServer $DBServer);
		
		public function CreateServerSnapshot(BundleTask $BundleTask);
		
		public function CheckServerSnapshotStatus(BundleTask $BundleTask);
		
		public function RemoveServerSnapshot(DBRole $DBRole);
		
		public function GetServerExtendedInformation(DBServer $DBServer);
		
		public function GetServerConsoleOutput(DBServer $DBServer);
		
		/**
		 * 
		 * Enter description here ...
		 * @param DBServer $DBServer
		 * @return IModules_Platforms_Adapters_Status $status
		 */
		public function GetServerRealStatus(DBServer $DBServer);
		
		public function GetServerIPAddresses(DBServer $DBServer);
		
		public function IsServerExists(DBServer $DBServer);
		
		public function PutAccessData(DBServer $DBServer, Scalr_Messaging_Msg $message);
		
		public function ClearCache();
		
		public function GetServerID(DBServer $DBServer);
		
		public function GetServerCloudLocation(DBServer $DBServer);
		
		public function GetServerFlavor(DBServer $DBServer);
	}
