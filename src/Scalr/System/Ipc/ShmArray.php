<?php

/**
 * @author marat
 */
// TODO: maybe better to use shmop_* functions ?
class Scalr_System_Ipc_ShmArray implements ArrayAccess, Countable, Iterator {

	/**
	 * @var Scalr_System_Ipc_Shm
	 */
	private $shm;

	private $semaphore;	
	
	private $semKey;
	
	private $logger;
	
	/**
	 * @param $config
	 * @key string [name]
	 * @key string [key]
	 */
	function __construct ($config) {
		$this->logger = Logger::getLogger(__CLASS__);
		$this->shm = new Scalr_System_Ipc_Shm($config);
		
		sem_acquire($this->sem());
		try {
			$this->shm->put(0, array(
				"count" => 0,
				"keys" => array(),
				"pos" => 0
			));
			sem_release($this->sem());
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	function delete () {
		if ($this->shm) {
			sem_acquire($this->sem());
			try {
				$this->shm->delete();
				sem_release($this->sem());
				
			} catch (Exception $e) {
				sem_release($this->sem());
			}
		}
		
		$this->logger->debug(sprintf("Delete semaphore (key: 0x%08x)", $this->semKey));
		sem_remove($this->sem());
		if (isset($e)) {
			throw $e;
		}
	}
	
	function getArrayCopy () {
		sem_acquire($this->sem());
		try {
			$ret = array();
			foreach ($this as $k => $v) {
				$ret[$k] = $v;
			}

			sem_release($this->sem());
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;		
		}
	}
	
	
	function offsetExists  ($offset) {
		sem_acquire($this->sem());
		try {
			$ret = $this->shm->containsKey(crc32($offset));
			sem_release($this->sem());
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	function offsetGet ($offset) {
		sem_acquire($this->sem());
		try {
			$ret = $this->shm->get(crc32($offset));
			sem_release($this->sem());
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	function offsetSet ($offset, $value) {
		sem_acquire($this->sem());
		try {
			$intOffset = crc32($offset);
			$keyExists = $this->shm->containsKey($intOffset);
			$this->logger->debug(sprintf("Put value at offset '%s' (key: %d, %s)", 
					$offset, $intOffset, $keyExists ? "key exists" : "key not exists"));
			$this->shm->put($intOffset, $value);
			
			if (!$keyExists) {
				$meta = $this->shm->get(0);
				$meta["keys"][] = $offset;
				$meta["count"]++;
				$this->shm->put(0, $meta);
			}
			
			sem_release($this->sem());
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	function offsetUnset ($offset) {
		sem_acquire($this->sem());
		try {
			$intOffset = crc32($offset);
			$this->shm->remove($intOffset);
			
			$meta = $this->shm->get(0);
			$meta["count"]--;
			array_splice($meta["keys"], array_search($offset, $meta["keys"]), 1);
			$this->shm->put(0, $meta);
			
			sem_release($this->sem());
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	function count () {
		sem_acquire($this->sem());
		try {
			$meta = $this->shm->get(0);
			
			sem_release($this->sem());
			return $meta["count"];
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	public function rewind() {
		sem_acquire($this->sem());
		try {
			$meta = $this->shm->get(0);
			$meta["pos"] = 0;
			$this->shm->put(0, $meta);
			
			sem_release($this->sem());
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
	
	public function current() {
		sem_acquire($this->sem());
		try {
			$meta = $this->shm->get(0);
			$key = $meta["keys"][$meta["pos"]];
			$ret = $key ? $this->shm->get(crc32($key)) : null;
			
			sem_release($this->sem());
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem());
			throw $e;
		}
	}
		
	public function key() {
		sem_acquire($this->sem());
    	try {
    		$meta = $this->shm->get(0);
    		$ret = $meta["keys"][$meta["pos"]];
    		
    		sem_release($this->sem());
    		return $ret;
    		
    	} catch (Exception $e) {
    		sem_release($this->sem());
    		throw $e;
    	}
    }
	
    public function next() {
    	sem_acquire($this->sem());
    	try {
    		$meta = $this->shm->get(0);
    		$meta["pos"]++;
    		$ret = $meta["keys"][$meta["pos"]];
    		$this->shm->put(0, $meta);
    		
    		sem_release($this->sem());
    		return $ret;
    		
    	} catch (Exception $e) {
    		sem_release($this->sem());
    		throw $e;
    	}
    	return next($this->container);
    }
	
    public function valid() {
    	sem_acquire($this->sem());
    	try {
    		$meta = $this->shm->get(0);
    		$ret = $meta["pos"] < $meta["count"];
    		
    		sem_release($this->sem());
    		return $ret;
    		
    	} catch (Exception $e) {
    		sem_release($this->sem());
    		throw $e;
    	}
    }    	
	
	private function sem () {
		if (!$this->semaphore) {
			$this->semKey = $this->shm->key + 8;
			$this->logger->debug(sprintf("Get semaphore (key: 0x%08x)", $this->semKey));
			$this->semaphore = sem_get($this->semKey, 1, 0666, true);
			if (!$this->semaphore) {
				throw new Scalr_System_Ipc_Exception(sprintf(
						"Cannot get semaphore. `sem_get` return false (key: 0x%08x)", 
						$this->semKey));
			}
		}
		
		return $this->semaphore;
	}
}