<?
	class ScriptingLogMessage
    {
    	public $FarmID;
    	public $EventName;
    	public $ServerID;
    	public $Message;
    	
    	function __construct($farmid, $event_name, $server_id, $message)
    	{
    		$this->FarmID = $farmid;
    		$this->EventName = $event_name;
    		$this->ServerID = $server_id;
    		$this->Message = $message;
    	}
    }
?>