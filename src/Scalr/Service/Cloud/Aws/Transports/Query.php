<?php
	class Scalr_Service_Cloud_Aws_Transports_Query
	{
		protected $serviceUrl;
		protected $accessKey;
		protected $accessKeyId;
		protected $apiVersion;
		protected $timestampFormat = "Y-m-d\TH:i:s";
		protected $signatureAlgo = 'SHA256';
		protected $serviceProtocol = 'https://';
		protected $serviceUriPrefix = '';
		
		protected function getSignature($params)
		{
			return base64_encode(@hash_hmac($this->signatureAlgo, implode("\n", $params), $this->accessKey, 1));
		}
		
		protected function getTimestamp()
		{
			$tz = @date_default_timezone_get();
			@date_default_timezone_set("GMT");
		    $dt = date($this->timestampFormat, time());
		    @date_default_timezone_set($tz);
		    return $dt;
		}
		
		protected function request($method, $uri, $args)
		{
			$parsedUrl = parse_url($this->ec2Url);
			$uri = "{$parsedUrl['path']}{$uri}";
			
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(
				//"redirect" => 10, 
			    "useragent" => "Scalr (https://scalr.net)"
			));
						
			$args['Version'] = $this->apiVersion;
			$args['SignatureVersion'] = 2;
			$args['SignatureMethod'] = "HmacSHA256";
			$args['Timestamp'] = $this->getTimestamp();
			$args['AWSAccessKeyId'] = $this->accessKeyId;

			ksort($args);
			
			foreach ($args as $k=>$v)
				$CanonicalizedQueryString .= "&{$k}=".rawurlencode($v);
				
			$CanonicalizedQueryString = trim($CanonicalizedQueryString, "&");
			
			$url = ($parsedUrl['port']) ? "{$parsedUrl['host']}:{$parsedUrl['port']}" : "{$parsedUrl['host']}";
			
			$args['Signature'] = $this->getSignature(array($method, $url, $uri, $CanonicalizedQueryString));
			
			$HttpRequest->setUrl("{$parsedUrl['scheme']}://{$url}{$uri}");
			
		    $HttpRequest->setMethod(constant("HTTP_METH_{$method}"));
		  
		    if ($args)
		    	if ($method == 'POST')
		    	{
		    		$HttpRequest->setPostFields($args);
		    		$HttpRequest->setHeaders(array('Content-Type' => 'application/x-www-form-urlencoded'));
		    	}
		    	else
		    		$HttpRequest->addQueryData($args);
		    	
			try 
            {
                $HttpRequest->send();

                $data = $HttpRequest->getResponseData();
                if ($HttpRequest->getResponseCode() == 200)
                {
                	$response = simplexml_load_string($data['body']);               
	                if ($response->Errors)
	                	throw new Exception($response->Errors->Error->Message);
	                else
	                	return $response;
                }
                else
                {
                	$response = @simplexml_load_string($data['body']);
                	if ($response)
                		throw new Exception($response->Error->Message);
                	
                	throw new Exception(trim($data['body']));
                }
                
                $this->LastResponseHeaders = $data['headers'];
            }
            catch (Exception $e)
            {
            	if ($e->innerException)
            		$message = $e->innerException->getMessage();
            	else
            		$message = $e->getMessage();  
            		
            	throw new Exception($message);
            }
		}
	}