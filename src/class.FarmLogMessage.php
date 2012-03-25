<?
	class FarmLogMessage
    {
    	public $FarmID;
    	public $Message;
    	
    	function __construct($farmid, $message)
    	{
    		$this->FarmID = $farmid;
    		$this->Message = $message;
    	}
    }
?>