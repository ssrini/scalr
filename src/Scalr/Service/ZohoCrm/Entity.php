<?php

/**
 * @author Marat Komarov
 * @property int $id
 * @property string $createdBy
 * @property string $createdTime
 * @property string $modifiedBy
 * @property string $modifiedTime
 */
class Scalr_Service_ZohoCrm_Entity extends Scalr_Service_ZohoCrm_EntityPart {
	
	function __construct () {
		$this->nameLabelMap = array_merge($this->nameLabelMap, array(
			"id" => "Id",
			"createdBy" => "Created By",
			"createdTime" => "Created Time",
			"modifiedBy" => "Modified By",
			"modifiedTime" => "Modified Time"
		));
	}
	
	function decode (DOMElement $container) {
		parent::decode($container);
		
		// Handle id returned by insertRecords
		if (!$this->id && $this->getProperty("Id")) {
			$this->id = $this->getProperty("Id");
		}		
	}
}