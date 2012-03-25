<?php

	class ServerSnapshotCreateInfo
	{
		/**
		 * 
		 * @var DBServer
		 */
		public $DBServer;
		
		public $roleName;
		public $replaceType;
		public $removePrototypeRole;
		public $description;
		public $rootVolumeSize;
		public $noServersReplace;
		
		public function __construct(DBServer $DBServer, $role_name, $replace_type, $remove_proto_role = false, $description = '', $rootVolumeSize = '', $noServersReplace = false)
		{
			$this->DBServer = $DBServer;
			$this->roleName = $role_name;
			$this->replaceType = $replace_type;
			$this->removePrototypeRole = $remove_proto_role;
			$this->description = $description;
			$this->rootVolumeSize = $rootVolumeSize;
			$this->noServersReplace = $noServersReplace;
		}
	}
	
?>