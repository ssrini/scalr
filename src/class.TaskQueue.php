<?
	class TaskQueue
	{
		/**
		 * Queue instances
		 *
		 * @var array
		 */
		private static $Instances = array();
		
		/**
		 * Queue name
		 *
		 * @var string
		 */
		private $QueueName;
		
		/**
		 * Intsance of ReflectionClass("QueueTask");
		 *
		 * @var ReflectionClass
		 */
		private $ReflectionTask;
		
		/**
		 * Attach to queue with specified name
		 *
		 * @param string $queue_name
		 * @return TaskQueue
		 */
		public static function Attach ($queue_name)
		{
			if (self::$Instances[$queue_name] === null)
			{
				self::$Instances[$queue_name] = new TaskQueue($queue_name);
			}
			return self::$Instances[$queue_name];
		}
		
		/**
		 * Constructor
		 *
		 * @param unknown_type $queue_name
		 */
		public function __construct($queue_name)
		{
			$this->QueueName = $queue_name;
			$this->DB = Core::GetDBInstance(null, true);
			$this->ReflectionTask = new ReflectionClass("Task");
			$this->LastTaskID = 0;
		}
		
		/**
		 * Returns queue size.
		 *
		 * @return integer
		 */
		public function Size()
		{
			return $this->DB->GetOne("SELECT COUNT(*) FROM task_queue WHERE queue_name=?", 
						array($this->QueueName)
					);
		}
		
		/**
		 * Increments failure attempts counter for task
		 */
		public function IncrementFailureAttemptsCounter(Task $Task)
		{
			$this->DB->Execute("UPDATE task_queue SET failed_attempts=failed_attempts+1 WHERE id=?", array($Task->ID));
		}
		
		/**
		 * Inserts the specified element into this queue, if possible.
		 *
		 * @return bool
		 */
		public function AppendTask(Task $Task)
		{
			return $this->DB->Execute("INSERT INTO task_queue SET
				queue_name	= ?,
				data		= ?,
				dtadded		= NOW()
			", array($this->QueueName, serialize($Task)));
		}
		
		/**
		 * Retrieves and removes the head of this queue, or null if this queue is empty.
		 *
		 * @return Task
		 */
		public function Poll($Task = null)
		{
			if ($Task === NULL)
				$Task = $this->Peek();
				
			if ($Task === NULL)
				return NULL;
			
			$this->DB->Execute("DELETE FROM task_queue WHERE id=?", array($Task->ID));
			
			return $Task;
		}
		
		/**
		 * Retrieves and removes the head of this queue. 
		 * This method differs from the poll method in that it throws an exception if this queue is empty. 
		 *
		 * @return Task
		 */
		public function Remove($Task = null)
		{
			$Task = $this->Poll($Task);
			if ($Task === NULL)
				throw new Exception(sprintf(_("Queue '%s' is empty"), $this->QueueName));
			
			return $Task;
		}
		
		/**
		 * Retrieves, but does not remove, the head of this queue, returning null if this queue is empty.
		 *
		 * @return Task
		 */
		public function Peek()
		{
			$dbtask = $this->DB->GetRow("SELECT * FROM task_queue WHERE queue_name=? AND id > ? ORDER BY id ASC",
				array($this->QueueName, $this->LastTaskID)
			);
			if (!$dbtask)
				return NULL;
				
			$this->LastTaskID = $dbtask['id'];
			$Task = unserialize($dbtask['data']);
			$Task->ID = $dbtask["id"];
			$Task->FailedAttempts = $dbtask["failed_attempts"]; 
			
			return $Task;
		}
		
		/**
		 * Retrieves, but does not remove, the head of this queue. 
		 * This method differs from the peek method only in that 
		 * it throws an exception if this queue is empty. 
		 *
		 * @return Task
		 */
		public function Element()
		{
			$Task = $this->Peek();
			if ($Task === NULL)
				throw new Exception(sprintf(_("Queue '%s' is empty"), $this->QueueName));
			
			return $Task;
		}
		
		/**
		 * Reset Queue pointer
		 *
		 */
		public function Reset()
		{
			$this->LastTaskID = 0;
		}
	}
?>