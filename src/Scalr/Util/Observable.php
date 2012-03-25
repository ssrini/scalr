<?php

/**
 * Observer pattern implementation. 
 * Base class for all classes that can fire events and add listeners
 * 
 * @author Marat Komarov
 */
class Scalr_Util_Observable {
	/**
	 * Event listeners map
	 * event -> [listener1, listener2 ...]
	 * @var array
	 */
	private $eventListeners = array();
	
	/**
	 * 'true' when object temporary does not fire events
	 * @var boolean
	 */
	private $eventsSuspended = false;
	
	/*
	function setConfig ($config, $override=true) {
		parent::setConfig($config);
		foreach ($this->initialConfig["listeners"] as $event => $listener) {
			$this->on($event, $listener);
		}
	}
	*/
	
	/**
	 * Define object events
	 * @param array $events Event names, ex: "ready", "loaded", "actionFailed"
	 */
	function defineEvents ($events) {
		foreach ($events as $event) {
			if (!array_key_exists($event, $this->eventListeners)) {
				$this->eventListeners[$event] = array();
			}
		}
	}
	
	/**
	 * Fire event. Call in a loop each registered listener with arguments followed by $event
	 * @param string $event
	 */
	function fireEvent ($event /** args */) {
		if (!$this->eventsSuspended) {
			$listeners = $this->eventListeners[$event];
			if ($listeners) {
				$args = array_slice(func_get_args(), 1);
				foreach ($listeners as $callback) {
					$r = call_user_func_array($callback, $args);
					if ($r === false) {
						return false;
					}
				}
			}
		}
		return true;
	}
	
	/**
	 * Add event listener
	 * Usage:
	 * 1)@param string $event
	 *   @param callback $callback
	 *
	 * 2)@param object $listener
	 * @throws Exception
	 */
	function addListener ()	{
		$args = func_get_args();
		if (is_object($args[0])) {
			return $this->addObjectListener($args[0]);
		}
		else if (is_string($args[0]) && is_callable($args[1])) {
			return $this->addFnListener($args[0], $args[1]);
		}
		
		throw new Exception('Invalid arguments');
	}
	
	/**
	 * Add event listening function
	 * @param string $event
	 * @param string/array $callback
	 * @throws Exception
	 */
	private function addFnListener ($event, $callback) {
		if (!array_key_exists($event, $this->eventListeners)) {
			throw new Exception("Event '$event' is not defined");
		}
		
		if (!is_callable($callback)) {
			throw new Exception("Listener is not callable");
		}
		
		$this->eventListeners[$event][] = $callback;
		
		return true;
	}
	
	/**
	 * Add event listener object
	 * @param object $listener
	 */
	private function addObjectListener ($listener) {
		$methods = get_class_methods($listener);
		foreach (array_keys($this->eventListeners) as $event) {
			$method = 'on' . ucfirst($event);
			if (in_array($method, $methods)) {
				$this->eventListeners[$event][] = array($listener, $method); 
			}
		}
		
		return true;
	}
	
	/**
	 * Remove event listener
	 * Usage:
	 * 1)@param string $event
	 *   @param callback $callback
	 * 
	 * 2)@param object $listener
	 * @throws Exception
	 */
	function removeListener () {
		$args = func_get_args();
		if (is_object($args[0])) {
			return $this->removeObjectListener($args[0]);
		}
		else if (is_string($args[0]) && is_callable($args[1])) {
			return $this->removeFnListener($args[0], $args[1]);
		}
		
		throw new Exception('Invalid arguments');
	}

	/**
	 * Remove event listener function
	 * @param string $event
	 * @param string/array $callback
	 * @return boolean
	 * @throws Exception
	 */
	private function removeFnListener ($event, $callback) {
		if (!array_key_exists($event, $this->eventListeners)) {
			throw new Exception("Event '$event' is not defined");
		}

		if (false !== ($k = array_search($callback, $this->eventListeners[$event]))) {
			unset($this->eventListeners[$event][$k]);
			return true;
		}
		
		return false;
	}
	
	/**
	 * Remove event listener object
	 * @param object $listener
	 */
	private function removeObjectListener ($listener) {
		$methods = get_class_methods($listener);
		foreach (array_keys($this->eventListeners) as $event) {
			$method = 'on' . $event;
			if (in_array($method, $methods)) {
				$callback = array($listener, $method);
				if (false != ($k = array_search($callback, $this->eventListeners[$event]))) {
					unset($this->eventListeners[$k]);
				}
			}
		}
		return true;			
	}
	
	function removeAllListeners () {
		foreach (array_keys($this->eventListeners) as $event) {
			$this->eventListeners[$event] = array();
		}
	}
	
	function resumeEvents () {
		$this->eventsSuspended = false;
	}
	
	function suspendEvents () {
		$this->eventsSuspended = true;
	}
	
	/**
	 * Alias for #addListener
	 */
	function on () {
		$args=func_get_args();
		return call_user_func_array(array($this, 'addListener'), $args);
	} 
	
	/**
	 * Alias for #removeListener
	 */
	function un () {
		$args=func_get_args();
		return call_user_func_array(array($this, 'removeListener'), $args);
	}	
}
