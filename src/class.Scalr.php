<?php

	class Scalr
	{
		private static $observersSetuped = false;
		private static function setupObservers()
		{
			Scalr::AttachObserver(new SSHWorker());
			Scalr::AttachObserver(new DBEventObserver());
			Scalr::AttachObserver(new ScriptingEventObserver());
			Scalr::AttachObserver(new DNSEventObserver());
			
			Scalr::AttachObserver(new Modules_Platforms_Ec2_Observers_Ebs());			
			Scalr::AttachObserver(new Modules_Platforms_Cloudstack_Observers_Cloudstack());
			
			Scalr::AttachObserver(new MessagingEventObserver());
			Scalr::AttachObserver(new ScalarizrEventObserver());
			Scalr::AttachObserver(new BehaviorEventObserver());
		
			Scalr::AttachObserver(new Modules_Platforms_Ec2_Observers_Ec2());

			Scalr::AttachObserver(new Modules_Platforms_Ec2_Observers_Eip());
			Scalr::AttachObserver(new Modules_Platforms_Ec2_Observers_Elb());
		
			Scalr::AttachObserver(new Modules_Platforms_Rds_Observers_Rds());
			
			Scalr::AttachObserver(new MailEventObserver(), true);
			Scalr::AttachObserver(new RESTEventObserver(), true);
			
			self::$observersSetuped = true;
		}
		
		
		
		private static $EventObservers = array();
		private static $DeferredEventObservers = array();
		private static $ConfigsCache = array();
		private static $InternalObservable;
		/**
		 * Attach observer
		 *
		 * @param EventObserver $observer
		 */
		public static function AttachObserver ($observer, $isdeffered = false)
		{
			if ($isdeffered)
				$list = & self::$DeferredEventObservers;
			else
				$list = & self::$EventObservers;
			
			if (array_search($observer, $list) !== false)
				throw new Exception(_('Observer already attached to class <Scalr>'));
				
			$list[] = $observer;
		}
		
		/**
		 * Method for multiprocess scripts. We must recreate DB connection created in constructor
		 */
		public static function ReconfigureObservers()
		{
			if (!self::$observersSetuped)
				self::setupObservers();
			
			foreach (self::$EventObservers as &$observer)
			{
				if (method_exists($observer, "__construct"))
					$observer->__construct();
			}
		}
		
		/**
		 * @return Scalr_Util_Observable
		 */
		private static function GetInternalObservable () 
		{
			if (self::$InternalObservable === null)
			{
				self::$InternalObservable = new Scalr_Util_Observable();
				self::$InternalObservable->defineEvents(array(
				
					/**
					 * @param Client $client
					 */
					"addClient",
				
					/**
					 * @param Client $client
					 */
					"updateClient",
				
					/**
					 * @param Client $client
					 */
					"beforeDeleteClient",
				
					/**
					 * @param Client $client
					 */
					"deleteClient",
				
					/**
					 * @param Client $client
					 * @param int $invoiceId
					 */
					"addPayment",
				
					/**
					 * @param Client $client
					 * @param string $paypalSubscrId
					 */
					"subscrSignup",
					
					/**
					 * @param Client $client
					 * @param string $paypalSubscrId
					 */
					"subscrCancel",
				
					/**
					 * @param Client $client
					 * @param string $paypalSubscrId
					 */
					"subscrEot",
				
					/**
					 * @param Client $client
					 */
					"subscrUpdate"				
				));
			}
			
			return self::$InternalObservable;
		}
		
		static function FireInternalEvent ($event_name)
		{
			$observable = self::GetInternalObservable();
			$args = func_get_args();
			try {
				call_user_func_array(array($observable, "fireEvent"), $args);
			} catch (Exception $e) {
				// No exceptions to uplevel 
				$logger = Logger::getLogger(__CLASS__);
				$logger->error($e->getMessage());
			}
		}
		
		static function AttachInternalObserver ()
		{
			$Observable = self::GetInternalObservable();
			$args = func_get_args();
			return call_user_func_array(array($Observable, "addListener"), $args);
		}
		
		/**
		 * Return observer configuration for farm
		 *
		 * @param string $farmid
		 * @param EventObserver $observer
		 * @return DataForm
		 */
		private static function GetFarmNotificationsConfig($farmid, $observer)
		{
			$DB = Core::GetDBInstance(NULL, true);
			
			// Reconfigure farm settings if changes made
			$farms = $DB->GetAll("SELECT farms.id as fid FROM farms INNER JOIN client_settings ON client_settings.clientid = farms.clientid WHERE client_settings.`key` = 'reconfigure_event_daemon' AND client_settings.`value` = '1'");
			if (count($farms) > 0)
			{
				Logger::getLogger(__CLASS__)->debug("Found ".count($farms)." with new settings. Cleaning cache.");
				foreach ($farms as $cfarmid)
				{
					Logger::getLogger(__CLASS__)->info("Cache for farm {$cfarmid["fid"]} cleaned.");
					self::$ConfigsCache[$cfarmid["fid"]] = false;
				}
			}
				
			// Update reconfig flag
			$DB->Execute("UPDATE client_settings SET `value`='0' WHERE `key`='reconfigure_event_daemon'");
							
			// Check config in cache
			if (!self::$ConfigsCache[$farmid] || !self::$ConfigsCache[$farmid][$observer->ObserverName])
			{
				Logger::getLogger(__CLASS__)->debug("There is no cached config for this farm or config updated. Loading config...");
				
				// Get configuration form
				self::$ConfigsCache[$farmid][$observer->ObserverName] = $observer->GetConfigurationForm();
				
				// Get farm observer id
				$farm_observer_id = $DB->GetOne("SELECT * FROM farm_event_observers 
					WHERE farmid=? AND event_observer_name=?",
					array($farmid, get_class($observer))
				);
								
				// Get Configuration values
				if ($farm_observer_id)
				{
					Logger::getLogger(__CLASS__)->info("Farm observer id: {$farm_observer_id}");
					
					$config_opts = $DB->Execute("SELECT * FROM farm_event_observers_config 
						WHERE observerid=?", array($farm_observer_id)
					);
					
					// Set value for each config option
					while($config_opt = $config_opts->FetchRow())
					{
						$field = &self::$ConfigsCache[$farmid][$observer->ObserverName]->GetFieldByName($config_opt['key']);
						if ($field)
							$field->Value = $config_opt['value'];
					}
				}
				else
					return false;
			}
			
			return self::$ConfigsCache[$farmid][$observer->ObserverName];
		}
		
		/**
		 * Fire event
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 * @param string $event_message
		 */
		public static function FireDeferredEvent (Event $event)
		{
			if (!self::$observersSetuped)
				self::setupObservers();
			
			try
			{
				// Notify class observers
				foreach (self::$DeferredEventObservers as $observer)
				{
					// Get observer config for farm
					$config = self::GetFarmNotificationsConfig($event->GetFarmID(), $observer);
					
					// If observer configured -> set config and fire event
					if ($config)
					{
						$observer->SetConfig($config);
						$res = call_user_func(array($observer, "On{$event->GetName()}"), $event);
					}
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal("Exception thrown in Scalr::FireDeferredEvent(): ".$e->getMessage());
			}
				
			return;
		}
		
		/**
		 * File event in database
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 */
		public static function FireEvent($farmid, Event $event)
		{
			if (!self::$observersSetuped)
				self::setupObservers();
			
			try
			{
				$event->SetFarmID($farmid);
				
				// Notify class observers
				foreach (self::$EventObservers as $observer)
				{
					$observer->SetFarmID($farmid);					
					Logger::getLogger(__CLASS__)->info(sprintf("Event %s. Observer: %s", "On{$event->GetName()}", get_class($observer)));
					call_user_func(array($observer, "On{$event->GetName()}"), $event);
				}
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal(
					sprintf("Exception thrown in Scalr::FireEvent(%s:%s, %s:%s): %s",
						@get_class($observer),
						$event->GetName(),
						$e->getFile(),
						$e->getLine(),
						$e->getMessage()	
					));
				throw new Exception($e->getMessage());
			}
			
			// invoke StoreEvent method
			$reflect = new ReflectionMethod("Scalr", "StoreEvent");
			$reflect->invoke(null, $farmid, $event);
		}
		
		/**
		 * Store event in database
		 *
		 * @param integer $farmid
		 * @param string $event_name
		 */
		public static function StoreEvent($farmid, Event $event)
		{
			if ($event->SkipDeferredOperations)
				return true;
			
			try
			{
				$DB = Core::GetDBInstance();
					
				// Get Smarty object
				$Smarty = Core::GetSmartyInstance();
				
				// Assign vars
				$Smarty->assign(array("event" => $event));
				
				// Generate event message 
				if (file_exists(CF_TEMPLATES_PATH."/event_messages/{$event->GetName()}.tpl")) {
					$message = $Smarty->fetch("event_messages/{$event->GetName()}.tpl");
					$short_message = $Smarty->fetch("event_messages/{$event->GetName()}.short.tpl");
						
					// Store event in database
					$DB->Execute("INSERT INTO events SET 
						farmid	= ?, 
						type	= ?, 
						dtadded	= NOW(),
						message	= ?,
						short_message = ?, 
						event_object = ?,
						event_id	 = ?
						",
						array($farmid, $event->GetName(), $message, $short_message, serialize($event), $event->GetEventID())
					);
					
					$eventid = $DB->Insert_ID();
					
					// Add task for fire deferred event
					TaskQueue::Attach(QUEUE_NAME::DEFERRED_EVENTS)->AppendTask(new FireDeferredEventTask($eventid));
				} else
					Logger::getLogger(__CLASS__)->warn(sprintf(_("Cannot store event in database: '{$event->GetName()}' message template not found")));
			}
			catch(Exception $e)
			{
				Logger::getLogger(__CLASS__)->fatal(sprintf(_("Cannot store event in database: %s"), $e->getMessage()));
			}
		}
		
		/**
		 * 
		 * @param ServerCreateInfo $ServerCreateInfo
		 * @return DBServer
		 */
		public static function LaunchServer(ServerCreateInfo $ServerCreateInfo = null, DBServer $DBServer = null)
		{
			$db = Core::GetDBInstance();
			
			if(!$DBServer && $ServerCreateInfo)
			{	
				$ServerCreateInfo->SetProperties(array(
					SERVER_PROPERTIES::SZR_KEY => Scalr::GenerateRandomKey(40),
					SERVER_PROPERTIES::SZR_KEY_TYPE => SZR_KEY_TYPE::ONE_TIME
				));
				
				$DBServer = DBServer::Create($ServerCreateInfo, false, true);
			}
			elseif(!$DBServer && !$ServerCreateInfo)
			{
				// incorrect arguments
				Logger::getLogger(LOG_CATEGORY::FARM)->error(sprintf("Cannot create server"));
                
                return null;
			}
			
			try
			{
				PlatformFactory::NewPlatform($DBServer->platform)->LaunchServer($DBServer);
				
				$DBServer->status = SERVER_STATUS::PENDING;
				$DBServer->Save();
			}
			catch(Exception $e)
			{
				Logger::getLogger(LOG_CATEGORY::FARM)->error(new FarmLogMessage($DBServer->farmId,	
					sprintf("Cannot launch server on '%s' platform: %s", 
	                	$DBServer->platform,
	                	$e->getMessage()
	                )
	            ));
	            
	            $DBServer->status = SERVER_STATUS::PENDING_LAUNCH;
				$DBServer->Save();
			}
			
			if ($DBServer->status == SERVER_STATUS::PENDING)
			{
				Scalr::FireEvent($DBServer->farmId, new BeforeInstanceLaunchEvent($DBServer));
				
				$db->Execute("UPDATE servers_history SET
					`dtlaunched` = NOW(),
					`cloud_server_id` = ?,
					`type` = ?
					WHERE server_id = ?
				", array(
					$DBServer->GetCloudServerID(),
					$DBServer->GetProperty(EC2_SERVER_PROPERTIES::INSTANCE_TYPE),
					$DBServer->serverId
				));
			}
			
			return $DBServer;
		}
	    
		public static function GenerateAPIKeys()
		{
			$key = Scalr::GenerateRandomKey();
			
			$sault = abs(crc32($key));
			$keyid = dechex($sault).dechex(time());
			
			$ScalrKey = $key;
			$ScalrKeyID = $keyid;
			
			return array("id" => $ScalrKeyID, "key" => $ScalrKey);
		}
		
		public static function GenerateRandomKey($length = 128)
		{
			$fp = fopen("/dev/urandom", "r");
		    $rnd = fread($fp, $length);
		    fclose($fp);
			$key = base64_encode($rnd);
			
			return $key;
		}
	    
		public static function GenerateUID($short = false)
		{
			$pr_bits = false;
	        if (is_a ( $this, 'uuid' )) {
	            if (is_resource ( $this->urand )) {
	                $pr_bits .= @fread ( $this->urand, 16 );
	            }
	        }
	        if (! $pr_bits) {
	            $fp = @fopen ( '/dev/urandom', 'rb' );
	            if ($fp !== false) {
	                $pr_bits .= @fread ( $fp, 16 );
	                @fclose ( $fp );
	            } else {
	                // If /dev/urandom isn't available (eg: in non-unix systems), use mt_rand().
	                $pr_bits = "";
	                for($cnt = 0; $cnt < 16; $cnt ++) {
	                    $pr_bits .= chr ( mt_rand ( 0, 255 ) );
	                }
	            }
	        }
	        $time_low = bin2hex ( substr ( $pr_bits, 0, 4 ) );
	        $time_mid = bin2hex ( substr ( $pr_bits, 4, 2 ) );
	        $time_hi_and_version = bin2hex ( substr ( $pr_bits, 6, 2 ) );
	        $clock_seq_hi_and_reserved = bin2hex ( substr ( $pr_bits, 8, 2 ) );
	        $node = bin2hex ( substr ( $pr_bits, 10, 6 ) );
	        
	        /**
	         * Set the four most significant bits (bits 12 through 15) of the
	         * time_hi_and_version field to the 4-bit version number from
	         * Section 4.1.3.
	         * @see http://tools.ietf.org/html/rfc4122#section-4.1.3
	         */
	        $time_hi_and_version = hexdec ( $time_hi_and_version );
	        $time_hi_and_version = $time_hi_and_version >> 4;
	        $time_hi_and_version = $time_hi_and_version | 0x4000;
	        
	        /**
	         * Set the two most significant bits (bits 6 and 7) of the
	         * clock_seq_hi_and_reserved to zero and one, respectively.
	         */
	        $clock_seq_hi_and_reserved = hexdec ( $clock_seq_hi_and_reserved );
	        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved >> 2;
	        $clock_seq_hi_and_reserved = $clock_seq_hi_and_reserved | 0x8000;
	        
	        if ($short)
	        	return sprintf ( '%012s', $node );
	        
	        return sprintf ( '%08s-%04s-%04x-%04x-%012s', $time_low, $time_mid, $time_hi_and_version, $clock_seq_hi_and_reserved, $node );
		}
	}
?>