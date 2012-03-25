<?php
	abstract class Scalr_Service_Cloud_Aws_Iam_20100508_Client extends Scalr_Service_Cloud_Aws_Transports_Query
	{
		function __construct()
		{
			$this->apiVersion = '2010-05-08';
			$this->uri = '/';
		}
		
		public function uploadServerCertificate($certificateBody, $privateKey, $serverCertificateName, $certificateChain=null, $path = "/")
		{
			$request_args = array(
				"Action" 			=> "UploadServerCertificate",
				"CertificateBody" 	=> $certificateBody,
				"PrivateKey"		=> $privateKey,
				"ServerCertificateName" => $serverCertificateName
			);
			if ($certificateChain)
				$request_args['CertificateChain'] = $certificateChain;
				
			//if ($path)
			//	$request_args['Path'] = $path;
				
			$response = $this->Request("POST", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Iam_20100508_UploadServerCertificateResponse($response);
		}
		
		public function listServerCertificates($pathPrefix=null, $marker=null, $maxItems=null)
		{
			$request_args = array(
				"Action" => "ListServerCertificates"
			);
			if ($pathPrefix)
				$request_args['PathPrefix'] = $pathPrefix;
				
			if ($marker)
				$request_args['Marker'] = $marker;
				
			if ($maxItems)
				$request_args['MaxItems'] = $maxItems;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			return new Scalr_Service_Cloud_Aws_Iam_20100508_ListServerCertificatesResponse($response);
		}
	}