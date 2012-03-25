<?php

	class Scalr_ServiceConfiguration extends Scalr_Model
	{
		protected $dbTableName = 'service_config_presets';
		protected $dbPrimaryKey = "id";
		protected $dbMessageKeyNotFound = "Preset #%s not found in database";

		protected $dbPropertyMap = array(
			'id'			=> 'id',
			'name'			=> array('property' => 'name', 'is_filter' => true),
			'client_id'		=> array('property' => 'clientId', 'is_filter' => true),
			'env_id'		=> array('property' => 'envId', 'is_filter' => true),
			'dtadded'		=> array('property' => 'dtAdded', 'createSql' => 'NOW()', 'type' => 'datetime', 'update' => false),
			'dtlastmodified'=> array('property' => 'dtLastModified', 'createSql' => 'NOW()', 'updateSql' => 'NOW()', 'type' => 'datetime'),
			'role_behavior'	=> array('property' => 'roleBehavior', 'is_filter' => true)
		);

		public
			$id,
			$name,
			$clientId,
			$envId,
			$dtAdded,
			$dtLastModified,
			$roleBehavior;

		private $parameters;

		/**
		 * 
		 * @return Scalr_ServiceConfiguration
		 */
		public static function init() {
			return parent::init();
		}
		
		public function loadBy($info)
		{
			parent::loadBy($info);

			error_reporting(E_ALL);

		    $ini_params = @parse_ini_file(dirname(__FILE__)."/../../www/storage/service-configuration-manifests/{$this->roleBehavior}.ini", true, INI_SCANNER_RAW);
		    
			foreach ($ini_params as $param => $props)
			{
				if ($param == '__defaults__')
					continue;
					
				$this->parameters[] = new Scalr_ServiceConfigurationParameter(
					$param,
					$props['default-value'],
					$props['type'],
					$props['description'],
					$props['allowed-values'],
					$props['group']
				);
			}

			if ($this->id)
			{
				//load actual values from database;
				$params = $this->db->Execute("SELECT * FROM service_config_preset_data WHERE preset_id = ?", array($this->id));
				while ($param = $params->FetchRow())
					$this->setParameterValue($param['key'], $param['value']);
			}

			return $this;
		}

		public function delete()
		{
			parent::delete();
			$this->db->Execute("DELETE FROM service_config_preset_data WHERE preset_id = ?", array($this->id));
			$this->db->Execute("DELETE FROM farm_role_service_config_presets WHERE preset_id = ?", array($this->id));
		}

		public function save()
		{
			parent::save();

			$this->db->Execute("DELETE FROM service_config_preset_data WHERE preset_id = ?", array($this->id));
			foreach ($this->parameters as $param)
			{
				if ($param->getValue() != null)
				{
					//Save params
					$this->db->Execute("INSERT INTO service_config_preset_data SET
						`preset_id`	= ?,
						`key`		= ?,
						`value`		= ?
					", array($this->id, $param->getName(), $param->getValue()));
				}
			}

			return true;
		}

		/**
		 * @return array
		 */
		public function getParameters()
		{
			$retval = array();
			foreach ($this->parameters as $param)
			{
				if ($param->getValue() != "")
					$retval[] = $param;
			}

			return $retval;
		}

		/**
		 *
		 * @param string $name
		 * @return Scalr_ServiceConfigurationParameter
		 */
		public function getParameter($name)
		{
			foreach ($this->parameters as &$param)
				if ($param->getName() == $name)
					return $param;

			return null;
		}

		public function setParameterValue($name, $value)
		{
			foreach ($this->parameters as &$param) {
				if ($param->getName() == $name) {
					if ($param->validate($value)) {
						$param->setValue($value);
					}
				}
			}
		}

		public function getJsParameters()
		{
			$items = array();
			foreach ($this->parameters as $param)
			{
				if ($param->getName() == '__defaults__')
					continue;
					
				$itemField = new stdClass();
				$itemField->name = "config[{$param->getName()}]";
				$itemField->flex = 1;

				switch($param->getType())
				{
					case 'text':
						$itemField->xtype = 'textfield';
						$itemField->allowBlank = true;
						$itemField->value = $param->getValue();
					break;

					case 'boolean':
						$itemField->xtype = 'checkbox';
						$itemField->inputValue = 1;
						$itemField->checked = ($param->getValue() == 1);
						break;

					case 'select':
						$itemField->xtype = 'combo';
						$itemField->allowBlank = true;
						$itemField->editable = true;
						$itemField->queryMode = 'local';
						$itemField->value = $param->getValue();
						$itemField->store = $param->getAllowedValues();
						break;
					default:
						continue;
						break;
				}

				$itemDescription = new stdClass();
				$itemDescription->xtype = 'displayfield';
				$itemDescription->width = 16;
				$itemDescription->margin = new stdClass();
				$itemDescription->margin->left = 5;
				$itemDescription->value = '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">';
				$itemDescription->hText = $param->getDescription();

				$item = new stdClass();
				$item->xtype = 'fieldcontainer';
				$item->fieldLabel = $param->getName();
				$item->labelWidth = 240;
				$item->layout = 'hbox';
				$item->items = array(
					$itemField,
					$itemDescription
				);

				$items[] = $item;
			}

			return $items;
		}
	}
