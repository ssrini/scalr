<?php
  class Scalr_Service_Cloud_Openstack_Connection
  {       
  		protected	$authUser;
		protected	$authKey;
		protected	$authProjectName;
		private		$LastResponseHeaders 	= array();
		private		$xSessionUrl			= null;		// session id which returned as X-Server-Management-Url  from header
		private		$httpRequest			= null;
		private		$xAuthToken				= null;

		const		ACCEPT_JSON				= "application/json";
		const		CONTENT_TYPE_JSON		= "application/json";
		
		protected 	$apiAuthURL = '';
		
		protected function __construct($authUser, $authKey, $apiUrl, $project = "")
		{
			$this->authUser = $authUser;
			$this->authProjectName = $project;
			$this->authKey  = $authKey;
			$this->apiAuthURL = $apiUrl;
			$this->httpRequest = new HttpRequest();			
		}
		
		/**
		* Makes request itself to the set or default url
		* 
		* @name  sendRequest
		* @param mixed $url
		* @return array  $data
		*/
		private function sendRequest($uri, $method = "GET", $args = array(), $url)
		{   			 
			try 
			{
				$this->httpRequest->setUrl("{$url}/{$uri}"); 
				
				$this->httpRequest->setOptions(array(
					"redirect" 		=> 10, 
					"useragent" 	=> "Scalr (http://scalr.net)",
					'timeout'		=> 15,
					'connecttimeout'=> 5
				));
	  
			    $this->httpRequest->setMethod(constant("HTTP_METH_{$method}"));
			    $this->httpRequest->setHeaders(array(
			    	"X-Auth-User" => $this->authUser,
			    	"X-Auth-Key"	=> $this->authKey,
			    	"X-Auth-Project-Id" => $this->authProjectName,
			    	"Accept"		=> self::ACCEPT_JSON,
			    	"Content-Type"	=> self::CONTENT_TYPE_JSON,
			    	"X-Auth-Token"	=> $this->xAuthToken
			    )); 
			     
				$time = time();
				switch($method)
				{
					case "GET":
						$args['t'] = $time;			
						ksort($args);
						foreach ($args as $k => $v)
							$CanonicalizedQueryString .= "&{$k}=".urlencode($v);
								
						$CanonicalizedQueryString = trim($CanonicalizedQueryString, "&");
						$this->httpRequest->setQueryData($CanonicalizedQueryString);
					break;
		
					case "PUT": 					 
						if($args)
							$this->httpRequest->setPutData(json_encode($args));
					break;
		
					case "POST": 				        				 		
						if($args)
							$this->httpRequest->setRawPostData(json_encode($args));
					break;
				 }
				 
				 // unique time to disable caching			 
				 //if($method !== "GET") 
				$this->httpRequest->setQueryData("&t={$time}");
            	
            	$this->httpRequest->send();
                $info = $this->httpRequest->getResponseInfo();
                
                $data = $this->httpRequest->getResponseData();
                $this->LastResponseHeaders = $data['headers'];
               
				if($info['response_code'] >= 400)
				{
					$errMsg = json_decode($data['body']);

					$errMsg = array_values(get_object_vars($errMsg));
					$errMsg = $errMsg[0];
					
					$code = ($errMsg->code) ? $errMsg->code : 0;
					$msg = ($errMsg->details) ? $errMsg->details : trim($data['body']);
						
					
					throw new Exception(sprintf('Request to Openstack failed (Code: %s): %s',
						$code,
						$msg
					));
				} 
            }
            catch (Exception $e)
            {
            	if ($e->innerException)
            		$message = $e->innerException->getMessage();
            	else
            		$message = $e->getMessage();
            		
            	throw new Exception($message);
            }
            
            return $data;
		}
		
		
		/**
		* makes a request to the cloud server
		* 
		* @name request
		* @param mixed $method
		* @param mixed $uri
		* @param mixed $args
		* @param mixed $url
		* @return	void
		*/		
		protected function request($uri, $method = "GET", $args = array())
		{
			try 
            {
            	if(!$this->xSessionUrl) 
            	{ 
					// authorization request
            		try
					{
						$response = $this->sendRequest("{$this->apiVersion}", "GET", array(), $this->apiAuthURL);
						$this->xAuthToken	= $response['headers']['X-Auth-Token'];
						
						$r = parse_url($response['headers']['X-Server-Management-Url']);
						
						$this->xSessionUrl	= "{$r['scheme']}://{$r['host']}";
						
						if ($r['port'])
							$this->xSessionUrl .= ":{$r['port']}";
						
						if ($r['path'])
							$this->xSessionUrl .= "{$r['path']}";
					}
					catch(Exception $e)
					{
						throw $e;
					}
				}
				
				$response = $this->sendRequest($uri, $method, $args, $this->xSessionUrl);  
			
            }
            catch (Exception $e)
            { 
               if($e->getCode() == 401)
					$this->xSessionUrl = null;

			   throw $e;
            }
            
            return json_decode($response['body']);

		}
  }
?>
