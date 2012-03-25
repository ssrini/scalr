<?php

class Scalr_System_Cronjob {
	
	protected $config;
			
	protected $jobName;	
	
	/**
	 * @return array
	 * @key string $description
	 * @key array $getoptRules @see Zend_Console_Getopt configuration options 
	 */
	static function getConfig () {
		return array(
			"description" => "Override cronjob description in a subclass",
			"getoptRules" => array()	 
		);
	}
	
	function __construct ($config=array()) {
		$this->config = Scalr_Util_Arrays::mergeReplaceRecursive(self::getConfig(), $config);
		foreach ($config as $k => $v) {
			if (property_exists($this, $k)) {
				$this->{$k} = $v;
			}
		}		
	}

	/**
	 * @param array $options Run options
	 * @key Zend_Console_Getopt $getopt
	 */
	function run ($options=array()) {
	}
}