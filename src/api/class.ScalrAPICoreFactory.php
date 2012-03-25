<?php
	
	/**
     * Scalr API Core Factory
     *
     */
    class ScalrAPICoreFactory
    {
    	/**
    	 * Create ScalrAPICore Object
    	 * @return ScalrAPICore
    	 */
    	public static function GetCore($version)
    	{
    		// Get Class name for Environment object
    		$class_version = str_replace(".", "_", $version);
    		$class_name = "ScalrAPI_{$class_version}";
    		
    		// Check class
    		if (class_exists($class_name))
    		{
    			// Get new instance of ScalrEnvironment object by version
    			$reflect = new ReflectionClass($class_name);
    			return $reflect->newInstance($version);
    		}
    		else
    			throw new Exception(sprintf(_("Version '%s' not supported by Scalr API"), $version));
    	}
    }
?>