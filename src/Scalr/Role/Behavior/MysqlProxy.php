<?php
	class Scalr_Role_Behavior_MysqlProxy extends Scalr_Role_Behavior implements Scalr_Role_iBehavior
	{	
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function getSecurityRules()
		{
			return array(
				"tcp:4040:4040:0.0.0.0/0"
			);
		}
	}