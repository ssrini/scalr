<?
	class RolesQueueProcess implements IProcess
    {
        public $ThreadArgs;
        public $ProcessDescription = "Roles queue";
        public $Logger;
        
    	public function __construct()
        {
        	// Get Logger instance
        	$this->Logger = Logger::getLogger(__CLASS__);
        }
        
        public function OnStartForking()
        {
            $db = Core::GetDBInstance();
            
            $roles = $db->GetAll("SELECT * FROM roles_queue WHERE `action` = 'remove'");
            foreach ($roles as $role)
            {
            	try {
            		$dbRole = DBRole::loadById($role['role_id']);
            		$dbRole->remove(true);
            	} catch (Exception $e) {
            		print $e->getMessage()."\n";
            	}
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