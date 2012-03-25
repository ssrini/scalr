<?php

class Scalr_UI_Controller_Tools_Aws_S3 extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		$enabledPlatforms = $this->getEnvironment()->getEnabledPlatforms();
		if (!in_array(SERVER_PLATFORMS::EC2, $enabledPlatforms))
			throw new Exception("You need to enable EC2 platform for current environment");

		return true;
	}
	
	public function defaultAction()
	{
		$this->manageBucketsAction();
	}
	
	public function manageBucketsAction()
	{
		$this->response->page('ui/tools/aws/s3/buckets.js', array(
			'locations'	=> self::loadController('Platforms')->getCloudLocations(SERVER_PLATFORMS::EC2, false)
		));
	}
	
	public function xListBucketsAction()
	{
		
		// Create Amazon s3 client object
	    $AmazonS3 = new AmazonS3(
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
	    );
        $AmazonCloudFront = new AmazonCloudFront(
        	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
        );

    	//Create cloundfront object
    	$distributions = $AmazonCloudFront->ListDistributions(); 	    	

		// Get list of all user buckets   
	    $buckets = array();
	          	    
	    foreach ($AmazonS3->ListBuckets() as $bucket)
	    {
			if (!$distributions[$bucket->Name]) {       
    			$info = array(
					"name" => $bucket->Name 
				);
			}
			else {     
				$info = array(
					"name" 	=> $bucket->Name,
					"cfid"	=> $distributions[$bucket->Name]['ID'],
					"cfurl"	=> $distributions[$bucket->Name]['DomainName'],
					"cname"	=> $distributions[$bucket->Name]['CNAME'],
					"status"=> $distributions[$bucket->Name]['Status'],
					"enabled"=> $distributions[$bucket->Name]['Enabled']
				);
			}
			
			$c = explode("-", $info['name']);
			if ($c[0] == 'farm') {
				$hash = $c[1];
				$farm = $this->db->GetRow("SELECT id, name FROM farms WHERE hash=? AND env_id = ?", array($hash, $this->environment->id));
				if ($farm) {
					$info['farmId'] = $farm['id'];
					$info['farmName'] = $farm['name'];
				}
			}
			
			$buckets[] = $info;
	    }
	    
	    $response = $this->buildResponseFromData($buckets, array('name', 'farmName'));

		$this->response->data($response);
	}

	public function xCreateBucketAction ()
	{
		$amazonS3 = new AmazonS3(
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
	    );
		$response = $amazonS3->CreateBucket($this->getParam('bucketName'), $this->getParam('location'));
		
		$this->response->success('Bucket successfully created');
	}

	public function xDeleteBucketAction ()
	{
		$amazonS3 = new AmazonS3(
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
	    );
		$response = $amazonS3->DeleteBucket($this->getParam('bucketName'));
		
		$this->response->success('Bucket successfully deleted');
	}
	
	public function manageDistributionAction ()
	{
		$this->response->page('ui/tools/aws/s3/distribution.js');
	}
	
	public function xCreateDistributionAction ()
	{
		$amazonCloudFront = new AmazonCloudFront(
        	$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
	    	$this->getEnvironment()->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
        );
	    $distributionConfig = new DistributionConfig();	    	
	    	if($this->getParam('localDomain') && $this->getParam('zone'))
	    		$distributionConfig->CNAME = $this->getParam('localDomain').'.'.$this->getParam('zone');
	    	else if($this->getParam('remoteDomain'))
	    		$distributionConfig->CNAME = $this->getParam('remoteDomain');
	    			 	
    		$distributionConfig->Comment 			= $this->getParam('comment');
    		$distributionConfig->Enabled 			= true;
    		$distributionConfig->CallerReference 	= date("YmdHis");
    		$distributionConfig->Origin 			= $this->getParam('bucketName').".s3.amazonaws.com";
				
		$result = $amazonCloudFront->CreateDistribution($distributionConfig);
	    		   			
		$this->db->Execute("INSERT INTO distributions SET
				cfid	= ?,
				cfurl	= ?,
				cname	= ?,
				zone	= ?,
				bucket	= ?,
				clientid= ?
			", 
				array($result['ID'], 
				$result['DomainName'], 
				$this->getParam('localDomain') ? $this->getParam('localDomain') : $distributionConfig->CNAME, 
				$this->getParam('zone')? $this->getParam('zone') : $distributionConfig->CNAME, 
				$this->getParam('bucketName'), 
				$this->user->getAccountId()
				)
			);

	/*	$zoneinfo = $this->db->GetRow("SELECT * FROM dns_zones WHERE zone_name=? AND client_id=?", 
			array(
			$this->getParam('zone')? $this->getParam('zone') : $distributionConfig->CNAME,
			$this->user->getAccountId()
		));
		if ($zoneinfo)
		{
			$this->db->Execute("INSERT INTO dns_zone_records SET 
				zone_id	= ?,
				type	= ?,
				ttl		= ?,
				name	= ?,
				value	= ?,
				issystem= ?
			", array($zoneinfo['id'], 'CNAME', 14400, $distributionConfig->CNAME, $result['DomainName'], 0));
		}   */

		$this->response->success("Distribution successfully created");
	}
	
	public function xUpdateDistributionAction ()
	{
		$AmazonCloudFront = new AmazonCloudFront(
        	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
        );
		
		$info = $AmazonCloudFront->GetDistributionConfig($this->getParam('id'));
		
		$distributionConfig = new DistributionConfig();
		$distributionConfig->CallerReference 	= $info['CallerReference'];
		$distributionConfig->CNAME 				= $info['CNAME'];
		$distributionConfig->Comment 			= $info['Comment'];
		$distributionConfig->Enabled 			= ($this->getParam('enabled')=='true') ? true: false;
		$distributionConfig->Origin 			= $info['Origin'];
		
		
		$E_TAG = $AmazonCloudFront->SetDistributionConfig($this->getParam('id'), $distributionConfig, $info['Etag']);
		
		$this->response->success("Distribution successfully updated");
	}
	
	public function xDeleteDistributionAction ()
	{
		$AmazonCloudFront = new AmazonCloudFront(
        	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY), 
	    	$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY)
        );
		
		$result = $AmazonCloudFront->DeleteDistribution($this->getParam('id'));
    			
		$info = $this->db->GetRow("SELECT * FROM distributions WHERE cfid=?", array($this->getParam('id')));
		
		if ($info)
		{
			$this->db->Execute("DELETE FROM distributions WHERE cfid=?", array($this->getParam('id')));
			
			// Remove CNAME from DNS zone
		/*	$zoneinfo = $this->db->GetRow("SELECT * FROM dns_zones WHERE zone_name=? AND client_id=?",
				array($info['zone'], $this->user->getAccountId())
			);
			
			if ($zoneinfo)
			{
				$this->db->Execute("DELETE FROM dns_zone_records WHERE 
					zone_id	= ? AND
					type	= ? AND
					name	= ? AND
					value	= ?
				", array($zoneinfo['id'], 'CNAME', $this->getParam('cname'), $this->getParam('cfurl')));
			}*/
		}
		
		$this->response->success("Distribution successfully removed");
	}
	
	public function xListZonesAction()
	{
		$zones = $this->db->GetAll("SELECT zone_name FROM dns_zones WHERE status!=? AND env_id=?", 
			array(DNS_ZONE_STATUS::PENDING_DELETE,
			$this->getEnvironmentId())
		);
		$this->response->data(array('data'=>$zones));
	}
}
