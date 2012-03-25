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

    Core::Load("System/Independent/Shell/class.Getopt.php");
    
	/**
     * @name JobLauncher
     * @category   LibWebta
     * @package    IO
     * @subpackage PCNTL
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     * @example tests.php
     * @see tests.php
     */
    class JobLauncher extends Core
    {
        private $ProcessName;
        
        private $Logger;
        
        private $PIDDir;
        
        function __construct($process_classes_folder)
        {
            $processes = @glob("{$process_classes_folder}/class.*Process.php");
            
            $this->Logger = Logger::getLogger('JobLauncher');
            
            $jobs = array();
            if (count($processes) > 0)
            {
                foreach ($processes as $process)
                {
                    $filename = basename($process);
                    $directory = dirname($process);
                    Core::Load($filename, $directory);
                    preg_match("/class.(.*)Process.php/s", $filename, $tmp);
                    $process_name = $tmp[1];
                    if (class_exists("{$process_name}Process"))
                    {
                        $reflect = new ReflectionClass("{$process_name}Process");
                        if ($reflect)
                        {
                            if ($reflect->implementsInterface("IProcess"))
                            {
                                $job = array(
                                                "name"          => $process_name,
                                                "description"   => $reflect->getProperty("ProcessDescription")->getValue($reflect->newInstance())
                                            );
                                array_push($jobs, $job);    
                            }
                            else 
                                Core::RaiseError("Class '{$process_name}Process' doesn't implement 'IProcess' interface.", E_ERROR);
                        }
                        else 
                            Core::RaiseError("Cannot use ReflectionAPI for class '{$process_name}Process'", E_ERROR);
                    }
                    else
                        Core::RaiseError("'{$process}' does not contain definition for '{$process_name}Process'", E_ERROR);
                }
            }
            else 
                Core::RaiseError(_("No job classes found in {$ProcessClassesFolder}"), E_ERROR);
             
            $options = array();
            foreach($jobs as $job)
                $options[$job["name"]] = $job["description"];

            $options["help"] = "Print this help";
            $options["piddir=s"] = "PID directory";
                           
            $Getopt = new Getopt($options);
            $opts = $Getopt->getOptions();
            
            if (in_array("help", $opts) || count($opts) == 0 || !$options[$opts[0]])
            {
                print $Getopt->getUsageMessage();    
                exit();
            }
            else
            {                               
                $this->ProcessName = $opts[0];
                if (in_array("piddir", $opts))
                {
                	$piddir = trim($Getopt->getOption("piddir"));
                	
                	if (substr($piddir, 0, 1) != '/')
                		$this->PIDDir = realpath($process_classes_folder . "/" . $piddir);
                	else
                		$this->PIDDir = $piddir;
                }
            }
        }
        
        /**
         * Return Process name
         *
         * @return string
         */
        function GetProcessName()
        {
        	return $this->ProcessName;
        }
        
        function Launch($max_chinds = 5, $child_exec_timeout = 0)
        {
            $proccess = new ReflectionClass("{$this->ProcessName}Process");
            $sig_handler = new ReflectionClass("SignalHandler");
            $PR = new ProcessManager($sig_handler->newInstance());
            $PR->SetChildExecLimit($child_exec_timeout);
            $PR->SetMaxChilds($max_chinds);
            if ($this->PIDDir)
            	$PR->SetPIDDir($this->PIDDir);
            $PR->Run($proccess->newInstance());
        }
    }
?>