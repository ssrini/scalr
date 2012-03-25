<?php

class Scalr_Service_Zookeeper {
	
	const OPT_SEQUENCE = "sequence";
	
	protected $url;
	
	/**
	 * cURL handler 
	 * @var resource
	 */
	protected $ch;
	protected $curlOptions = array();
	
	protected $logger;
	
	// For failover 
	protected $preferredUrl;
	protected $connUrl;

	
	
	/**
	 * Zookeeper client constructor
	 * 
	 * @param $config array Configuration. See @key phpdocs
	 * @key string $url Connection URL (ex: http://localhost:9998/znodes/v1)
	 * @key string $preferredUrl Url to connect first (for failover url) 
	 * @return Scalr_Service_Zookeeper
	 */
	function __construct ($config) {
		foreach ($config as $k => $v) {
			if ("url" == $k) {
				$url = is_array($v) ? $v : explode(",", $v);
				$this->url = count($url) > 1 ? $url : $url[0];
			} else {
				$this->{$k} = $v;
			}
		}
		
		$this->logger = Logger::getLogger(__CLASS__);

		// Failover stuff
		if (is_array($this->url)) {
			$this->preferredUrl = $this->preferredUrl ? $this->preferredUrl : $this->url[0];
			$this->connUrl = $this->preferredUrl;
			$this->curlOptions[CURLOPT_CONNECTTIMEOUT] = 1; // 1 second 
		}
	}
	
	/**
	 * @param string $path
	 * @return bool
	 */
	function exists ($path) {
		list($httpCode, $response) = $this->request0("HEAD", $path);
		if (in_array($httpCode, array(200, 204, 404))) {
			return in_array($httpCode, array(200, 204));
		} else {
			throw new Scalr_Service_Zookeeper_Exception(sprintf("Unexpected response. code=%s, response=%s", 
					$httpCode, $response), $httpCode);
		}
	}
	
	/**
	 * @param string $path
	 * @return Scalr_Service_Zookeeper_StatData
	 */
	function get ($path) {
		return $this->request("GET", $path);
	}
	
	function getData ($path) {
		return base64_decode($this->get($path)->data64);
	}
	
	/**
	 * @param $path
	 * @param $data
	 * @param $options
	 * @return Scalr_Service_Zookeeper_StatData
	 */
	function set ($path, $data=null, $options=array()) {
		return $this->request("PUT", $path, $options, $data);
	}

	/**
	 * @param string $path
	 * @param array $options
	 * @return Scalr_Service_Zookeeper_PathData
	 */
	function create ($path, $data=null, $options=array()) {
		$pathinfo = pathinfo($path);
		if ($options[self::OPT_SEQUENCE]) {
			$options[self::OPT_SEQUENCE] = "true";
		}
		
		return $this->request("POST", $pathinfo["dirname"], 
				array_merge(array("op" => "create", "name" => $pathinfo["basename"]), $options), 
				$data);
	}
	
	/**
	 * @param $path
	 * @param $data
	 * @param $setFirst
	 * @return Scalr_Service_Zookeeper_PathData
	 */
	function setOrCreate ($path, $data=null, $setFirst=true) {
		if ($setFirst) {
			try {
				return $this->set($path, $data);
			} catch (Scalr_Service_Zookeeper_Exception $e) {
				if ($e->getCode() == Scalr_Service_Zookeeper_Exception::NOT_FOUND) {
					return $this->create($path, $data);
				} else {
					throw $e;
				}
			}
		} else {
			try {
				return $this->create($path, $data);
			} catch (Scalr_Service_Zookeeper_Exception $e) {
				if ($e->getCode() == Scalr_Service_Zookeeper_Exception::CONFLICT) {
					return $this->set($path, $data);
				} else {
					throw $e;
				}
			}
		}
	}

	function delete ($path) {
		$this->request("DELETE", $path);
	}
	
	/**
	 * @param string $path
	 * @return Scalr_Service_Zookeeper_ChildData
	 */
	function getChildren ($path) {
		return $this->request("GET", $path, "view=children");
	}
	
