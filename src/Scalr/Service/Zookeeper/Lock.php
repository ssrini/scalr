<?php

class Scalr_Service_Zookeeper_Lock {
	
	/**
	 * @var Scalr_Service_Zookeeper
	 */
	public $zookeeper;
	
	/**
	 * @var string
	 */
	public $path;
	
	/**
	 * @var Scalr_Util_Timeout
	 */
	public $acquireAttemptTimeout;
	
	private $logger; 
	
	/**
	 * @param $config
	 * @key Scalr_Service_Zookeeper [zookeeper]
	 * @key string [path]
	 * @key int|Scalr_Util_Timeout [acquireAttemptTimeout]
	 */
	function __construct ($config) {
		foreach ($config as $k => $v) {
			$this->{$k} = $v;
		}
		$this->logger = Logger::getLogger(__CLASS__);
		if ($this->acquireAttemptTimeout) {
			if (!$this->acquireAttemptTimeout instanceof Scalr_Util_Timeout) {
				$this->acquireAttemptTimeout = new Scalr_Util_Timeout($this->acquireAttemptTimeout);
			}
		}
	}
	
	function acquire () {
		while (!$this->tryAcquire() && 
				(($this->acquireAttemptTimeout && !$this->acquireAttemptTimeout->reached()) 
				|| !$this->acquireAttemptTimeout)) {
			$this->logger->debug(sprintf("Cannot acquire exclusive lock on '%s'. Wait '%s'", 
					$this->path, "100 millis"));
			Scalr_Util_Timeout::sleep(100);
		}
	}
	
	function tryAcquire () {
		try {
			$this->zookeeper->create($this->path);
			return true;
		} catch (Scalr_Service_Zookeeper_Exception $e) {
			if ($e->getCode() == Scalr_Service_Zookeeper_Exception::CONFLICT) {
				return false;
			}
			throw $e;
		}
	}
	
	function release () {
		try {
			$this->zookeeper->delete($this->path);
		} catch (Scalr_Service_Zookeeper_Exception $e) {
			if ($e->getCode() != Scalr_Service_Zookeeper_Exception::NOT_FOUND) {
				throw $e;
			}
		}
	}
}