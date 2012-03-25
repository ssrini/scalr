<?php

/**
 * Utility class for better timeouts handling 
 * Usage:
 * <php>
 * $timeout = new Scalr_Util_Timeout(5000); // 5 seconds
 * try {
 * 		while (!$timeout->reached()) {
 * 			// Do something in a loop
 * 		}
 * } catch (Scalr_Util_TimeoutException $e) {
 * 		print "Timeout exceed!";
 * }
 * </php
 * 
 * @author Marat Komarov
 *
 */
class Scalr_Util_Timeout {
	
	const SECONDS = 1000000;
	const MILLIS = 1000;
	const MICROS = 1;
	
	public static $messages = array(
		self::SECONDS => "secconds",
		self::MILLIS => "millis",
		self::MICROS => "micros"
	);
	
	public $start;
	private $timeout;
	private $dim;
	
	static function get ($timeout) {
		if ($timeout instanceof Scalr_Util_Timeout) {
			return $timeout;
		} else {
			return new Scalr_Util_Timeout($timeout);
		}
	}
	
	function __construct ($timeout, $dim=self::MILLIS) {
		$this->reset();
		$this->setTimeout($timeout, $dim);		
	}
	
	function reset () {
		$this->start = microtime(true);
	}
	
	/**
	 * @return bool
	 * @throws Scalr_Util_TimeoutException
	 */
	function reached ($throw=true) {
		if ((microtime(true) - $this->start) > $this->timeout) {
			if ($throw) {
				throw new Scalr_Util_TimeoutException();
			} else {
				return true;			
			}
		}
		return false;
	}
	
	function format () {
		return sprintf("%d %s", $this->timeout*$this->dim, self::$messages[$this->dim]);
	}
	
	/**
	 * Delay script execution
	 * @static
	 * @param int $timeout
	 * @param int $dim Timeout dimension
	 */
	function sleep ($timeout, $dim=self::MILLIS) {
		usleep($timeout*$dim);
	}

	function setTimeout ($timeout, $dim=self::MILLIS) {
		$this->timeout = $timeout*$dim/1000000;
		$this->dim = $dim;
	}
	function getTimeout () {
		return $this->timeout;
	}
}