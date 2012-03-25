<?php

class Scalr_System_Shell {
	const WIN = "win";
	const UNIX = "unix";

	private static $shellConfig = array(
		self::WIN => array(
			"os" => self::WIN,
			"CLRF" => "\r\n"
		),
		self::UNIX => array(
			"os" => self::UNIX,
			"CLRF" => "\n"
		)
	);
	
	private $os;
	private $CLRF;
	
	function __construct ($os=null) {
		if ($os === null) {
			$os = strtoupper(substr(PHP_OS, 0, 3)) == 'WIN' ? self::WIN : self::UNIX;
		}
		
		// Configure this shell
		foreach (self::$shellConfig[$os] as $k => $v) {
			$this->{$k} = $v;
		}
	}
	
	function execute($cmd, $args) {
		foreach ($args as $arg) {													 
			$farg .= " ". escapeshellarg($arg);
		}
		@exec(escapeshellcmd($cmd) . $farg, $notused, $retval);
		return ($retval == 0);
	}
		
		
	function executeRaw($cmd) {
		$cmd = str_replace(array("\r", "\n"), "", $cmd);
		@exec($cmd, $notused, $retval);
		return ($retval == 0);
	}


	function query($cmd, $args = array()) {
		foreach ($args as $arg) {
			$farg .= " ". escapeshellarg($arg);
		}
		@exec(escapeshellcmd($cmd) . $farg, $retval, $notused);
		$retval = implode($this->CLRF, $retval);
		return $retval;
	}

	function queryRaw($cmd, $singlestring = true) {
		$cmd = str_replace(array("\r", "\n"), "", $cmd);
		
		@exec($cmd, $retval);
		
		if ($singlestring) {
			$retval = implode($this->CLRF, $retval);
		} else {
			$retval = explode($this->CLRF, $retval);
		}
		return $retval;
	}	
}