<?php

class Scalr_Validator
{
	const REGEX = 'regex';
	const MINMAX = 'minmax';
	const RANGE = 'range';
	const REQUIRED = 'required';
	const NOHTML = 'nohtml';
	const EMAIL = 'email';
	
	protected $errors = array();
	
	public function validate($value, $validators)
	{
		$result = true;
		$type = gettype($value);
		foreach ($validators as $key => $validator) {
			$method = "validate" . ucfirst($key);
			$resultValidator = $this->{$method}($value, $type, $validator);
			if ($resultValidator !== true && $result === true) {
				$result = array();
				$result = array_merge($result, $resultValidator);
			} else if ($resultValidator !== true && $result !== true) {
				$result = array_merge($result, $resultValidator);
			}
		}
		
		return $result;
	}
	
	public function validateRegex($value, $type, $options)
	{
		return true;
	}
	
	public function validateMinmax($value, $type, $options)
	{
		if ($type == 'string') {
			$len = strlen($value);
			if (isset($options['min']) && $options['min'] > $len)
				return array("Value must be longer then {$options['min']} chars");

			if (isset($options['max']) && $options['max'] < $len)
				return array("Value must be shorter then {$options['max']} chars");
				
			return true;

		} else if ($type == 'integer') {
			if (isset($options['min']) && $options['min'] > $value)
				return array("Value must be greater then {$options['min']}");

			if (isset($options['max']) && $options['max'] < $value)
				return array("Value must be lower then {$options['max']}");

			return true;

		} else {
			return true;
		}
	}
	
	public function validateRequired($value, $type, $options)
	{
		if ($options === true && !$value)
			return array("Value is required");
		else
			return true; 
	}
	
	public function validateRange($value, $type, $options)
	{
		if (($type == 'string' || $type == 'integer') && $value && is_array($options)) {
			if (! in_array($value, $options))
				return array('Not allowed value');
			else
				return true;
		} else {
			return true;
		}
	}
	
	public function validateNohtml($value, $type, $options)
	{
		if ($options === true && preg_match("/^[A-Za-z0-9\s]+$/si", $value))
			return true;
		else
			return array('Value should contain only letters and numbers');
	}
	
	public function validateEmail($value, $type, $options)
	{
		if ($options === true && $value) {
			if (filter_var($value, FILTER_VALIDATE_EMAIL) !== false)
				return true;
			else
				return array('Value should be valid email address');
		} else {
			return true;
		}
	}
}
