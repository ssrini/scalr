<?php

	class Scalr_Storage_Volume extends Scalr_Model
	{
		protected $dbTableName = 'storage_volumes';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "Volume #%s not found in database";
		
		protected $dbPropertyMap = array(
			'id'				=> 'id',
			'client_id'			=> array('property' => 'clientId', 'is_filter' => true),
			'env_id'			=> array('property' => 'envId', 'is_filter' => true),
			'name'				=> array('property' => 'name', 'is_filter' => true),
			'attachment_status'	=> array('property' => 'attachmentStatus', 'is_filter' => false),
			'mount_status'		=> array('property' => 'mountStatus', 'is_filter' => false),
			'platform'			=> array('property' => 'platform', 'is_filter' => false),
			'config'			=> array('property' => 'config', 'is_filter' => false),
			'type'				=> array('property' => 'type', 'is_filter' => false),
			'dtcreated'			=> array('property' => 'dtCreated', 'createSql' => 'NOW()', 'type' => 'datetime', 'update' => false),
			'fstype'			=> array('property' => 'fsType', 'is_filter' => false),
			'size'				=> array('property' => 'size', 'is_filter' => false),
			'farm_roleid'		=> array('property' => 'farmRoleId', 'is_filter' => false),
			'server_index'		=> array('property' => 'serverIndex', 'is_filter' => false),
			'purpose'			=> array('property' => 'purpose', 'is_filter' => false),
		);
		
		public
			$id,
			$clientId,
			$envId,
			$type,
			$platform,
			$name,
			$farmRoleId,
			$serverIndex,
			$purpose,
			$attachmentStatus,
			$mountStatus,
			$dtCreated,
			$fsType,
			$size;
			
		protected $config;
		
		/**
		 *
		 * @param integer $id
		 */
		public function loadByFarmRoleServer($farmRoleId, $serverIndex, $purpose = false)
		{
			$sql = "SELECT * FROM {$this->dbTableName} WHERE farm_roleid=? AND server_index=?";
			$args = array($farmRoleId, $serverIndex);
			
			if ($purpose) {
				$sql .= " AND purpose=?";
				$args[] = $purpose; 
			}
			
			$info = $this->db->GetRow($sql, $args);
			if (! $info)
				throw new Exception("Storage not found ({$farmRoleId}, {$serverIndex}, {$purpose})");

			return $this->loadBy($info);
		}
		
		public function getConfig($encoded = false)
		{
			return (!$encoded) ? json_decode($this->config) : $this->config;
		}
		
		/**
		 * 
		 * @param string $config
		 * @param boolean $encoded
		 */
		public function setConfig($config, $encoded = false)
		{
			$this->config = (!$encoded) ? json_encode($config) : $config;
		}
	}
	
?>