	/**
	 * 
	 * @param string $method one of GET, POST, PUT, DELETE
	 */
	protected function request0 ($method, $path, $query=null, $data=null) {
		
		// Set cURL transaction options
		$options = $this->curlOptions + array(
			CURLOPT_URL => $this->prepareUrl(is_array($this->url) ? $this->connUrl : $this->url, $path, $query),
			CURLOPT_CUSTOMREQUEST => $method,
			CURLOPT_HTTPHEADER => array("Accept: application/json", "Expect:"),
			CURLOPT_RETURNTRANSFER => 1,
			//CURLOPT_VERBOSE => 1
		);
		if ($method == "PUT" || $method == "POST") {
			$filename = tempnam(sys_get_temp_dir(), "zoo");
			file_put_contents($filename, $data);
 
			$options = $options + array(
				CURLOPT_UPLOAD => 1,
				CURLOPT_INFILE => fopen($filename, "r"),
				CURLOPT_INFILESIZE => filesize($filename)
			);
		} 
		
		$urlsStack = null;
		do {
			$loop = false;
			$error = $errno = null; 
			
			// Do request
			$this->ch = curl_init();		
			curl_setopt_array($this->ch, $options);
			
			$this->logger->debug(sprintf("Sending request: %s", "{$method} {$options[CURLOPT_URL]}"));
			$httpBody = curl_exec($this->ch);
			
			// Check errors
			$error = curl_error($this->ch);
			$errno = curl_errno($this->ch);
			
			// Couldn't connect ot host. Failover host switch
			if (($errno == CURLE_COULDNT_CONNECT || $errno == CURLE_COULDNT_RESOLVE_HOST) 
					&& is_array($this->url)) {
				$this->logger->warn(sprintf("Couldn't connect to zookeeper server '%s'. cURL error: %s", $options[CURLOPT_URL], $error));
						
				if ($urlsStack === null) {
					$urlsStack = array();
					$i = array_search($this->connUrl, $this->url);
					if ($i+1 < count($this->url)) {
						$urlsStack = array_slice($this->url, $i+1);
					}
					$urlsStack = array_merge($urlsStack, array_slice($this->url, 0, count($this->url)-count($urlsStack)-1));
				}
				
				if ($urlsStack) {
					$this->connUrl = array_shift($urlsStack);
					$options[CURLOPT_URL] = $this->prepareUrl($this->connUrl, $path, $query);
					$this->logger->warn(sprintf("Switch to another server '%s'", $this->connUrl));
					$loop = true;
				} else {
					$urlsStack = null;
					$error = "All servers are down";
				}
			}
			
		} while ($loop);
		
		// Post request		
		if ($method == "PUT" || $method == "POST") {
			unlink($filename);
		}
		
		
		// Process response
		if ($error) {
			curl_close($this->ch);
			throw new Scalr_Service_Zookeeper_Exception(sprintf("Request to zookeeper service failed. cURL error: %s", $error));
		} else {
			$httpCode = (int)curl_getinfo($this->ch, CURLINFO_HTTP_CODE);
			curl_close($this->ch);			
			
			if ($httpCode >= 200 && $httpCode < 500) {
				return array(
					$httpCode,	// HTTP status
					$httpCode != 204 ? json_decode($httpBody) : null	// unserialized JSON response
				);
			} else {
				throw new Scalr_Service_Zookeeper_Exception(sprintf("Zookeeper service failed with code: %d", $httpCode));
			} 
		}
	}
	
	protected function request ($method, $path, $query=null, $data=null) {
		list($httpCode, $response) = $this->request0($method, $path, $query, $data);
		
		if ($httpCode >= 400) {
			throw new Scalr_Service_Zookeeper_Exception($response->message, $httpCode);
		}
		return $response;
		
	}
	
	protected function prepareUrl ($base, $path=null, $query=null) {
		$url = $base . $path;
		if ($query) {
			$url .= "?" . (is_array($query) ? http_build_query($query) : $query);
		}
		return $url;
	}
	
	// Helper methods
	
	function deleteRecursive ($path) {
		try {
			$this->delete($path);
		} catch (Scalr_Service_Zookeeper_Exception $e) {
			if ($e->getCode() == Scalr_Service_Zookeeper_Exception::CONFLICT) {
				$this->logger->debug(sprintf("Cannot delete path '%s' because it's not empty. First, delete it's children", $path));
				$childData = $this->getChildren($path);
				foreach ((array)$childData->children as $childName) {
					$this->deleteRecursive("{$path}/{$childName}");
				}

				$this->delete($path);
			}
		}
	}
	
	function deleteChildren ($path) {
		$childData = $this->getChildren($path);
		foreach ((array)$childData->children as $childName) {
			$this->deleteRecursive("{$path}/{$childName}");
		}
	}
	
	// Getters & Setters
	function getConnectionUrl () {
		return $this->connUrl;
	}
}



// Zookeeper REST data types

/**
 * @property string $path
 * @property string $uri
 */
class Scalr_Service_Zookeeper_PathData {}

/**
 * @property string $child_uri_template
 * @property array $children
 */
class Scalr_Service_Zookeeper_ChildData extends Scalr_Service_Zookeeper_PathData {}

/**
 * @property string $data64
 * @property int $czxid
 * @property int $mzxid
 * @property int $ctime
 * @property int $mtime
 * @property int $version
 * @property int $cversion
 * @property int $aversion
 * @property int $ephemeralOwner
 * @property int $dataLength
 * @property int $numChildren
 * @property int $pzxid
 */
class Scalr_Service_Zookeeper_StatData extends Scalr_Service_Zookeeper_PathData {}