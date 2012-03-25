<?php

	class Scalr_Storage_Snapshot extends Scalr_Model
	{
		protected $dbTableName = 'storage_snapshots';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "Snapshot #%s not found in database";
		
		protected $dbPropertyMap = array(
			'id'			=> 'id',
			'client_id'		=> array('property' => 'clientId', 'is_filter' => true),
			'env_id'		=> array('property' => 'envId', 'is_filter' => true),
			'farm_id'		=> array('property' => 'farmId', 'is_filter' => true),
			'farm_roleid'	=> array('property' => 'farmRoleId', 'is_filter' => true),
			'name'			=> array('property' => 'name', 'is_filter' => true),
			'type'			=> array('property' => 'type', 'is_filter' => false),
			'platform'		=> array('property' => 'platform', 'is_filter' => false),
			'config'		=> array('property' => 'config', 'is_filter' => false),
			'description'	=> array('property' => 'description', 'is_filter' => true),
			'ismysql'		=> array('property' => 'isMysql', 'is_filter' => false),
			'dtcreated'		=> array('property' => 'dtCreated', 'createSql' => 'NOW()', 'type' => 'datetime', 'update' => false),
			'service'		=> array('property' => 'service', 'is_filter' => false)
		);
		
		public
			$id,
			$clientId,
			$envId,
			$farmId,
			$farmRoleId,
			$type,
			$platform,
			$name,
			$description,
			$isMysql,
			$service,
			$dtCreated;
			
		protected $config;
		
		
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