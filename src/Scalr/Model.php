<?php

	abstract class Scalr_Model
	{
		public $id;
		protected $db;

		const ENVIRONMENT				= 'Scalr_Environment';
		const SERVICE_CONFIGURATION 	= 'Scalr_ServiceConfiguration';
		const SCALING_METRIC			= 'Scalr_Scaling_Metric';
		const SCALING_FARM_ROLE_METRIC	= 'Scalr_Scaling_FarmRoleMetric';
		const SSH_KEY					= 'Scalr_SshKey';

		const STORAGE_SNAPSHOT			= 'Scalr_Storage_Snapshot';
		const STORAGE_VOLUME			= 'Scalr_Storage_Volume';

		const DM_APPLICATION			= 'Scalr_Dm_Application';
		const DM_SOURCE					= 'Scalr_Dm_Source';
		const DM_DEPLOYMENT_TASK		= 'Scalr_Dm_DeploymentTask';

		const APACHE_VHOST				= 'Scalr_Service_Apache_Vhost';

		/**
		 * 'dbkey' => 'classkey'
		 * 'dbkey' => array('property' => 'classkey', 'type' => 'string' (default) or 'bool' or 'datetime' (unixstamp) or serialize (by php), 'is_filter' => true or false, 'update' => true or false, 'updateSql', 'createSql')
		 */

		protected $dbTableName = null;
		protected $dbPrimaryKey = "id";
		protected $dbPropertyMap = array();
		protected $dbMessageKeyNotFound = "Key#%s not found in database";

		protected $crypto, $cryptoKey;

		public function __construct($id = null)
		{
			$this->id = $id;
			$this->db = Core::GetDBInstance();
			$this->dbMessageKeyNotFound = get_class($this) . " " . $this->dbMessageKeyNotFound;
		}

		/**
		 * @return Scalr_Util_CryptoTool
		 */
		protected function getCrypto()
		{
			if (! $this->crypto) {
				$this->crypto = new Scalr_Util_CryptoTool(MCRYPT_TRIPLEDES, MCRYPT_MODE_CFB, 24, 8);
				$this->cryptoKey = @file_get_contents(dirname(__FILE__)."/../../etc/.cryptokey");
			}

			return $this->crypto;
		}

		public function __call($name, $arguments)
		{
			if (!strncmp($name, "loadBy", 6) && count($arguments) > 0) {
				$name = lcfirst(substr($name, 6));
				$property = $this->findDbKeyByProperty($name);
				$value = $arguments[0];
				$loadFlag = !isset($arguments[1]) || isset($arguments[1]) && $arguments[1];

				if ($property && is_array($this->dbPropertyMap[$property]) && isset($this->dbPropertyMap[$property]['is_filter']) && $this->dbPropertyMap[$property]['is_filter']) {
					if ($loadFlag) {
						$info = $this->db->getRow("SELECT * FROM {$this->dbTableName} WHERE {$property} = ?", array($value));
						if (! $info)
							throw new Exception(sprintf(_($this->dbMessageKeyNotFound), $value));

						return $this->loadBy($info);
					} else {
						return $this->db->getAll("SELECT * FROM {$this->dbTableName} WHERE {$property} = ?", array($value));
					}
				}
			}

			throw new Exception(_("Method '{$name}' of class '".get_called_class()."' not found"));
		}

		/**
		 *
		 * @param string $className
		 * @return Scalr_Model
		 *
		 */
		public static function init($className=null)
		{
			//TODO: Validate class
			if (!$className)
				$className = get_called_class();

			return new $className();
		}

		private function findDbKeyByProperty($property)
		{
			foreach ($this->dbPropertyMap as $key => $value) {
				if (is_array($value) && $value['property'] == $property)
					return $key;
				elseif(is_string($value) && $value == $property)
					return $key;
			}
			return null;
		}

		public function loadBy($info)
		{
			foreach($this->dbPropertyMap as $key => $value) {
				$property = is_array($value) ? $value['property'] : $value;

				if (isset($info[$key])) {
					$val = $info[$key];
					if (is_array($value) && $value['type']) {
						switch ($value['type']) {
							case 'bool':
								$val = $val ? true : false;
								break;
							case 'datetime':
								$val = strtotime($val);
								break;
							case 'serialize':
								$val = unserialize($val);
								break;
						}
					}

					$this->{$property} = $val;
				}
			}

			return $this;
		}

		/**
		 *
		 * @param integer $id
		 */
		public function loadById($id)
		{
			$info = $this->db->GetRow("SELECT * FROM {$this->dbTableName} WHERE {$this->dbPrimaryKey}=?", array($id));
			if (! $info)
				throw new Exception(sprintf(_($this->dbMessageKeyNotFound), $id));

			return $this->loadBy($info);
		}

		public function loadByFilter($filterArgs = array(), $ufilterArgs = array())
		{
			$sql = array();
			$args = array();

			foreach ($filterArgs as $key => $value) {
				if (($property = $this->findDbKeyByProperty($key))) {
					if (is_array($value)) {
						if (count($value)) {
							foreach ($value as $vvalue)
								$args[] = $this->db->quote($vvalue);
							$sql[] = "`{$property}` IN (" . implode(",", array_fill(0, count($value), "?")) . ")";
						}
					} else {
						$sql[] = "`{$property}` = ?";
						$args[] = $value;
					}
				}
			}

			foreach ($ufilterArgs as $key => $value) {
				if (($property = $this->findDbKeyByProperty($key))) {
					if (is_array($value)) {
						if (count($value)) {
							foreach ($value as $vvalue)
								$args[] = $this->db->quote($vvalue);
							$sql[] = "`{$key}` NOT IN (" . implode(",", array_fill(0, count($value), "?")) . ")";
						}
					} else {
						$sql[] = "`{$key}` != ?";
						$args[] = $value;
					}
				}
			}

			$sqlString = "SELECT * FROM {$this->dbTableName}";
			if (count($sql))
				$sqlString .= " WHERE " . implode(" AND ", $sql);

			//TODO: Return array of objects
			//die("TODO");

			return $this->db->GetAll($sqlString, $args);
		}

		public function save($forceInsert = false)
		{
			$set = array();
			$bind = array();

			foreach ($this->dbPropertyMap as $field => $value) {
				$isArrayValue = is_array($value);

				if ($field == $this->dbPrimaryKey && !$forceInsert)
					continue;

				if ($isArrayValue && isset($value['createSql']) && (!$this->id || $forceInsert)) {
					$set[] = "`{$field}` = {$value['createSql']}";
					continue;
				}

				if ($isArrayValue && isset($value['updateSql']) && $this->id && !$forceInsert) {
					$set[] = "`{$field}` = {$value['updateSql']}";
					continue;
				}

				if ($isArrayValue && isset($value['update']) && $value['update'] == false)
					continue;

				$property = $isArrayValue ? $value['property'] : $value;
				$val = $this->{$property};

				if ($isArrayValue && isset($value['type'])) {
					switch ($value['type']) {
						case 'bool':
							$val = $val ? 1 : 0;
							break;
						case 'datetime':
							$val = is_null($val) ? $val : date("Y-m-d H:m:s", $val);
							break;
						case 'serialize':
							$val = serialize($val);
							break;
					}
				}

				$set[] = "`{$field}` = ?";
				$bind[] = $val;
			}
			$set = implode(', ', $set);

			try {
				if ($this->id && !$forceInsert) {
					// Perform Update
					$bind[] = $this->id;
					$this->db->Execute("UPDATE {$this->dbTableName} SET {$set} WHERE id = ?", $bind);
				} else {
					// Perform Insert
					$this->db->Execute("INSERT INTO {$this->dbTableName} SET {$set}", $bind);

					if (!$this->id)
						$this->id = $this->db->Insert_ID();
				}
			} catch (Exception $e) {
				throw new Exception (sprintf(_("Cannot save record. Error: %s"), $e->getMessage()), $e->getCode());
			}

			return $this;
		}

		public function delete($id = null)
		{
			$id = !is_null($id) ? $id : $this->id;
			try {
				$this->db->Execute("DELETE FROM {$this->dbTableName} WHERE id=?", array($id));
			} catch (Exception $e) {
				throw new Exception (sprintf(_("Cannot delete record. Error: %s"), $e->getMessage()), $e->getCode());
			}
		}
	}
