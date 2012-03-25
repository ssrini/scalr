<?php

// TODO: Add 'alarm' event
class Scalr_System_OS {
	const MEM_RES = "res";
	const MEM_VIRT = "virt";
	
	const LINUX = "Linux";
	const FREEBSD = "FreeBSD";
	const SUN = "Sun";
	
	private static $instance;

	private $shell;
	
	private $uname;

	/**
	 * @return Scalr_System_OS
	 */
	static function getInstance () {
		if (self::$instance === null) {
			self::$instance = new Scalr_System_OS();
		}
		return self::$instance;
	}	
	
	protected function __construct() {
	}

	/**
	 * @return Scalr_System_Shell
	 */
	private function getShell () {
		if ($this->shell === null) {
			$this->shell = new Scalr_System_Shell();
		}
		return $this->shell;
	}
	
	function getMemoryUsage ($pid, $flag=null) {
		
		if ($this->isLinux()) {
			$status = $this->getProcessStatus($pid);
			if ($flag == self::MEM_VIRT) {
				return (int)$status["VmSize"];
			} else if ($flag == self::MEM_RES) {
				return (int)$status["VmRSS"];
			} else {
				return array(
					self::MEM_VIRT => (int)$status["VmSize"],
					self::MEM_RES => (int)$status["VmRSS"]
				);
			}
			
		} else {
			// FreeBSD
			$output = $this->getShell()->query("ps -o vsize,rss -p {$pid}");
			$lines = explode("\n", $output);
			list($virt,$res) = array_map("intval", array_map("trim", explode(" ", trim(end($lines)), 2)));
	
			if ($flag == self::MEM_VIRT) {
				return $virt;
			} else if ($flag == self::MEM_RES) {
				return $res;
			} else {
				return array(
					self::MEM_RES => $res,
					self::MEM_VIRT => $virt
				);
			}
		}
	}
	
	function getParentProcess ($pid) {
		$status = $this->getProcessStatus($pid);
		return (int)$status["PPid"]; 
	}
	
	function getProcessChilds ($pid) {
		if (!file_exists("/proc/{$pid}")) {
			throw new Scalr_System_Exception("No such process");
		}
		
		$ret = array();
		foreach (glob("/proc/[0-9]*/status") as $filename) {
			if (is_numeric(basename(dirname($filename)))) {
				try {
					$status = $this->readProcessStatus($filename);
					if ($status["PPid"] == $pid) {
						$ret[] = (int)$status["Pid"];
					}
				} catch (Scalr_System_Exception $ignore) {
				} 
			} 
		}
		
		return $ret;
	}
	
	function getProcessStatus ($pid) {
		if (!file_exists("/proc/{$pid}")) {
			throw new Scalr_System_Exception("No such process");
		}
		
		return $this->readProcessStatus("/proc/{$pid}/status");
	}
	
	private function readProcessStatus ($filename) {
		if (!is_readable($filename)) {
			throw new Scalr_System_Exception(sprintf(
					"Cannot access process status file '%s'", $filename));
		}
		
		if ($this->isLinux()) {
			$ret = array();
			
			foreach (file($filename) as $line) {
				list($key, $value) = array_map("trim", explode(":", $line, 2));
				$ret[$key] = $value;
			}
			
			return $ret;
		} else if ($this->isFreeBsd()) {

			$values = explode(" ", file_get_contents($filename));
			
			$ret = array(
				"Name" => $values[0],
				"Pid" => $values[1],
				"PPid" => $values[2]
			);
			
			return $ret;
			
		} else {
			throw new Scalr_System_Exception(sprintf(
					"readProcessStatus is not implemented for OS '%s'", $this->getUname()->system));
		}
	}
	
	function tok ($name) {
		return (int)hexdec(substr(md5($name), 0, 7));
	}
	
	/**
	 * @return Scalr_System_OS_Uname
	 */
	function getUname () {
		if ($this->uname === null) {
			$uname = new Scalr_System_OS_Uname();
			
			list($uname->system, $uname->hostname, $uname->release, 
					$uname->version, $uname->machine) = explode(" ", php_uname("a"));

			$this->uname = $uname;
		}
		return $this->uname;
	}
	
	function isLinux () {
		return $this->getUname()->system == self::LINUX;
	}
	
	function isFreeBsd () {
		return $this->getUname()->system == self::FREEBSD;	
	}
	
	function isSun () {
		return $this->getUname()->system == self::SUN;
	}
	
	function getHostName () {
		return $this->getUname()->hostname;
	}
	
	function getLinuxName () {
		$uname = $this->getUname();
		if ($uname->linuxName === null) {
			$output = $this->getShell()->queryRaw("cat /etc/*-release|grep -v LSB");
			$lines = explode("\n", $output);
			$uname->linuxName = $lines[0];
		}
		
		return $uname->linuxName;
	}
}

class Scalr_System_OS_Uname {
	public 
		$system, 
		$hostname, 
		$release, 
		$version, 
		$machine,
		$linuxName; 
}