<?php

class XmlMenu
{
	protected $Xml;
	
	function LoadFromFile ($filename) 
	{
		$this->Xml = simplexml_load_file($filename);
		if (!$this->Xml)
			throw new Exception(sprintf(_("Cannot load navigation menu from file '%s'"), $filename));
	}

	function Walk ($ContextNode, $fn, &$result, $parents)
	{
		$parents[] = $ContextNode;		
		foreach ($ContextNode->children() as $Item)
		{
			call_user_func($fn, $Item, $parents, &$result);
			if ("node" == $Item->getName())
			{
				$this->Walk($Item, $fn, $result, $parents);
			}
		}
	}
	
	function GetExtJSMenuItems ()
	{
		$ret=array();
		$this->GetExtJSMenuItemsRecursive($this->Xml, $ret);
		return $ret;
	}
	
	private function GetExtJSMenuItemsRecursive ($Context, &$ret)
	{
		foreach ($Context->children() as $Item)
		{
			if ("separator" == $Item->getName())
				$xj_item = "-";
			else if ("item" == $Item->getName())
			{
				$xj_item = array
				(
					"text" => (string)$Item, 
					"href" => (string)$Item->attributes()->href
				);
			}
			else if ("node" == $Item->getName())
			{
				$xj_item = array
				(
					"text" => (string)$Item->attributes()->title,
					"hideOnClick" => false
				);
				$xj_item["menu"] = array();
				$this->GetExtJSMenuItemsRecursive($Item, $xj_item["menu"]);
			}
			$ret[] = $xj_item;
		}
	}

	
	function GetXml ()
	{
		return $this->Xml;
	}
	function SetXml ($Xml)
	{
		if ($Xml instanceof SimpleXMLElement)
			$this->Xml = $Xml;
		elseif ($Xml instanceof DOMElement)
			$this->Xml = simplexml_import_dom($Xml);
		else
			throw new Exception("Argument '\$Xml' must be instance of SimpleXMLElement or DOMElement");
	}
	function WtiteXmlToFile($filepath)
	{
		try
		{
			$this->Xml->asXML($filepath);
		}
		catch (Exception $e)
		{
			throw $e; 
		}
		
	
	}
}