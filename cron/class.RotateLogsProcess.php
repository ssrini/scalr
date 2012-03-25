<?
	class RotateLogsProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Rotate logs table";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = Logger::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            // Clear old instances log
            $oldlogtime = mktime(date("H"), date("i"), date("s"), date("m"), date("d")-10, date("Y"));
            $db->Execute("DELETE FROM logentries WHERE `time` < {$oldlogtime}");
            sleep(60);
            
            $oldlogtime = date("Y-m-d", mktime(date("H"), date("i"), date("s"), date("m"), date("d")-20, date("Y")));
            $db->Execute("DELETE FROM scripting_log WHERE `dtadded` < {$oldlogtime}");
            sleep(60);

            $oldlogtime = date("Y-m-d", mktime(date("H"), date("i"), date("s"), date("m")-6, date("d"), date("Y")));
            $db->Execute("DELETE FROM events WHERE `dtadded` < {$oldlogtime}");
            sleep(60);
            
            $oldlogtime = date("Y-m-d", mktime(date("H"), date("i"), date("s"), date("m"), date("d")-30, date("Y")));
            $db->Execute("DELETE FROM messages WHERE type='out' AND status='1' AND `dtlasthandleattempt` < {$oldlogtime}");
            sleep(60);
            
            //Clear old scripting events
            $year = date("Y");
            $month = date("m", mktime(date("H"), date("i"), date("s"), date("m")-1, date("d"), date("Y")));
            $db->Execute("DELETE FROM  `farm_role_scripts` WHERE ismenuitem='0' AND event_name LIKE  'CustomEvent-{$year}{$month}%'");
            $db->Execute("DELETE FROM  `farm_role_scripts` WHERE ismenuitem='0' AND event_name LIKE  'APIEvent-{$year}{$month}%'");
            
            // Rotate syslog
            if ($db->GetOne("SELECT COUNT(*) FROM syslog") > 1000000)
            {
                $dtstamp = date("dmY");
                $db->Execute("CREATE TABLE syslog_{$dtstamp} (id INT NOT NULL AUTO_INCREMENT,
                              PRIMARY KEY (id))
                              ENGINE=MyISAM SELECT dtadded, message, severity, transactionid FROM syslog;");
                $db->Execute("TRUNCATE TABLE syslog");
                $db->Execute("OPTIMIZE TABLE syslog");
                $db->Execute("TRUNCATE TABLE syslog_metadata");
                $db->Execute("OPTIMIZE TABLE syslog_metadata");
                
                $this->Logger->debug("Log rotated. New table 'syslog_{$dtstamp}' created.");
            }
        }
        
        public function OnEndForking()
        {
            
        }
        
        public function StartThread($farminfo)
        {
            
        }
    }
?>