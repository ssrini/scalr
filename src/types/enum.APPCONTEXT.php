<?
	final class APPCONTEXT
	{
		/**
		 * We are in the control panel
		 *
		 */
		const CONTROL_PANEL = 1;
				
		/**
		 * We are inside cronjob
		 *
		 */
		const CRONJOB = 2;
		
		/**
		 * We are in the order wizard
		 *
		 */
		const ORDER_WIZARD = 3;
		
		/**
		 * Context not yet determined (you should never get this)
		 *
		 */
		const NOT_YET_DEFINED = 4;
		
		/**
		 * Event handler
		 *
		 */
		const EVENT_HANDLER = 5;
		
		/**
		 * Ajax request
		 */
		const AJAX_REQUEST = 6;
		
		public static function GetContextName($context)
		{
			switch($context)
			{
				case self::CONTROL_PANEL:
					
					return "User";
					
					break;
					
				case self::CRONJOB:
					
					return "Cronjob";
					
					break;
					
				case self::ORDER_WIZARD:
					
					return "Order wizard";
					
					break;
					
				case self::NOT_YET_DEFINED:
					
					return "Unknown";
					
					break;
					
				case self::EVENT_HANDLER:
					
					return "Event handler";
					
					break;
					
				case self::AJAX_REQUEST:
					
					return "Ajax request";
					
					break;
			}
		}
	}
	
?>