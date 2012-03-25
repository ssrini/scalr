<?php

abstract class Scalr_Service_Cloud_Cloudstack_Connection {
    public $apiKey;
    public $secretKey;
    public $endpoint; // Does not ends with a "/"
	
    protected $zonesCache;
    
	public function __construct($endpoint, $apiKey, $secretKey) {
	    // API endpoint
	    if (empty($endpoint)) {
	        throw new Scalr_Service_Cloud_Cloudstack_Exception(ENDPOINT_EMPTY_MSG, ENDPOINT_EMPTY);
	    }
	    
	    if (!preg_match("|^http(s)?://.*$|", $endpoint)) {
	        throw new Scalr_Service_Cloud_Cloudstack_Exception(sprintf(ENDPOINT_NOT_URL_MSG, $endpoint), ENDPOINT_NOT_URL);
	    }
	    
	    // $endpoint does not ends with a "/"
	    $this->endpoint = substr($endpoint, -1) == "/" ? substr($endpoint, 0, -1) : $endpoint;
	    
	    // API key
	    if (empty($apiKey)) {
	        throw new Scalr_Service_Cloud_Cloudstack_Exception(APIKEY_EMPTY_MSG, APIKEY_EMPTY);
	    }
		$this->apiKey = $apiKey;
		
		// API secret
		if (empty($secretKey)) {
		    throw new Scalr_Service_Cloud_Cloudstack_Exception(SECRETKEY_EMPTY_MSG, SECRETKEY_EMPTY);
		}
		$this->secretKey = $secretKey;
	}
	
    public function getSignature($queryString) {
        if (empty($queryString)) {
            throw new Scalr_Service_Cloud_Cloudstack_Exception(STRTOSIGN_EMPTY_MSG, STRTOSIGN_EMPTY);
        }
        
        $hash = @hash_hmac("SHA1", $queryString, $this->secretKey, true);
        $base64encoded = base64_encode($hash);
        return urlencode($base64encoded);
    }

    public function request($command, $args = array(), $responseCmd = null) {
        if (empty($command)) {
            throw new Scalr_Service_Cloud_Cloudstack_Exception(NO_COMMAND_MSG, NO_COMMAND);
        }
        
        if (!is_array($args)) {
            throw new Scalr_Service_Cloud_Cloudstack_Exception(sprintf(WRONG_REQUEST_ARGS_MSG, $args), WRONG_REQUEST_ARGS);
        }
        
        foreach ($args as $key => $value) {
            if ($value == "") {
                unset($args[$key]);
            }
            
            if ($key == 'zoneid' && $value != '') {
            	if (!$this->zonesCache) {
            		foreach ($this->listZones() as $zone) {
            			$this->zonesCache[$zone->name] = $zone->id; 	
            		}
            	}
            	
            	if ($this->zonesCache[$value])
            		$args[$key] = $this->zonesCache[$value];
            	else
            		throw new Scalr_Service_Cloud_Cloudstack_Exception("Availability zone '{$value}' no longer supported");
            }
        }
        
        // Building the query
        $args['apikey'] = $this->apiKey;
        $args['command'] = $command;
        $args['response'] = "json";
        ksort($args);
        $query = http_build_query($args);
        $query = str_replace("+", "%20", $query);
        $query .= "&signature=" . $this->getSignature(strtolower($query));
    
       // var_dump($query);
        
        $httpRequest = new HttpRequest();
        $httpRequest->setMethod(HTTP_METH_POST);
        $url = $this->endpoint . "?" . $query;

        $httpRequest->setUrl($url);
    
        $httpRequest->send();
        
        $code =$httpRequest->getResponseCode();
        
        $data = $httpRequest->getResponseData();
        if (empty($data)) {
            throw new Scalr_Service_Cloud_Cloudstack_Exception(NO_DATA_RECEIVED_MSG, NO_DATA_RECEIVED);
        }
        //echo $data['body'] . "\n";
        $result = @json_decode($data['body']);
        
        if (empty($result)) {
            throw new Scalr_Service_Cloud_Cloudstack_Exception(NO_VALID_JSON_RECEIVED_MSG, NO_VALID_JSON_RECEIVED);
        }
        
        if (!$responseCmd)
        	$responseCmd = strtolower($command);
        
        $propertyResponse = "{$responseCmd}response";
        
        if (!property_exists($result, $propertyResponse)) {
            if (property_exists($result, "errorresponse") && property_exists($result->errorresponse, "errortext")) {
                throw new Scalr_Service_Cloud_Cloudstack_Exception($result->errorresponse->errortext);
            } else {
                throw new Scalr_Service_Cloud_Cloudstack_Exception(sprintf("Unable to parse the response. Got code %d and message: %s", $code, $data['body']));
            }
        }
        
        $response = $result->{$propertyResponse};
        
        if ($code > 400) {
        	throw new Exception("Request to cloudstack failed. {$response->errortext} ({$response->errorcode}) (".serialize($args).")");
        }
        
        // list handling : most of lists are on the same pattern as listVirtualMachines :
        // { "listvirtualmachinesresponse" : { "virtualmachine" : [ ... ] } }
        preg_match('/list(\w+)s/', strtolower($command), $listMatches);
        if (!empty($listMatches)) {
            $objectName = $listMatches[1];
            if (property_exists($response, $objectName)) {
                $resultArray = $response->{$objectName};
                if (is_array($resultArray)) {
                    return $resultArray;
                }
            } else {
                // sometimes, the 's' is kept, as in :
                // { "listasyncjobsresponse" : { "asyncjobs" : [ ... ] } }
                $objectName = $listMatches[1] . "s";
                if (property_exists($response, $objectName)) {
                    $resultArray = $response->{$objectName};
                    if (is_array($resultArray)) {
                        return $resultArray;
                    }
                }
            }
        }
        
        return $response;
    }
}