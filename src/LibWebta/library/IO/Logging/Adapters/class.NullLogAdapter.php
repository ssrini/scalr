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
     * @package    IO
     * @subpackage Logging
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	/**
	 * Load LogAdapter
	 */
	Core::Load("IO/Logging/Adapters/interface.LogAdapter.php");

	/**
     * @name NullLogAdapter
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	class NullLogAdapter extends Core implements LogAdapter
	{
	    /**
	    * Class Constructor
	    *@ignore 
	    */
	    public function __construct()
	    {
	    	parent::__construct();
	    	
	        return true;
	    }
	
	
	    /**
	    * Class Destructor
	    *
	    * Always check that the file has been closed and the buffer flushed before destruction.
	    * @ignore 
	    */
	    public function __destruct()
	    {
	        $this->Close();
	    }
	
	
		/**
		 * Sets an option specific to the implementation of the log adapter.
		 *
		 * @param  $optionKey      Key name for the option to be changed.  Keys are adapter-specific
		 * @param  $optionValue    New value to assign to the option
		 * @return bool            True
		 */
	    public function SetOption($optionKey, $optionValue)
	    {
	        return true;
	    }
	
	
		/**
		 * Sets an option specific to the implementation of the log adapter.
		 *
		 * @param  $optionKey      Key name for the option to be changed.  Keys are adapter-specific
		 * @param  $optionValue    New value to assign to the option
		 * @return bool            True
		 */
		public function Open($filename = null, $accessMode = 'a')
		{
	        return true;
		}
	
	
		/**
		 * Write a message to the log.  This function really just writes the message to the buffer.
		 * If buffering is enabled, the message won't hit the filesystem until the buffer fills
		 * or is flushed.  If buffering is not enabled, the buffer will be flushed immediately.
		 *
		 * @param  $message    Log message
		 * @return bool        True
		 */
	    public function Write($fields)
	    {
		    return true;
		}
	
	
		/**
		 * Closes the file resource for the logfile.  Calling this function does not write any
		 * buffered data into the log, so flush() must be called before close().
		 *
		 * @return bool        True
		 */
		public function Close()
		{
		    return true;
		}
	
	}
	
?>