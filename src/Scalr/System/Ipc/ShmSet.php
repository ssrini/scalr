<?php

// TODO: test it

class Scalr_System_Ipc_ShmSet implements Scalr_Util_Set {
	
	/**
	 * @var Scalr_System_Ipc_Shm
	 */
	private $shm; 

	private $sem;	
	
	private $logger;
	
	private $initialConfig;
	
	/**
	 * @param $config
	 * @key string [name]
	 * @key string [key]
	 * @key array [items]
	 */
	function __construct ($config) {
		$this->logger = Logger::getLogger(__CLASS__);
		
		$this->initialConfig = $config;
		$this->shm = new Scalr_System_Ipc_Shm($config);
		
		$key = $this->shm->key + 8;
		$this->logger->debug(sprintf("Get semaphore (key: 0x%08x)", $key));
		$this->sem = sem_get($key, 1, 0666, true);
		if (!$this->sem) {
			throw new Scalr_System_Ipc_Exception("Cannot sem_get (key: $key)");
		}
		
		if (!sem_acquire($this->sem)) {
			throw new Scalr_System_Ipc_Exception("Cannot acquire semaphore");
		}
		
		try {
			$meta = $this->getMeta();
			if ($meta === null) {
				$this->clear0();
			}
			sem_release($this->sem);
		} catch (Exception $e) {
			sem_release($this->sem);
			throw $e;
		}
		
		if ($config["items"]) {
			foreach ($config["items"] as $item) {
				$this->add($item);
			}
		}
	}
	
	function add ($item) {
		if (!sem_acquire($this->sem)) {
			throw new Scalr_System_Ipc_Exception("Cannot acquire semaphore");
		}
		
		try {
			$ret = false;
			if (!$this->contains0($item)) {
				//$this->logger->debug("Add item '$item'");
				
				$meta = $this->getMeta();
				//$this->logger->debug("Meta before add '$item' : " . var_export($meta, true));
				$this->logger->debug("put '$item' at '{$meta["nextIndex"]}'");
				$this->shm->put($meta["nextIndex"], $item);
				$meta["nextIndex"]++;
				$meta["size"]++;
				//$this->logger->debug("Meta after add '$item' : " . var_export($meta, true));
				$this->putMeta($meta);
				
				$ret = true;
			}
			
			sem_release($this->sem);
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem);
			throw $e;
		}
	}
	
	function remove ($item) {
		if (!sem_acquire($this->sem)) {
			throw new Scalr_System_Ipc_Exception("Cannot acquire semaphore");
		}
		
		try {
			$ret = false;
			if (-1 != ($i = $this->indexOf($item))) {
				$this->logger->debug("remove item at '$i'");
				$this->shm->remove($i);
				$meta = $this->getMeta();
				$meta["size"]--;
				//$this->logger->debug("Set meta after remove: " . var_export($meta, true));
				$this->putMeta($meta);
				
				$ret = true;
			}
			
			sem_release($this->sem);
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem);
			throw $e;
		}
	}
	
	function contains ($item) {
		if (!sem_acquire($this->sem)) {
			throw new Scalr_System_Ipc_Exception("Cannot acquire semaphore");
		}
		
		try {
			$ret = $this->contains0($item);
			sem_release($this->sem);
			return $ret;
			
		} catch (Exception $e) {
			sem_release($this->sem);
			throw $e;
		}
	}
	
	private function contains0 ($item) {
		return $this->indexOf($item) != -1;	
	}
	
	function size () {
		if (!sem_acquire($this->sem)) {
			throw new Scalr_System_Ipc_Exception("Cannot acquire semaphore");
		}
		
		try {
			$meta = $this->getMeta();
			sem_release($this->sem);
			return $meta["size"];
			
		} catch (Exception $e) {
			sem_release($this->sem);
			throw $e;
		}
	}	
	
	function clear () {
		if (!sem_acquire($this->sem)) {
			throw new Scalr_System_Ipc_Exception("Cannot acquire semaphore");
		}
		
		try {
			$this->clear0();
			sem_release($this->sem);
			
		} catch (Exception $e) {
			sem_release($this->sem);
			throw $e;
		}
	}
	
	private function clear0 () {
		$this->putMeta(array("size" => 0, "nextIndex" => 1));
	}
	
	function delete () {
		$this->logger->debug(sprintf("Delete shm set (key:  0x%08x)", $this->shm->key));
		
		if (!sem_acquire($this->sem)) {
			throw new Scalr_System_Ipc_Exception("Cannot acquire semaphore");
		}
	
		try {
			$this->shm->delete();
			unset($this->shm);
		} catch (Exception $ignore) {
		}
		
		sem_release($this->sem);
		sem_remove($this->sem);
	}
	
	private function indexOf ($item) {
		$meta = $this->getMeta();
		for ($i=1; $i<$meta["nextIndex"]; $i++) {
			if ($item == $this->shm->get($i)) {
				return $i;
			}
		}
		
		return -1;
	}
	
	private function getMeta () {
		$meta = $this->shm->get(0);
		$this->logger->debug("get meta (size: {$meta["size"]}, nextIndex: {$meta["nextIndex"]})");
		return $meta;
	}
	
	private function putMeta ($meta) {
		$this->logger->debug("put meta (size: {$meta["size"]}, nextIndex: {$meta["nextIndex"]})");
		$this->shm->put(0, $meta);
	}
}