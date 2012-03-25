<?php

	class DBEBSVolume
	{
		public 
			$id,
			$farmId,
			$envId,
			$farmRoleId,
			$volumeId,
			$serverId,
			$clientId,
			$attachmentStatus,
			$mountStatus,
			$deviceName,
			$serverIndex,
			$mount,
			$mountPoint,
			$ec2AvailZone,
			$ec2Region,
			$size,
			$snapId,
			$isFsExists,
			$isManual,
			$isMysqlVolume;
			
		private
			$db,
			$environment,
			$logger;
			
		/**
		 * 
		 * @var DBServer
		 */
		private $server;
			
		private static $FieldPropertyMap = array(
			'id' 			=> 'id',
			'env_id'		=> 'envId',
			'farm_id'		=> 'farmId',
			'farm_roleid'	=> 'farmRoleId',			
			'volume_id'		=> 'volumeId',
			'server_id' 	=> 'serverId',
			'attachment_status'	=> 'attachmentStatus',
			'mount_status'	=> 'mountStatus',
			'device'		=> 'deviceName',
			'server_index'	=> 'serverIndex',
			'mount'			=> 'mount',
			'mountpoint'	=> 'mountPoint',
			'ec2_avail_zone'=> 'ec2AvailZone',
			'ec2_region'	=> 'ec2Region',
			'isfsexist'		=> 'isFsExists',
			'ismanual'		=> 'isManual',
			'size'			=> 'size',
			'snap_id'		=> 'snapId',
			'ismysqlvolume'	=> 'isMysqlVolume',
			'client_id'		=> 'clientId'
		);
			
		public function __construct($volumeId=null)
		{
			$this->volumeId = $volumeId;
			$this->db = Core::GetDBInstance();
			$this->logger = Logger::getLogger(__CLASS__);
		}
		
		/**
		 * @return Scalr_Environment
		 */
		public function getEnvironmentObject()
		{
			if (!$this->environment)
				$this->environment = Scalr_Model::init(Scalr_Model::ENVIRONMENT)->loadById($this->envId);
				
			return $this->environment;
		}
		
		/**
		 * @return DBEBSVolume
		 * @param string $id
		 */
		public static function loadById($id)
		{
			$db = Core::GetDBInstance();
			
			$ebs_info = $db->GetRow("SELECT * FROM ec2_ebs WHERE id = ?", array($id));
			if (!$ebs_info)
				throw new Exception(sprintf(_("EBS ID#%s not found in database"), $id));
				
			$DBEBSVolume = new DBEBSVolume($ebs_info['volume_id']);
			
			foreach(self::$FieldPropertyMap as $k=>$v)
			{
				if (isset($ebs_info[$k]))
					$DBEBSVolume->{$v} = $ebs_info[$k];
			}

			return $DBEBSVolume;
		}
		
		/**
		 * @return DBEBSVolume
		 * @param string $volumeId
		 */
		public static function loadByVolumeId($volumeId)
		{
			$db = Core::GetDBInstance();
			
			$ebs_info = $db->GetRow("SELECT id FROM ec2_ebs WHERE volume_id = ?", array($volumeId));
			if (!$ebs_info)
				throw new Exception(sprintf(_("EBS volume ID#%s not found in database"), $volumeId));
				
			return self::loadById($ebs_info['id']);
		}
		
		private function unBind () {
			$row = array();
			foreach (self::$FieldPropertyMap as $field => $property) {
				$row[$field] = $this->{$property};
			}
			
			return $row;		
		}
				
		public function dettach()
		{
			
		}
		
		public function delete()
		{
			$this->db->Execute("DELETE FROM ec2_ebs WHERE id=?", array($this->id));
		}
		
		public function save () {
				
			$row = $this->unBind();
			unset($row['id']);
			
			$this->db->BeginTrans();
			
			// Prepare SQL statement
			$set = array();
			$bind = array();
			foreach ($row as $field => $value) {
				$set[] = "`$field` = ?";
				$bind[] = $value;
			}
			$set = join(', ', $set);
	
			try	{
				
				//Save zone;
				
				if ($this->id) {
					// Perform Update
					$bind[] = $this->id;
					$this->db->Execute("UPDATE ec2_ebs SET $set WHERE id = ?", $bind);	
				}
				else {
					// Perform Insert
					$this->db->Execute("INSERT INTO ec2_ebs SET $set", $bind);
					$this->id = $this->db->Insert_ID();
				}
				
			} catch (Exception $e) {
				
				$this->db->RollbackTrans();
				throw new Exception ("Cannot save ec2 ebs volume. Error: " . $e->getMessage(), $e->getCode());			
			}
			
			$this->db->CommitTrans();
		}
	}
	
?>