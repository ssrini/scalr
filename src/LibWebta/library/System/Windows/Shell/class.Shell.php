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
     * @package    System_Windows
     * @subpackage Shell
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	/**
	 * @name       WinShell
	 * @category   LibWebta
     * @package    System_Windows
     * @subpackage Shell
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 */
    class WinShell 
	{

		/**
		* Execute command
		* @access public
		* @param string $cmd Command to be executed
		* @param mixed $args Array of command parameters
		* @return boolean
		*/

		public final  function Execute($cmd, $args) 
		{
			foreach ($args as $arg)
			{													 
				$farg .= " ". escapeshellarg($arg);
			}
			@exec(escapeshellcmd($cmd) . $farg, $notused, $retval);
			return ($retval == 0);
		}
		
		
		/**
		* Execute raw shell string
		* @access public
		* @param string $cmd Command to be executed
		* @return boolean
		*/

		public final  function ExecuteRaw($cmd) 
		{
			$cmd = str_replace(array("\r", "\n"), "", $cmd);
			@exec($cmd, $notused, $retval);
			return ($retval == 0);
		}


		/**
		* Execute command, return stdout and stderr
		* @access public
		* @param string $cmd Command to be executed
		* @param mixed $args Array of command parameters
		* @return string
		*/

		public final  function Query($cmd, $args = array()) 
		{
			foreach ($args as $arg)
			{
				$farg .= " ". escapeshellarg($arg);
			}
			@exec(escapeshellcmd($cmd) . $farg, $retval, $notused);
			$retval = implode("\r\n", $retval);
			return $retval;
		}


		/**
		* Execute raw command line
		* @access public
		* @param string $cmd Full command line with params
		* @param string $singlestring Either return a imploded single string or array of lines
		* @return string
		*/

		public final  function QueryRaw($cmd, $singlestring = true) 
		{
			$cmd = str_replace(array("\r", "\n"), "", $cmd);
			
			@exec($cmd, $retval);
			
			if ($singlestring)
				$retval = implode("\r\n", $retval);
			else
				$retval = explode("\r\n", $retval);
			return $retval;
		}


	}
?>