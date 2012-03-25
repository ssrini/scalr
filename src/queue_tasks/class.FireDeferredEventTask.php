<?
	/**
	 * Task for fire deffered event
	 *
	 */
	class FireDeferredEventTask extends Task
	{
		public $EventID;

		/**
		 * Constructor
		 *
		 * @param string $eventid
		 */
		public function __construct($eventid)
		{
			$this->EventID = $eventid;
		}
		
		public function Run()
		{
			$DB = Core::GetDBInstance(null, true);
			
			$dbevent = $DB->GetRow("SELECT * FROM events WHERE id=?", array($this->EventID));
            if ($dbevent)
            {
            	try
            	{
            		//TODO: Initialize Event classes
            		$Event = unserialize($dbevent['event_object']);
            		if ($Event)
            		{
	            		Logger::getLogger(__CLASS__)->info(sprintf(_("Fire event %s for farm: %s"), $Event->GetName(), $Event->GetFarmID()));
			            // Fire event
						Scalr::FireDeferredEvent($Event);
            		}
            		
            		$DB->Execute("UPDATE events SET ishandled='1', event_object = '' WHERE id=?", array($dbevent['id']));
            	}
            	catch(Exception $e)
            	{
            		Logger::getLogger(__CLASS__)->fatal(sprintf(_("Cannot fire deferred event: %s"), $e->getMessage()));
            	}
            }
            
            return true;
		}
	}
?>