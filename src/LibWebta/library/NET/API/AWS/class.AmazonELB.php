<?php

	class ELBListenersList
	{
		private $Items;
		
		function __construct()
		{
			$this->Items = array();
		}
		
		function AddListener($protocol, $lb_port, $instance_port, $certificateId=null)
		{
			$o = new stdClass();
			$o->Protocol = $protocol;
			$o->LoadBalancerPort = $lb_port;
			$o->InstancePort = $instance_port;
			$o->SSLCertificateId = $certificateId;
			
			$this->Items[] = $o;
		}
		
		function GetListeners()
		{
			return $this->Items;
		}
	}
	
	class ELBHealthCheckType
	{
		/*
		 * The number of consecutive health probe successes required before moving the instance to the Healthy state. 
		 * The default is three and a valid value lies between two and ten.
		 */
		public $HealthyThreshold;
		
		/*
		 * The approximate interval (in seconds) between health checks of an individual instance.
		 * The default is 30 seconds and a valid interval must be between 5 seconds and 600 seconds. 
		 * Also, the interval value must be greater than the Timeout value
		 */
		public $Interval;
		
		/**
		 * The instance being checked. The protocol is either TCP or HTTP. The range of valid ports is one (1) through 65535.
		 * Notes: TCP is the default, specified as a TCP: port pair, for example "TCP:5000". 
		 * In this case a healthcheck simply attempts to open a TCP connection to the instance on the specified port. 
		 * Failure to connect within the configured timeout is considered unhealthy. 
		 * For HTTP, the situation is different. HTTP is specified as a HTTP:port;/;PathToPing; grouping, 
		 * for example "HTTP:80/weather/us/wa/seattle". In this case, a HTTP GET request is issued to the instance on the given port 
		 * and path. Any answer other than "200 OK" within the timeout period is considered unhealthy. 
		 * The total length of the HTTP ping target needs to be 1024 16-bit Unicode characters or less.
		 */
		public $Target;
		
		/*
		 * Amount of time (in seconds) during which no response means a failed health probe. 
		 * The default is five seconds and a valid value must be between 2 seconds and 60 seconds. 
		 * Also, the timeout value must be less than the Interval value.
		 */
		public $Timeout;
		
		/*
		 * The number of consecutive health probe failures that move the instance to the unhealthy state. 
		 * The default is 5 and a valid value lies between 2 and 10.
		 */
		public $UnhealthyThreshold;
		
		/**
		 * 
		 * Constructor
		 * @param $Target
		 * @param $HealthyThreshold
		 * @param $Interval
		 * @param $Timeout
		 * @param $UnhealthyThreshold
		 * @return void
		 */
		public function __construct($Target, $HealthyThreshold = 3, $Interval = 30, $Timeout = 5, $UnhealthyThreshold = 5)
		{
			$this->HealthyThreshold = $HealthyThreshold;
			$this->Interval = $Interval;
			$this->Target = $Target;
			$this->Timeout = $Timeout;
			$this->UnhealthyThreshold = $UnhealthyThreshold;
		}
	}

	class AmazonELB
	{
		const API_VERSION 	= "2011-08-15";
		const HASH_ALGO 	= 'SHA1';
		const USER_AGENT 	= 'Libwebta AWS Client (http://webta.net)';
	    
		private $AWSAccessKeyId = NULL;
		private $AWSAccessKey = NULL;
		private $Region = 'us-east-1';
		private $LastResponseHeaders = array();
		private static $Instance;
		
		/**
		 *
		 * @param $AWSAccessKeyId
		 * @param $AWSAccessKey
		 * @return AmazonELB
		 */
		public static function GetInstance($AWSAccessKeyId, $AWSAccessKey)
		{
			 self::$Instance = new AmazonELB($AWSAccessKeyId, $AWSAccessKey);
			 return self::$Instance;
		}
		
		public function __construct($AWSAccessKeyId, $AWSAccessKey)
		{
			$this->AWSAccessKeyId = $AWSAccessKeyId;
			$this->AWSAccessKey = $AWSAccessKey;
			
			if (!function_exists("hash_hmac"))
                throw new Exception("hash_hmac() function not found. Please install HASH Pecl extension.", E_ERROR);
		}
		
		public function SetRegion($region)
		{
			if (in_array($region, array('us-east-1', 'eu-west-1', 'us-west-1', 'us-west-2', 'ap-southeast-1', 'ap-northeast-1', 'sa-east-1')))
				$this->Region = $region;	
		}
		
		private function GetRESTSignature($params)
		{
			return base64_encode(@hash_hmac(AmazonELB::HASH_ALGO, implode("\n", $params), $this->AWSAccessKey, 1));
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
		
		private function Request($method, $uri, $args, $attempt = 1)
		{
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "LibWebta AWS Client (http://webta.net)"
			                                    )
			                              );
						
			$timestamp = $this->GetTimestamp();
			$URL = "elasticloadbalancing.{$this->Region}.amazonaws.com";
			
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
            		
            	if (stristr($message, "Timeout was reached"))
            	{
            		if ($attempt < 3)
            			return $this->Request($method, $uri, $args, $attempt+1);
            			
            		throw new Exception("(Attempt: {$attempt}) ".$message);
            	}
            	
            	throw new Exception($message);
            }
		}
		
		public function CreateAppCookieStickinessPolicy($LoadBalancerName, $PolicyName, $CookieName)
		{
			$request_args = array(
				"Action" => "CreateAppCookieStickinessPolicy", 
				"LoadBalancerName" => $LoadBalancerName,
				"PolicyName"	=> $PolicyName,
				"CookieName"	=> $CookieName
			);
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		public function CreateLBCookieStickinessPolicy($LoadBalancerName, $PolicyName, $CookieExpirationPeriod = 3600)
		{
			$request_args = array(
				"Action" => "CreateLBCookieStickinessPolicy", 
				"LoadBalancerName" => $LoadBalancerName,
				"PolicyName"	=> $PolicyName,
				"CookieExpirationPeriod"	=> $CookieExpirationPeriod
			);
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		public function SetLoadBalancerPoliciesOfListener($LoadBalancerName, $LoadBalancerPort, $PolicyName = "")
		{
			$request_args = array(
				"Action" => "SetLoadBalancerPoliciesOfListener", 
				"LoadBalancerName" => $LoadBalancerName,
				"LoadBalancerPort"	=> $LoadBalancerPort
			);
			
			if ($PolicyName != '')
				$request_args['PolicyNames.member.1'] = $PolicyName;
			else 
				$request_args['PolicyNames'] = "";
				
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		public function DeleteLoadBalancerPolicy($LoadBalancerName, $PolicyName)
		{
			$request_args = array(
				"Action" => "DeleteLoadBalancerPolicy", 
				"LoadBalancerName" => $LoadBalancerName,
				"PolicyName"	=> $PolicyName
			);
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		/**
		 * 
		 * This method returns detailed configuration information for the specified LoadBalancers, or if no LoadBalancers are specified, 
		 * then the method returns configuration information for all LoadBalancers created by the caller.
		 * 
		 * @param array $LoadBalancerNames
		 * @return array
		 */
		public function DescribeLoadBalancers(array $LoadBalancerNames = array())
		{
			$request_args = array(
				"Action" => "DescribeLoadBalancers", 
			);
			foreach ($LoadBalancerNames as $i=>$n)
				$request_args['LoadBalancerNames.member.'.($i+1)] = $n;
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		/**
		 * 
		 * This method enables you to define an application healthcheck for the instances.
		 * 
		 * @param string $LoadBalancerName
		 * @param ELBHealthCheckType $HealthCheck
		 * @return object
		 */
		public function ConfigureHealthCheck($LoadBalancerName, ELBHealthCheckType $HealthCheck)
		{
			$response = $this->Request("GET", "/", array(
				"Action" 				=> "ConfigureHealthCheck", 
				"LoadBalancerName" 		=> $LoadBalancerName,
				"HealthCheck.Timeout"	=> $HealthCheck->Timeout,
				"HealthCheck.Target"	=> $HealthCheck->Target,
				"HealthCheck.Interval"	=> $HealthCheck->Interval,
				"HealthCheck.UnhealthyThreshold" 	=> $HealthCheck->UnhealthyThreshold,
				"HealthCheck.HealthyThreshold"		=> $HealthCheck->HealthyThreshold
			));
			
			
			return $response;
		}
		
		public function DeleteLoadBalancerListeners($LoadBalancerName, array $LbPorts) 
		{
			$request_args = array(
				"Action" 			=> "DeleteLoadBalancerListeners",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			foreach ($LbPorts as $i=>$o)
				$request_args['LoadBalancerPorts.member.'.($i+1)] = $o;

			return $this->Request("GET", "/", $request_args);
		}
		
		public function CreateLoadBalancerListeners($LoadBalancerName, ELBListenersList $Listeners)
		{
			$request_args = array(
				"Action" 			=> "CreateLoadBalancerListeners",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			foreach ($Listeners->GetListeners() as $i=>$o)
			{
				$request_args['Listeners.member.'.($i+1).".Protocol"] = $o->Protocol;
				$request_args['Listeners.member.'.($i+1).".LoadBalancerPort"] = $o->LoadBalancerPort;
				$request_args['Listeners.member.'.($i+1).".InstancePort"] = $o->InstancePort;
				if ($o->SSLCertificateId)
					$request_args['Listeners.member.'.($i+1).".SSLCertificateId"] = $o->SSLCertificateId;
			}

			return $this->Request("GET", "/", $request_args);
		}
		
		/**
		 * 
		 * This method creates a new LoadBalancer.
		 * 
		 * @param string $LoadBalancerName
		 * @param array $AvailabilityZonesList
		 * @param ELBListenersList $Listeners
		 * @return string LoadBalancer hostname
		 */
		public function CreateLoadBalancer($LoadBalancerName, array $AvailabilityZonesList, ELBListenersList $Listeners)
		{
			$request_args = array(
				"Action" 			=> "CreateLoadBalancer",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			foreach ($Listeners->GetListeners() as $i=>$o)
			{
				$request_args['Listeners.member.'.($i+1).".Protocol"] = $o->Protocol;
				$request_args['Listeners.member.'.($i+1).".LoadBalancerPort"] = $o->LoadBalancerPort;
				$request_args['Listeners.member.'.($i+1).".InstancePort"] = $o->InstancePort;
				if ($o->SSLCertificateId)
					$request_args['Listeners.member.'.($i+1).".SSLCertificateId"] = $o->SSLCertificateId;
			}
			
			foreach ($AvailabilityZonesList as $i=>$o)
				$request_args['AvailabilityZones.member.'.($i+1)] = $o;
				
			$response = $this->Request("GET", "/", $request_args);
			
			return (string)$response->CreateLoadBalancerResult->DNSName;
		}
		
		/**
		 * 
		 * This method deletes the specified LoadBalancer. On deletion, all of the configured properties of the LoadBalancer will be deleted. 
		 * If you attempt to recreate the LoadBalancer, you need to reconfigure all the settings. 
		 * The DNS name associated with a deleted LoadBalancer is no longer be usable. 
		 * Once deleted, the name and associated DNS record of the LoadBalancer no longer exist and traffic sent to any of its 
		 * IP addresses will no longer be delivered to your instances. You will not get the same DNS name even 
		 * if you create a new LoadBalancer with same LoadBalancerName.
		 * 
		 * @param string $LoadBalancerName
		 * @return object
		 */
		public function DeleteLoadBalancer($LoadBalancerName)
		{
			$request_args = array(
				"Action" 			=> "DeleteLoadBalancer",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		/**
		 * 
		 * This API deregisters instances from the LoadBalancer. 
		 * Trying to deregister an instance that is not registered with the LoadBalancer does nothing. 
		 * In order to successfully call this method, you must provide the same account 
		 * credentials as those that were used to create the LoadBalancer. 
		 * Once the instance is deregistered, it will stop receiving traffic from the LoadBalancer.
		 * 
		 * @param string $LoadBalancerName
		 * @param array $Instances
		 * @return object
		 */
		public function DeregisterInstancesFromLoadBalancer($LoadBalancerName, array $Instances)
		{
			$request_args = array(
				"Action" 			=> "DeregisterInstancesFromLoadBalancer",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			foreach ($Instances as $i=>$o)
				$request_args['Instances.member.'.($i+1).".InstanceId"] = $o;
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
				
		/**
		 * 
		 * This method returns the current state of the instances of the specified LoadBalancer. 
		 * If no instances are specified, the state of all the instances for the LoadBalancer is returned.
		 * You must have been the one who created in the LoadBalancer. 
		 * In other words, in order to successfully call this method, you must provide the same account credentials as 
		 * those that were used to create the LoadBalancer. 
		 * 
		 * @param string $LoadBalancerName
		 * @param array $Instances
		 * @return unknown_type
		 */
		public function DescribeInstanceHealth($LoadBalancerName, array $Instances = array())
		{
			$request_args = array(
				"Action" 			=> "DescribeInstanceHealth",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			foreach ($Instances as $i=>$o)
				$request_args['Instances.member.'.($i+1).".InstanceId"] = $o;
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		/**
		 * 
		 * This method removes the specified EC2 Availability Zones from the set of configured Availability Zones for the LoadBalancer. 
		 * Once an Availability Zone is removed, all the instances registered with the LoadBalancer that are in the removed 
		 * Availability Zone go into the OutOfService state. Upon Availability Zone removal, the LoadBalancer attempts to equally balance 
		 * the traffic among its remaining usable Availability Zones. Trying to remove an Availability Zone that was not associated 
		 * with the LoadBalancer does nothing.
		 * There must be at least one Availability Zone registered with a LoadBalancer at all times. You cannot remove all the 
		 * Availability Zones from a LoadBalancer.
		 * In order for this call to be successful, you must have created the LoadBalancer. In other words, in order to 
		 * successfully call this API, you must provide the same account credentials as those that were used to create the LoadBalancer.
		 * 
		 * @param string $LoadBalancerName
		 * @param array $AvailabilityZonesList
		 * @return object
		 */
		public function DisableAvailabilityZonesForLoadBalancer($LoadBalancerName, array $AvailabilityZonesList)
		{
			$request_args = array(
				"Action" 			=> "DisableAvailabilityZonesForLoadBalancer",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			foreach ($AvailabilityZonesList as $i=>$o)
				$request_args['AvailabilityZones.member.'.($i+1)] = $o;
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		/**
		 * 
		 * This method is used to add one or more EC2 Availability Zones to the LoadBalancer. 
		 * The LoadBalancer evenly distributes requests across all its registered Availability Zones that contain instances. 
		 * As a result, you must ensure that your LoadBalancer is appropriately scaled for each registered Availability Zone. 
		 * 
		 * @param string $LoadBalancerName
		 * @param array $AvailabilityZonesList
		 * @return object
		 */
		public function EnableAvailabilityZonesForLoadBalancer($LoadBalancerName, array $AvailabilityZonesList)
		{
			$request_args = array(
				"Action" 			=> "EnableAvailabilityZonesForLoadBalancer",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			foreach ($AvailabilityZonesList as $i=>$o)
				$request_args['AvailabilityZones.member.'.($i+1)] = $o;
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		/**
		 * 
		 * This method adds new instances to the LoadBalancer.
		 * Once the instance is registered, it starts receiving traffic and requests from the LoadBalancer. 
		 * Any instance that is not in any of the Availability Zones registered for the LoadBalancer will be moved to the OutOfService state. 
		 * It will move to the InService state when the Availability Zone is added to the LoadBalancer. 
		 * You must have been the one who created the LoadBalancer. In other words, in order to successfully call this method, 
		 * you must provide the same account credentials as those that were used to create the LoadBalancer.
		 * 
		 * @param string $LoadBalancerName
		 * @param array $Instances
		 * @return object
		 */
		public function RegisterInstancesWithLoadBalancer($LoadBalancerName, array $Instances)
		{
			$request_args = array(
				"Action" 			=> "RegisterInstancesWithLoadBalancer",
				"LoadBalancerName"	=> $LoadBalancerName
			);
			
			foreach ($Instances as $i=>$o)
				$request_args['Instances.member.'.($i+1).".InstanceId"] = $o;
				
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
	}
?>