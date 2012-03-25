<?php

class Scalr_Messaging_Msg_MongoDb_RemoveShardResult extends Scalr_Messaging_Msg_MongoDb {
	
	public $status;
	public $lastError;
	public $shardIndex;
	
	function __construct () {
		parent::__construct();	
	}	
}