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
	 * @name ReflectionClassEx
	 * @category LibWebta
	 * @package Reflection
	 * @author Igor Savchenko <http://webta.net/company.html>
	 */
    class ReflectionClassEx extends ReflectionClass 
	{
	    private $ConstructorArgs = array();
	    
	    function __construct($name, $args = array())
	    {
	        parent::__construct($name);
	        $this->ConstructorArgs = $args;
	    }
	    
	    /**
	     * Return public methods of class
	     *
	     * @return array
	     */
	    function getPublicMethods()
	    {
	        $methods = array();
	        foreach (parent::getMethods() as $method) 
	        {
                // Don't aggregate magic methods
                if ('__' == substr($method->getName(), 0, 2))
                    continue;
    
                // Show only public methods
                if ($method->isPublic()) 
                    $methods[] = new ReflectionMethodEx($this->getName(), $method->getName(), $this->ConstructorArgs);
            }
            
            return $methods;
	    }
	}
?>