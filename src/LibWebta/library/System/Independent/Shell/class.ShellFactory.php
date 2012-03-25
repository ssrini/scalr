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
     * @package    System
     * @subpackage Shell
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	/**
	 * @name       ShellFactory
	 * @category   LibWebta
     * @package    System
     * @subpackage Shell
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
	 *
	 */
	class ShellFactory 
	{
        /**
         * Return shell insrtance
         *
         * @static 
         * @return object
         */
		public static function GetShellInstance()
		{
			// Yeah, no much stuff here now
			if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')
			{
				Core::Load("System/Windows/Shell/Shell");
				return new WinShell();
			}
			else
			{
				Core::Load("System/Unix/Shell/Shell");
				return new Shell();
			}
		}
		
	}
?>