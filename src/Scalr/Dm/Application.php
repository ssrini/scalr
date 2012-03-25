<?php

	class Scalr_Dm_Application extends Scalr_Model
	{
		protected $dbTableName = 'dm_applications';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "Application #%s not found in database";
		
		protected $dbPropertyMap = array(
			'id'			=> array('property' => 'id', 'is_filter' => true),
			'env_id'		=> array('property' => 'envId', 'is_filter' => true),
			'name'			=> array('property' => 'name', 'is_filter' => true),
			'dm_source_id'	=> array('property' => 'sourceId', 'is_filter' => true)
		);
		
		public
			$id,
			$envId,
			$name,
			$sourceId;
			
		protected $preDeployScript = false;
		protected $postDeployScript = false;
		
		protected $source;
				
		public function setPreDeployScript($scriptBody = "")
		{
			$this->preDeployScript = $scriptBody;
		}
		
		public function setPostDeployScript($scriptBody = "")
		{
			$this->postDeployScript = $scriptBody;
		}
		
		public static function getIdByNameAndSource($name, $sourceId)
		{
			return Core::GetDBInstance()->GetOne("SELECT id FROM dm_applications WHERE `name`=? AND dm_source_id=?", array($name, $sourceId));
		}
		
		/**
		 * @return Scalr_Dm_Source
		 */
		public function getSource()
		{
			if (!$this->source)
				$this->source = Scalr_Model::init(Scalr_Model::DM_SOURCE)->loadById($this->sourceId);
			
			return $this->source;
		}
		
		public function getPreDeployScript()
		{
			if ($this->preDeployScript === false)
				$this->preDeployScript = (string)$this->db->GetOne("SELECT pre_deploy_script FROM dm_applications WHERE id=?", array($this->id));
			
			return $this->preDeployScript;
		}
		
		public function getPostDeployScript()
		{
			if ($this->postDeployScript === false)
				$this->postDeployScript = (string)$this->db->GetOne("SELECT post_deploy_script FROM dm_applications WHERE id=?", array($this->id));
			
			return $this->postDeployScript;
		}
		
		public function save($forceInsert = false)
		{
			parent::save();
			
			if ($this->preDeployScript !== false)
				$this->db->Execute("UPDATE dm_applications SET pre_deploy_script = ? WHERE id = ?", array($this->preDeployScript, $this->id));
				
			if ($this->postDeployScript !== false)
				$this->db->Execute("UPDATE dm_applications SET post_deploy_script = ? WHERE id = ?", array($this->postDeployScript, $this->id));
		}
	}
