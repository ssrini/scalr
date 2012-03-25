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
     * @package    PE
     * @subpackage PipedChain
     * Piping data between processes in POSIX environment.
     * Commands being compiled in "chains" and then executed as PE/Process
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */ 
	
    Core::Load("PE/ManagedProcess");

    /**
     * @name Process 
     * Piping data between processes in POSIX environment.
     * Commands being compiled in "chains" and then executed as PE/Process.
     * Build a piped command line command1 | command2 | commandN
     * Send $StdIn to STDIN of a 1st 'link' and read STDERR/STDOUT of a last one.
     * @category   LibWebta
     * @package    PE
     * @author Alex Kovalyov <http://webta.net/company.html>
     */	    
	class PipedChain extends Core
	{
		
		/**
		 * Array of commands
		 *
		 * @var array
		 */
		protected $Links;
		
		/**
		* Contents of STDOUT after Execute() call
		* @var int
		* @access public
		*/
		public $StdOut;
		
		/**
		* Contents of STDERR after Execute() call
		* @var int
		* @access public
		*/
		public $StdErr;
		
		
		/**
		* Constructor
		* @access public
		* @return array Mounts
		*/
		function __construct()
		{
			parent::__construct();
		}
		
		
		/**
		 * Append a new command to a chain
		 *
		 * @param string $cmd
		 */
		function AddLink($cmd)
		{
			$this->Links[] = $cmd; 
		}
		
		
		/**
		 * Reset links array
		 *
		 */
		public function ClearLinks()
		{
			$this->Links = array();
			$this->StdOut = null;
			$this->StdErr = null; 
		}
		
		/**
		* Execute commands chain.
		* @access public
		* @var mixed $stdin Optional STDIN string to write to a 1st proccess in chain
		* @var string $in_file_path Optional input file path that input for the last proccess will be read from
		* @var string $out_file_path Optional output file path that output of the last proccess will be written to
		* @return int The return value of the command (0 on success) or strict false on failure.
		* @todo may be it worths rewriting interptoccess piping from | binary usage to posix_mkfifo()
		*/
		function Execute($stdin = null, $out_file_path = null, $in_file_path = null)
		{
			if (count($this->Links) == 0)
				Core::RaiseError("Cannot call PipedChain->Execute() - no links in chain");
				
			// Build a piped string
			$cmd = implode(" | ", $this->Links);
			
			$MProcess = new ManagedProcess($cmd, $stdin);
			$retval = $MProcess->Execute($cmd, $stdin, $out_file_path, $in_file_path);
			$this->StdOut = $MProcess->StdOut;
			$this->StdErr = $MProcess->StdErr;
						
			return $retval;
		}
		
	}
	
?>
