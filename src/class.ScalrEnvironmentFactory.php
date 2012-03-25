<?
	
	/**
     * Scalr Environment Factory
     *
     */
    class ScalrEnvironmentFactory
    {
    	/**
    	 * Create ScalrEnvironment Object
    	 * @return ScalrEnvironment
    	 */
    	public static function CreateEnvironment($version)
    	{
    		// Get Class name for Environment object
    		$class_version = str_replace("-", "", $version);
    		$class_name = "ScalrEnvironment{$class_version}";
    		
    		// Check class
    		if (class_exists($class_name))
    		{
    			// Get new instance of ScalrEnvironment object by version
    			$reflect = new ReflectionClass($class_name);
    			return $reflect->newInstance();
    		}
    		else
    			throw new Exception(sprintf(_("Version '%s' not supported"), $version));
    	}
    }
?>