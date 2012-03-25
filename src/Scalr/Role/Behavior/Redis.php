<?php
	class Scalr_Role_Behavior_Redis extends Scalr_Role_DbMsrBehavior implements Scalr_Role_iBehavior
	{
		/** DBFarmRole settings **/
		//In Scalr_Db_Msr
		
		public function __construct($behaviorName)
		{
			parent::__construct($behaviorName);
		}
		
		public function getSecurityRules()
		{
			return array(
				"tcp:6379:6379:0.0.0.0/0"
			);
		}
	}