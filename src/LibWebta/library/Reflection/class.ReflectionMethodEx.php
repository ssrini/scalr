<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
	 * This source file is subject to version 2 of the GPL license,
	 * that is bundled with this package in the file license.txt and is
	 * available through the world-wide-web at the following url:
	 * http://www.gnu.org/copyleft/gpl.html
     *
     * @category   LibWebta
     * @package    Reflection
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	/**
	 * @name ReflectionMethodEx
	 * @category LibWebta
	 * @package Reflection
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
    class ReflectionMethodEx extends ReflectionMethod 
	{
	    private $stdClassConstructArgs;
	    
	    function __construct($class_or_method, $name = null, $stdClassConstructArgs = array())
	    {
	        parent::__construct($class_or_method, $name);
	        $this->stdClassConstructArgs = $stdClassConstructArgs;
	    }
	    
	    /**
	     * Invoke method
	     *
	     * @return method
	     */
	    function invoke()
	    {
	        if (count($this->stdClassConstructArgs) > 1)
	           $stdClass = $this->getDeclaringClass()->newInstanceArgs($this->stdClassConstructArgs);
	        else 
	           $stdClass = $this->getDeclaringClass()->newInstance();
	           
	        return parent::invokeArgs($stdClass, func_get_args());
	    }
	    
	    /**
	     * Invoke method with args
	     *
	     * @param arrays $args
	     * @return method
	     */
	    function invokeArgs($args)
	    {
	        if (count($this->stdClassConstructArgs) > 1)
	           $stdClass = $this->getDeclaringClass()->newInstanceArgs($this->stdClassConstructArgs);
	        else 
	           $stdClass = $this->getDeclaringClass()->newInstance();
	           
	        return parent::invokeArgs($stdClass, $args);
	    }
	}
?>