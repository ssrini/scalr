<?php

class Scalr_Db_Msr_Redis_Info extends Scalr_Db_Msr_Info {
	
	protected $masterPassword;
	protected $persistenceType;
	
	public function __construct(DBFarmRole $dbFarmRole, DBServer $dbServer) {
		
		$this->databaseType = Scalr_Db_Msr::DB_TYPE_REDIS;
		
		parent::__construct($dbFarmRole, $dbServer);
		
		$this->masterPassword = $dbFarmRole->GetSetting(Scalr_Db_Msr_Redis::MASTER_PASSWORD);
		$this->persistenceType = $dbFarmRole->GetSetting(Scalr_Db_Msr_Redis::PERSISTENCE_TYPE);
	}
	
	public function getMessageProperties() {
		$retval = parent::getMessageProperties();
		
		$retval->masterPassword = $this->masterPassword;
		$retval->persistence_type = $this->persistenceType;
		
		return $retval;
	}
	
	public function setMsrSettings($settings) {

		if ($this->replicationMaster) {
			parent::setMsrSettings($settings);
			
			$roleSettings = array(
				Scalr_Db_Msr_Redis::MASTER_PASSWORD => $settings->masterPassword
			);
			
			foreach ($roleSettings as $name=>$value)
				$this->dbFarmRole->SetSetting($name, $value);
				
		}
	}
}