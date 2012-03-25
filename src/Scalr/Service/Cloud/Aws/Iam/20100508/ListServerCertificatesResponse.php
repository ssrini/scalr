<?php
	class Scalr_Service_Cloud_Aws_Iam_20100508_ListServerCertificatesResponse
	{
		public $IsTruncated;
		public $ServerCertificateMetadataList;
		
		public function __construct(SimpleXMLElement $response)
		{
			$this->IsTruncated = ($response->ListServerCertificatesResult->IsTruncated == 'false') ? false : true;
			
			$this->ServerCertificateMetadataList = array();
			foreach ($response->ListServerCertificatesResult->ServerCertificateMetadataList->member as $item)
			{
				$itm = new stdClass();
				foreach ($item as $k=>$v)
					$itm->{$k} = (string)$v;
				
				$this->ServerCertificateMetadataList[] = $itm;
			}
		}
	}