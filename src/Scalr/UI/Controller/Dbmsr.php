<?php
class Scalr_UI_Controller_Dbmsr extends Scalr_UI_Controller
{
	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function xSetupPmaAccessAction()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int'),
			'farmRoleId' => array('type' => 'int')
		));

		$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
		$this->user->getPermissions()->validate($dbFarm);

		$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
		if ($dbFarmRole->FarmID != $dbFarm->ID)
			throw new Exception("Role not found");

		$dbFarmRole->ClearSettings("mysql.pma");

		$masterDbServer = null;
		foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $dbServer) {
			if ($dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER)) {
				$masterDbServer = $dbServer;
				break;
			}
		}

		if ($masterDbServer) {
			$time = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME);
			if (!$time || $time+3600 < time()) {
				$msg = new Scalr_Messaging_Msg_Mysql_CreatePmaUser($dbFarmRole->ID, CONFIG::$PMA_INSTANCE_IP_ADDRESS);
				$masterDbServer->SendMessage($msg);

				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME, time());
				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR, "");

				$this->response->success();
			}
			else
				throw new Exception("MySQL access credentials for PMA already requested. Please wait...");
		}
		else
			throw new Exception("There is no running MySQL master. Please wait until master starting up.");
	}

	public function xCreateDataBundleAction()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int'),
			'farmRoleId' => array('type' => 'int')
		));

		$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
		$this->user->getPermissions()->validate($dbFarm);

		$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
		if ($dbFarmRole->FarmID != $dbFarm->ID)
			throw new Exception("Role not found");

		if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
			foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $dbServer) {
				if ($dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER)) {

					if ($dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING) == 1)
						throw new Exception("Data bundle already in progress");

					$dbServer->SendMessage(new Scalr_Messaging_Msg_Mysql_CreateDataBundle());

					$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING, 1);
					$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_BUNDLE_SERVER_ID, $dbServer->serverId);

					$this->response->success('Data bundle successfully initiated');
					return;
				}
			}
		} else {
			foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $dbServer) {
				if ($dbServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) == 1) {

					if ($dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BUNDLE_IS_RUNNING) == 1)
						throw new Exception("Data bundle already in progress");

					$dbServer->SendMessage(new Scalr_Messaging_Msg_DbMsr_CreateDataBundle());

					$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_IS_RUNNING, 1);
					$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_SERVER_ID, $dbServer->serverId);


					$this->response->success('Data bundle successfully initiated');
					return;
				}
			}
		}

		$this->response->failure('Scalr unable to initiate data bundle. No running replication master found.');
	}

	public function xCreateBackupAction()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int'),
			'farmRoleId' => array('type' => 'int')
		));

		$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
		$this->user->getPermissions()->validate($dbFarm);

		$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
		if ($dbFarmRole->FarmID != $dbFarm->ID)
			throw new Exception("Role not found");

		if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
			if ($dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING) == 1)
				throw new Exception("Backuping already in progress");

			foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $dbServer) {
				if (!$dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER))
					$slaveDbServer = $dbServer;
				else
					$masterDbServer = $dbServer;
			}

			if (!$slaveDbServer)
				$slaveDbServer = $masterDbServer;

			if ($slaveDbServer) {
				$slaveDbServer->SendMessage(new Scalr_Messaging_Msg_Mysql_CreateBackup($dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD)));

				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING, 1);
				$dbFarmRole->SetSetting(DBFarmRole::SETTING_MYSQL_BCP_SERVER_ID, $slaveDbServer->serverId);

				$this->response->success('Backuping successfully initiated');
				return;
			}
		} else {
			if ($dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BACKUP_IS_RUNNING) == 1)
					throw new Exception("Backup already in progress");

			foreach ($dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING)) as $dbServer) {
				if (!$dbServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER)) {
					$slaveDbServer = $dbServer;
					break;
				}
				else
					$masterDbServer = $dbServer;
			}

			if (!$slaveDbServer)
				$slaveDbServer = $masterDbServer;

			if ($slaveDbServer) {
				$slaveDbServer->SendMessage(new Scalr_Messaging_Msg_DbMsr_CreateBackup());

				$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BACKUP_IS_RUNNING, 1);
				$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BACKUP_SERVER_ID, $slaveDbServer->serverId);

				$this->response->success('Backuping successfully initiated');
				return;
			}
		}

		$this->response->failure('Scalr unable to initiate data backup. No running replication master found.');
	}

	public function statusAction()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int'),
			'farmRoleId' => array('type' => 'int'),
			'type'
		));

		$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
		$this->user->getPermissions()->validate($dbFarm);

		if ($this->getParam('farmRoleId')) {
			$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));
			if ($dbFarmRole->FarmID != $dbFarm->ID)
				throw new Exception("Role not found");
		}
		elseif ($this->getParam('type')) {
			foreach ($dbFarm->GetFarmRoles() as $sDbFarmRole) {
				if ($sDbFarmRole->GetRoleObject()->hasBehavior($this->getParam('type'))) {
					$dbFarmRole = $sDbFarmRole;
					break;
				}
			}

			if (!$dbFarmRole)
				throw new Exception("Role not found");

		} else {
			throw new Scalr_UI_Exception_NotFound();
		}

		$data = array('farmRoleId' => $dbFarmRole->ID, 'farmHash' => $dbFarm->Hash);
		
		//TODO: Legacy code. Move to DB_MSR
		if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL)) {
			$data['dbType'] = Scalr_Db_Msr::DB_TYPE_MYSQL;

			$data['dtLastBundle'] = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LAST_BUNDLE_TS) ? Scalr_Util_DateTime::convertTz((int)$dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LAST_BUNDLE_TS), 'd M Y \a\\t H:i:s') : 'Never';
			$data['dtLastBackup'] = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LAST_BCP_TS) ? Scalr_Util_DateTime::convertTz((int)$dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_LAST_BCP_TS), 'd M Y \a\\t H:i:s') : 'Never';
			$data['pmaAccessConfigured'] = false;
			
			$data['additionalInfo']['MasterPassword'] = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_ROOT_PASSWORD);
			
			foreach ($dbFarmRole->GetServersByFilter() as $dbServer) {
				if ($dbServer->status != SERVER_STATUS::RUNNING) {
					//TODO:
					continue;
				}

				if ($dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) == 1)
				{
					$data['isBundleRunning'] = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_IS_BUNDLE_RUNNING);
					$data['bundleServerId'] = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_BUNDLE_SERVER_ID);

		   			if ($dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_USER))
		   				$data['pmaAccessConfigured'] = true;
		   			else
		   			{

		   				$errmsg = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_ERROR);
	   					if (!$errmsg)
	   					{
		   					$time = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_PMA_REQUEST_TIME);
		   					if ($time)
		   					{
		   						if ($time+3600 < time())
		   							$data['pmaAccessError'] = _("Scalr didn't receive auth info from MySQL instance. Please check that MySQL running and Scalr has access to it.");
		   						else
		   							$data['pmaAccessSetupInProgress'] = true;
		   					}
	   					} else
	   						$data['pmaAccessError'] = $errmsg;
		   			}
				}

	   			$data['isBackupRunning'] = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_IS_BCP_RUNNING);
				$data['backupServerId'] = $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_BCP_SERVER_ID);

				$slave_num = 0;

				try
		   		{
		   			$conn = &NewADOConnection("mysqli");
		   			$conn->Connect($dbServer->remoteIp, CONFIG::$MYSQL_STAT_USERNAME, $dbFarmRole->GetSetting(DBFarmRole::SETTING_MYSQL_STAT_PASSWORD), null);
		   			$conn->SetFetchMode(ADODB_FETCH_ASSOC);

					if ($dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) == 1)
					{
		   				$r = $conn->GetRow("SHOW MASTER STATUS");
		   				$MasterPosition = $r['Position'];
		   				$master_ip = $dbServer->remoteIp;
		   				$master_iid = $dbServer->serverId;
					}
		   			else
		   			{
		   				$r = $conn->GetRow("SHOW SLAVE STATUS");

		   				$slaveNumber = ++$slave_num;
		   				$SlavePosition = $r['Exec_Master_Log_Pos'];
		   			}

		   			$data["replicationStatus"][] =
		   			array(
		   				"serverId" => $dbServer->serverId,
		   				"localIp" => $dbServer->localIp,
		   				"remoteIp" => $dbServer->remoteIp,
		   				"data" => $r,
		   				"masterPosition" => $MasterPosition,
		   				"slavePosition" => $SlavePosition,
		   				"replicationRole" => $dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) ? 'Master' : "Slave #{$slaveNumber}"
		   			);
		   		}
		   		catch(Exception $e)
		   		{
		   			$data["replicationStatus"][] = array(
		   				"serverId" => $dbServer->serverId,
		   				"localIp" => $dbServer->localIp,
		   				"remoteIp" => $dbServer->remoteIp,
		   				"error" => ($e->msg) ? $e->msg : $e->getMessage(),
		   				"replicationRole" => $dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) ? 'Master' : 'Slave'
		   			);
		   		}
			}

		} elseif ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL) || $dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS)) {

			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL)) {
				$data['dbType'] = Scalr_Db_Msr::DB_TYPE_POSTGRESQL;
				
				$data['additionalInfo']['MasterPassword'] = $dbFarmRole->GetSetting(Scalr_Db_Msr_Postgresql::ROOT_PASSWORD);
			}
			elseif ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS)) {
				$data['dbType'] = Scalr_Db_Msr::DB_TYPE_REDIS;
				
				$data['additionalInfo']['MasterPassword'] = $dbFarmRole->GetSetting(Scalr_Db_Msr_Redis::MASTER_PASSWORD);
			}

			$data['dtLastBackup'] = $dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BACKUP_LAST_TS) ? Scalr_Util_DateTime::convertTz((int)$dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BACKUP_LAST_TS), 'd M Y \a\\t H:i:s') : 'Never';
			$data['dtLastBundle'] = $dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BUNDLE_LAST_TS) ? Scalr_Util_DateTime::convertTz((int)$dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BUNDLE_LAST_TS), 'd M Y \a\\t H:i:s') : 'Never';

			foreach ($dbFarmRole->GetServersByFilter() as $dbServer) {
				if ($dbServer->status != SERVER_STATUS::RUNNING) {
					//TODO:
					continue;
				}

				if ($dbServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) == 1) {
					$data['isBundleRunning'] = $dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BUNDLE_IS_RUNNING);
					$data['bundleServerId'] = $dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BUNDLE_SERVER_ID);
				}

	   			$data['isBackupRunning'] = $dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BACKUP_IS_RUNNING);
				$data['backupServerId'] = $dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_BACKUP_SERVER_ID);

				$slave_num = 0;

				try {
		   			if ($dbServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER) == 1) {
						//TODO:
					}
		   			else
		   			{
		   				$slaveNumber = ++$slave_num;
		   			}

		   			$data["replicationStatus"][] =
		   			array(
		   				"serverId" => $dbServer->serverId,
		   				"localIp" => $dbServer->localIp,
		   				"remoteIp" => $dbServer->remoteIp,
		   				"data" => array(),
		   				"replicationRole" => $dbServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) ? 'Master' : "Slave #{$slaveNumber}"
		   			);
		   		}
		   		catch(Exception $e)
		   		{
		   			$data["replicationStatus"][] = array(
		   				"serverId" => $dbServer->serverId,
		   				"error" => ($e->msg) ? $e->msg : $e->getMessage(),
		   				"replicationRole" => $dbServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER) ? 'Master' : 'Slave'
		   			);
		   		}
			}
		}

		$this->response->page('ui/dbmsr/status.js', $data);
	}
}
