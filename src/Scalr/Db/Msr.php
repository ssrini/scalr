<?php 
class Scalr_Db_Msr
{
	/** DBServer settings **/
	const REPLICATION_MASTER = 'db.msr.replication_master';
	
	/** DBFarmRole settings **/
	const VOLUME_ID    = 'db.msr.volume_id';
	const SNAPSHOT_ID  = 'db.msr.snapshot_id';
	const DATA_STORAGE_ENGINE = 'db.msr.data_storage.engine';
	
	// For EBS storage
	const DATA_STORAGE_EBS_SIZE = 'db.msr.data_storage.ebs.size';
	const DATA_STORAGE_EBS_ENABLE_ROTATION = 'db.msr.data_storage.ebs.snaps.enable_rotation';
	const DATA_STORAGE_EBS_ROTATE = 'db.msr.data_storage.ebs.snaps.rotate';
	
	// For Raid storage
	//const DATA_STORAGE_RAID
	
	/** Replication settings **/
	const SLAVE_TO_MASTER = 'db.msr.slave_to_master';
	
	/** Data Bundle settings **/
	const DATA_BUNDLE_ENABLED = 'db.msr.data_bundle.enabled';
	const DATA_BUNDLE_EVERY   = 'db.msr.data_bundle.every';
	const DATA_BUNDLE_IS_RUNNING = 'db.msr.data_bundle.is_running';
	const DATA_BUNDLE_SERVER_ID = 'db.msr.data_bundle.server_id';
	const DATA_BUNDLE_LAST_TS	= 'db.msr.data_bundle.timestamp';
	const DATA_BUNDLE_TIMEFRAME_START_HH = 'db.msr.data_bundle.timeframe.start_hh';
	const DATA_BUNDLE_TIMEFRAME_END_HH = 'db.msr.data_bundle.timeframe.end_hh';
	const DATA_BUNDLE_TIMEFRAME_START_MM = 'db.msr.data_bundle.timeframe.start_mm';
	const DATA_BUNDLE_TIMEFRAME_END_MM = 'db.msr.data_bundle.timeframe.end_mm';
	
	/** Data Backup settings **/
	const DATA_BACKUP_ENABLED = 'db.msr.data_backup.enabled';
	const DATA_BACKUP_EVERY   = 'db.msr.data_backup.every';
	const DATA_BACKUP_IS_RUNNING = 'db.msr.data_backup.is_running';
	const DATA_BACKUP_SERVER_ID = 'db.msr.data_backup.server_id';
	const DATA_BACKUP_LAST_TS	= 'db.msr.data_backup.timestamp';
	const DATA_BACKUP_TIMEFRAME_START_HH = 'db.msr.data_backup.timeframe.start_hh';
	const DATA_BACKUP_TIMEFRAME_END_HH = 'db.msr.data_backup.timeframe.end_hh';
	const DATA_BACKUP_TIMEFRAME_START_MM = 'db.msr.data_backup.timeframe.start_mm';
	const DATA_BACKUP_TIMEFRAME_END_MM = 'db.msr.data_backup.timeframe.end_mm';
	
	
	
	const DB_TYPE_MYSQL = 'mysql';
	const DB_TYPE_POSTGRESQL = 'postgresql';
	const DB_TYPE_REDIS = 'redis';
	
	
	public static $reflect;
	public static function getConstant($name)
	{
		if (!self::$reflect)
			self::$reflect = new ReflectionClass('Scalr_Db_Msr');
			
		return self::$reflect->getConstant($name);
	}
	
