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
     * @subpackage PCNTL
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	/**
     * @name ProcessManager
     * @category   LibWebta
     * @package    IO
     * @subpackage PCNTL
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @example tests.php
     * @see tests.php
     */
	class ProcessManager extends Core
    {
        /**
         * SignalHandler
         *
         * @var SignalHandler
         * @access private
         */
        private $SignalHandler;
        
        /**
         * Proccess object
         *
         * @var object
         * @access protected
         */
        protected $ProcessObject;
        
        /**
         * PIDs of child processes
         *
         * @var array
         * @access public
         */
        public $PIDs;
        
        /**
         * Maximum allowed childs in one moment
         *
         * @var integer
         * @access public
         */
        public $MaxChilds;
        
        public $PIDDir;
        
        private $Logger;
        
        private $ChildProcessExecTimeLimit = 0;
        
        /**
         * Proccess manager Constructor
         *
         * @param SignalHandler $SignalHandler
         */
        function __construct(&$SignalHandler)
        {
            $this->Logger = Logger::getLogger('ProcessManager');
        	
        	if ($SignalHandler instanceof SignalHandler)
            {
                $SignalHandler->ProcessManager = &$this;
                $this->SignalHandler = $SignalHandler;
                
                $this->MaxChilds = 5;
                                  
                $this->Logger->debug("Process initialized.");
            }
            else 
                self::RaiseError("Invalid signal handler");  
        }
        
        public function SetChildExecLimit($limit)
        {
        	$this->ChildProcessExecTimeLimit = $limit;
        }
        
        /**
         * Destructor
         * @ignore 
         */
        function __destruct()
        {
  
        }
        
        /**
         * Set MaxChilds
         *
         * @param integer $num
         * @final 
         */
        final public function SetMaxChilds($num)
        {
            if (count($this->PIDs) == 0)
            {
                $this->MaxChilds = $num;
                
                $this->Logger->debug("Number of MaxChilds set to {$num}");
            }
            else
                self::RaiseError("You can only set MaxChilds *before* you Run() is executed.");
        }
        
        public function SetPIDDir ($path)
        {
        	$this->PIDDir = $path;
        } 
        
        /**
         * Start Forking
         *
         * @param object $ProcessObject
         * @final 
         */
        final public function Run(&$ProcessObject)
        {
            // Check for ProcessObject existence
            if (!is_object($ProcessObject) || !($ProcessObject instanceof IProcess))
                self::RaiseError("Invalid Proccess object", E_ERROR);    
               
            // Set class property   
            $this->ProcessObject = $ProcessObject;
            $pid = posix_getpid();
            
        	if ($this->PIDDir)
            {
            	$this->Logger->debug("Touch process PID file {$pid}");
            	@touch("{$this->PIDDir}/{$pid}");
            }
            
            $this->Logger->debug("Executing 'OnStartForking' routine");
                            	
            // Run routines before threading            	
            $this->ProcessObject->OnStartForking();  
            
            $this->Logger->debug("'OnStartForking' successfully executed.");
            
            if (count($this->ProcessObject->ThreadArgs) != 0)
            {
            
	            // Add handlers to signals
	            $this->SignalHandler->SetSignalHandlers();
	            
	            $this->Logger->debug("Executing ProcessObject::ForkThreads()");
	            
	            // Start Threading
	            $this->ForkThreads();
	            
	            // Wait while threads working 
	            $iteration = 1;          
	            while (true)
	        	{
	        		if (count($this->PIDs) == 0)
	        			break;
	
	        		if ($this->ChildProcessExecTimeLimit != 0)
					{
		        		foreach ($this->PIDs as $ipid=>$ipid_info)
	        	        {
							if ($ipid_info['start_time']+$this->ChildProcessExecTimeLimit < time())
							{
								$this->Logger->error(sprintf(
									_("Maximum execution time of %s seconds exceeded in %s. Killing process..."),
									$this->ChildProcessExecTimeLimit,
									get_class($this->ProcessObject)."(Child PID: {$ipid_info['pid']})"
								));
								
								posix_kill($ipid, SIGKILL);
							}
						}
        	        }
	        			
	        		sleep(2);
	        	    
	        	    if ($iteration++ == 10)
	        	    {
	        	        $this->Logger->debug("Goin to MPWL. PIDs(".implode(", ", array_keys($this->PIDs)).")");
	        	        
	        	        //
	        	        // Zomby not needed.
	        	        //
	        	        
	        	        $pid = pcntl_wait($status, WNOHANG | WUNTRACED);
	        	        if ($pid > 0)
			    		{
			    		    $this->Logger->debug("MPWL: pcntl_wait() from child with PID# {$pid} (Exit code: {$status})");
			  
			    		    foreach((array)$this->PIDs as $ipid=>$ipid_info)
			    			{
			    				if ($ipid == $pid)
			    				{
			    					if ($this->PIDDir)
			    					{
			    						$this->Logger->debug("Delete thread PID file $pid");
			    						@unlink($this->PIDDir . "/" . $pid);
			    					}
			    					unset($this->PIDs[$ipid]);
			    				}
			    			}
			    			
			    			$this->ForkThreads();
			    		}
	        	        
	        	        foreach ($this->PIDs as $ipid=>$ipid_info)
	        	        {
	        	           	$res = posix_kill($ipid, 0);
	        	           	$this->Logger->debug("MPWL: Sending 0 signal to {$ipid} = ".intval($res));
	        	                              
							if ($res === FALSE)
							{
                       			$this->Logger->debug("MPWL: Deleting '{$ipid}' from PIDs queue");
		                   		if ($this->PIDDir)
    							{
    								$this->Logger->debug("Delete thread PID file {$ipid}");
    								@unlink($this->PIDDir . "/" . $ipid);
    							}
                       			unset($this->PIDs[$ipid]);
                   			}
	        	        }
	
	        	        $iteration = 1;
	        	    }
	        	    
	        	}
            }
            else
            {
            	$this->Logger->debug("ProcessObject::ThreadArgs is empty. Nothing to do.");
            }
            
        	$pid = posix_getpid();
            if ($this->PIDDir)
            {
            	$this->Logger->debug("Delete Process PID file {$pid}");
            	@unlink("{$this->PIDDir}/{$pid}");
            } 
        	
        	$this->Logger->debug("All childs exited. Executing OnEndForking routine");
        	   
        	// Run routines after forking
            $this->ProcessObject->OnEndForking();
            
            $this->Logger->debug("Main process complete. Exiting...");
                
            exit();
        }
        
        /**
         * Start forking processes while number of childs less than MaxChilds and we have data in ThreadArgs
         * @access private
         * @final 
         */
        final public function ForkThreads()
        {
            while(count($this->ProcessObject->ThreadArgs) > 0 && count($this->PIDs) < $this->MaxChilds)
            {
                $arg = array_shift($this->ProcessObject->ThreadArgs);
                
                if ($arg)
                {
                	$this->Fork($arg);
                	
                	usleep(500000);
                }
            }
        }
        
        /**
         * Fork child process
         *
         * @param mixed $arg
         * @final
         */
        final private function Fork($arg)
        {
        	$pid = @pcntl_fork();
			if(!$pid) 
			{
			    try
			    {
                    if ($this->ProcessObject->IsDaemon)
                    	$this->DemonizeProcess();
                    	
			    	$this->ProcessObject->StartThread($arg);
			    }
			    catch (Exception $err)
			    {
			        $this->Logger->error($err->getMessage());
			    }
				exit();
			}
			else
			{
			    if ($this->PIDDir)
			    { 
			    	$this->Logger->debug("Touch thread PID file $pid");
			    	touch($this->PIDDir."/".$pid);
			    }
			    	
				$this->Logger->debug("Child with PID# {$pid} successfully forked");
                    
			    $this->PIDs[$pid] = array(
			    	"start_time" 	=> time(),
			    	"pid"			=> $pid
			    );
			}
        }
        
    	final private function DemonizeProcess()
        {
        	$this->Logger->debug("Daemonizing process...");
        	
        	// Make the current process a session leader. 
        	if (posix_setsid() == -1)
            {
            	$this->Logger->fatal("posix_setsid() return -1");
            	exit();
            }
            
            // We need wait for some time
            sleep(2);
            
            // Send signal about demonize this child 
            posix_kill(posix_getppid(), SIGUSR2);
            
            $this->Logger->debug("Process successfully demonized.");
        }
    }
?>