<?php
  class Scalr_Service_Cloud_Rackspace_Connection
  {       
  		protected	$xAuthUser;
		protected	$xAuthKey;
		private		$LastResponseHeaders 	= array();
		private		$xSessionUrl			= null;		// session id which returned as X-Server-Management-Url  from header
		private		$httpRequest			= null;
		private		$xAuthToken				= null;

		const		ACCEPT_JSON				= "application/json";
		const		CONTENT_TYPE_JSON		= "application/json";
		const		API_VERSION				= "v1.0";
		const		URL						= "auth.api.rackspacecloud.com";
		
		protected 	$apiAuthURL = '';
		
		protected function __construct($xAuthUser, $xAuthKey, $cloudLocation)
		{
			$this->xAuthUser = $xAuthUser;
			$this->xAuthKey  = $xAuthKey;
			$this->httpRequest = new HttpRequest();
			
			switch($cloudLocation)
			{
				case 'rs-ORD1':
					$this->apiAuthURL = 'auth.api.rackspacecloud.com';
					break;
					
				case 'rs-LONx':
					$this->apiAuthURL = 'lon.auth.api.rackspacecloud.com';
					break;
			}
			
		}
		
		/**
		* Authorizes if the user didn't do it before
		* before first API call you have to authorizes yourself and 
		* recieve your unique X-Server-Management-Url which will be added
		* to the end of your next URLs
		* @name auth
		* @param mixed $url
		* @return  void
		*/
		private function auth()
		{
			try
			{
				$this->setRequestOptions("https://{$this->apiAuthURL}/".self::API_VERSION, "GET");
				$response = $this->sendRequest();
				$this->xAuthToken	= $response['headers']['X-Auth-Token'];
				$this->xSessionUrl	= $response['headers']['X-Server-Management-Url'];
			}
			catch(Exception $e)
			{
				throw $e;
			}
		}
		
		
		/**
		* Makes request itself to the set or default url
		* 
		* @name  sendRequest
		* @param mixed $url
		* @return array  $data
		*/
		private function sendRequest()
		{   			 
			try 
           {
                $this->httpRequest->send();
                $info = $this->httpRequest->getResponseInfo();

                $data = $this->httpRequest->getResponseData();
                $this->LastResponseHeaders = $data['headers'];
               
				if($info['response_code'] >= 400)
				{
					$errMsg = json_decode($data['body']);

					$errMsg = @array_values(@get_object_vars($errMsg));
					$errMsg = $errMsg[0];
					
					$code = ($errMsg->code) ? $errMsg->code : 0;
					$msg = ($errMsg->details) ? $errMsg->details : trim($data['body']);
						
					
					throw new Exception(sprintf('Request to Rackspace failed (Code: %s): %s',
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
		protected function request($method, $uri = "", $args = null, $url = null)
		{
			try 
            {
            	if(!$this->xSessionUrl) 
            	{ 
					// authorization request
					$this->auth();
				}
 				
				if(!$url)
					$url = $this->xSessionUrl."/{$uri}";
					
				$this->setRequestOptions($url, $method, $args);				
				$response = $this->sendRequest();  
			
            }
            catch (Exception $e)
            { 
               if($e->getCode() == 401)
					$this->xSessionUrl = null;

			   throw $e;
            }
            
            return json_decode($response['body']);

		}
		
		
		/**
 		* Set request headers and options
 		* 
 		* @name  buildHttpRequestObject
 		* @param mixed $method
 		* @param mixed $args
 		* @return HttpRequest
 		*/
		private function setRequestOptions($url, $method, $args = null)
		{

			$this->httpRequest->setUrl($url); 
			
			$this->httpRequest->setOptions(array(
				"redirect" 		=> 10, 
				"useragent" 	=> "Scalr",
				'timeout'		=> 30,
				'connecttimeout'=> 10
			));
  
		    $this->httpRequest->setMethod(constant("HTTP_METH_{$method}"));
		    $this->httpRequest->setHeaders(array("X-Auth-User" => $this->xAuthUser,
		    	"X-Auth-Key"	=> $this->xAuthKey,
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
			 if($method !== "GET") 
				$this->httpRequest->setQueryData("&t={$time}");
			 
		} 
  }
?>
