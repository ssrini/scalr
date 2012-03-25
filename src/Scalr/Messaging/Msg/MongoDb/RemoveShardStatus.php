<?php

class Scalr_Messaging_Msg_MongoDb_RemoveShardStatus extends Scalr_Messaging_Msg_MongoDb {
	
	public $shardIndex;
	public $totalChunks;
	public $chunksLeft;
	public $progress;

	
	function __construct () {
		parent::__construct();	
	}	
}