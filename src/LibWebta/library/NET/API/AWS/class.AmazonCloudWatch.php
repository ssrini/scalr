<?php

	class AmazonCloudWatch
	{
		const API_VERSION 	= "2010-08-01";
		const HASH_ALGO 	= 'SHA256';
		const USER_AGENT 	= 'Scalr AWS Client (http://scalr.net)';
	    
		private $AWSAccessKeyId = NULL;
		private $AWSAccessKey = NULL;
		private $LastResponseHeaders = array();
		private static $Instance;
		private $Region;
		
		/**
		 * 
		 * @param string $AWSAccessKeyId
		 * @param string $AWSAccessKey
		 * @return AmazonCloudWatch
		 */
		public static function GetInstance($AWSAccessKeyId, $AWSAccessKey, $Region)
		{
			 self::$Instance = new AmazonCloudWatch($AWSAccessKeyId, $AWSAccessKey, $Region);
			 return self::$Instance;
		}
		
		public function __construct($AWSAccessKeyId, $AWSAccessKey, $Region)
		{
			$this->AWSAccessKeyId = $AWSAccessKeyId;
			$this->AWSAccessKey = $AWSAccessKey;
			$this->Region = $Region;
			
			if (!function_exists("hash_hmac"))
                throw new Exception("hash_hmac() function not found. Please install HASH Pecl extension.", E_ERROR);
		}
		
		private function GetRESTSignature($params)
		{
			return base64_encode(@hash_hmac(AmazonCloudWatch::HASH_ALGO, implode("\n", $params), $this->AWSAccessKey, 1));
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
		
		private function Request($method, $uri, $args)
		{
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    
				"redirect" 	=> 10, 
			    "useragent" => AmazonCloudWatch::USER_AGENT
			));
						
			$timestamp = $this->GetTimestamp();
			$URL = "monitoring.{$this->Region}.amazonaws.com";
			
			$args['Version'] = self::API_VERSION;
			$args['SignatureVersion'] = 2;
			$args['SignatureMethod'] = "HmacSHA256";
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
		 * 
		 * @param $MeasureName
		 * @param $StartTime
		 * @param $EndTime
		 * @param $Statistics (Minimum, Maximum, Sum and Average)
		 * @param $Unit (Seconds, Percent, Bytes, Bits, Count, Bytes, Bits/Second, Count/Second, and None)
		 * @param $Period
		 * @param $Namespace
		 * @param $Dimensions
		 * @return unknown_type
		 */
		public function GetMetricStatistics($MeasureName, $StartTime, $EndTime, array $Statistics, $Unit = null, $Period = 60, $Namespace = null, array $Dimensions = array())
		{
			$request = array(
				"Action" 		=> "GetMetricStatistics",
				"MetricName"	=> $MeasureName,
				"StartTime"		=> date("c", $StartTime),
				"EndTime"		=> date("c", $EndTime),
				"Period"		=> $Period
			);
			
			if ($Unit)
				$request["Unit"] = $Unit;
			
			if ($Namespace)
				$request["Namespace"] = $Namespace;
				
			foreach ($Statistics as $i => $s)
				$request['Statistics.member.'.($i+1)] = $s;
				
			
			$k = 0;
			foreach ($Dimensions as $i => $s)
			{
				$request['Dimensions.member.'.($k+1).'.Name'] = $i;
				$request['Dimensions.member.'.($k+1).'.Value'] = $s;
			}
			
			$response = $this->Request("GET", "/", $request);
			$res = (array)$response->GetMetricStatisticsResult->Datapoints;
			
			$dps = array();
			foreach ($res['member'] as $r)
			{
				$dps['unit'] = (string)$r->Unit;
				foreach ($Statistics as $i => $s)
				{						
					if ((string)$r->Unit == 'Bytes' || (string)$r->Unit == 'Bytes/Second')
						$dps[strtotime((string)$r->Timestamp)][$s] = round((string)$r->{$s}/1024, 2);
					else
						$dps[strtotime((string)$r->Timestamp)][$s] = (string)$r->{$s};
				}
			}
			
			return $dps;
		}
		
		/**
		 * The ListQueues action returns a list of your queues. 
		 * @param string $queue_name_prefix
		 * @return array
		 */
		public function ListMetrics($namespace = 'AWS/EC2', array $dimensions = array())
		{
			$request = array("Action" => "ListMetrics");
			
			if ($namespace)
				$request["Namespace"] = $namespace;
				
			
			$k = 0;
			foreach ($dimensions as $i => $s) {
				$request['Dimensions.member.'.($k+1).'.Name'] = $i;
				$request['Dimensions.member.'.($k+1).'.Value'] = $s;
			}
			
			$response = $this->Request("GET", "/", $request);
			
			$retval = $response->ListMetricsResult;
			$metrics = (array)$retval->Metrics;
			
			$list = array();
			foreach ($metrics['member'] as $item)
			{	
				if (!$list[(string)$item->Namespace])
					$list[(string)$item->Namespace] = array();
				
				if (!$list[(string)$item->Namespace][(string)$item->MetricName])
					$list[(string)$item->Namespace][(string)$item->MetricName] = array();
				
				$a = (array)$item->Dimensions->member;
				
				if (!empty($a))
				{
					if (!$list[(string)$item->Namespace][(string)$item->MetricName][$a['Name']])
						$list[(string)$item->Namespace][(string)$item->MetricName][$a['Name']] = array();
						
					$list[(string)$item->Namespace][(string)$item->MetricName][$a['Name']][] = $a['Value'];
				}
			}

			return $list;
		}
	}
?>