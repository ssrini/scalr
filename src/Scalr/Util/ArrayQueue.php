<?php

class Scalr_Util_ArrayQueue implements Scalr_Util_Queue {
	
	protected $items;
	
	function __construct ($items=array()) {
		$this->items = $items;
	}
	
	function put ($data) {
		if ($data === null)
			throw new Scalr_Util_Exception("Queue item must be not NULL");

		array_push($this->items, $data);
		return true;
	}
	
	function peek () {
		return array_shift($this->items);
	}
	
	function capacity () {
		return count($this->items);
	}
	
}