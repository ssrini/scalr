<?php

class Scalr_Service_ZohoCrm_Service {
	
	const METHOD_INSERT = "insertRecords";
	const METHOD_UPDATE = "updateRecords";
	const METHOD_DELETE = "deleteRecords";
	const METHOD_GET 	= "getRecordById";
	const METHOD_SEARCH = "getSearchRecords";

	
	protected $moduleName;
	
	protected $entityCls;
	
	protected $apiKey;
	
	protected $username;
	
	protected $password;
	
	/**
	 * @var Scalr_Service_ZohoCrm_Session
	 */
	protected $session;
	
	private $logger;
	
	function __construct ($config) {
		foreach ($config as $k => $v) {
			if (property_exists($this, $k)) {
				$this->{$k} = $v; 
			}
		}
		$this->logger = Logger::getLogger(__CLASS__);
	}
	
	protected function getTicketId () {
		if (!$this->session->ticketId) {
			$this->session->ticketId = $this->generateTicketId();
		}
		return $this->session->ticketId;
	}
	
	protected function generateTicketId () {
		
		$this->logger->debug("Request zohocrm for ticket id...");
		
		$ch = curl_init();
		
		$query = array(
			"servicename" => "ZohoCRM",
			"FROM_AGENT" => "true",
			"LOGIN_ID" => $this->username,
			"PASSWORD" => $this->password
		);
		
		curl_setopt_array($ch, array(
			CURLOPT_URL => "https://accounts.zoho.com/login?" . http_build_query($query),
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER => 0,
			CURLOPT_SSL_VERIFYPEER => 0,
			CURLOPT_SSL_VERIFYHOST => 0
		));
		
		$httpBody = curl_exec($ch);
		$httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);

// Output example:
/*		
#
#Mon Nov 02 03:49:58 PST 2009
GETUSERNAME=null
WARNING=null
PASS_EXPIRY=-1
TICKET=3da08fd10867a6114f70090982878cbc
RESULT=TRUE
*/		
			
