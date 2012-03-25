<?
	/**
	 * Abstract task
	 *
	 */
	abstract class Task
	{		
		/**
		 * Task ID
		 *
		 * @var integer
		 */
		public $ID;
		
		/**
		 * Failed attempts
		 * @var integer
		 */
		public $FailedAttempts;
	}
?>