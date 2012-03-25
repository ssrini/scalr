<?php

class Scalr_System_Ipc_ShmQueue implements Scalr_Util_Queue {
	
	public $key;
	
	protected $name;
	
	public $perms = 0666;
	
	public $maxMsgSize = 65536; // 64 Kb
	
	public $blocking = false;
	
	public $blockingTimeout;
	
	protected $seg;
	
	private $initialized = false;
	
	private static $msgsnd_errors = array(
		/*EACCES*/ 13 => "The calling process does not have write permission on the message queue",
		MSG_EAGAIN => "The message can't be sent due to the msg_qbytes limit for the queue",
		/*EFAULT*/ 14 => "The address pointed to by msgp isn't accessible",
		/*EIDRM*/ 43 => "The message queue was removed",
		/*EINTR*/ 4 => "Sleeping on a full message queue condition, the process caught a signal",
		/*EINVAL*/ 22 => "Invalid msqid value, or non-positive mtype value, or invalid msgsz value (less than 0 or greater than the system value MSGMAX)",
		/*ENOMEM*/ 12 => "The system does not have enough memory to make a copy of the message pointed to by msgp"
	);
	
	private static $msgrcv_errors = array(
		/*E2BIG*/ 7 => "The message text length is greater than msgsz and MSG_NOERROR isn't specified in msgflg",
		/*EACCES*/ 13 => "The calling process does not have read permission on the message queue",
		MSG_EAGAIN => "No message was available in the queue and IPC_NOWAIT was specified in msgflg",
		/*EFAULT*/ 14 => "The address pointed to by msgp isn't accessible",
		/*EIDRM*/ 43 => "While the process was sleeping to receive a message, the message queue was removed",
		/*EINTR*/ 4 => "While the process was sleeping to receive a message, the process caught a signal",
		/*EINVAL*/ 22 => "msgqid was invalid, or msgsz was less than 0",
		MSG_ENOMSG => "IPC_NOWAIT was specified in msgflg and no message of the requested type existed on the message queue"
	);
	
	private $logger;
	
	/**
	 * @param $config
	 * @key int $key
	 * @key string $name
	 * @key int $perms
	 * @key int $maxMsgSize
	 * @key bool $blocking
	 * @key int $blockingTimeout
	 * @key bool $autoInit Initialize all component resources immediately in constructor (default false)
	 * @return Scalr_System_Ipc_ShmQueue
	 */
	function __construct ($config) {
		if (!function_exists('msg_get_queue')) {
			throw new Scalr_System_Ipc_Exception('msg_* functions are required');
		}
		
		foreach ($config as $k => $v) {
			if (property_exists($this, $k)) {
				$this->{$k} = $v;
			}
		}
		$this->logger = Logger::getLogger(__CLASS__);
		
		if (!isset($this->key) && $config["name"]) {
			$this->key = Scalr_System_OS::getInstance()->tok($config["name"]);
			$this->logger->debug(sprintf("Queue key 0x%08x from name '%s' is generated", $this->key, $config["name"]));			
		}
		
		if ($config["autoInit"]) {
			$this->init();
		}
	}
	
	protected function init () {
		if (! $this->initialized) {
			$this->logger->info(sprintf("Initialize queue (key: 0x%08x, blocking %s, blockingTimeout: %s)", 
					$this->key, $this->blocking, $this->blockingTimeout));

			$this->seg = @msg_get_queue($this->key, $this->perms);
			
			if (!$this->seg) {
				//$this->logger->fatal("msg_get_queue: ({$this->name}) (".serialize($GLOBALS["warnings"]).")");
				throw new Scalr_System_Ipc_Exception(sprintf("msg_get_queue failed for key 0x%08x", $this->key));
			}
			$this->initialized = true;
		}
	}
	
	function put ($data) {
		$this->init();
		
		if (!@msg_send($this->seg, 1, serialize($data), false, false, $errno)) {
			throw new Scalr_System_Ipc_Exception($errno ? self::$msgsnd_errors[$errno] : "Cannot send message", $errno);
		}
		
		return true;
	}
	
	function peek () {
		$this->init();
		$flags = $this->blocking && !$this->blockingTimeout ? 0 : MSG_IPC_NOWAIT;
		
		if (!$this->blockingTimeout || !$this->blocking) {
			msg_receive($this->seg, 1, $msgtype, $this->maxMsgSize, $message, false, $flags, $errno);
		} else {
			$timeout = new Scalr_Util_Timeout($this->blockingTimeout);
			try {
				while (!$message && !$timeout->reached()) {
					if (!msg_receive($this->seg, 1, $msgtype, $this->maxMsgSize, $message, false, $flags, $errno)) {
						$timeout->sleep(10);
					}
				}
			} catch (Scalr_Util_TimeoutException $e) {
				return null;
			}
		}
		
		if ($message) {
			return unserialize($message);
		} else {
			if ($errno == MSG_ENOMSG && !$this->blocking) {
				return null;
			}
			
			if ($errno == 22) {
				return null;
			}
			
			throw new Scalr_System_Ipc_Exception($errno ? self::$msgrcv_errors[$errno] : "Cannot receive message", $errno);
		}
	}
	
	function capacity () {
		$this->init();
		$stat = msg_stat_queue($this->seg);
		return $stat["msg_qnum"];
	}

	function stat () {
		$this->init();
		return msg_stat_queue($this->seg);
	}
	
	function delete () {
		$this->logger->debug(sprintf("Delete queue 0x%08x", $this->key));
		return msg_remove_queue($this->seg);
	}
}