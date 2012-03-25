<?php

	class ParametersList
	{
		private $Items;		
		static  $ItemsCounter;
		public function __construct()
		{
			$this->Items = array();
		}
		
		public function AddParameters($ParameterName,$ParameterValue,$ApplyMethod)		
		{
			if(empty($ParameterValue))				 // $ParameterValue can't be empty
				$ParameterValue = '0';
					
			$o = new stdClass();
			$o->ParameterName	= $ParameterName;
			$o->ParameterValue	= $ParameterValue;
			$o->ApplyMethod		= $ApplyMethod;		
			
			$this->Items[] = $o;
			$ParameterName++;
		}	
		
		public function GetParameters()
		{
			return $this->Items;
		}
	}

	
	class DBModifyInstanceSettings
	{	// The DBModifyInstanceSettings class just coveres DB settings for request. It's not amazonRDS class
		public $DBInstanceIdentifier;
		public $DBParameterGroupName;
		public $DBSecurityGroups;
		public $PreferredMaintenanceWindow;
		public $MasterUserPassword;
		public $AllocatedStorage;			// The new storage capacity of the RDS instance. This change does not result in an outage and is applied during the next maintenance window unless the ApplyImmediately parameter is specified as True for this request. Type: Integer
		public $DBInstanceClass;			// The new compute and memory capacity of the DB Instance. This change causes an outage during the change and is applied during the next maintenance window, unless the ApplyImmediately parameter true for this request. Type: String
		public $ApplyImmediately;			// Specifies that the modifications in this request and any pending modifications are asynchronously applied as soon as possible, regardless of the PreferredMaintenanceWindow setting for the DB Instance. If this parameter is false, changes to the DB Instance are applied on the next call to RebootDBInstance or the next maintenance or failure reboot, whichever occurs first. Type: Boolean
		public $BackupRetentionPeriod;		// The number of days to retain automated backups. Setting this parameter to a positive number enables backups. Setting this parameter to 0 disables automated backups. Type: Integer
		public $PreferredBackupWindow;		// The daily time range during which automated backups are created if backups are enabled (as determined by the --backup-retention-period). Type: String		
		
		
		public function __construct($DBInstanceIdentifier,
										 $DBParameterGroupName	= null,
										 $DBSecurityGroups		= null,
										 $PreferredMaintenanceWindow = null,
										 $MasterUserPassword	= null,
										 $AllocatedStorage		= null,
										 $DBInstanceClass		= null,
										 $ApplyImmediately		= null,
										 $BackupRetentionPeriod	= null,
										 $PreferredBackupWindow	= null
										)
		{			
			$this->DBInstanceIdentifier		= $DBInstanceIdentifier;
			$this->DBParameterGroupName		=  $DBParameterGroupName;
			$this->DBSecurityGroups			= $DBSecurityGroups;
			$this->PreferredMaintenanceWindow = $PreferredMaintenanceWindow;
			$this->MasterUserPassword		= $MasterUserPassword;
			$this->AllocatedStorage			= $AllocatedStorage;
			$this->DBInstanceClass			= $DBInstanceClass;
			$this->ApplyImmediately			= $ApplyImmediately;
			$this->BackupRetentionPeriod	= $BackupRetentionPeriod;
			$this->PreferredBackupWindow	= $PreferredBackupWindow;
		}
	}
	class DBInstanceSettings
	{ // The DBInstanceSettings class just coveres DB settings for request. It's not amazonRDS class
		
		public $DBInstanceIdentifier;		// DB Instance identifier. This is the unique key that identifies a DB Instance. This parameter is stored as a lowercase string. Type: String									
		public $AllocatedStorage;			// Amount of storage to be initially allocated for the database instance, in gigabytes. Type: String.						   
		public $DBInstanceClass;			// Contains the compute and memory capacity of the DB Instance. Type: String
		public $Engine;						// Name of the database engine to be used for this instance. Type: String
		public $MasterUsername;				// Name of master user for your DB Instance. Type: String.
		public $MasterUserPassword;			// Password for the master DB Instance user. Type: String.
		public $MultiAZ;					// Specifies if the DB Instance is a Multi-AZ deployment. 
		public $Port;						// Type: Integer Default: 3306
		public $DBName;						// Name of a database to create when the DB Instance is created. If this parameter is not specified, no database is created in the DB Instance. Type: String									
		public $DBParameterGroup;			// Name of the database parameter group to associate with this DB instance. If this argument is omitted, the default DBParameterGroup for the specified engine will be used. Type: String
		public $DBSecurityGroups;			// List of DB Security Groups to associate with this DB Instance. Type: String							
		public $AvailabilityZone;			// The EC2 Availability Zone that the database instance will be created in. Type: String
		public $PreferredMaintenanceWindow; // The weekly time range (in UTC) during which system maintenance can occur. Type: String
		public $BackupRetentionPeriod;		// The number of days for which automated backups are retained. Type: Integer
		public $PreferredBackupWindow;		// The daily time range during which automated backups are created if automated backups are enabled (as determined by the --backup-retention-period). Type: String
		
		
		public function __construct($DBInstanceIdentifier,
										$AllocatedStorage,
										$DBInstanceClass,
										$Engine,$MasterUsername, 
										$MasterUserPassword, 
										$Port = null, 
										$DBName = null,										
										$DBParameterGroup  = null, 
										$DBSecurityGroups = null, 
										$AvailabilityZone = null,
										$PreferredMaintenanceWindow = null, 
										$BackupRetentionPeriod = null,
										$PreferredBackupWindow = null,
										$MultiAZ = null
										)
		{
			$this->DBInstanceIdentifier		= $DBInstanceIdentifier;		 
			$this->AllocatedStorage			= $AllocatedStorage;
			$this->DBInstanceClass			= $DBInstanceClass;
			$this->Engine					= $Engine;
			$this->MasterUsername			= $MasterUsername;
			$this->MasterUserPassword		= $MasterUserPassword;
			$this->Port						= $Port;
			$this->DBName					= $DBName;
			$this->DBParameterGroup			= $DBParameterGroup;
			$this->DBSecurityGroups			= $DBSecurityGroups;
			$this->AvailabilityZone			= $AvailabilityZone;
			$this->PreferredMaintenanceWindow  = $PreferredMaintenanceWindow;
			$this->BackupRetentionPeriod	= $BackupRetentionPeriod;
			$this->PreferredBackupWindow	= $PreferredBackupWindow;
			$this->MultiAZ					= $MultiAZ;
		}
	}
	
	class AmazonRDS  
	{
		const API_VERSION 				= "2010-01-01";		
		const HASH_ALGO 				= 'SHA256';
		const USER_AGENT 				= 'Libwebta AWS Client (http://webta.net)';
		const MAX_MODIFY_PARAMETERS_NUM	= 5;
		
		private $AWSAccessKeyId			= NULL;
		private $AWSAccessKey			= NULL;
		private $Region					= 'us-east-1';
		private $LastResponseHeaders	= array();
		private static $Instance;
		
		public function GetMaxNum()
		{
			return MAX_MODIFY_PARAMETERS_NUM;
		}
		/**
		 *
		 * @param $AWSAccessKeyId
		 * @param $AWSAccessKey
		 * @return AmazonRDS
		 */		
		
		public static function GetInstance($AWSAccessKeyId, $AWSAccessKey)
		{
			self::$Instance = new AmazonRDS($AWSAccessKeyId, $AWSAccessKey);
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
			if (in_array($region, array('sa-east-1', 'us-east-1','us-west-1','us-west-2','eu-west-1','ap-southeast-1','ap-northeast-1')))
				$this->Region = $region;				
		}
		
		private function GetRESTSignature($params)
		{
			return base64_encode(@hash_hmac(AmazonRDS::HASH_ALGO, implode("\n", $params), $this->AWSAccessKey, 1));
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
			//timeout , connecttimeout , dns_cache_timeout
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(
				"redirect" 	=> 10, 
				"useragent" => "LibWebta AWS Client (http://webta.net)",
				"timeout"	=> 30,
				"connecttimeout" => 10,
				"dns_cache_timeout"	=> 5 
			));
			
			$timestamp = $this->GetTimestamp();
			$URL = "rds.{$this->Region}.amazonaws.com";

			$args['Version'] = self::API_VERSION;
			$args['SignatureVersion'] = 2;
			$args['SignatureMethod'] = "HmacSHA256";
			$args['Expires'] = $timestamp;
			$args['AWSAccessKeyId'] = $this->AWSAccessKeyId;

			ksort($args);
								
			foreach ($args as $k=>$v)
				$CanonicalizedQueryString .= "&{$k}=".rawurlencode($v);
			$CanonicalizedQueryString = trim($CanonicalizedQueryString, "&");
						
			$args['Signature'] = $this->GetRESTSignature(array($method, $URL, $uri, $CanonicalizedQueryString));		
			$HttpRequest->setUrl("https://{$URL}{$uri}");
			
			$HttpRequest->setMethod(constant("HTTP_METH_{$method}"));
			
			if ($args)
				$HttpRequest->addQueryData($args);
			
			try 
			{
				$HttpRequest->send();
				$info = $HttpRequest->getResponseInfo();
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
		 * Check  values of any function's parameters
		 *
		 * if parametr == null, function CutNullArgs dosn't add it to the "Get request" string $request_args
		 * else - adds it as new value of array.
		 * 
		 * @param array $$method_args
		 * @param array $method_name 
		 * @param refference to &$request_args
		 * @return void
		 *
		 */
		protected function CutNullArgs($method_args, $method_name, &$request_args)
		{
			$ReflectionMethod = new ReflectionMethod($this, $method_name);
			$params = $ReflectionMethod->getParameters();
			$args = array();
			foreach ($params as $param)
			{
				if ($param->isOptional() && $param->getDefaultValue() === null)
					$args[$param->getName()] = $method_args[$param->getPosition()]; 
			}
			
			foreach($args as $k=>$v)
			{
				if($v !== null)
					$request_args[$k] = $v;										   	
			}	
		}
	
		/**
		 * Creates a new Amazon RDS database instance.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 *
		 * @param  string $DBInstanceIdentifier
		 * @param  string $AllocatedStorage
		 * @param  string $DBInstanceClass
		 * @param  string $Engine
		 * @param  string $MasterUsername
		 * @param  string $MasterUserPassword
		 * @param  int    $Port
		 * @param  string $DBName
		 * @param  string $DBParameterGroup
		 * @param  string $DBSecurityGroups
		 * @param  string $AvailabilityZone
		 * @param  string $PreferredMaintenanceWindow
		 * @param  int    $BackupRetentionPeriod
		 * @param  string $PreferredBackupWindow
		 * @param  int	  $MultiAZ
		 * @return object $response
		 *
		 */
		public function CreateDBInstance($DBInstanceIdentifier,
												$AllocatedStorage,
												$DBInstanceClass,
												$Engine,				
												$MasterUsername,
												$MasterUserPassword,												
												$Port					= null,
												$DBName					= null,
												$DBParameterGroupName	= null,
												$DBSecurityGroups		= array(),
												$AvailabilityZone		= null ,
												$PreferredMaintenanceWindow  = null,
												$BackupRetentionPeriod	= null ,
												$PreferredBackupWindow	= null,
												$MultiAZ				= 0)
		{
			$request_args = array(
				"Action"						=> "CreateDBInstance",
				"DBInstanceIdentifier"			=> $DBInstanceIdentifier,		 
				"AllocatedStorage"				=> $AllocatedStorage,
				"DBInstanceClass"				=> $DBInstanceClass,
				"Engine"						=> $Engine,
				"MasterUsername"				=> $MasterUsername,
				"MasterUserPassword"			=> $MasterUserPassword,
				"MultiAZ"						=> ($MultiAZ)?$MultiAZ = 1: $MultiAZ = 0			
			 );		
			
			foreach ($DBSecurityGroups as $i=>$o)
				$request_args['DBSecurityGroups.member.'.($i+1)]	 = $o;
			 
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
			
		/**
		 * Retrieves information about one or all DB Instances for an AWS account.
		 * 
		 * You can call this operation recursively using the Marker parameter.	
		 *  
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string	$DBInstanceIdentifier 
		 * @param	int		$maxRecords 
		 * @param	string	$marker 
		 * @return	object	$response
		 *
		 */
		public function DescribeDBInstances($DBInstanceIdentifier = null, $maxRecords = null, $marker = null)
		{
			$request_args = array("Action" => "DescribeDBInstances" );		
					
			
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);	

			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		 
		/**
		 * Changes the settings of an existing DB Instance.
		 * 		
		 * Changes are applied in the following manner: A ModifyDBInstance API call to
		 * modify security groups or to change the maintenance windows results in 
		 * immediate action. 
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string  $DBInstanceIdentifier
		 * @param	string  $DBParameterGroupName
		 * @param	string  $DBSecurityGroups
		 * @param	string	$PreferredMaintenanceWindow
		 * @param	string	$MasterUserPassword
		 * @param	int		$AllocatedStorage
		 * @param	string	$DBInstanceClass
		 * @param	bool	$ApplyImmediately
		 * @param	int		$BackupRetentionPeriod
		 * @param	string	$PreferredBackupWindow
		 * @param   int $MultiAZ
		 * @return	object	$response
		 *
		 */
		public function ModifyDBInstance($DBInstanceIdentifier,
													$DBParameterGroupName	= null,
													$DBSecurityGroups		= array(),
													$PreferredMaintenanceWindow = null,
													$MasterUserPassword	    = null,
													$AllocatedStorage		= null,
													$DBInstanceClass		= null,
													$ApplyImmediately		= false,
													$BackupRetentionPeriod	= null,
													$PreferredBackupWindow	= null,
													$MultiAZ				= 0)
		{
			$request_args = array(
					"Action"						=> "ModifyDBInstance",
					"DBInstanceIdentifier"			=> $DBInstanceIdentifier								
					);		
						
			foreach ($DBSecurityGroups as $i=>$o)
				$request_args['DBSecurityGroups.member.'.($i+1)]	 = $o;
				
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);

			$response = $this->Request("GET", "/", $request_args);

			return $response;
		}
		
		/**
		 * Reboots a DB Instance. 
		 * 
		 * Once started, the process cannot be stopped, and the database instance will be 
		 * unavailable until the reboot complete
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	DBInstanceIdentifier $DBInstanceIdentifier 
		 * @return	object $response
		 *
		 */
		public function RebootDBInstance($DBInstanceIdentifier)
		{
			$request_args = array(
					"Action"				=> "RebootDBInstance",
					"DBInstanceIdentifier"	=> $DBInstanceIdentifier							
					);		
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		/**
		 * Deletes a DB Instance.
		 * 
		 * Once started, the process cannot be stopped, and the DB Instance
		 * will no longer be accessible.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string $DBInstanceIdentifier
		 * @param	bool   $skipFinalSnapshot
		 * @param	string $finalDBSnapshotIdentifier
		 * @return	object $response
		 *
		 */
		public function DeleteDBInstance($DBInstanceIdentifier,
											$SkipFinalSnapshot = true,
											$FinalDBSnapshotIdentifier = null)
		{
			$request_args = array(
					"Action"					=> "DeleteDBInstance",				
					"DBInstanceIdentifier"		=> $DBInstanceIdentifier,
					"SkipFinalSnapshot"			=> $SkipFinalSnapshot
					);	
										
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);
			
			$response = $this->Request("GET", "/", $request_args);		
			return $response;
		}
		
		
		
		/**
		 * Creates a new DB Security Group.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string $DBSecurityGroupName 
		 * @param	string $DBSecurityGroupDescription 
		 * @return	object $response
		 *
		 */
		public function CreateDBSecurityGroup($DBSecurityGroupName,$DBSecurityGroupDescription)
		{
			$request_args = array(
					"Action"					 => "CreateDBSecurityGroup",
					"DBSecurityGroupName"		 => $DBSecurityGroupName,
					"DBSecurityGroupDescription" => $DBSecurityGroupDescription,							
					);		
			
			$response = $this->Request("GET", "/", $request_args);		
			return $response;
		}
		
		/**
		 * This is method DescribeDBSecurityGroups
		 *
		 * Returns all the DB Security Group details for a particular AWS account, 
		 * or for a particular DB Security Group if a name is specified.
		 * You can call this operation recursively using the Marker parameter.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string $DBSecurityGroupName 
		 * @param	int    $maxRecords
		 * @param	string $marker 
		 * @return	object $response 
		 *
		 */
		public function DescribeDBSecurityGroups($DBSecurityGroupName=null,$maxRecords = null, $marker = null)
		{
			$request_args = array(
					"Action"				=> "DescribeDBSecurityGroups",							
					);		
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);							
	
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Deletes a DB Security Group.
		 * 
		 * The specified database security group must not be associated with any DB instances.
		 *
		 * @param sting $DBSecurityGroupName
		 * @return object $response
		 *
		 */
		public function DeleteDBSecurityGroup($DBSecurityGroupName)
		{
			$request_args = array(
					"Action"				=> "DeleteDBSecurityGroup",
					"DBSecurityGroupName"	=> $DBSecurityGroupName
					);		
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
	
		
		/**
		 * Authorizes network ingress for an Amazon EC2 security group or an IP address range.
		 * 
		 * EC2 security groups can be added to the DBSecurityGroup if the application using
		 * the database is running on EC2 instances. IP ranges are available
		 * if the application accessing your database is running on the Internet. 
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string $DBSecurityGroupName
		 * @param	string $CIDRIP 
		 * @param	string $EC2SecurityGroupName 
		 * @param	string $EC2SecurityGroupOwnerId 
		 * @return	object $response
		 *
		 */
		public function AuthorizeDBSecurityGroupIngress($DBSecurityGroupName,
															$CIDRIP = null,
															$EC2SecurityGroupName = null,
															$EC2SecurityGroupOwnerId = null)
		{
			$request_args = array(
					"Action"					=> "AuthorizeDBSecurityGroupIngress",
					"DBSecurityGroupName"		=> $DBSecurityGroupName
					);		
			
			if ($CIDRIP)
				$request_args['CIDRIP'] = $CIDRIP;
			else
			{
				$request_args['EC2SecurityGroupName'] = $EC2SecurityGroupName;
				$request_args['EC2SecurityGroupOwnerId'] = $EC2SecurityGroupOwnerId;
			}
					
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Revokes ingress to a DBSecurityGroup for previously authorized 
		 * IP ranges or EC2 Security Groups. 
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more 
		 * 
		 * @param	string  $DBSecurityGroupName 
		 * @param	string  $CIDRIP
		 * @param	string  $EC2SecurityGroupName 
		 * @param	string  $EC2SecurityGroupOwnerId
		 * @return	object  $response
		 *
		 */
		public function RevokeDBSecurityGroupIngress($DBSecurityGroupName,
														$CIDRIP,
														$EC2SecurityGroupName,
														$EC2SecurityGroupOwnerId)
		{
			$request_args = array(
					"Action"					=> "RevokeDBSecurityGroupIngress",
					"DBSecurityGroupName"		=> $DBSecurityGroupName
					);		
					
			if ($CIDRIP)
				$request_args['CIDRIP'] = $CIDRIP;
			else
			{
				$request_args['EC2SecurityGroupName'] = $EC2SecurityGroupName;
				$request_args['EC2SecurityGroupOwnerId'] = $EC2SecurityGroupOwnerId;
			}
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Creates a DB Parameter Group.
		 *
		 * @param	string $DBParameterGroupName
		 * @param	string $Engine
		 * @param	string $Description
		 * @return	object $response
		 *
		 */
		public function CreateDBParameterGroup($DBParameterGroupName, $Description, $Engine = 'MySQL5.1')
		{
			$request_args = array(
					"Action"					=> "CreateDBParameterGroup",
					"DBParameterGroupName"		=> strtolower($DBParameterGroupName),
					"Engine"					=> $Engine,
					"Description"				=> $Description				
					);		
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Deletesa DB Parameter Group.
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * @param	string $DBParameterGroupNam 
		 * @return	object $response
		 */
		 
		public function DeleteDBParameterGroup($DBParameterGroupName)
		{
			$request_args = array(
					"Action"					=> "DeleteDBParameterGroup",
					"DBParameterGroupName"		=> strtolower($DBParameterGroupName)		
					);		
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Returns the default engine and system parameter information for each supported database engine. 
		 *
		 * You can call this operation recursively using the Marker parameter.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string $DBSecurityGroupName
		 * @param	int    $maxRecords 
		 * @param	string $marker 
		 * @return	object $response
		 *
		 */
		public function DescribeEngineDefaultParameters($Engine, $maxRecords = null, $marker = null)
		{
			$request_args = array(
					"Action"				=> "DescribeEngineDefaultParameters",
					"Engine"				=> $Engine	
					);		
			
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);	
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Returns information about all DB Parameter Groups for an account if no DB
		 * Parameter Group name is supplied, or displays information about
		 * a specific named DB Parameter Group. 
		 * 
		 * You can call this operation recursively using the Marker parameter.
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string $DBParameterGroupName
		 * @param	int    $maxRecords
		 * @param	string $marker
		 * @return	object $response
		 *
		 */
		public function DescribeDBParameterGroups($DBParameterGroupName  = null,
															$maxRecords  = null,
															$marker		 = null)
		{
			$request_args = array(
					"Action"					=> "DescribeDBParameterGroups"			
					);		
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);	
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		/**
		 * Returns information about parameters that are part of a parameter group. 
		 * 
		 * You can optionally request only parameters from a specific source. 
		 * You can call this operation recursively using the Marker parameter. * 
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string $DBParameterGroupName
		 * @param	string $Source
		 * @param	int    $maxRecords
		 * @param	string $marker
		 * @return	object $response
		 *
		 */
		public function DescribeDBParameters($DBParameterGroupName,
														$source		= null,
														$maxRecords = null,
														$marker		= null)
		{
			$request_args = array(
					"Action"					=> "DescribeDBParameters",
					"DBParameterGroupName"		=> strtolower($DBParameterGroupName)			
					);		
					
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Modifies the parameters of a DB Parameter Group. 
		 * 
		 * To modify more than one parameter, submit a list of the following: ParameterName,
		 * ParameterValue, and ApplyMethod. You can modify a maximum of 20 parameters
		 * in a single request.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param	string			$DBParameterGroupName
		 * @param	ParametersList	$Parameters
		 * @return	object			$response
		 *
		 */
		public function ModifyDBParameterGroup($DBParameterGroupName, ParametersList $Parameters)
		{
			$request_args = array(
					"Action" 					=> "ModifyDBParameterGroup",
					"DBParameterGroupName"		=> strtolower($DBParameterGroupName)
					);
			
			foreach ($Parameters->GetParameters() as $i=>$o)
			{
				$request_args['Parameters.member.'.($i+1).".ParameterName"]	 = $o->ParameterName;
				$request_args['Parameters.member.'.($i+1).".ParameterValue"] = $o->ParameterValue;				
				$request_args['Parameters.member.'.($i+1).".ApplyMethod"]	 = $o->ApplyMethod;
			}			
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		
		/**
		 * 
		 * Resets some or all of the parameters of a DB Parameter Group to the default values. 
		 * 
		 * When resetting the entire group, dynamic parameters are updated immediately,
		 * and static parameters are set to pending-reboot to take effect when the DB 
		 * Instance reboots.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param  string			$DBParameterGroupName 
		 * @param  ParametersList	$parameters 
		 * @param  bool				$resetAllParameters 
		 * @return object			$response
		 *
		 */
		public function ResetDBParameterGroup($DBParameterGroupName,
												ParametersList $parameters,
												$resetAllParameters = true )
		{
			$request_args = array(
					"Action" 					=> "ResetDBParameterGroup",
					"DBParameterGroupName"		=> strtolower($DBParameterGroupName),
					"resetAllParameters"		=> $resetAllParameters			
					);
			
			foreach ($parameters->GetParameters() as $i=>$o)
			{
				$request_args['Parameters.member.'.($i+1).".ParameterName"]	 = $o->ParameterName;			
				$request_args['Parameters.member.'.($i+1).".ApplyMethod"]	 = $o->ApplyMethod;
			}		
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
			
		
		/**
		 * Creates a restorable DB Snapshot of all data associated with a DB Instance.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param  string $DBSnapshotIdentifier
		 * @param  string $DBInstanceIdentifier
		 * @return object $response
		 *
		 */
		public function CreateDBSnapshot($DBSnapshotIdentifier,$DBInstanceIdentifier)
		{
			$request_args = array(
					"Action"					=> "CreateDBSnapshot",
					"DBSnapshotIdentifier"		=> $DBSnapshotIdentifier,
					"DBInstanceIdentifier"		=> $DBInstanceIdentifier							
					);		
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Returns information about the DB Snapshots for this account.
		 *
		 * If you pass in a DBInstanceIdentifier, it returns information only about 
		 * DB Snapshots taken for that DB Instance. If you pass in a 
		 * DBSnapshotIdentifier,it will return information only about the specified 
		 * snapshot. 
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param  string $DBInstanceSettings
		 * @param  string $DBSnapshotIdentifier 
		 * @param  int    $maxRecords
		 * @param  string $marker
		 * @return object $response
		 *
		 */
		public function DescribeDBSnapshots($DBInstanceIdentifier	= null,
											$DBSnapshotIdentifier	= null,											
											$maxRecords				= null,
											$marker					= null)
		{
			$request_args = array(
				"Action"					=> "DescribeDBSnapshots"				
			);
					
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);
								
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Deletes a DB Snapshot.
		 *
		 * The specified DB Snapshot must be in the available state.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param  string $DBSnapshotIdentifier 
		 * @return object $response
		 */
	
		public function DeleteDBSnapshot($DBSnapshotIdentifier)
		{
			$request_args = array(
					"Action"					=> "DeleteDBSnapshot",
					"DBSnapshotIdentifier"		=> $DBSnapshotIdentifier											
					);		
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		
		/**
		 * Creates a new DB Instance from a DB Snapshot. 
		 * 
		 * The source DB Snapshot must be in the available state. 
		 * The new DB Instance is created with the default DB Security Group.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param  string $DBSnapshotIdentifier 
		 * @param  string $DBInstanceIdentifier,
		 * @param  string $DBInstanceClass,
		 * @param  int    $Port,
		 * @param  string $AvailabilityZone
		 * @param  int	  $MultiAZ
		 * @return object $response
		 */
		public function RestoreDBInstanceFromDBSnapshot($DBSnapshotIdentifier, 
																$DBInstanceIdentifier,
																$DBInstanceClass,
																$Port,
																$AvailabilityZone,
																$MultiAZ = 0)
		{
			$request_args = array(
					"Action"					=> "RestoreDBInstanceFromDBSnapshot",
					"DBSnapshotIdentifier"		=> $DBSnapshotIdentifier,
					"DBInstanceIdentifier"		=> $DBInstanceIdentifier,
					"DBInstanceClass"			=> $DBInstanceClass,
					"Port"						=> $Port,
					"MultiAZ"					=> ($MultiAZ)?$MultiAZ = 1: $MultiAZ = 0
					
					);		
				
			// AvailabilityZone not allowed if MultiAZ is true 
			if(!$MultiAZ)			
				$request_args["AvailabilityZone"] = $AvailabilityZone;
				
			$request_args["MultiAZ"] = ($MultiAZ)?$MultiAZ = 1: $MultiAZ = 0;
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Restores a DB Instance to a specified time, creating a new DB Instance.
		 *
		 * Some characteristics of the new DB Instance can be modified using optional parameters. 
		 * If these options are omitted, the new DB Instance defaults to the characteristics 
		 * of the DB Instance from which the DB Snapshot was created. 
		 *
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more
		 * 
		 * @param  string  $SourceDBInstanceIdentifier
		 * @param  bool	   $UseLatestRestorableTime
		 * @param  date	   $RestoreTime
		 * @param  string  $TargetDBInstanceIdentifier
		 * @param  string  $DBInstanceClass
		 * @param  int	   $Port
		 * @param  string  $AvailabilityZone
		 * @return object  $response 
		 */
		public function RestoreDBInstanceToPointInTime($SourceDBInstanceIdentifier,
															$UseLatestRestorableTime = false,
															$RestoreTime,
															$TargetDBInstanceIdentifier,
															$DBInstanceClass		 = null,
															$Port					 = null,
															$AvailabilityZone		 = null)
		{
			$request_args = array(
					"Action"						=> "RestoreDBInstanceToPointInTime",
					"SourceDBInstanceIdentifier"	=> $SourceDBInstanceIdentifier,
					"UseLatestRestorableTime"		=> $UseLatestRestorableTime,
					"RestoreTime"					=> $RestoreTime,
					"TargetDBInstanceIdentifier"	=> $TargetDBInstanceIdentifier
					);		
			
			
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);
					
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
		
		
		/**
		 * Returns information about events related to your DB Instances, DB Security Groups,
		 * and DB Parameter Groups for up to the past 14 days.
		 * 
		 * You can get events specific to a particular DB Instance or DB Security Group
		 * by providing the name as a parameter. By default, the past hour of events are returned.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonRDS/latest/APIReference/ learn more	 * 
		 *
		 * @param	string	$SourceIdentifie
		 * @param	string	$SourceType 
		 * @param	date	$StartTime 
		 * @param	date	$EndTime 
		 * @param	int		$Duration 
		 * @param	int		$MaxRecords 
		 * @param	string	$Marker 
		 * @return  object	$response
		 *
		 */
		public function DescribeEvents($SourceIdentifier	= null,
												$SourceType	= null,
												$StartTime	= null,
												$EndTime	= null,
												$Duration	= null,
												$MaxRecords = null,
												$Marker		= null)
		{
			$request_args = array(
					"Action"					=> "DescribeEvents",				
					);		
			
			
			$this->CutNullArgs(func_get_args(), __FUNCTION__, &$request_args);	
			
			$response = $this->Request("GET", "/", $request_args);
			
			return $response;
		}
	}
?>