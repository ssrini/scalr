<?php

/**
 *
 * @author marat
 * @ignore
 */
class Scalr_System_Cronjob_Distributed_NodeRegistry {
	
	public $path;
	
	/**
	 * @var Scalr_Service_Zookeeper
	 */
	public $zookeeper;
	
	public $node;	
	
	private $initialized;
	
	function __construct($config) {
		foreach ($config as $k => $v) {
			$this->{$k} = $v;
		}
	}
	
	private function init () {
		if (!$this->initialized) {
			$this->zookeeper->setOrCreate($this->path);
			$this->zookeeper->setOrCreate("{$this->path}/{$this->node}");
			$this->initialized = true;
		}
	}
	
	private function formatKey ($key) {
		return "{$this->path}/{$this->node}/{$key}";
	}
	
	function set ($key, $value) {
		$this->init();
		return $this->zookeeper->setOrCreate($this->formatKey($key), "{$value}");
	}
	
	function get ($key) {
		return $this->zookeeper->getData($this->formatKey($key));
	}
	
	function remove ($key) {
		$this->zookeeper->delete($this->formatKey($key));
	}
	
	function removeAll () {
		$this->zookeeper->deleteChildren("{$this->path}/{$this->node}");
	}
	
	function deleteNode () {
		$this->zookeeper->deleteRecursive("{$this->path}/{$this->node}");
	}
	
	function touchNode () {
		$this->zookeeper->set("{$this->path}/{$this->node}");
	}
	
	function nodesCapacity () {
		try {
			return $this->zookeeper->get($this->path)->numChildren;
		} catch (Scalr_Service_Zookeeper_Exception $e) {
			if ($e->getCode() == Scalr_Service_Zookeeper_Exception::NOT_FOUND) {
				return 0;
			}
			throw $e;
		}
	}
}