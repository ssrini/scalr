<?php
	class Scalr_Service_Cloud_Aws_Iam_20100508_UploadServerCertificateResponse
	{
		public $ServerCertificateMetadata;
		
		public function __construct(SimpleXMLElement $response)
		{
			$this->ServerCertificateMetadata = new stdClass();
			
			foreach ((array)$response->UploadServerCertificateResult->ServerCertificateMetadata as $k => $v)
				$this->ServerCertificateMetadata->{$k} = (string)$v;
		}
	}