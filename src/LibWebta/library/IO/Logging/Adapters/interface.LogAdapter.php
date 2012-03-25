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
     * @name LogAdapter Interface
     * @category   LibWebta
     * @package    IO
     * @subpackage Logging
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Sergey Koksharov <http://webta.net/company.html>
     */
	interface LogAdapter
	{
		/**
		 * Open the storage resource.  If the adapter supports buffering, this may not
		 * actually open anything until it is time to flush the buffer.
		 */
		public function Open();
	
	
		/**
		 * Write a message to the log.  If the adapter supports buffering, the
		 * message may or may not actually go into storage until the buffer is flushed.
		 *
		 * @param $fields     Associative array, contains keys 'message' and 'level' at a minimum.
		 */
		public function Write($fields);
	
	
		/**
		 * Close the log storage opened by the log adapter.  If the adapter supports
		 * buffering, all log data must be sent to the log before the storage is closed.
		 */
		public function Close();
	
	
		/**
		 * Sets an option specific to the implementation of the log adapter.
		 *
		 * @param $optionKey       Key name for the option to be changed.  Keys are adapter-specific
		 * @param $optionValue     New value to assign to the option
		 */
	    public function SetOption($optionKey, $optionValue);
	}
	
?>