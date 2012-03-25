<?php
	class Scalr_Service_Cloud_Nimbula_Connection
	{
		protected $sessionCookie;
		
		protected function auth()
		{
			$this->request("/authenticate/", HTTP_METH_POST, array(
				'user'		=> $this->username,
				'password'	=> $this->password
			));
		}
		
		protected function request($uri, $method, $data)
		{
			$httpRequest = new HttpRequest();
			
			$httpRequest->setOptions(array(
			    "useragent" => "Scalr (https://scalr.net)"
			));
						
			$httpRequest->setUrl("{$this->apiUrl}{$uri}");
			
		    $httpRequest->setMethod($method);
		    
		    $httpRequest->resetCookies();
		    
			$httpRequest->addHeaders(array(
				'Cookie'	   => $this->sessionCookie,
				'Content-Type' => 'application/nimbula-v1+json'
		  	));
			  	
		    switch ($method) {
		    	case HTTP_METH_POST:
		    		$httpRequest->setRawPostData(json_encode($data));
		    		$httpRequest->addHeaders(array(
				  		'Content-Type' => 'application/nimbula-v1+json'
				  	));
		    		break;
		    }
		    
			try 
            {
            	$httpRequest->send();
            	
                $data = $httpRequest->getResponseData();
                $result = @json_decode($data['body']);
				if ($httpRequest->getResponseCode() > 204)
				{
					$message = $result->message;
					
					if ($message)
					{
						if ($message instanceof stdClass)
						{
							$r = (array)$message;
							$msg = '';
							foreach ($r as $k=>$v)
								$msg .= "{$k}: {$v} ";
								
							throw new Exception(trim($msg));
						}
						else 
							throw new Exception($message);
					}
						
					throw new Exception($data['body']);
				}
				
				$headers = $httpRequest->getResponseHeader('Set-Cookie');
				if ($headers) {
					if (!is_array($headers)) {
						if (stristr($headers, "nimbula"))
							$this->sessionCookie = $headers;
					}
					else {
						
					}
				}
					
                $this->LastResponseHeaders = $data['headers'];
                
                return $result;
            }
            catch (Exception $e)
            {
            	if ($e->innerException)
            		$message = $e->innerException->getMessage();
            	else
            		$message = $e->getMessage();  
            		
            	throw new Exception("Nimbula error: {$message}");
            }
		}
	}
