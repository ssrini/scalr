<?php

class Scalr_System_Ipc_Shm {
	public $key;

	protected $shm;	
	
	private $logger;
	
	/**
	 * @param array $config
	 * @key string [name]
	 * @key int [key]
	 */
	function __construct ($config=array()) {
		foreach ($config as $k => $v) {
			if (property_exists($this, $k)) {
				$this->$k = $v;
			}
		}
		$this->logger = Logger::getLogger(__CLASS__);
		if (!isset($this->key) && $config["name"]) {
			$this->key = Scalr_System_OS::getInstance()->tok($config["name"]);
			//$this->logger->error(sprintf("Shm key 0x%08x from name '%s' is generated", $this->key, $config["name"]));			
		}

		$this->logger->debug(sprintf("Initialize shm segment (key: 0x%08x)", $this->key));
		// TODO: check errors
		$this->shm = shm_attach($this->key);
		if (!$this->shm) {
			throw new Scalr_System_Ipc_Exception(sprintf("shm_attach failed for key 0x%08x", $this->key));
		}
	}

	
	function put ($key, $value) {
		if (!is_numeric($key)) {
			throw new Scalr_System_Exception(sprintf("key must be numeric. '%s' is given", $key));
		}
		
		// shm_get_var return false in case of non-existed key. 
		// We need a wrapper to store boolean values 
		if (is_bool($value)) {
			$wrapper = new stdClass();
			$wrapper->__shmWrapperClass = true;
			$wrapper->value = $value;
			$value = $wrapper;
		}

		if (!shm_put_var($this->shm, $key, $value)) {
			throw new Scalr_System_Exception(sprintf("Cannot put key '%s' into shared memory", $key));
		}
		
		return true;
	}
	
	function get ($key) {
		if (!is_numeric($key)) {
			throw new Scalr_System_Exception(sprintf("key must be numeric. '%s' is given", $key));
		}
		
		// `shm_get_var` generates PHP warning if key doesn't exists
		$value = @shm_get_var($this->shm, $key);
		
		if (is_object($value) && property_exists($value, "__shmWrapperClass")) {
			return $value->value;
		} else {
			return $value !== false ? $value : null;
		}
	}
	
	function containsKey ($key) {
		if (!is_numeric($key)) {
			throw new Scalr_System_Exception(sprintf("key must be numeric. '%s' is given", $key));
		}		
		
		// `shm_get_var` generates PHP warning if key doesn't exists		
		$value = @shm_get_var($this->shm, $key);
		return $value !== false;
	}
	
	function remove ($key) {
		shm_remove_var($this->shm, $key);
	}
	
	function delete () {
		$this->logger->debug(sprintf("Delete shm segment (key: 0x%08x)", $this->key));
		shm_remove($this->shm);
		shm_detach($this->shm);
	}
}
