<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
	 * This source file is subject to version 2 of the GPL license,
	 * that is bundled with this package in the file license.txt and is
	 * available through the world-wide-web at the following url:
	 * http://www.gnu.org/copyleft/gpl.html
     *
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */ 

	
    /**
     * @name AmazonS3
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @version 1.0
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
	
	class AmazonS3 
    {
	    const EC2WSDL = 'http://s3.amazonaws.com/doc/2006-03-01/AmazonS3.wsdl';
	    const USER_AGENT = 'Libwebta AWS Client (http://webta.net)';
	    const HASH_ALGO = 'SHA1';
	    const SIGN_STRING = 'AmazonS3%s%s';
	    const CONNECTION_TIMEOUT = 15;
	    
		private $S3SoapClient = NULL;
		private $AWSAccessKeyId = NULL;
	
		/**
		 * Constructor
		 *
		 * @param string $AWSAccessKeyId
		 * @param string $AWSAccessKey
		 */
		public function __construct($AWSAccessKeyId, $AWSAccessKey) 
		{
			$this->S3SoapClient = new SoapClient(AmazonS3::EC2WSDL, array(
				'trace' => 1, 
				'exceptions'=> 0, 
				'user_agent' => AmazonS3::USER_AGENT,
				'connection_timeout' => self::CONNECTION_TIMEOUT
			));
			$this->AWSAccessKeyId = $AWSAccessKeyId;
			$this->AWSAccessKey = $AWSAccessKey;
			
			if (!function_exists("hash_hmac"))
                throw new Exception("hash_hmac() function not found. Please install HASH Pecl extension.", E_ERROR);
		}
        
		/**
		 * Generate signature
		 *
		 * @param string $operation
		 * @param string $time
		 * @return string
		 */
		private function GetSOAPSignature($operation, $time)
		{
            return base64_encode(@hash_hmac(AmazonS3::HASH_ALGO, sprintf(AmazonS3::SIGN_STRING, $operation, $time), $this->AWSAccessKey, 1));
		}
		
		private function GetRESTSignature($data)
		{
			$data_string = implode("\n", $data);
			return base64_encode(@hash_hmac(AmazonS3::HASH_ALGO, $data_string, $this->AWSAccessKey, 1));
		}
		
		/**
		 * Return GMT timestamp for Amazon AWS S3 Requests
		 *
		 * @return unknown
		 */
		private function GetTimestamp($REST_FORMAT = false)
		{
		    $tz = @date_default_timezone_get();
			date_default_timezone_set("GMT");
		    if (!$REST_FORMAT)
		    	$dt = date("Y-m-d\TH:i:s.B\Z");
		   	else
				$dt = date("r");
		    date_default_timezone_set($tz);
		    return $dt;
		}
		
		/**
		 * List all objects on bucket
		 *
		 * @param string $bucket_name
		 * @param string $prefix
		 * @return array
		 */
		public function ListBucket($bucket_name, $prefix = "")
		{
			$timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->ListBucket(
            		                                      array(  
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                      		  "Bucket"		   => $bucket_name,
            		                                      		  "Prefix"		   => $prefix,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSOAPSignature("ListBucket", $timestamp)
            		                                           )
            		                                     );
            		                                                 		                                                   
                if (!($res instanceof SoapFault))
                {
                    $retval = $res->ListBucketResponse->Contents;
                    if (!$retval)
                    	return array();
                    else
                    {
                    	if ($retval instanceof stdClass)
                    		$retval = array($retval);
                    		
                    	return $retval;
                    }
                }
                else 
                {
                	throw new Exception($res->faultString ? $res->faultString : $res->getMessage(), E_ERROR);
                }
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
		}
		
		public function CopyObject($source_object, $source_bucket, $dest_object, $dest_bucket)
		{
			$timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $params = array(  
					"SourceBucket" => $source_bucket,
					"SourceKey" => $source_object,
					"DestinationBucket" => $dest_bucket,
					"DestinationKey" => $dest_object,
					"AWSAccessKeyId" => $this->AWSAccessKeyId,
					"Timestamp"      => $timestamp,
					"Signature"      => $this->GetSOAPSignature("CopyObject", $timestamp)
				);
				
		    	$res = $this->S3SoapClient->CopyObject($params);
		    	
		    	if ($res->detail->Endpoint)
		    	{
		    		$loc = $this->S3SoapClient->location;
		    		$this->S3SoapClient->location = "https://{$res->detail->Endpoint}/soap";
		    		
		    		$res = $this->S3SoapClient->CopyObject($params);
		    		
		    		$this->S3SoapClient->location  = $loc;
		    	}
		    	
                if (!($res instanceof SoapFault))
                {
                    return true;
                }
                else 
                {
                	throw new Exception($res->faultString ? $res->faultString : $res->getMessage(), E_ERROR);
                }
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }	
		}
		
		
		/**
		 * The ListBuckets operation returns a list of all buckets owned by the sender of the request.
		 *
		 * @return array
		 */
		public function ListBuckets()
		{
		    $timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->ListAllMyBuckets(
            		                                      array(  
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSOAPSignature("ListAllMyBuckets", $timestamp)
            		                                           )
            		                                     );
            		                                                 		                                                   
                if (!($res instanceof SoapFault))
                {
                    $retval = $res->ListAllMyBucketsResponse->Buckets->Bucket;
                    if ($retval instanceof stdClass)
                    	$retval = array($retval);
                	
                	return $retval;
                }
                else 
                {
                	throw new Exception($res->faultString ? $res->faultString : $res->getMessage(), E_ERROR);
                }
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
		}
		
		public function DownloadObject($object_path, $bucket_name, $out_filename = false)
		{
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "LibWebta AWS Client (http://webta.net)"
			                                    )
			                              );
						
			$timestamp = $this->GetTimestamp(true);
			
			$data_to_sign = array("GET", "", "", $timestamp, "/{$bucket_name}/{$object_path}");
			$signature = $this->GetRESTSignature($data_to_sign);
			
			$HttpRequest->setUrl("http://s3.amazonaws.com/{$bucket_name}/{$object_path}");
		    $HttpRequest->setMethod(constant("HTTP_METH_GET"));

		    $headers["Date"] = $timestamp;
            $headers["Authorization"] = "AWS {$this->AWSAccessKeyId}:{$signature}";
            
            $HttpRequest->addHeaders($headers);
			
			try 
            {
                $HttpRequest->send();
                
                $info = $HttpRequest->getResponseInfo();                
                if ($info['response_code'] == 200)
                {                	
                	if ($out_filename)
                		return (bool)@file_put_contents($out_filename, $HttpRequest->getResponseBody());
                	else
						return $HttpRequest->getResponseBody();
                }
                else
                {
                	$xml = @simplexml_load_string($HttpRequest->getResponseBody());
                	throw new Exception((string)$xml->Message);               	
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
		}
		
		public function CreateFolder($folder_path, $bucket_name)
		{
			return $this->CreateObject("{$folder_path}_\$folder\$", $bucket_name, null, "plain/text");
		}
		
		/**
		 * Create new object on S3 Bucket
		 *
		 * @param string $object_path
		 * @param string $bucket_name
		 * @param string $filename
		 * @param string $object_content_type
		 * @param string $object_permissions
		 * @return bool
		 */
		public function CreateObject($object_path, $bucket_name, $contents, $object_content_type, $object_permissions = "private", $is_contents_in_file = true)
		{
			if ($filename && !file_exists($filename))
				throw new Exception("{$filename} - no such file.");
			
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "LibWebta AWS Client (http://webta.net)"
			                                    )
			                              );
						
			$timestamp = $this->GetTimestamp(true);
			
			$data_to_sign = array("PUT", "", $object_content_type, $timestamp, "x-amz-acl:{$object_permissions}","/{$bucket_name}/{$object_path}");
			
			$signature = $this->GetRESTSignature($data_to_sign);
			
			$HttpRequest->setUrl("http://{$bucket_name}.s3.amazonaws.com/{$object_path}");
		    $HttpRequest->setMethod(constant("HTTP_METH_PUT"));
		   	 
		    $headers = array(
		    	"Content-type" => $object_content_type,
		    	"x-amz-acl"	   => $object_permissions,
		    	"Date"		   => $timestamp,
		    	"Authorization"=> "AWS {$this->AWSAccessKeyId}:{$signature}"
		    );
			                
            $HttpRequest->addHeaders($headers);
            
            if ($is_contents_in_file)
            	$HttpRequest->setPutFile($contents);
            else
            	$HttpRequest->setPutData($contents);
            
            try 
            {
                $HttpRequest->send();
                
                $info = $HttpRequest->getResponseInfo();
                
                if ($info['response_code'] == 200)
                	return true;
                else
                {
                	$xml = @simplexml_load_string($HttpRequest->getResponseBody());                	
                	throw new Exception((string)$xml->Message);
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
		}
		
		/**
		 * Delete bucket from S3
		 *
		 * @param string $bucket_name
		 * @return boolean
		 */
		public function DeleteBucket($bucket_name)
		{
			$timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->DeleteBucket(
            		                                      array(  
            		                                              "Bucket" => $bucket_name,
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSOAPSignature("DeleteBucket", $timestamp)
            		                                           )
            		                                     );

				if (!($res instanceof SoapFault))
                    return true;
                else 
                    throw new Exception($res->getMessage(), E_ERROR);
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
		}
		
		public function GetObjectMetaData($object_path, $bucket_name)
		{
			$timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->GetObject(
            		                                      array(  
            		                                              "Bucket" => $bucket_name,
            		                                              "Key"	   => $object_path,
            		                                      		  "GetMetadata" => true,
            		                                      		  "GetData"		=> false,
            		                                      		  "InlineData"  => false,
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSOAPSignature("GetObject", $timestamp)
            		                                           )
            		                                     );
				if (!($res instanceof SoapFault))
                    return $res->GetObjectResponse;
                else 
                    throw new Exception($res->getMessage(), E_ERROR);
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
		}
		
    	/**
		 * Delete folder from bucket
		 *
		 * @param string $object_path
		 * @param string $bucket_name
		 * @return bool
		 */
		public function DeleteFolder($object_path, $bucket_name)
		{
			return $this->DeleteObject("{$object_path}_\$folder\$", $bucket_name);
		}
		
		/**
		 * Delete object from bucket
		 *
		 * @param string $object_path
		 * @param string $bucket_name
		 * @return bool
		 */
		public function DeleteObject($object_path, $bucket_name)
		{
			$timestamp = $this->GetTimestamp();
		    
		    try 
		    {
    		    $res = $this->S3SoapClient->DeleteObject(
            		                                      array(  
            		                                              "Bucket" => $bucket_name,
            		                                              "Key"	   => $object_path,
            		                                              "AWSAccessKeyId" => $this->AWSAccessKeyId,
            		                                              "Timestamp"      => $timestamp,
            		                                              "Signature"      => $this->GetSOAPSignature("DeleteObject", $timestamp)
            		                                           )
            		                                     );

				if (!($res instanceof SoapFault))
                    return true;
                else 
                    throw new Exception($res->getMessage(), E_ERROR);
		    }
		    catch (SoapFault $e)
		    {
		        throw new Exception($e->faultString, E_ERROR);
		    }
		}
		
		/**
		 * The CreateBucket operation creates a bucket. Not every string is an acceptable bucket name.
		 *
		 * @param string $bucket_name
		 * @return string 
		 */
		public function CreateBucket($bucket_name, $region = 'us-east-1')
		{
			$HttpRequest = new HttpRequest();
			
			$HttpRequest->setOptions(array(    "redirect" => 10, 
			                                         "useragent" => "LibWebta AWS Client (http://webta.net)"
			                                    )
			                              );
						
			$timestamp = $this->GetTimestamp(true);
			
			switch($region)
			{
				case "us-east-1":
					$request = "";
					break;	

				case "us-west-2":
					$request = "<CreateBucketConfiguration><LocationConstraint>us-west-2</LocationConstraint></CreateBucketConfiguration>";
					break;
					
				case "us-west-1":
					$request = "<CreateBucketConfiguration><LocationConstraint>us-west-1</LocationConstraint></CreateBucketConfiguration>";
					break;
				
				case "ap-southeast-1":
					$request = "<CreateBucketConfiguration><LocationConstraint>ap-southeast-1</LocationConstraint></CreateBucketConfiguration>";
					break;
					
				case "ap-northeast-1":
					$request = "<CreateBucketConfiguration><LocationConstraint>ap-northeast-1</LocationConstraint></CreateBucketConfiguration>";
					break;
				case "sa-east-1":
					$request = "<CreateBucketConfiguration><LocationConstraint>sa-east-1</LocationConstraint></CreateBucketConfiguration>";
					break;
				case "eu-west-1":
					$request = "<CreateBucketConfiguration><LocationConstraint>EU</LocationConstraint></CreateBucketConfiguration>";
					break;
			}
			
			$data_to_sign = array("PUT", "", "", $timestamp, "/{$bucket_name}/");
			
			$signature = $this->GetRESTSignature($data_to_sign);
			
			$HttpRequest->setUrl("https://{$bucket_name}.s3.amazonaws.com/");
		    $HttpRequest->setMethod(constant("HTTP_METH_PUT"));
		   	 
		    $headers = array(
		    	"Content-length" => strlen($request),
		    	"Date"		   	 => $timestamp,
		    	"Authorization"	 => "AWS {$this->AWSAccessKeyId}:{$signature}"
		    );
			                
            $HttpRequest->addHeaders($headers);
            
            if ($request != '')
            	$HttpRequest->setPutData($request);
            
            try 
            {
               	$HttpRequest->send();
            	
            	$info = $HttpRequest->getResponseInfo();

                if ($info['response_code'] == 200)
                	return true;
                else
                {
                	if ($HttpRequest->getResponseBody())
                	{
                		$xml = @simplexml_load_string($HttpRequest->getResponseBody());                	
                		throw new Exception((string)$xml->Message);
                	}
                	else
                		throw new Exception(_("Cannot create S3 bucket at this time. Please try again later."));
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
		}
    }
?>
