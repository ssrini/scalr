<?php
	
	class DistributionConfig
	{
		public $Origin;
		public $CallerReference;
		public $CNAME;
		public $Comment;
		public $Enabled;
				
		public function __construct()
		{
			$this->CallerReference = round(microtime(true)*10);
		}
		
		public function Serialize()
		{
			return '
			<?xml version="1.0" encoding="UTF-8"?>
			<DistributionConfig xmlns="http://cloudfront.amazonaws.com/doc/'.AmazonCloudFront::API_VERSION.'/">
			   <Origin>'.$this->Origin.'</Origin>
			   <CallerReference>'.$this->CallerReference.'</CallerReference>
			   <CNAME>'.$this->CNAME.'</CNAME>
			   <Comment>'.$this->Comment.'</Comment>
			   <Enabled>'.(($this->Enabled == true) ? "true" : "false").'</Enabled>
			</DistributionConfig>';
		}
	}

	class AmazonCloudFront
	{
		const API_VERSION 	= "2008-06-30";
		const HASH_ALGO 	= 'SHA1';
		const USER_AGENT 	= 'Libwebta AWS Client (http://webta.net)';
	    
		private $AWSAccessKeyId = NULL;
		private $AWSAccessKey = NULL;
		private $LastResponseHeaders = array();
		
		public function __construct($AWSAccessKeyId, $AWSAccessKey)
		{
			$this->AWSAccessKeyId = $AWSAccessKeyId;
			$this->AWSAccessKey = $AWSAccessKey;
			
			if (!function_exists("hash_hmac"))
                throw new Exception("hash_hmac() function not found. Please install HASH Pecl extension.", E_ERROR);
		}
		
		private function GetRESTSignature($date)
		{
			return base64_encode(@hash_hmac(AmazonCloudFront::HASH_ALGO, $date, $this->AWSAccessKey, 1));
		}
		
		/**
		 * Return GMT timestamp for Amazon AWS S3 Requests
		 *
		 * @return unknown
		 */
		private function GetTimestamp()
		{
		    $tz = @date_default_timezone_get();
			date_default_timezone_set("GMT");
		    $dt = date("r");
		    date_default_timezone_set($tz);
		    return $dt;
		}
		
		private function Request($method, $uri, $request_body, $query_args, $headers = array())
		{
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "LibWebta AWS Client (http://webta.net)"
			                                    )
			                              );
						
			$timestamp = $this->GetTimestamp();
			
			$signature = $this->GetRESTSignature($timestamp);
			
			$HttpRequest->setUrl("https://cloudfront.amazonaws.com/".self::API_VERSION.$uri);
			
		    $HttpRequest->setMethod($method);
		    
		    if ($query_args)
		    	$HttpRequest->addQueryData($query_args);
		    	
		    if ($request_body)
		    {
		    	if ($method == constant("HTTP_METH_POST"))
		    		$HttpRequest->setRawPostData(trim($request_body));
		    	else
		    		$HttpRequest->setPutData(trim($request_body));
		    		
		    	$headers["Content-type"] = "text/xml";
		    }
		    	
		    $headers["Date"] = $timestamp;
            $headers["Authorization"] = "AWS {$this->AWSAccessKeyId}:{$signature}";
                        
            $HttpRequest->addHeaders($headers);
			try 
            {
                $HttpRequest->send();
                //$info = $HttpRequest->getResponseInfo();
                $data = $HttpRequest->getResponseData();
                $this->LastResponseHeaders = $data['headers'];
                
                return $data['body'];
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
		
		public function CreateDistribution(DistributionConfig $DistributionConfig)
		{			
			$result = $this->Request(constant("HTTP_METH_POST"), "/distribution", $DistributionConfig->Serialize());
			
			$response = simplexml_load_string($result);
			if (!$response->Error)
				return array('ID' => $response->Id, 'DomainName' => $response->DomainName);
			else
				throw new Exception($response->Error->Message);
		}
		
		public function ListDistributions($Marker = "", $MaxItems = 100)
		{
			$result = $this->Request(constant("HTTP_METH_GET"), "/distribution", "", array("Marker" => $Marker, "MaxItems" => $MaxItems));
			$response = simplexml_load_string($result);
			if (!$response->Error)
			{
				if (!is_array($response->DistributionSummary))
					$response->DistributionSummary = array($response->DistributionSummary);
					
				$retval = array();
				foreach ($response->DistributionSummary as $distr)
				{
					$retval[str_replace(".s3.amazonaws.com", "", (string)$distr->Origin)] = array(
						'ID'		=> (string)$distr->Id,
						'Status'	=> (string)$distr->Status,
						'LastModifiedTime' => (string)$distr->LastModifiedTime,
						'DomainName'	=> (string)$distr->DomainName,
						'Origin'		=> (string)$distr->Origin,
						'CNAME'			=> (string)$distr->CNAME,
						'Comment'		=> (string)$distr->Comment,
						'Enabled'		=> (string)$distr->Enabled
					);
				}
				
				return $retval;
			}
			else
				throw new Exception($response->Error->Message);
		}
		
		public function GetDistributionInfo($distributionID)
		{
			$result = $this->Request(constant("HTTP_METH_GET"), "/distribution/{$distributionID}", "");
			$response = simplexml_load_string($result);
			if (!$response->Error)
			{
				$retval = array(
					'ID'		=> (string)$response->Id,
					'Status'	=> (string)$response->Status,
					'LastModifiedTime' => (string)$response->LastModifiedTime,
					'DomainName'	=> (string)$response->DomainName,
					'Origin'		=> (string)$response->DistributionConfig->Origin,
					'CNAME'			=> (string)$response->DistributionConfig->CNAME,
					'Comment'		=> (string)$response->DistributionConfig->Comment,
					'Enabled'		=> (string)$response->DistributionConfig->Enabled
				);
				
				return $retval;
			}
			else
				throw new Exception($response->Error->Message);
		}
		
		public function GetDistributionConfig($distributionID)
		{
			$result = $this->Request(constant("HTTP_METH_GET"), "/distribution/{$distributionID}/config", "");
			$response = simplexml_load_string($result);
			if (!$response->Error)
			{
				$retval = array(
					'Origin'		=> (string)$response->Origin,
					'CNAME'			=> (string)$response->CNAME,
					'Comment'		=> (string)$response->Comment,
					'Enabled'		=> (string)$response->Enabled,
					'Etag'			=> $this->LastResponseHeaders['Etag'],
					'CallerReference'	=> (string)$response->CallerReference
				);
				
				return $retval;
			}
			else
				throw new Exception($response->Error->Message);
		}
		
		public function SetDistributionConfig($distributionID, DistributionConfig $DistributionConfig, $E_TAG)
		{
			$result = $this->Request(constant("HTTP_METH_PUT"), "/distribution/{$distributionID}/config", $DistributionConfig->Serialize(), false, array("If-Match" => $E_TAG));
			$response = simplexml_load_string($result);			
			if (!$response->Error)
				return $this->LastResponseHeaders['Etag'];
			else
				throw new Exception($response->Error->Message);
		}
		
		public function DeleteDistribution($distributionID)
		{
			$info = $this->GetDistributionConfig($distributionID);
			
			if ($info['Enabled'] == 'true')
			{
				$DistributionConfig = new DistributionConfig();
				$DistributionConfig->CallerReference = $info['CallerReference'];
				$DistributionConfig->CNAME = $info['CNAME'];
				$DistributionConfig->Comment = $info['Comment'];
				$DistributionConfig->Enabled = false;
				$DistributionConfig->Origin = $info['Origin'];
				
				$E_TAG = $this->SetDistributionConfig($distributionID, $DistributionConfig, $info['Etag']);
			}
			else
				$E_TAG = $info['Etag'];
				
			$result = $this->Request(constant("HTTP_METH_DELETE"), "/distribution/{$distributionID}", "", false, array("If-Match" => $E_TAG));
			
			$response = simplexml_load_string($result);
			if (!$response->Error)
			{
				return true;
			}
			else
				throw new Exception($response->Error->Message);
		}
	}
?>