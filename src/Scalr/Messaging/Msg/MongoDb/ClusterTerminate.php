<?php

class Scalr_Messaging_Msg_MongoDb_ClusterTerminate extends Scalr_Messaging_Msg_MongoDb {
	
	public $timeout = 7200;
	
	function __construct () {
		parent::__construct();	
	}	
}