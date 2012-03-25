<?php

class Scalr_Util_IgnoringQueue implements Scalr_Util_Queue {
	
	protected $queue;
	
	protected $ignoringSet;
	
	/**
	 * @param Scalr_Util_Queue $queue
	 * @param Scalr_Util_Set $ignoringSet
	 */
	function __construct ($queue, $ignoringSet) {
		$this->queue = $queue;
		$this->ignoringSet = $ignoringSet;
	}
	
	function put ($data) {
		if (!$this->ignoringSet->contains($data)) {
			return $this->queue->put($data);
		}
		return false;
	}
	
	function peek () {
		do {
			$item = $this->queue->peek();
		} while ($item !== null && $this->ignoringSet->contains($item));
		
		return $item;
	}
	
	function capacity () {
		return $this->queue->capacity();
	}

	/**
	 * @return Scalr_Util_Queue
	 */
	function getWrappedQueue () {
		return $this->queue;
	}
	
	/**
	 * @return Scalr_Util_Set
	 */
	function getIgnoringSet () {
		return $this->ignoringSet;
	}
}