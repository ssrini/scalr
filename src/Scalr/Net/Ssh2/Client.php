<? 	
	class Scalr_Net_Ssh2_Client 
	{
		
		/**
		 * Stream timeout
		 */
		const STREAM_TIMEOUT = 10;
		
		/**
		 * Default units for terminal dimensions
		 */
		const TERM_UNITS = SSH2_TERM_UNIT_CHARS;
		
		/**
		 * Default terminal height
		 */
		const TERM_HEIGHT = 132; #SSH2_DEFAULT_TERM_HEIGHT;
		
		/**
		 * Default terminal width
		 */
		const TERM_WIDTH = 200; #SSH2_DEFAULT_TERM_WIDTH;
		
		/**
		 * @var integer Units for terminal dimensions
		 * @access public
		 */
		public $termUnits;
		
		/**
		 * @var integer Terminal height
		 * @access public
		 */
		public $termHeight;
		
		/**
		 * @var integer Terminal width
		 * @access public
		 */
		public $termWidth;
		
	
		/**
		* SSH connection resource
		* @var resource SSH connection resource
		* @access private
		*/
		private $connection;
		
		/**
		* Passwords array
		* @var array
		* @access private
		* @see AddPassword
		*/
		private $passwords;
		
		/**
		* Public keys array
		* @var array
		* @access private
		* @see AddPubkey
		*/
		private $pubkeys;
		
		/**
		 * Stream timeout
		 *
		 * @var integer
		 * @access private
		 */
		private $timeout;
		
		/**
		 * SFTP Stream
		 *
		 * @var stream
		 * @access private
		 */
		private $sftp = false;
		
		
		public $stdErr;
		
		/**
		 * 
		 * Login
		 * @var string
		 */
		private $login = 'root';
		
		/**
		 * SSH2 constructor
		 *
		 * @param integer $term_height
		 * @param integer $term_width
		 * @param integer $term_units
		 */
		function __construct($term_height = null, $term_width = null, $term_units = null)
		{
		    $this->termHeight = (is_int($term_height) && $term_height > 0) ? $term_height : self::TERM_HEIGHT;
			$this->termUnits = $term_units ? $term_units : self::TERM_UNITS;
			$this->termWidth = (is_int($term_width) && $term_width > 0) ? $term_width : self::TERM_WIDTH;
		    $this->timeout = self::STREAM_TIMEOUT;
		    
		    $this->logger = Logger::getLogger('SSH2');
		}
		
		/**
		 * Set stream timeout
		 *
		 * @param integer $timeout
		 * @access public
		 */
		public function setTimeout($timeout)
		{
		    $this->timeout = $timeout;
		}
		
		/**
		 * Add password credentials for auth
		 *
		 * @param string $login SSH login
		 * @param string $password SSH password
		 * @access public
		 */
		public function addPassword($login, $password)
		{
			$this->passwords[] = array($login, $password);
			$this->login = $login;
		}
		
		/**
		 * Add Pubkey auth data
		 *
		 * @param string $login
		 * @param string $pubkeyfile
		 * @param string $privkeyfile
		 * @param string $passphrase
		 */
		public function addPubkey($login, $pubkeyfile, $privkeyfile, $passphrase=null)
		{
			$this->pubkeys[] = array($login, $pubkeyfile, $privkeyfile, $passphrase);
			$this->login = $login;
		}
		
		/**
		 * Return true if we connected to SSH
		 *
		 * @return bool
		 */
		public function isConnected()
		{
		    return ($this->connection && is_resource($this->connection));
		}
		
		/**
		 * Test connection to remote host
		 *
		 * @param string $host
		 * @param integer $port
		 * @return bool
		 */
		public function testConnection($host, $port=22)
		{
		    $sock = @fsockopen($host, $port, $errno, $errstr, $this->timeout);
		    if (!$sock)
		        throw new Scalr_Net_Ssh2_Exception("Unable to connect to SSH server on {$host}:{$port}. ({$errno}) {$errstr}");
		    else 
                @fclose($sock);
			
            return true;
		}
		
		/**
		* Connect to SSH server and authenticate with password
		* @access public
		* @param string $host Host to connect
		* @param string $port Port to connect
		* @param string $login Login to authenticate with
		* @param string $password Password to authenticate with
		* @return array
		*/
		public function connect($host, $port=22, $login = null, $password = null)
		{
				
			// Backwards compat
			if ($login)
				$this->addPassword($login, $password);
			
			if (count($this->pubkeys) > 0)
				$hostkeys = array('hostkey' => 'ssh-rsa');
			else 
				$hostkeys = array();
			
		    $this->connection = @ssh2_connect($host, $port, $hostkeys);
		    				
			if (!$this->isConnected())
				throw new Scalr_Net_Ssh2_Exception("Unable to connect to SSH server on {$host}:{$port}");
			else
			{
				// Try all avaliable pubkeys
				foreach ((array)$this->pubkeys as $p)
				{
					if (@ssh2_auth_pubkey_file($this->connection, $p[0], $p[1], $p[2], $p[3]))
						return true;
				    else 
				        throw new Scalr_Net_Ssh2_Exception("Cannot login to SSH using PublicKey");
				}
				
				
				// Try all avaliable passwords
				foreach ((array)$this->passwords as $p)
				{
					if (ssh2_auth_password($this->connection, $p[0], $p[1]))
						return true;
				    else 
				        throw new Scalr_Net_Ssh2_Exception("Cannot login to SSH using login/password");
				}
				
			}
				
			return true;
		}

		/**
		 * Return SSH2 shell stream
		 *
		 * @return stream
		 */
		public function getShell()
		{
			try 
			{
			    if ($this->isConnected())
				{
				    $stream = @ssh2_shell($this->connection, 
					                    null, 
					                    null, 
					                    $this->termWidth, 
					                    $this->termHeight, 
					                    $this->termUnits
					                   );
					                   
					if ($stream)
						return $stream;
					else
						return false;
				}
				else
					return false;
			} 
			catch (Exception $e) 
			{
				return false;
			}
		}
		
		/**
		* Execute a command and returns both stdout and stderr output
		* @access public
		* @param string $command remote shell command to execute
		* @return bool
		*/
		public function exec($command, $stopstring = false)
		{
		    try 
			{
			    if ($this->isConnected())
				{
				    $stream = @ssh2_exec($this->connection, 
					                    "{$command}", 
					                    null, 
					                    null, 
					                    $this->termWidth, 
					                    $this->termHeight, 
					                    $this->termUnits
					                   );
					                   
					@stream_set_blocking($stream, true);
					@stream_set_timeout($stream, $this->timeout);
					
					$stderr_stream = @ssh2_fetch_stream($stream, SSH2_STREAM_STDERR);
					$this->stdErr = @fread($stderr_stream, 4096);
					@fclose($stderr_stream);
					
					if ($this->stdErr != '')
						$this->logger->info("STDERR: {$this->stdErr}");
					
					// Read from stream
					while($l = @fgets($stream, 4096))
					{
						$meta = stream_get_meta_data($stream);
						if ($meta["timed_out"])
							break;
						$retval .= $l;
						
						if ($stopstring && stripos($l, $stopstring) !== false)
							break;
					}
					
					if ($retval == '')
						$retval = true;
					
					// Close stream
					@fclose($stream);
				}
				else
					return false;
			} 
			catch (Exception $e) 
			{
				return false;
			}
		
			return $retval;
		}
		
		
		/**
		* Transfer file over sftp
		* @access public
		* @param string $remote_path Remote file path
		* @param string $local_path Local file path
		* @param string $write_type Write path
		* @param bool $read_content_from_file If True we read content from '$source' else content = $source
		* @return bool
		*/
		public function sendFile($remote_path, $source, $write_type = "w+", $read_content_from_file = true)
		{
			if ($this->isConnected())
			{
				if (!$this->sftp || !is_resource($this->sftp))
					$this->sftp = @ssh2_sftp($this->connection);
					
				if ($this->sftp && is_resource($this->sftp))
				{
					$stream = @fopen("ssh2.sftp://{$this->sftp}".$remote_path, $write_type);
					if ($stream)
					{
					    if ($read_content_from_file)
						 $content = @file_get_contents($source);
						else 
						 $content = $source;
						 
						if (fwrite($stream, $content) === false) 
							throw new Scalr_Net_Ssh2_Exception(sprintf(_("sftp: Cannot write to file '%s'"), $remote_path));

						@fclose($stream);
						return true;
					}
					else
						throw new Scalr_Net_Ssh2_Exception(sprintf(_("sftp: Cannot open remote file '%s'"), $remote_path));

				}
				else
					throw new Scalr_Net_Ssh2_Exception(sprintf(_("sftp: Unable to init sftp")));
			}
			else
				throw new Scalr_Net_Ssh2_Exception(sprintf(_("SSH connection not initialized")));

		}
		
		/**
		* Get file contents over sftp
		* @access public
		* @param string $remote_path Remote file path
		* @return strung
		*/
		public function getFile($remote_path)
		{
			$retval = false;

			if ($this->isConnected())
			{
				if (!$this->sftp || !is_resource($this->sftp))
					$this->sftp = @ssh2_sftp($this->connection);
					
				if ($this->sftp && @is_resource($this->sftp))
				{
					$this->logger->info("Open stream to: ssh2.sftp://CONNECTION$remote_path");
					
					$stream = @fopen("ssh2.sftp://{$this->sftp}".$remote_path, "r");
					if ($stream)
					{
						$this->logger->info("Reading: $remote_path");
						$string = true;
						
						@stream_set_timeout($stream, 5);
						@stream_set_blocking($stream, 0);
						
						while($string !== false)
						{
							$string = @fgetc($stream);
							$retval .= $string;
						}
						
						@fclose($stream);
						
						return $retval;
					}
					else
						throw new Scalr_Net_Ssh2_Exception(sprintf(_("sftp: Cannot open remote file '%s'"), $remote_path));
				}
				else
					throw new Scalr_Net_Ssh2_Exception(_("sftp: connection broken"));
			}
			else
				throw new Scalr_Net_Ssh2_Exception(_("No established SSH connection"));
		}
		
	}
        
?>