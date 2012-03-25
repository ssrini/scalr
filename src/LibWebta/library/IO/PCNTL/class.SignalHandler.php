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
     * @name SignalHandler
     * @category   LibWebta
     * @package    IO
     * @subpackage PCNTL
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */
    class SignalHandler extends Core
    {
        /**
         * Processmanager instance
         *
         * @var ProcessManager
         */
        public $ProcessManager;
        
        private $Logger;
        
        /**
         * Constructor
         * @ignore 
         */
        function __construct() 
        {
            $this->Logger = Logger::getLogger('SignalHandler');
        	
        	if (!function_exists("pcntl_signal"))
                self::RaiseError("Function pcntl_signal() not found. PCNTL must be enabled in PHP.", E_ERROR);
        }
        
        /**
         * Set handlers to signals
         *
         */
        final public function SetSignalHandlers()
        {
        	$this->Logger->debug("Begin add handler to signals...");
                
            // Add default handlers
            $res = @pcntl_signal(SIGCHLD, array(&$this,"HandleSignals"));
            $this->Logger->debug("Handle SIGCHLD = {$res}");
            
            $res = @pcntl_signal(SIGTERM, array(&$this,"HandleSignals"));
            $this->Logger->debug("Handle SIGTERM = {$res}");
                        
            $res = @pcntl_signal(SIGABRT, array(&$this,"HandleSignals"));
            $this->Logger->debug("Handle SIGABRT = {$res}");
            
            $res = @pcntl_signal(SIGUSR2, array(&$this,"HandleSignals"));
            $this->Logger->debug("Handle SIGUSR2 = {$res}");
        }
        
        /**
         * Signals handler function
         *
         * @param integer $signal
         * @final 
         */
        final public function HandleSignals($signal)
        {
            $this->Logger->debug("HandleSignals received signal {$signal}");            
            
            if ($signal == SIGUSR2)
            {
            	$this->Logger->debug("Recived SIGUSR2 from one of childs");
            	$this->ProcessManager->PIDs = array();
            	$this->ProcessManager->ForkThreads();
            	return;
            }
            
            $pid = @pcntl_wait($status, WNOHANG | WUNTRACED);
                        
            if ($pid > 0)
    		{
    		    $this->Logger->debug("Application received signal {$signal} from child with PID# {$pid} (Exit code: {$status})");
  
    		    foreach((array)$this->ProcessManager->PIDs as $ipid=>$ipid_info)
    			{
    				if ($ipid == $pid)
    				{
    					unset($this->ProcessManager->PIDs[$ipid]);
    					
    					if ($this->ProcessManager->PIDDir)
    					{
    						$this->Logger->debug("Delete thread PID file $ipid");
    						@unlink($this->ProcessManager->PIDDir . "/" . $ipid);
    					}
    						
    					$known_child = true;
    					break;
    				}
    			}
    			
    			if ($known_child)
    				$this->ProcessManager->ForkThreads();
    			else
    				$this->Logger->debug("Signal received from unknown child.");
    		}
        }
        
        /**
         * Add new handler on signal
         *
         * @param integer $signal
         * @param mixed $handler
         * @final 
         */
        final public function AddHandler($signal, $handler = false)
        {
            $signal = (int)$signal;
            
            if (!$handler)
                $handler = array(&$this,"HandleSignals");
            
            @pcntl_signal($signal, $handler);
            
            $this->Logger->debug("Added new handler on signal {$signal}.");
        }
    }
?>