	public static function onCreateBackupResult(Scalr_Messaging_Msg $message, DBServer $dbServer)
	{
		$dbFarmRole = $dbServer->GetFarmRoleObject();
		
		$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BACKUP_LAST_TS, time());
		$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BACKUP_IS_RUNNING, 0);
		$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BACKUP_SERVER_ID, "");
		
		//TODO: $message->backupParts
	}
	
	public static function onCreateDataBundleResult(Scalr_Messaging_Msg $message, DBServer $dbServer)
	{		
		$dbFarm = $dbServer->GetFarmObject();
		$dbFarmRole = $dbServer->GetFarmRoleObject();
		
		$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_LAST_TS, time());
		$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_IS_RUNNING, 0);
		$dbFarmRole->SetSetting(Scalr_Db_Msr::DATA_BUNDLE_SERVER_ID, "");
		
		$dbSettings = $message->{$message->dbType};
		
		if ($dbSettings->snapshotConfig) {
			try {					
				$snapshot = Scalr_Model::init(Scalr_Model::STORAGE_SNAPSHOT);
				$snapshot->loadBy(array(
					'id'			=> $dbSettings->snapshotConfig->id,
					'client_id'		=> $dbServer->clientId,
					'env_id'		=> $dbServer->envId,
					'name'			=> "Automatical '{$message->dbType}' data bundle",
					'type'			=> $dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_STORAGE_ENGINE),
					'platform'		=> $dbServer->platform,
					'description'	=> "'{$message->dbType}' data bundle created automatically by Scalr",
					'service'		=> $message->dbType
				));
				$snapshot->setConfig($dbSettings->snapshotConfig);
				$snapshot->save(true);
													
			
				$dbFarmRole->SetSetting(Scalr_Db_Msr::SNAPSHOT_ID, $snapshot->id);
				
				if ($message->dbType == self::DB_TYPE_MYSQL) {
           			$dbFarmRole->SetSetting(Scalr_Db_Msr_Mysql::LOG_FILE, $dbSettings->logFile);
           			$dbFarmRole->SetSetting(Scalr_Db_Msr_Mysql::LOG_POS, $dbSettings->logPos);
				}
                elseif ($message->dbType == self::DB_TYPE_POSTGRESQL) {
                	$dbFarmRole->SetSetting(Scalr_Db_Msr_Postgresql::XLOG_LOCATION, $dbSettings->currentXlogLocation);
                }
				elseif ($message->dbType == self::DB_TYPE_REDIS) {
                	//Nothing todo
                } 
			}
			catch(Exception $e) {
				Logger::getLogger(__CLASS__)->error(new FarmLogMessage($dbServer->farmId, "Cannot save storage snapshot: {$e->getMessage()}"));
			} 
		}
	}
	
	public static function onPromoteToMasterResult(Scalr_Messaging_Msg_DbMsr_PromoteToMasterResult $message, DBServer $dbServer)
	{
		$dbFarm = $dbServer->GetFarmObject();
		$dbFarmRole = $dbServer->GetFarmRoleObject();
	       					
	    $dbFarmRole->SetSetting(Scalr_Db_Msr::SLAVE_TO_MASTER, 0);
	    
	    if ($message->status == Scalr_Messaging_Msg_Mysql_PromoteToMasterResult::STATUS_FAILED) {
	    	
	    	$dbServer->SetProperty(Scalr_Db_Msr::REPLICATION_MASTER, 0);
	    	
	    	return false;
	    }
	    
	    $dbSettings = $message->{$message->dbType};
	    
	    //Update volumeCondig
		if ($dbSettings->volumeConfig) {
			try {					
				$storageVolume = Scalr_Storage_Volume::init();
				try {
					$storageVolume->loadById($dbSettings->volumeConfig->id);
					$storageVolume->setConfig($dbSettings->volumeConfig);
					$storageVolume->save();
				} catch (Exception $e) {
					if (strpos($e->getMessage(), 'not found')) {
						$storageVolume->loadBy(array(
							'id'			=> $dbSettings->volumeConfig->id,
							'client_id'		=> $dbServer->clientId,
							'env_id'		=> $dbServer->envId,
							'name'			=> "'{$message->dbType}' data volume",
							'type'			=> $dbFarmRole->GetSetting(Scalr_Db_Msr::DATA_STORAGE_ENGINE),
							'platform'		=> $dbServer->platform,
							'size'			=> $dbSettings->volumeConfig->size,
							'fstype'		=> $dbSettings->volumeConfig->fstype,
							'purpose'		=> $message->dbType,
							'farm_roleid'	=> $dbFarmRole->ID,
							'server_index'	=> $dbServer->index
						));
						$storageVolume->setConfig($dbSettings->volumeConfig);
						$storageVolume->save(true);
					} else
						throw $e;
				}
			}
			catch(Exception $e) {
				Logger::getLogger(__CLASS__)->error(new FarmLogMessage($dbServer->farmId, "Cannot save storage volume: {$e->getMessage()}"));
			} 
		}
		
		self::onCreateDataBundleResult($message, $dbServer);
		
		return true;
	}
}

?>