		if ($httpStatus == 200) {
			$response = Scalr_Util_Compat::parseIniString($httpBody);
			if ($response) {
				if ($response["RESULT"] == "1") {
					$this->logger->debug(sprintf("Ticket '%s' was generated", $response["TICKET"]));
					return $response["TICKET"]; 
				} else {
					throw new Scalr_Service_ZohoCrm_Exception(sprintf(
							"Cannot generate ticket. ZohoCRM warn: %s", $response["WARNING"]));
				}
			} else {
				throw new Scalr_Service_ZohoCrm_Exception(sprintf(
						"Cannot parse response. ZohoCRM response: '%s'", $httpBody));
			}
			
		} else {
			throw new Scalr_Service_ZohoCrm_Exception(sprintf(
					"ZohoCRM service failed (code: %d, body: %s)", 
					$httpStatus, $httpBody));
		}
	}
	
	/**
	 * @param array $options
	 * @key string [method]* 
	 * @key string [module]
	 * @key string [httpMethod] default: POST
	 * @key DOMDocument|string [xmlData]
	 * @key array [queryParams]
	 * @return DOMDocument
	 */
	protected function request ($options) {
		if (!$options["httpMethod"]) {
			$options["httpMethod"] = "POST";
		}
		if (!$options["module"]) {
			$options["module"] = $this->moduleName;
		}

		
		$url = "http://crm.zoho.com/crm/private/xml/{$options["module"]}/{$options["method"]}";
		
		$queryData = array(
			"apikey" => $this->apiKey,
			"ticket" => $this->getTicketId()
		);
		if ($options["xmlData"]) {
			$queryData["xmlData"] = $options["xmlData"] instanceof DOMDocument ? 
					$options["xmlData"]->saveXML(null, LIBXML_NOXMLDECL) : $options["xmlData"];		
		}
		if ($options["queryParams"]) {
			$queryData = array_merge($queryData, $options["queryParams"]);
		}
		
		$ch = curl_init();
		
		$curlOptions = array(
			CURLOPT_RETURNTRANSFER => 1,
			CURLOPT_HEADER => 0			
		);
		if ($options["httpMethod"] == "POST") {
			$curlOptions[CURLOPT_URL] = $url;
			$curlOptions[CURLOPT_POST] = 1;
			$curlOptions[CURLOPT_POSTFIELDS] = http_build_query($queryData); 
		} else {
			$curlOptions[CURLOPT_URL] = $url . "?" . http_build_query($queryData);
		}
		curl_setopt_array($ch, $curlOptions);
		
		$this->logger->debug("Request " . $curlOptions[CURLOPT_URL] 
				. ($queryData["xmlData"] ? "\n{$queryData["xmlData"]}" : ""));
		$httpBody = curl_exec($ch);
		$httpStatus = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
		$this->session->numApiCalls++;

		
		if ($httpStatus == 200) {
			$doc = DOMDocument::loadXML($httpBody, LIBXML_NOWARNING);
			if ($doc) {
				$this->logger->debug("Response:\n" . $doc->saveXML());				
				
				if ($doc->documentElement->firstChild->nodeName == "error") {
					$errorNode = $doc->documentElement->firstChild;
					throw new Scalr_Service_ZohoCrm_Exception(
							$errorNode->childNodes->item(1)->nodeValue,
							(int)$errorNode->childNodes->item(0)->nodeValue);
				}
				
				return $doc;
			} else {
				throw new Scalr_Service_ZohoCrm_Exception(
						"Cannot load XML. libxml errors: " . join("; ", libxml_get_errors()));
			}
		} else {
			throw new Scalr_Service_ZohoCrm_Exception(sprintf(
					"ZohoCRM service failed (code: %d, body: %s)", 
					$httpStatus, $httpBody));
		}
		
	}
	
	protected function newRequest () {
		$request = new DOMDocument();
		$root = $request->createElement($this->moduleName);
		$request->appendChild($root);
		
		return $request;
	}
	
	/**
	 * @return Scalr_Service_ZohoCrm_Entity
	 */
	protected function newEntity () {
		return new $this->entityCls;
	}
	
	private function decodeRecordDetail ($entity, $recorddetail, $xpath=null) {
		if (!$xpath) {
			$xpath = new DOMXPath($recorddetail->ownerDocument);
		}
		
		$entity->id = $xpath->evaluate("string(FL[@val='Id'])", $recorddetail);
		$entity->createdTime = $xpath->evaluate("string(FL[@val='Created Time'])", $recorddetail);
		$entity->modifiedTime = $xpath->evaluate("string(FL[@val='Modified Time'])", $recorddetail);
		$entity->createdBy = $xpath->evaluate("string(FL[@val='Created By'])", $recorddetail);
		$entity->modifiedBy = $xpath->evaluate("string(FL[@val='Modified By'])", $recorddetail);
	}
	
	/**
	 * @param $id
	 * @return Scalr_Service_ZohoCrm_Entity
	 */
	function get ($id) {
		$response = $this->request(array(
			"method" => self::METHOD_GET,
			"queryParams" => array("id" => $id)
		));
		
		$xpath = new DOMXPath($response);
		$rows = $xpath->query("//response/result/{$this->moduleName}/row");
		if ($rows->length) {
			$entity = $this->newEntity();
			$entity->decode($rows->item(0));
			return $entity;			
		} else {
			throw new Scalr_Service_ZohoCrm_Exception(sprintf("Entity %s(Id=%s) not found", 
					$this->moduleName, $id));
		}
	}	
	
	/**
	 * @param Scalr_Service_ZohoCrm_Entity $entity
	 * @return Scalr_Service_ZohoCrm_Entity
	 */
	function create ($entity) {
		$ret = $this->createAll(array($entity));
		return $ret[0];
	}
	
	/**
	 * @param array $entities
	 * @return array
	 */
	function createAll ($entities) {
		$request = $this->newRequest();
		
		foreach ($entities as $i => $entity) {
			$row = $request->createElement("row");
			$row->setAttribute("no", $i+1);
			$entity->encode($row, $request);
			$request->documentElement->appendChild($row);
		}
		
		$response = $this->request(array(
			"method" => self::METHOD_INSERT,
			"xmlData" => $request,
			"queryParams" => array("newFormat" => 2)
		));
		
		$xpath = new DOMXPath($response);
		$recorddetails = $xpath->query("//response/result/recorddetail");
		foreach ($recorddetails as $i => $recorddetail) {
			$this->decodeRecordDetail($entities[$i], $recorddetail, $xpath);
		}
		
		return $entities;
	}	
	
	
	/**
	 * @param Scalr_Service_ZohoCrm_Entity $entity
	 * @return Scalr_Service_ZohoCrm_Entity
	 */
	function update ($entity) {
		$request = $this->newRequest(); 
		
		$row = $request->createElement("row");
		$row->setAttribute("no", "1");
		$entity->encode($row, $request);
		$request->documentElement->appendChild($row);
		
		$response = $this->request(array(
			"method" => self::METHOD_UPDATE,
			"xmlData" => $request,
			"queryParams" => array("id" => $entity->id)
		));
		
		$xpath = new DOMXPath($response);
		$recorddetails = $xpath->query("//response/result/recorddetail");
		if ($recorddetails->length) {
			$this->decodeRecordDetail($entity, $recorddetails->item(0), $xpath);
		} else {
			throw new Scalr_Service_ZohoCrm_Exception("Cannot find 'recorddetail' node in response xml");
		}
		 
		return $entity;
	}
	
	/**
	 * @param Scalr_Service_ZohoCrm_Entity|number $id
	 * @return void
	 */
	function delete ($id) {
		if (is_object($id)) {
			$id = $id->id;
		}
		
		try {
			$this->request(array(
				"method" => self::METHOD_DELETE,
				"queryParams" => array("id" => $id)
			));
			return true;
		} catch (Scalr_Service_ZohoCrm_Exception $e) {
			if ($e->getCode() == 4832 && preg_match("/deleted successfully/", $e->getMessage())) {
				return true;
			}
			throw $e;
		}
	}

	function search ($condition, $columns=null) {
		$response = $this->request(array(
			"method" => self::METHOD_SEARCH,
			"queryParams" => array(
				"selectColumns" => $columns ? $this->moduleName . '(' . join(',', $columns) . ')' : 'All',
				"searchCondition" => '('.join('|', $condition).')',
				"newFormat" => 1
			)
		));
		
		$xpath = new DOMXPath($response);
		$rows = $xpath->query("//response/result/{$this->moduleName}/row");
		$ret = array();
		foreach ($rows as $row) {
			$entity = $this->newEntity();
			$entity->decode($row);
			$ret[] = $entity;
		}

		return $ret;
	}
}