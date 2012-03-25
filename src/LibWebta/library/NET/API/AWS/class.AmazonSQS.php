<?php

	class AmazonSQS
	{
		const API_VERSION 	= "2009-02-01";
		const HASH_ALGO 	= 'SHA1';
		const USER_AGENT 	= 'Libwebta AWS Client (http://webta.net)';
	    
		private $AWSAccessKeyId = NULL;
		private $AWSAccessKey = NULL;
		private $LastResponseHeaders = array();
		private $Region = 'us-east-1';
		private static $Instance;
		
		public static function GetInstance($AWSAccessKeyId, $AWSAccessKey)
		{
			 self::$Instance = new AmazonSQS($AWSAccessKeyId, $AWSAccessKey);
			 return self::$Instance;
		}
		
		public function __construct($AWSAccessKeyId, $AWSAccessKey)
		{
			$this->AWSAccessKeyId = $AWSAccessKeyId;
			$this->AWSAccessKey = $AWSAccessKey;
			
			if (!function_exists("hash_hmac"))
                throw new Exception("hash_hmac() function not found. Please install HASH Pecl extension.", E_ERROR);
		}
		
		private function GetRESTSignature($params)
		{
			return base64_encode(@hash_hmac(AmazonCloudFront::HASH_ALGO, implode("\n", $params), $this->AWSAccessKey, 1));
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
		    $dt = date("c", time()+3600);
		    date_default_timezone_set($tz);
		    return $dt;
		}
		
		public function SetRegion($region)
		{ // only US region is available now
			if (in_array($region, array('sa-east-1', 'us-east-1', 'eu-west-1', 'us-west-1', 'us-west-2', 'ap-southeast-1', 'ap-northeast-1')))
				$this->Region = $region;	
		}
		
		private function Request($method, $uri, $args)
		{
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "LibWebta AWS Client (http://webta.net)"
			                                    )
			                              );
						
			$timestamp = $this->GetTimestamp();
			$URL = "queue.amazonaws.com";
			if ($this->Region != 'us-east-1')
				$URL = "{$this->Region}.queue.amazonaws.com";
			//EU URL: eu-west-1.queue.amazonaws.com
			
			$args['Version'] = self::API_VERSION;
			$args['SignatureVersion'] = 2;
			$args['SignatureMethod'] = "HmacSHA1";
			$args['Expires'] = $timestamp;
			$args['AWSAccessKeyId'] = $this->AWSAccessKeyId;

			ksort($args);
			
			foreach ($args as $k=>$v)
				$CanonicalizedQueryString .= "&{$k}=".urlencode($v);
			$CanonicalizedQueryString = trim($CanonicalizedQueryString, "&");
			
			
			$args['Signature'] = $this->GetRESTSignature(array($method, $URL, $uri, $CanonicalizedQueryString));
			
			$HttpRequest->setUrl("https://{$URL}{$uri}");
			
		    $HttpRequest->setMethod(constant("HTTP_METH_{$method}"));
		    
		    if ($args)
		    	$HttpRequest->addQueryData($args);

			try 
            {
                $HttpRequest->send();
                //$info = $HttpRequest->getResponseInfo();
                $data = $HttpRequest->getResponseData();
                
                $this->LastResponseHeaders = $data['headers'];
                
                $response = simplexml_load_string($data['body']);               
                if ($response->Error)
                	throw new Exception($response->Error->Message);
                else
                	return $response;
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
		
		/**
		 * The ListQueues action returns a list of your queues. 
		 * @param string $queue_name_prefix
		 * @return array
		 */
		public function ListQueues($queue_name_prefix = '')
		{
			$response = $this->Request("GET", "/", array("Action" => "ListQueues", "QueueNamePrefix" => $queue_name_prefix));
			
			$retval = (array)$response->ListQueuesResult;
			
			if (!is_array($retval['QueueUrl']))
				$list = array($retval['QueueUrl']);
			else
				$list = $retval['QueueUrl'];
				
			foreach ($list as &$r)
				$r = basename($r);
			
			return $list;
		}
		
		/**
		 * The GetQueueAttributes action gets one or all attributes of a queue.
		 * @param string $queue_name
		 * @param string $attribute
		 * @return array
		 */
		public function GetQueueAttributes($queue_name, $attribute='All')
		{
			$response = $this->Request("GET", "/{$queue_name}", array("Action" => "GetQueueAttributes", "AttributeName" => $attribute));
			$list = (array)$response->GetQueueAttributesResult;
			
			if (!is_array($list['Attribute']))
				$list = array($list['Attribute']);
			else
				$list = $list['Attribute'];
		
			$retval = array();
				
			foreach ($list as $l)
				$retval[(string)$l->Name] = (string)$l->Value;
			
			return $retval;
		}
		
		/**
		 * The SendMessage action delivers a message to the specified queue. The maximum allowed message size is 8 KB.
		 * @param $queue_name
		 * @param $message
		 * @return string $messageID
		 */
		public function SendMessage($queue_name, $message)
		{
			$response = $this->Request("GET", "/{$queue_name}", array("Action" => "SendMessage", "MessageBody" => base64_encode($message)));
			return $response->SendMessageResult->MessageId;
		}
		
		/**
		 * The DeleteMessage action deletes the specified message from the specified queue.
		 * @param $receipt_handle
		 * @return bool
		 */
		public function DeleteMessage($receipt_handle)
		{
			$response = $this->Request("GET", "/{$queue_name}", array("Action" => "DeleteMessage", "ReceiptHandle" => $receipt_handle));
			return true;
		}
		
		/**
		 * The CreateQueue action creates a new queue.
		 * @param $queue_name
		 * @param $visibility_timeout
		 * @return string $QueueUrl
		 */
		public function CreateQueue($queue_name, $visibility_timeout = 30)
		{
			$response = $this->Request("GET", "", array("Action" => "CreateQueue", "QueueName" => $queue_name, "DefaultVisibilityTimeout" => $visibility_timeout));
			return $response->CreateQueueResult->QueueUrl;
		}
		
		/**
		 * The ReceiveMessage action retrieves one or more messages from the specified queue. 
		 * @param $queue_name
		 * @param $max_number_of_messages
		 * @param $visibility_timeout
		 * @return array
		 */
		public function ReceiveMessage($queue_name, $max_number_of_messages = 1, $visibility_timeout = 30)
		{
			$response = $this->Request("GET", "/$queue_name", array("Action" => "ReceiveMessage", "MaxNumberOfMessages" => $max_number_of_messages, "DefaultVisibilityTimeout" => $visibility_timeout));
			
			if (!is_array($response->ReceiveMessageResult->Message))
				$retval = array($response->ReceiveMessageResult->Message);
			else
				$retval = $response->ReceiveMessageResult->Message;
				
			foreach ($retval as &$r)
			{
				$r = (array)$r;
				$r['Body'] = base64_decode($r['Body']);
			}
				
			return $retval;
		}
	}
?>