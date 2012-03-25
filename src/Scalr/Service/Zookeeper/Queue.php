<?php

class Scalr_Service_Zookeeper_Queue implements Scalr_Util_Queue {
	
	/**
	 * @var Scalr_Service_Zookeeper
	 */
	public $zookeeper;
	
	/**
	 * @var string
	 */
	public $path;
	
	/**
	 * @var bool
	 */
	public $blocking;
	
	/**
	 * @var int
	 */
	public $blockingTimeout;
	
	protected $initialized = false;
	
	protected $children = array();
	
	protected $logger;
	
	/**
	 * 
	 * @param $config
	 * @key Scalr_Service_Zookeeper $zookeeper
	 * @key string $path
	 * @key bool $blocking
	 * @key int $blockingTimeout
	 * @key bool $autoInit
	 */
	function __construct ($config) {
		foreach ($config as $k => $v) {
			$this->{$k} = $v;
		}
		$this->logger = Logger::getLogger(__CLASS__);
		
		if ($this->autoInit) {
			$this->init();
			unset($this->autoInit);
		}
	}
	
	protected function init () {
		if (!$this->initialized) {
			if (!$this->zookeeper->exists($this->path)) {
				$this->zookeeper->create($this->path);
			}
			$this->initialized = true;
		}
	}
	
	function put ($data) {
		$this->init();
		$pathData = $this->zookeeper->create("{$this->path}/element", $data, array("sequence" => true));
	}
	
	function peek () {
		$this->init();
		do {
			// Fetch path child nodes
			if (!$this->children) {
				//$this->logger->debug("Getting queue path child nodes");
				
				if ($this->blocking) {
					$start = microtime(true);
					try {
						do {
							$childData = $this->zookeeper->getChildren($this->path);
	
							$loop = !$childData->children && 
									(($this->blockingTimeout && 
									 !Scalr_Util_Timeout::reached($this->blockingTimeout, $start)) || 
									 !$this->blockingTimeout);
						} while ($loop);
					} catch (Scalr_Util_TimeoutException $e) {
						return null;
					}
				} else {
					$childData = $this->zookeeper->getChildren($this->path);
				}
				
				if ($childData->children) {
					sort($childData->children);
					$this->children = $childData->children;
				} else {
					$this->logger->debug("Queue is empty");
					if (!$this->blocking) {
						return null;
					}
				}
			}

			$this->logger->debug("Children capacity: " . count($this->children));			
			while ($childName = array_shift($this->children)) {
				try {				
					$statData = $this->zookeeper->get("{$this->path}/{$childName}");
					$this->zookeeper->delete("{$this->path}/{$childName}");
					
					$this->logger->info("Peeked {$this->path}/{$childName} from queue");
					return base64_decode($statData->data64);
				} catch (Exception $wasDeleted) {
					// Continue loop					
					$this->logger->debug(sprintf("Got error while delete node %s. "
							. "I think it was processed by another client", "{$this->path}/{$childName}"));
				}
			}
			
		} while (!$this->children);
	}
	
	function capacity () {
		$this->init();
		if ($this->children) {
			return count($this->children);
		} else {
			$stat = $this->zookeeper->get($this->path);
			return $stat->numChildren;
		}
	}
	
	/**
	 * Delete queue and all her items 
	 */
	function delete () {
		$this->zookeeper->deleteRecursive($this->path);
	}
}