<?php
class Scalr_Service_Zookeeper_Election {
	
	/**
	 * @var Scalr_Service_Zookeeper
	 */
	public $zookeeper;
	
	public $path;
	
	public $quorum;
	
	public $timeout;
	
	private $votes;
	
	private $isInitiator = false;
	
	const STATUS_READY = "ready";
	const STATUS_COMPLETE = "complete";
	const STATUS_NOTSET = null;
	
	/**
	 * @var Scalr_Service_Zookeeper_Lock
	 */
	private $lock;
	
	private $originalQuorum;
	
	private $logger;
	
	/**
	 * @param $config
	 * @key Scalr_Service_Zookeeper $zookeeper
	 * @key string $path
	 * @key int $quorum
	 * @key Scalr_Util_Timeout|int $timeout
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
		$this->lock = new Scalr_Service_Zookeeper_Lock(array(
			"zookeeper" => $this->zookeeper,
			"path" => "{$this->path}/lock"
		));
		$this->logger = Logger::getLogger(__CLASS__);
	}
	

	function vote ($data) {
		$this->zookeeper->create("{$this->path}/n", serialize($data), 
				array(Scalr_Service_Zookeeper::OPT_SEQUENCE => true));
		
		try {
			if (!$this->isInitiator) {
				// Initiator timeout is more accurate. 
				// But i need it too if initiator node crashes during election
				$this->timeout->reset();
			}
			
			$this->logger->debug("isInitiator: " . (int)$this->isInitiator);
			
			while (($this->timeout && !$this->timeout->reached()) || !$this->timeout) {
				if (($this->isInitiator && $this->capacity() >= $this->quorum) ||
					(!$this->isInitiator && $this->getStatus() == self::STATUS_COMPLETE)) {
					break;
				} else {
					Scalr_Util_Timeout::sleep(100);
				}
			}
		} catch (Exception $e) {
			$this->voteFinally();
			throw $e;
		}
		
		$this->voteFinally();
	}
	
	private function voteFinally () {
		$childStat = $this->zookeeper->getChildren($this->path);
		if (count($childStat->children)) {
			// Collect votes
			$this->logger->debug("Collect votes");			
			$this->votes = array();				
			sort($childStat->children);	
			foreach ($childStat->children as $childName) {
				if (strpos($childName, "n") === 0) {
					$childStat = $this->zookeeper->get("{$this->path}/{$childName}");
					$vote = unserialize(base64_decode($childStat->data64));
					$this->logger->debug("Add vote '".serialize($vote)."'");
					$this->votes[] = $vote;
				}
			}
		}
		
		$this->setStatus(self::STATUS_COMPLETE);
		$this->isInitiator = false;
		
		$this->logger->debug("Release lock");
		if ($this->originalQuorum) {
			$this->quorum = $this->originalQuorum;
			$this->originalQuorum = null;
		}
		$this->lock->release();
	}
	
	
	function initiate ($quorum=null) {
		if ($quorum != null) {
			$this->originalQuorum = $this->quorum;
			$this->quorum = $quorum;
		}
		try {
			$this->zookeeper->create($this->path);
		} catch (Scalr_Service_Zookeeper_Exception $ignore) {
		}
		
		if (!$this->lock->tryAcquire()) {
			throw new Scalr_Service_Zookeeper_InterruptedException("Cannot get exclusive lock on election node. "
					. "Another election is already initiated");
		}
		
		try {
			// Delete previous votes
			$childInfo = $this->zookeeper->getChildren($this->path);
			if ($childInfo->children) {
				foreach ($childInfo->children as $childName) {
					if (strpos($childName, "n") === 0) {
						$this->zookeeper->delete("{$this->path}/{$childName}");
					}
				}
			}
			
			$this->isInitiator = true;
			$this->setStatus(self::STATUS_READY);
			if ($this->timeout) {
				$this->timeout->reset();
			}
			
			$this->logger->info(sprintf("Election initiated (quorum: %d, path: %s)", $this->quorum, $this->path));
		
		} catch (Exception $e) {
			// Finally release lock
			$this->lock->release();
			throw $e;
		}
	}

	function isInitiated () {
		$status = $this->getStatus();
		return $status == self::STATUS_READY;
	}
	
	function getVotes () {
		return $this->votes;
	}

	function capacity () {
		$childStat = $this->zookeeper->get($this->path);
		// Num children - Lock - Status		
		$ret = $childStat->numChildren - 2;
		$this->logger->debug(sprintf("Election capacity: %d, numChildren: %s", $ret, $childStat->numChildren));
		return $ret;
	}
	
	function delete () {
		try {
			$this->zookeeper->deleteRecursive($this->path);
		} catch (Scalr_Service_Zookeeper_Exception $ignore) {
		}
	}

	private function getStatus () {
		try {
			$stat = $this->zookeeper->get("{$this->path}/status");
			return unserialize(base64_decode($stat->data64));
		} catch (Scalr_Service_Zookeeper_Exception $e) {
			if ($e->getCode() == Scalr_Service_Zookeeper_Exception::NOT_FOUND) {
				return self::STATUS_NOTSET;
			}
			throw $e;
		}
	}
	
	private function setStatus ($status) {
		if ($status != self::STATUS_NOTSET) {
			try {
				$this->zookeeper->set("{$this->path}/status", serialize($status));				
			} catch (Scalr_Service_Zookeeper_Exception $e) {
				if ($e->getCode() == Scalr_Service_Zookeeper_Exception::NOT_FOUND) {
					$this->zookeeper->create("{$this->path}/status", serialize($status));					
					return;
				}
				throw $e;
			}
		} else {
			try {
				$this->zookeeper->delete("{$this->path}/status");
			} catch (Scalr_Service_Zookeeper_Exception $ignore) {}
		}
	}
	

}