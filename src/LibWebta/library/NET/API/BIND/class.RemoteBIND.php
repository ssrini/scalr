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
     * @package NET_API
     * @subpackage BIND
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */
	
	/**
	 * Load SSH
	 */
	Core::Load("NET/SSH/class.SSH2.php");
	
	/**
	 * Load FTP
	 */
	Core::Load("NET/FTP/class.FTP.php");
	
	/**
     * @name RemoteBIND
     * @category   LibWebta
     * @package NET_API
     * @subpackage BIND
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */
	class RemoteBIND extends BIND
	{
		const DEFAULT_TRANSPORT = "ssh";
		/**
		 * Zones on Starting
		 *
		 * @var integer
		 * @access private
		 */
		private $ZonesOnStart;
		
		/**
		 * Have new zones
		 *
		 * @var bool
		 * @access private
		 */
		private $HaveNewZones;
		
		/**
		 * Make backups of zone files and named.conf before edit.
		 *
		 * @var bool
		 * @access private
		 */
		private $DoMakeBackup;
	
		/**
		 * Current transport
		 *
		 * @var string
		 * @access private
		 */
		private $Transport;
		
		/**
		 * Transport host
		 *
		 * @var string
		 * @access private
		 */
		private $Host;
		
		/**
		 * Transport port
		 *
		 * @var integer
		 * @access private
		 */
		private $Port;
		
		/**
		 * Transport auth info
		 *
		 * @var array
		 * @access private
		 */
		private $Authinfo;
		
		/**
		 * FTP Instance
		 *
		 * @var string
		 * @access private
		 */
		private $FTP;
		
		private $Logger;
		
		/**
		 * Constructor
		 *
		 * @param string $host
		 * @param string $port
		 * @param array $authinfo
		 * @param string $rndc_path
		 * @param string $namedconf_path
		 * @param string $nameddb_path
		 * @param string $zonetemplate
		 */
		function __construct($host, $port, $authinfo, $rndc_path, $namedconf_path, $nameddb_path, $zonetemplate, $inittransport= true)
		{
			// Call Bind class construct
			parent::__construct($namedconf_path, $nameddb_path, $zonetemplate, $rndc_path, false);	

			$this->Logger = Logger::getLogger('RemoteBIND');
			
			$this->Host = $host;
			$this->Port = $port;
			$this->Authinfo = $authinfo;
					
			$this->Transport = self::DEFAULT_TRANSPORT;
			
			if ($inittransport)
				if (!$this->InitTransport())
					throw new Exception("Cannot init transport");
			
			$this->DoMakeBackup = false;
			$this->HaveNewZones = 0;
		}
		
		/**
		 * Init transport
		 * @access protected
		 **/
		protected function InitTransport()
		{
			if ($this->Transport == "ssh")
			{
				// Remote part
				$this->SSH2 = new SSH2();
							
				if ($this->Authinfo["type"] == "password")
					$this->SSH2->AddPassword($this->Authinfo["login"], $this->Authinfo["password"]);
				elseif ($this->Authinfo["type"] == "pubkey")
					$this->SSH2->AddPubkey($this->Authinfo["login"], $this->Authinfo["pubkey_path"], $this->Authinfo["privkey_path"], $this->Authinfo["key_pass"]);
					
				if (!$this->SSH2->Connect($this->Host, $this->Port))
				{
					$this->Logger->error(sprintf(_("Cannot connect to %s on port %d!"), $this->Host, $this->Port));
					return false;
				}
											
				// Fetch named.conf
				$this->Conf = $this->SSH2->GetFile($this->NamedConfPath);
				if (!$this->Conf)				    
				{
					$this->Logger->error(sprintf(_("named.conf does not exist or empty on %s"), $this->Host));
					return false;
				}
						
				// COunt initial number of zones	
				$this->ZonesOnStart = $this->RndcStatus();
				if (!$this->ZonesOnStart)
				{
					$this->Logger->error(sprintf(_("BIND is not running on %s"), $this->Host));
					return false;
				}
			}
			elseif ($this->Transport == "ftp")
			{
				$this->FTP = new FTP($this->Host, $this->Authinfo["login"], $this->Authinfo["password"], $this->Port);
				if (!$this->FTP)
				{
					$this->Logger->error(sprintf(_("Cannot connect to %s on port %s!"), $this->Host, $this->Port));
					return false;
				}
					
				$this->Conf = $this->FTP->GetFile("/", basename($this->NamedConfPath), 1);
				if (!$this->Conf)
				{
					$this->Logger->error(sprintf(_("Cannot fetch named.conf from %s"), $this->Host));
					return false;
				}
			}
			
			return true;
		}
		
		/**
		 * Set current transport
		 *
		 * @param string $transport ftp|ssh
		 */
		public function SetTransport($transport)
		{
			$this->Transport = $transport;
			return $this->InitTransport();
		}
		
		/**
		* Save zone file
		* @access protected
		* @param string $name Zone name (undotted domain name)
		* @param string $content Zone text
		* @return bool Operation status
		*/
		public  function SaveZoneFile($name, $content)
		{
			$name = "{$name}.db";
			
			// Make backup
			if ($this->DoMakeBackup)
			{
				$zone_db_bcp = $name.".".time();
				
				// Create backup
				if ($this->Transport == "ssh")
					$this->SSH2->Exec("/bin/mv {$this->RootPath}/{$name} {$this->RootPath}/{$zone_db_bcp}");
				elseif ($this->Transport == "ftp")
					$this->FTP->Rename("/", basename($zone_db), basename($name));
			}
			
			$tempfn = tempnam("","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $content);
			fclose($temp);
			
			try
			{
				if ($this->Transport == "ssh")
				{
					$retval = $this->SSH2->SendFile("{$this->RootPath}/{$name}", $tempfn);
					$this->SSH2->Exec("chown {$this->Authinfo["login"]}:{$this->Authinfo["login"]} {$this->RootPath}/{$name}");
					$this->SSH2->Exec("chmod 0744 {$this->RootPath}/{$name}");
				}
				elseif($this->Transport == "ftp")
					$retval = $this->FTP->SendFile("{$this->RootPath}", "{$name}", $tempfn, 1);
					
				@unlink($tempfn);
			}
			catch(Exception $e)
			{
				@unlink($tempfn);
				
				throw new $e;
			}
			
			return $retval;
		}
		
		
		/**
		* Determine either zone file or zone declaration exist
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @return bool True if zone file or declaration exist
		*/
		public function IsZoneExists($name)
		{
		    preg_match_all("/zone[^A-Za-z0-9]*({$name})[^{]+{[^;]+;([^A-Za-z0-9]+)file[^A-Za-z0-9;]*([A-Za-z0-9\.\/-]+)[^}]+};/msi", $this->Conf, $matches); 
			
		    if ($matches[1][0] == $name)
				return $matches[3][0];
			else
				return false;
		}
		
		public function UpdateZoneDirectives($name, $allow_transfer = "none")
		{
			preg_match_all("/^[^;#]*zone\W*({$name})\W+{(.*?)^\W*};$/msi", $this->Conf, $matches);
			
			if ($matches[1][0] == $name)
			{
				$filename = "{$name}.db";
				
				$template = str_replace("{zone}", $name, $this->Template);
				$template = str_replace("{db_filename}", $filename, $template);
				$template = str_replace("{allow_transfer}", $allow_transfer, $template);
				
				if ($allow_transfer == 'none' || !$allow_transfer)
					$template = str_replace("{also-notify}", "", $template);
				else
					$template = str_replace("{also-notify}", "also-notify { {$allow_transfer}; };", $template);
				
				$this->Conf = str_replace($matches[0][0], $template, $this->Conf);
				
				return $this->SaveConf();	
			}
			else
			{
				throw new Exception("Zone {$name} doesn't exist in named.conf");
			}
		}
		
		public function AddZone($name, $allow_transfer = "none")
		{
			$filename = "{$name}.db";
			
			if (!$this->IsZoneExists($name))
			{
				$template = str_replace("{zone}", $name, $this->Template);
				$template = str_replace("{db_filename}", $filename, $template);
				$template = str_replace("{allow_transfer}", $allow_transfer, $template);
				
				if ($allow_transfer == 'none' || !$allow_transfer)
					$template = str_replace("{also-notify}", "", $template);
				else
					$template = str_replace("{also-notify}", "also-notify { {$allow_transfer}; };", $template);
				
				$this->Conf = $this->Conf . $template;
				
				$this->NewZonesCount++;
				return $this->SaveConf();
			}
			else
				return true;
		}
		
		/**
		* Save DNS zne into zone file
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @param string $content Zone content
		* @return bool Operation status
		*/
		public function SaveZone($name, $content, $reloadndc = true, $allow_transfer = "none")
		{
			// Delete if already exists in named.conf
			$zone_db = $this->IsZoneExists($name);
			
			$filename = "{$name}.db";
			
			if ($zone_db)
			{				
				// Save zone contents to zone file
				if (!$this->SaveZoneFile($filename, $content))
					$this->RaiseWarning("Cannot save zone file for {$filename}");
			}
			else
			{
				$template = str_replace("{zone}", $name, $this->Template);
				$template = str_replace("{db_filename}", $filename, $template);
				$template = str_replace("{allow_transfer}", $allow_transfer, $template);
				
				if ($allow_transfer == 'none' || !$allow_transfer)
					$template = str_replace("{also-notify}", "", $template);
				else
					$template = str_replace("{also-notify}", "also-notify { {$allow_transfer}; };", $template);
				
				$this->Conf = $this->Conf . $template;
				
				$this->NewZonesCount++;
							
				// Save zone contents to zone file
				if (!$this->SaveZoneFile($filename, $content))
					$this->RaiseWarning("Cannot save zone file for {$filename}");
				else
					// Save named.conf
					$this->SaveConf();
			}	
			
			// Reload rndc and count zones
			if ($reloadndc)
			{
				$this->ReloadRndc();
				$retval = $this->RndcStatus();
				
				$need = (int)($this->ZonesOnStart + $this->NewZonesCount);
			
				if (($this->NewZonesCount == 0 && $retval == $this->ZonesOnStart) || 
					($this->NewZonesCount != 0 && $retval == $need)
			 	  )
				{
					return true;
				}
				else
				{
					if (!$retval)
						Core::RaiseWarning(_("rndc reload failed"));
					else
						Core::RaiseWarning(
							sprintf(_("Cannot save DNS zone. Number of zones dont match. Should be: %d. There are: %d"), 
							$this->ZonesOnStart + $this->NewZonesCount, $retval)
						);
					
					return false;
				}
			}
			else
				return true;		
		}
		
		
		/**
		* Save named.conf
		* @access public
		* @return bool Operation status
		*/
		public function SaveConf()
		{
			$this->ConfCleanup();
			//
			$tempfn = tempnam("","");
			$temp = fopen($tempfn, "w+");
			fwrite($temp, $this->Conf);
			fclose($temp);
			
			if ($this->Transport == "ssh")
			{
				if ($this->DoMakeBackup)
					$this->SSH2->Exec("/bin/mv {$this->NamedConfPath} {$this->NamedConfPath}.".time());
				
				$retval = $this->SSH2->SendFile("{$this->NamedConfPath}", $tempfn);
			}
			elseif ($this->Transport == "ftp")
			{
				if ($this->DoMakeBackup)
				{
					$this->FTP->Rename("/", basename($this->NamedConfPath), basename("{$this->NamedConfPath}.".time()));
				}
				
				$retval = $this->FTP->SendFile("/", basename($this->NamedConfPath), $tempfn, 1);
			}
				
						
			@unlink($tempfn);
			
			return($retval);
		}
		
		
		/**
		* Delete DNS zone
		* @access public
		* @param string $zone Zone name (undotted domain name)
		* @return bool Operation status
		* @todo Delete zonename.db file?
		*/
		public function DeleteZone($name, $reload_rndc = true, $remove_zone_file = false)
		{
			preg_match_all("/^[^;#]*zone\W*({$name})\W+{(.*?)^\W*};$/msi", $this->Conf, $matches);
			
			if ($matches[1][0] == $name)
			{
				preg_match("/file\W+(.*?)\W+\s/si", $matches[2][0], $m);
				$filename = $m[1];
				$this->Conf = str_replace($matches[0][0], "", $this->Conf);				
				$this->SaveConf();
				
				if ($reload_rndc)
					$this->ReloadRndc();
				
				if ($this->DoMakeBackup)
				{
					$this->Logger->info("Backup {$this->RootPath}/{$filename}");
					
					if ($this->Transport == "ssh")
						$this->SSH2->Exec("/bin/mv {$this->RootPath}/{$filename} {$this->RootPath}/{$filename}.".time());
					elseif ($this->Transport == "ftp")
						$this->FTP->Rename("/", basename($filename), basename("{$filename}.".time()));
				}
				else
				{
					if ($remove_zone_file)
					{
						$this->Logger->info("Removing {$this->RootPath}/{$filename}");
						
						if ($this->Transport == "ssh")
							$this->SSH2->Exec("rm -f {$this->RootPath}/{$filename}");
						elseif ($this->Transport == "ftp")
						{
							//TODO:
						}
					}
				}
					
			}
			else
			{
				$this->Logger->info("Zone {$name} not found in named.conf");
			}
			
			return true;
		}
		
		
		/**
		* Load DNS zone
		* @access public
		* @param string $name Zone name (undotted domain name)
		* @return string Zone contents
		*/
		public function LoadZone($name)
		{
			preg_match_all("/zone[^A-Za-z0-9]*({$name})[^{]+{[^;]+;([^A-Za-z0-9]+)file[^A-Za-z0-9;]*([A-Za-z0-9\.-]+)[^}]+};/msi", $this->Conf, $matches); 
			if ($matches[1][0] == $name)
			{
				$filename = $matches[3][0];
				
				if (substr($filename, 0, 1) != "/")
					$filename = "{$this->RootPath}/{$filename}";
					
				if ($this->Transport == "ssh")
				{
					return $this->SSH2->GetFile("{$filename}");
				}
				elseif ($this->Transport == "ftp")
				{
					return $this->FTP->GetFile("/", basename($filename), 1);
				}
			}
			else
				return false;
		}
		
		/**
		 * Return content of zone file
		 *
		 * @param string $filename
		 * @return string
		 */
		public function GetZoneFileContent($filename)
		{
			if (substr($filename, 0, 1) != "/")
				$filename = "{$this->RootPath}/{$filename}";
				
			if ($this->Transport == "ssh")
			{
				return $this->SSH2->GetFile("{$filename}");
			}
			elseif ($this->Transport == "ftp")
			{
				return $this->FTP->GetFile("/", basename($filename), 1);
			}
		}
		
		/**
		* Reload named - issue rndc reload
		* @access public
		* @param string $zone Zone name (undotted domain name)
		* @return bool Operation status
		*/
		public function ReloadRndc()
		{
			$this->Logger->info("Execute rndc reload");
			
			if ($this->Transport == "ssh")
			{
				return $this->SSH2->Exec("{$this->Rndc} reload");
			}
			elseif ($this->Transport == "ftp")
			{
				Core::RaiseWarning(_("FTP transport not support RNDC reload command"));
			}
		}
		
		/**
		 * Return Number of zones if BIND worked else false
		 *
		 * @return integer
		 */
		public function RndcStatus()
		{
			if ($this->Transport == "ssh")
			{
				$retval = $this->SSH2->Exec("{$this->Rndc} status");
				
				$this->Logger->info("Execute rndc status");
				$this->Logger->info("Result: {$retval}");
				
				preg_match_all("/number of zones:[^0-9]([0-9]+)/", $retval, $matches);
			
				if ($matches[1][0] > 0)
					return $matches[1][0];
				else
					return false;
			}
			elseif ($this->Transport == "ftp")
			{
				Core::RaiseWarning(_("FTP transport not support RNDC status command"));
			}
		}
		
		/**
		 * If $state tru, we backup zone file and named.conf before edit.
		 *
		 * @param bool $state
		 */
		public function SetBackup($state)
		{
			$this->DoMakeBackup = $state;
		}
	}
?>