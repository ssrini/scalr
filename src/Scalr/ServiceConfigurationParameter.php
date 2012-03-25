<?php

	class Scalr_ServiceConfigurationParameter
	{
		function __construct($name, $value, $dataType, $description, $allowedValues, $group) {
			$this->name = $name;
			$this->defaultValue = $value;
			$this->value = $this->defaultValue;
			$this->dataType = $dataType;
			$this->description = $description;
			$this->allowedValues = $allowedValues;
			$this->group = $group;
		}
		
		function getAllowedValues()
		{
			return explode(',', $this->allowedValues);
		}
		
		function getDescription() {
			return $this->description;
		}
		
		function getName() {
			return $this->name;
		}
		
		function getType() {
			return $this->dataType;
		}
		
		function getValue() {
			return $this->value;
		}
		
		function setValue($value) {
			$this->value = $value;
		}
		
		function getDefaultValue() {
			return $this->defaultValue;
		}
		
		function validate() {
			return true;
		}
	}
