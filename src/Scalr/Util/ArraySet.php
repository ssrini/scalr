<?php

class Scalr_Util_ArraySet implements Scalr_Util_Set {
	
	private $items;
	
	function __construct ($items=array()) {
		$this->clear();
		if ($items) {
			foreach ($items as $item) {
				$this->add($item);
			}
		}
	}
	
	function add ($item) {
		if (false === array_search($item, $this->items)) {
			$this->items[] = $item;
			return true;
		}
		return false;
	}
	
	function remove ($item) {
		if (false !== ($i = array_search($item, $this->items))) {
			array_splice($this->items, $i, 1);
			return true;
		}
		return false;
	}
	
	function contains ($item) {
		return false !== array_search($item, $this->items);
	}
	
	function size () {
		return count($this->items);
	}
	
	function clear () {
		$this->items = array();
	}	
}