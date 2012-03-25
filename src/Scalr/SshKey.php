<?php

	class Scalr_SshKey extends Scalr_Model
	{
		protected $dbTableName = 'ssh_keys';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "SSH key #%s not found in database";

		const TYPE_GLOBAL = 'global';
		const TYPE_USER	  = 'user';
		
		protected $dbPropertyMap = array(
			'id'			=> 'id',
			'client_id'		=> array('property' => 'clientId', 'is_filter' => true),
			'env_id'		=> array('property' => 'envId', 'is_filter' => true),
			'type'			=> array('property' => 'type', 'is_filter' => false),
			'private_key'	=> array('property' => 'privateKeyEnc', 'is_filter' => false),
			'public_key'	=> array('property' => 'publicKeyEnc', 'is_filter' => false),
			'cloud_location'=> array('property' => 'cloudLocation', 'is_filter' => false),
			'farm_id'		=> array('property' => 'farmId', 'is_filter' => false),
			'cloud_key_name'=> array('property' => 'cloudKeyName', 'is_filter' => false),
			'platform'		=> array('property' => 'platform', 'is_filter' => true),
		);
		
		public
			$id,
			$clientId,
			$envId,
			$type,
			$cloudPlatform,
			$farmId,
			$cloudKeyName,
			$platform;
			
		protected $privateKeyEnc,
				$publicKeyEnc;

		/**
		 * 
		 * @return Scalr_SshKey
		 */
		public static function init() {
			return parent::init();
		}

		public function getFingerprint()
		{
			return "ab:ab:ab:ab";
		}
		
		public function loadGlobalByName($name, $cloudLocation, $envId)
		{
			$info = $this->db->GetRow("SELECT * FROM ssh_keys WHERE `cloud_key_name`=? AND `cloud_location`=? AND `type`=? AND `env_id` = ?", 
				array($name, $cloudLocation, self::TYPE_GLOBAL, $envId)
			);
			if (!$info)
				return false;
			else 
				return parent::loadBy($info);
		}
		
		public function loadGlobalByFarmId($farmId, $cloudLocation)
		{
			$info = $this->db->GetRow("SELECT * FROM ssh_keys WHERE `farm_id`=? AND `cloud_location`=? AND `type`=?", 
				array($farmId, $cloudLocation, self::TYPE_GLOBAL)
			);
			if (!$info)
				return false;
			else 
				return parent::loadBy($info);
		}
		
		private function getSshKeygenValue($args, $tmpFileContents, $readTmpFile = false)
		{
			$descriptorspec = array(
			   0 => array("pipe", "r"),
			   1 => array("pipe", "w"),
			   2 => array("pipe", "w")
			);
			
			$filePath = CACHEPATH."/_tmp.".md5($tmpFileContents);
			
			if (!$readTmpFile)
			{
				@file_put_contents($filePath, $tmpFileContents);
				@chmod($filePath, 0600);
			}
			
			$pipes = array();
			$process = @proc_open("/usr/bin/ssh-keygen -f {$filePath} {$args}", $descriptorspec, &$pipes);
			if (@is_resource($process)) {
			    
				@fclose($pipes[0]);
			
			    $retval = trim(stream_get_contents($pipes[1]));
			    
			    fclose($pipes[1]);
			    fclose($pipes[2]);
			}
			
			if ($readTmpFile)
				$retval = file_get_contents($filePath);
			
			@unlink($filePath);
			
			return $retval;
		}
		
		public function generateKeypair()
		{
			$private_key = $this->getSshKeygenValue("-t dsa -q -P ''", "", true);			
			$this->setPrivate($private_key);
			$this->setPublic($this->generatePublicKey());
			return array('private' => $private_key, 'public' => $this->getPublic());
		}
		
		public function generatePublicKey()
		{
			if (!$this->getPrivate())
				throw new Exception("Public key cannot be generated without private key");
				
			$pub_key = $this->getSshKeygenValue("-y", $this->getPrivate());

			return $pub_key;
		}
		
		public function setPrivate($key)
		{
			$this->privateKeyEnc = $this->getCrypto()->encrypt($key, $this->cryptoKey);
		}
		
		public function setPublic($key)
		{
			$this->publicKeyEnc = $this->getCrypto()->encrypt($key, $this->cryptoKey);
		}
		
		public function getPrivate()
		{
			return $this->getCrypto()->decrypt($this->privateKeyEnc, $this->cryptoKey);
		}
		
		public function getPublic()
		{
			return $this->getCrypto()->decrypt($this->publicKeyEnc, $this->cryptoKey);
		}
	}
