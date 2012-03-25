<?php

class Scalr_Service_ZohoCrm_EntityPart {

	protected $properties = array();
	
	protected $nameLabelMap = array();
	
	function __get ($name) {
		return $this->getProperty($name);
	}
	
	function __set ($name, $value) {
		$this->setProperty($name, $value);
	}
	
	function __unset($name) {
		$this->setProperty($name, null);
	}
	
	function getProperty ($name) {
		return $this->properties[$name];
	}
	
	function setProperty ($name, $value) {
		$this->properties[$name] = $value;
	}
	
	function encode (DOMElement $container, DOMDocument $doc) {
		foreach ($this->properties as $k => $v) {
			$label = $this->nameLabelMap[$k] ? $this->nameLabelMap[$k] : $k;

			if ($v === false) {
				$v = "false";
			} else if ($v === true) {
				$v = "true";
			} else if ($v === null) {
				// TODO: unsetting fields doesn't work
				// Follow this topic http://forums.zoho.com/?ftid=2266000000626377
				$v = "null"; 
			}
			
			$el = $doc->createElement("FL");
			$el->setAttribute("val", $label);
			$el->appendChild($doc->createTextNode($v));

			$container->appendChild($el);
		}
	}
	
	function decode (DOMElement $container) {
		foreach ($container->childNodes as $child) {
			if ($child->nodeType == XML_ELEMENT_NODE) {
				$label = $child->getAttribute("val");
				$name = array_search($label, $this->nameLabelMap);
				
				$value = $child->firstChild->nodeValue;
				if ($value == "null") {
					$value = null;
				} else if ($value == "false") {
					$value = false;
				} else if ($value == "true") {
					$value = true;
				}
				
				if ($value !== null) {
					$this->setProperty($name ? $name : $label, $value);
				}
			} 
		}
	}
	
}