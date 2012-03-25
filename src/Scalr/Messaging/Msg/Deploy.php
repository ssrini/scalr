<?php

class Scalr_Messaging_Msg_Deploy extends Scalr_Messaging_Msg {
	
	public $deployTaskId;
	
	/**
	 * stdObject->
	 * type : [svn|git|http]
	 * url
     * # when type=svn
     * login
     * password
     * # when type=git
     * sslCert # File containing the SSL certificate when fetching or pushing over HTTPS
     * sslPk # File containing the SSL private key when fetching or pushing over HTTPS.
     * sslNoVerify # Don't verify the SSL certificate when fetching or pushing over HTTPS
     * sslCaInfo # File containing the certificates to verify the peer with when fetching or pushing over HTTPS
	 * 
	 * @var stdClass
	 */
	public $source;
	
	
	public $remotePath;
	
	/**
	 * stdObject->
	 * body
     * execTimeout
	 * @var stdClass
	 */
	public $preDeployRoutines;
	
	/**
	 * stdObject->
	 * body
     * execTimeout
	 * @var stdClass
	 */
	public $postDeployRoutines;
	
	function __construct ($deployTaskId, $remotePath, $source, $preDeployRoutines = "", $postDeployRoutines = "") {
		parent::__construct();
		
		$this->deployTaskId = $deployTaskId;
		$this->source = $source;
		$this->remotePath = $remotePath;
		
		$this->preDeployRoutines = new stdClass();
		$this->preDeployRoutines->body = $preDeployRoutines;
		$this->preDeployRoutines->execTimeout = 300;
		
		$this->postDeployRoutines = new stdClass();
		$this->postDeployRoutines->body = $postDeployRoutines;
		$this->postDeployRoutines->execTimeout = 300;
	}
}