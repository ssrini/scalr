<?php

class Scalr_Service_Zookeeper_Barrier {
	
	/**
	 * @var Scalr_Service_Zookeeper
	 */
	public $zookeeper;
	
	/**
	 * @var string
	 */
	public $path;
	
	/**
	 * @var int
	 */
	public $quorum;
	
	/**
	 * @var Scalr_Util_Timeout
	 */
	public $timeout;
	
	/**
	 * @var bool
	 */
	public $autoDelete = true;
	
	/**
	 * @param array $config
	 * @key Scalr_Service_Zookeeper $zookeeper
	 * @key string $path
	 * @key int $quorum
	 * @key int|Scalr_Util_Timeout $timeout
	 * @key bool $autoDelete
	 */
	function __construct ($config) {
		foreach ($config as $k => $v) {
			$this->{$k} = $v;
		}
		foreach (array("zookeeper", "path", "quorum") as $k) {
			if (!$this->{$k}) {
				throw new Scalr_Service_Zookeeper_Exception(sprintf("'%s' is required config option", $k));
			}
		}
		if ($this->timeout) {
			$this->timeout = Scalr_Util_Timeout::get($this->timeout);
		}
	}
	
	function enter ($data=null) {
		// who will be first?
		$iAmFirst = false;
		try {
			$this->zookeeper->create($this->path);
			$iAmFirst = true;
		} catch (Scalr_Service_Zookeeper_Exception $e) {
			if ($e->getCode() != Scalr_Service_Zookeeper_Exception::CONFLICT) {
				throw $e;
			}
		}
		
		// create child node
		$this->zookeeper->create("{$this->path}/n", $data ? serialize($data) : null, 
				array(Scalr_Service_Zookeeper::OPT_SEQUENCE => true));
				
		// wait while all nodes will enter barrier
		try {
			while (($this->timeout && !$this->timeout->reached()) || !$this->timeout) {
				if (($iAmFirst && $this->capacity() >= $this->quorum) ||
					(!$iAmFirst && !$this->zookeeper->exists($this->path))) {
					break;
				} else {
					Scalr_Util_Timeout::sleep(100);
				}
			}
		} catch (Exception $e) {
			// Finally delete barrier znode
			if ($this->autoDelete) {
				$this->delete();
			}
			throw $e;
		}
		
		if ($this->autoDelete) {
			$this->delete();
		}
	}
	
	function capacity () {
		try {
			$childData = $this->zookeeper->getChildren($this->path);
			return count($childData->children);			
		} catch (Scalr_Service_Zookeeper_Exception $e) {
			if ($e->getCode() == Scalr_Service_Zookeeper_Exception::NOT_FOUND) {
				return 0;
			}
			throw $e;
		}
	}
	
	function delete () {
		try {
			$this->zookeeper->deleteRecursive($this->path);
		} catch (Scalr_Service_Zookeeper_Exception $ignore) {
		}		
	}
}