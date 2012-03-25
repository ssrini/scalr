<?php

require_once(LOG4PHP_DIR . '/LoggerAppender.php');
require_once(LOG4PHP_DIR . '/helpers/LoggerOptionConverter.php');

class LoggerAppenderScalr extends LoggerAppender {

    /**
     * Create the log table if it does not exists (optional).
     * @var boolean
     */
    var $createTable = true;
    
    /**
     * The type of database to connect to
     * @var string
     */
    var $type;
    
    /**
     * Database user name
     * @var string
     */
    var $user;
    
    /**
     * Database password
     * @var string
     */
    var $password;
    
    /**
     * Database host to connect to
     * @var string
     */
    var $host;
    
    /**
     * Name of the database to connect to
     * @var string
     */
    var $database;
    
    /**
     * A {@link LoggerPatternLayout} string used to format a valid insert query (mandatory).
     * @var string
     */
    var $sql;
    
    /**
     * Table name to write events. Used only if {@link $createTable} is true.
     * @var string
     */    
    var $table;
    
    /**
     * @var object Adodb instance
     * @access private
     */
    var $db = null;
    
    /**
     * @var boolean used to check if all conditions to append are true
     * @access private
     */
    var $canAppend = true;
    
    /**    
     * @access private
     */
    var $requiresLayout = false;
    
    /**
     * Constructor.
     *
     * @param string $name appender name
     */
    function LoggerAppenderDb($name)
    {
        $this->LoggerAppenderSkeleton($name);
    }

    /**
     * Setup db connection.
     * Based on defined options, this method connects to db defined in {@link $dsn}
     * and creates a {@link $table} table if {@link $createTable} is true.
     * @return boolean true if all ok.
     */
    function activateOptions()
    {        
        $this->db = Core::GetDBInstance();//null, true);
        
        $this->layout = LoggerReflectionUtils::createObject('LoggerPatternLayoutScalr');
        $this->layout->setConversionPattern($this->getSql());
    
        $this->canAppend = true;
    }
    
    function append(LoggerLoggingEvent $event)
    {    	
    	if ($this->canAppend) 
        {
        	try
            {
	        	// Reopen new mysql connection (need for php threads)
	        	$this->activateOptions();
	        	
	        	if ($event->message instanceOf FarmLogMessage)
	        	{
	        		$severity = $this->SeverityToInt($event->getLevel()->toString());
	        		$message = $this->db->qstr($event->message->Message);
	        		
	        		$query = "INSERT DELAYED INTO logentries SET 
	        			serverid	= '', 
	        			message		= {$message},
	        			severity	= '{$severity}',
	        			time		= '".time()."',
	        			source 		= '".$event->getLoggerName()."',
	        			farmid 		= '{$event->message->FarmID}' 
	        		";
	        			
	        		$this->db->Execute($query);
	        		
	        		$event->message = "[FarmID: {$event->message->FarmID}] {$event->message->Message}";
	        		
	        		return;
	        	}
	        	elseif ($event->message instanceof ScriptingLogMessage)
	        	{
	        		$message = $this->db->qstr($event->message->Message);
	        		
	        		$query = "INSERT DELAYED INTO scripting_log SET 
	        			farmid 		= '{$event->message->FarmID}',
	        			event		= '{$event->message->EventName}',
	        			server_id	= '{$event->message->ServerID}',
	        			dtadded		= NOW(),
	        			message		= {$message}
	        		";
	        			
	        		$this->db->Execute($query);
	        		
	        		$event->message = "[Farm: {$event->message->FarmID}] {$event->message->Message}";
	        		
	        		return;
	        	}
	        	else
	        	{
	        		if (stristr($event->message, "AWS was not able to validate the provided access credentials") || 
	        			stristr($event->message, "The X509 Certificate you provided does not exist in our records")
	        		)
	        		
	        		return;
	        	}
	        	
	        	$level = $event->getLevel()->toString();
	        		        	
	        	// Redeclare threadName
	            $event->threadName = TRANSACTION_ID; 
	
	            $event->subThreadName = defined("SUB_TRANSACTIONID") ? SUB_TRANSACTIONID 
	            		: $GLOBALS["SUB_TRANSACTIONID"] ? $GLOBALS["SUB_TRANSACTIONID"] 
	            		: TRANSACTION_ID;
	            
	            $event->farmID = defined("LOGGER_FARMID") ? LOGGER_FARMID 
	            		: $GLOBALS["LOGGER_FARMID"] ? $GLOBALS["LOGGER_FARMID"] 
	            		: null;
	            
	           	if (defined('TRANSACTION_ID'))
	           	{
		        	if ($level == "FATAL" || $level == "ERROR")
		        	{
		        		// Set meta information
		        		$this->db->Execute("INSERT DELAYED INTO syslog_metadata SET transactionid='".TRANSACTION_ID."', errors='1', warnings='0'
		        			ON DUPLICATE KEY UPDATE errors=errors+1
		        		");
		        	}
		        	else
		        	{
		        		if ($level == "WARN")
		        		{
		        			// Set meta information
			        		$this->db->Execute("INSERT DELAYED INTO syslog_metadata SET transactionid='".TRANSACTION_ID."', errors='0', warnings='1'
			        			ON DUPLICATE KEY UPDATE warnings=warnings+1
			        		");	
		        		}
		        	}
	           	}
	        	
	           	$msg = $event->message; 
	           	$event->message = $this->db->qstr($event->message);
	           	
	            $query = $this->layout->format($event);
	            
            	$this->db->Execute($query);
            	
            	$event->message = $msg;
            }
            catch(Exception $e)
            {

            }
        }
    }
    
    function SeverityToInt($severity)
    {
    	$severities = array("DEBUG" => 1, "INFO" => 2, "WARN" => 3, "ERROR" => 4, "FATAL" => 5);
    	
    	return $severities[$severity];
    }
    
    function close()
    {
        if ($this->db !== null)
            $this->db->Close();
        $this->closed = true;
    }
    
    /**
     * @return boolean
     */
    function getCreateTable()
    {
        return $this->createTable;
    }
    
    /**
     * @return string the sql pattern string
     */
    function getSql()
    {
        return $this->sql;
    }
    
    /**
     * @return string the table name to create
     */
    function getTable()
    {
        return $this->table;
    }
    
    /**
     * @return string the database to connect to
     */
    function getDatabase() {
        return $this->database;
    }
    
    /**
     * @return string the database to connect to
     */
    function getHost() {
        return $this->host;
    }
    
    /**
     * @return string the user to connect with
     */
    function getUser() {
        return $this->user;
    }
    
    /**
     * @return string the password to connect with
     */
    function getPassword() {
        return $this->password;
    }
    
    /**
     * @return string the type of database to connect to
     */
    function getType() {
        return $this->type;
    }
    
    function setCreateTable($flag)
    {
        $this->createTable = LoggerOptionConverter::toBoolean($flag, true);
    }
    
    function setType($newType)
    {
        $this->type = $newType;
    }
    
    function setDatabase($newDatabase)
    {
        $this->database = $newDatabase;
    }
    
    function setHost($newHost)
    {
        $this->host = $newHost;
    }
    
    function setUser($newUser)
    {
        $this->user = $newUser;
    }
    
    function setPassword($newPassword)
    {
        $this->password = $newPassword;
    }
    
    function setSql($sql)
    {
        $this->sql = $sql;    
    }
    
    function setTable($table)
    {
        $this->table = $table;
    }
    
}
?>