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
     * @name RedirectException
     * @version 1.0
     * @category   LibWebta
     * @package    Core
     * @author Alex Kovalyov <http://webta.net/company.html>
     */		
	class RedirectException extends Exception
	{ 
		
		/**
		* Constructor
		* @access public
		* @return void
		*/
 		function __construct($message, $code = 0)
 		{
 			parent::__construct($message, $code);
 			// Add log entry
 			Log::Log($code == 0 ? "Error: " : "Warning: " . $this->getMessage());

			redirect(CF_ROUTER_URL."/error.php?msg=".urlencode($message));
		}
	
	
		/**
		* Destructor
		* @access public
		* @return void
		* @ignore 
		*/
		function __destruct()
 		{
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