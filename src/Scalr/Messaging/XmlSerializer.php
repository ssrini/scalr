<?php

class Scalr_Messaging_XmlSerializer {
	const SERIALIZE_BROADCAST = 'serializeBroadcast';
	
	private $msgClassProperties = array();
	
	static private $instance;
	
	/**
	 * @return Scalr_Messaging_XmlSerializer
	 */
	static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new Scalr_Messaging_XmlSerializer();
		}
		return self::$instance;
	}
	
	function __construct () {
		$this->msgClassProperties = array_keys(get_class_vars('Scalr_Messaging_Msg'));
	} 
	
	function serialize (Scalr_Messaging_Msg $msg, $options = array()) {
		
		$doc = new DOMDocument('1.0','utf-8');
		$doc->loadXML(sprintf('<message id="%s" name="%s"><meta /><body /></message>', 
				$msg->messageId, $msg->getName()));

		$metaEl = $doc->getElementsByTagName("meta")->item(0);
		$this->walkSerialize($msg->meta, $metaEl, $doc);
		
		$bodyEl = $doc->getElementsByTagName("body")->item(0);
		$body = array();
		foreach (get_object_vars($msg) as $k => $v) {
			if (in_array($k, $this->msgClassProperties)) {
				continue;
			}
			if ($options[self::SERIALIZE_BROADCAST] && in_array($k, $msg->forecastVars)) {
				continue;
			}
			
			$body[$k] = $v;
		}
		$this->walkSerialize($body, $bodyEl, $doc);
		
		return trim($doc->saveXML());
	}
	
	private function walkSerialize ($value, $el, $doc) {
		if (is_array($value) || is_object($value)) {
			if (is_array($value) && array_keys($value) === range(0, count($value)-1)) {
				// Numeric indexes array
				foreach ($value as $v) {
					$itemEl = $doc->createElement("item");
					$el->appendChild($itemEl);
					$this->walkSerialize($v, $itemEl, $doc);
				}
			} else {
				// Assoc arrays and objects
				foreach ($value as $k => $v) {
					$itemEl = $doc->createElement($this->under_scope($k));
					$el->appendChild($itemEl);
					$this->walkSerialize($v, $itemEl, $doc);
				}
			}
		} else {
			if (preg_match("/[\<\>\&]+/", $value)) {
				$valueEl = $doc->createCDATASection($value);
			} else {
				$valueEl = $doc->createTextNode($value);
			}
			$el->appendChild($valueEl);
		}
	}
	
	/**
	 * @param string $xmlString
	 * @return Scalr_Messaging_Msg
	 */
	function unserialize ($xmlString) {
		$xml = simplexml_load_string($xmlString);
		if (!$xml) {
			
			throw new Exception('Cannot load XML string: ' . $xmlString);
		}
		
		$ref = new ReflectionClass(Scalr_Messaging_Msg::getClassForName($xml->attributes()->name));
		$msg = $ref->newInstance();
		$msg->messageId = "{$xml->attributes()->id}";
		
		foreach ($xml->meta->children() as $el) {
			$msg->meta["{$el->getName()}"] = "{$el}";
		}
		
		foreach ($xml->body->children() as $el) {
			$msg->{$this->camelCase("{$el->getName()}")} = $this->walkUnserialize($el);
		}
		
		return $msg;
	}
	
	private function walkUnserialize ($xml) {
		if ($xml->children()) {
			
			$isArray = true;
			foreach ($xml->children() as $ch) {
				$isArray &= $ch->getName() == "item";
			}
			
			if ($isArray) {
				$ret = array();				
				foreach ($xml->children() as $ch) {
					$ret[] = $this->walkUnserialize($ch);
				}
			} else {
				$ret = new stdClass();
				foreach ($xml->children() as $ch) {
					$ret->{$this->camelCase("{$ch->getName()}")} = $this->walkUnserialize($ch);
				}
			}
			
			return $ret;
		} else {
			return "{$xml}";
		}
	}
	
	private function under_scope ($name) {
		$parts = preg_split("/[A-Z]/", $name, -1, PREG_SPLIT_OFFSET_CAPTURE | PREG_SPLIT_NO_EMPTY);
		$ret = "";
		foreach ($parts as $part) {
			if ($part[1]) {
				$ret .= "_" . strtolower($name{$part[1]-1});
			}
			$ret .= $part[0];
		}
		return $ret;
	}
	
	private function camelCase ($name) {
		$parts = explode("_", $name);
		$first = array_shift($parts);
		return $first . join("", array_map("ucfirst", $parts));
	}
}