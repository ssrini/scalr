<?php
class Scalr_Db_Msr_Mysql_Info extends Scalr_Db_Msr_Info
{
	public
		$rootUser,
		$rootPassword,
		$replPassword,
		$statPassword,
		$logFile,
		$logPos;
		
	public function __construct(DBFarmRole $dbFarmRole, DBServer $dbServer) {
		
		$this->databaseType = Scalr_Db_Msr::DB_TYPE_MYSQL;
		
		/*
		parent::__construct($dbFarmRole, $dbServer);
				
		$this->rootUser = $dbFarmRole->GetSetting(Scalr_Db_Msr_Postgresql::ROOT_USERNAME);
		$this->rootPassword = $dbFarmRole->GetSetting(Scalr_Db_Msr_Postgresql::ROOT_PASSWORD);
		$this->rootSshPrivateKey = $dbFarmRole->GetSetting(Scalr_Db_Msr_Postgresql::ROOT_SSH_PRIV_KEY);
		$this->rootSshPublicKey = $dbFarmRole->GetSetting(Scalr_Db_Msr_Postgresql::ROOT_SSH_PUB_KEY);
		$this->currentXlogLocation = $dbFarmRole->GetSetting(Scalr_Db_Msr_Postgresql::XLOG_LOCATION);
		*/
	}
	
	public function getMessageProperties() {
		
		/*
		$retval = parent::getMessageProperties();
		
		$retval->rootUser = $this->rootUser;
		$retval->rootPassword = $this->rootPassword;
		$retval->rootSshPrivateKey = $this->rootSshPrivateKey;
		$retval->rootSshPublicKey = $this->rootSshPublicKey;
		$retval->currentXlogLocation = $this->currentXlogLocation;
		
		return $retval;
		*/
	}
	
	public function setMsrSettings($settings) {

		/*
		if ($this->replicationMaster) {
			parent::setMsrSettings($settings);
			
			$roleSettings = array(
				Scalr_Db_Msr_Postgresql::ROOT_USERNAME => $settings->rootUser,
				Scalr_Db_Msr_Postgresql::ROOT_PASSWORD => $settings->rootPassword,
				Scalr_Db_Msr_Postgresql::ROOT_SSH_PRIV_KEY => $settings->rootSshPrivateKey,
				Scalr_Db_Msr_Postgresql::ROOT_SSH_PUB_KEY => $settings->rootSshPublicKey,
				Scalr_Db_Msr_Postgresql::XLOG_LOCATION => $settings->currentXlogLocation
			);
			
			foreach ($roleSettings as $name=>$value)
				$this->dbFarmRole->SetSetting($name, $value);
		}
		*/
	}
}