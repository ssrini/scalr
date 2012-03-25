<?php

class Scalr_Service_Zookeeper_Set implements Scalr_Util_Set {

	/**
	 * @var string
	 */
	public $path;
	
	/**
	 * @var Scalr_Service_Zookeeper
	 */
	public $zookeeper;
	
	private $logger;
	
	/**
	 * @var Scalr_Service_Zookeeper_Lock
	 */
	private $lock;
	
	private $initialized = false;
	
	/**
	 * @param array $config
	 * @key Scalr_Service_Zookeeper [zookeeper]
	 * @key string [path]
	 */
	function __construct ($config) {
		foreach ($config as $k => $v) {
			$this->{$k} = $v;
		}
		$this->logger = Logger::getLogger(__CLASS__);
		$this->lock = new Scalr_Service_Zookeeper_Lock(array(
			"zookeeper" => $this->zookeeper,
			"path" => "{$this->path}/lock",
			"acquireAttemptTimeout" => 10000 // 10 seconds 
		));
	}
	
	protected function init () {
		if (!$this->initialized) {
			if (!$this->zookeeper->exists($this->path)) {
				$this->zookeeper->setOrCreate($this->path, "", false);
			}
			$this->initialized = true;
		}
	}
	
	function add ($item) {
		$this->init();
		$this->lock->acquire();
		try {
			$ret = false;
			if (!$this->contains0($item)) {
				$ret = true;
				$this->zookeeper->create("{$this->path}/element", "{$item}", 
						array(Scalr_Service_Zookeeper::OPT_SEQUENCE => true));
			}
			
			$this->lock->release();
			return $ret;
			
		} catch (Exception $e) {
			$this->lock->release();
			throw $e;
		}
	}
	
	function remove ($item) {
		$this->init();
		$this->lock->acquire();
		
		try {
			$ret = false;
			if ($path = $this->pathOf($item)) {
				$this->zookeeper->delete($path);
				$ret = true;
			}
			
			$this->lock->release();
			return $ret;
			
		} catch (Exception $e) {
			$this->lock->release();
			throw $e;
		}
	}
	
	function contains ($item) {
		$this->init();
		$this->lock->acquire();
		try {
			$ret = $this->contains0($item);
			
			$this->lock->release();
			return $ret;
			
		} catch (Exception $e) {
			$this->lock->release();
			throw $e;
		}
	}
	
	private function contains0 ($item) {
		return (bool) $this->pathOf($item);
	}
	
	private function pathOf ($item) {
		foreach ($this->itemNames() as $name) {
			$path = "{$this->path}/{$name}";
			if ($item == $this->zookeeper->getData($path)) {
				return $path;
			}
		}
		
		return null;
	}
	
	private function matchElementName ($name) {
		return strpos($name, "element") === 0;
	}
	
	private function itemNames () {
		$ret = array();
		$childData = $this->zookeeper->getChildren($this->path);
		if ($childData->children) {
			foreach ($childData->children as $childName) {
				if ($this->matchElementName($childName)) {
					$ret[] = $childName;
				}
			}
		}
		
		return $ret;
	}
	
	function size () {
		$this->init();
		$this->lock->acquire();
		try {
			$ret = count($this->itemNames());
			
			$this->lock->release();
			return $ret;
			
		} catch (Exception $e) {
			$this->lock->release();
			throw $e;
		}
	}
	
	function clear () {
		$this->init();
		$this->lock->acquire();
		try {
			foreach ($this->itemNames() as $name) {
				$this->zookeeper->delete("{$this->path}/{$name}");
			}
			
			$this->lock->release();
			
		} catch (Exception $e) {
			$this->lock->release();
			throw $e;
		}
	}
}