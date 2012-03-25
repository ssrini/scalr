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
     * @package    Core
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */
	
	/**
     * @name CoreException
     * @category   LibWebta
     * @package    Core
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     */	
	class CoreException extends Exception
	{ 
		
		/**
		* Constructor
		* @access public
		* @param string $message Exception message
		* @param int $code 0 - Error, 1 - Warning
		* @param bool $dolog Either exception should be logged or not. Used to prevent infinite loops inside Log.
		* @return void
		*/
 		function __construct($message, $code = 0, $dolog = false)
 		{	
 			$message = $code ? "Error: " . $message : "Warning: " . $message;
 			 				
 			// If CLI, print message
 			if (PHP_SAPI == "cli")
 				echo $message . "\n"; // FIXME: Add platform-independent line return
 				
 			parent::__construct($message, $code);
		}	
 		
 		/**
		* Return result of debug_backtrace
		* @access protected
		* @return string HTML'ed backtrace
		*/
		protected function Backtrace()
		{
			
			$backtrace = debug_backtrace();
			foreach ($backtrace as $bt) 
			{
				$args = '';
				foreach ($bt['args'] as $a) 
				{
					if (!empty($args)) {
						$args .= ', ';
					}
					switch (gettype($a)) {
					case 'integer':
					case 'double':
						$args .= $a;
						break;
					case 'string':
						$a = htmlspecialchars(substr($a, 0, 64)).((strlen($a) > 64) ? '...' : '');
						$args .= "\"$a\"";
						break;
					case 'array':
						$args .= 'Array('.count($a).')';
						break;
					case 'object':
						$args .= 'Object('.get_class($a).')';
						break;
					case 'resource':
						$args .= 'Resource('.strstr($a, '#').')';
						break;
					case 'boolean':
						$args .= $a ? 'True' : 'False';
						break;
					case 'NULL':
						$args .= 'Null';
						break;
					default:
						$args .= 'Unknown';
					}
				}
				if ($bt['file'])
					$output .= "
								<div style='padding-top:5px;border-bottom:1px solid #f0f0f0;font-size:x-small;'>&bull; {$bt['file']}:{$bt['line']}
								<br>{$bt['class']}{$bt['type']}{$bt['function']}($args)
								</div>
								";
				else
					$output .= "
								<div style='padding-top:5px;border-bottom:1px solid #f0f0f0;font-size:x-small;'>
								{$bt['class']}{$bt['type']}{$bt['function']}($args)
								</div>
								";
			}
			return $output;

		}
		
		
	} 
?>