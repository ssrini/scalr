<?php

interface Scalr_Util_Set {
	
	/**
	 * Adds the specified element to this set if it is not already present
	 * @param $item
	 * @return bool true if this set did not already contain the specified element 
	 */
	function add ($item);
	
	/**
	 * Removes the specified element from this set if it is present
	 * @param $item
	 * @return bool true if this set contained the element
	 */
	function remove ($item);
	
	/**
	 * Returns true if set contains such element 
	 * @param $item
	 * @return bool
	 */
	function contains ($item);
	
	/**
	 * Returns the number of elements in this set
	 * @return int
	 */
	function size ();
	
	/**
	 * Removes all of the elements from this set
	 * @return void
	 */
	function clear ();
}