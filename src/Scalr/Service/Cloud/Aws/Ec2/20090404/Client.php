<?php 
	abstract class Scalr_Service_Cloud_Aws_Ec2_20090404_Client extends Scalr_Service_Cloud_Aws_Transports_Query
	{
		function __construct()
		{
			$this->apiVersion = '2009-04-04';
			$this->uri = '/';
		}
		
		///
		// EBS
		///
		public function createVolume($size, $availabilityZone, $snapshotId=null)
		{
			$request_args = array(
				"Action" => "CreateVolume",
				"AvailabilityZone"	=> "kvm-cluster",
				"Size"	=> $size
			);
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_CreateVolumeResponse($response);
		}
		
		public function describeVolumes(array $volumes = null)
		{
			$request_args = array(
				"Action" => "DescribeVolumes", 
			);
			foreach ((array)$volumes as $i=>$n)
				$request_args['VolumeId.'.($i+1)] = $n;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeVolumesResponse($response);
		}
		
		
		///
		// Other
		///
		
		public function describeAvailabilityZones($zoneName = null)
		{
			$request_args = array(
				"Action" => "DescribeAvailabilityZones", 
			);
			
			if ($zoneName)
				$request_args['ZoneName'] = $zoneName;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeAvailabilityZonesResponse($response);
		}
		
		
		///
		// Instances 
		///
		
		/**
		 * 
		 * Enter description here ...
		 * @param string $instanceId
		 * @return string
		 */
		public function getConsoleOutput($instanceId)
		{
			$request_args = array(
				"Action" 		=> "GetConsoleOutput",
				"InstanceId"	=> $instanceId
			);
			
			$response = $this->Request("GET", $this->uri, $request_args);			
			return base64_decode((string)$response->output);
		}
		
		public function rebootInstances(array $instanceIds)
		{
			$request_args = array(
				"Action" => "RebootInstances", 
			);
			foreach ((array)$instanceIds as $i=>$n)
				$request_args['InstanceId.'.($i+1)] = $n;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param array $instanceIds
		 */
		public function terminateInstances(array $instanceIds)
		{
			$request_args = array(
				"Action" => "TerminateInstances", 
			);
			foreach ((array)$instanceIds as $i=>$n)
				$request_args['InstanceId.'.($i+1)] = $n;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			//TODO: Scalr_Service_Cloud_Aws_Ec2_20090404_TerminateInstancesResponse
			
			return true;
		}
		
		/**
		 * 
		 * Describe instances ...
		 * @param array $instanceIds
		 * @return Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeInstancesResponse
		 */
		public function describeInstances(array $instanceIds = array())
		{
			$request_args = array(
				"Action" => "DescribeInstances", 
			);
			foreach ((array)$instanceIds as $i=>$n)
				$request_args['InstanceId.'.($i+1)] = $n;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeInstancesResponse($response);
		}
		
		/**
		 * 
		 * Run new instance ...
		 * @param string $imageId
		 * @param string $instanceType
		 * @param string $keyName
		 * @param string $availZone
		 * @param array $securityGroup
		 * @param string $userData
		 * @param integer $minCount
		 * @param integer $maxCount
		 * @param string $kernelId
		 * @param string $ramdiskId
		 * @param boolean $monitoring
		 * @return Scalr_Service_Cloud_Aws_Ec2_20090404_RunInstancesResponse
		 */
		public function runInstances($imageId, $instanceType, $keyName = null, $availZone = null, $securityGroup = array(), $userData = "", 
			$minCount = 1, $maxCount = 1, $kernelId = null, $ramdiskId = null, $monitoring = false)
		{
			
			$request_args = array(
				"Action" 		=> "RunInstances",
				"ImageId"		=> $imageId,
				"MinCount"		=> $minCount,
				"MaxCount"		=> $maxCount,
				"InstanceType"	=> $instanceType
			);
			
			if ($availZone)
				$request_args['Placement.AvailabilityZone'] = $availZone;
			if ($keyName)
				$request_args['KeyName'] = $keyName;
			if ($kernelId)
				$request_args['KernelId']	= $kernelId;
			if ($ramdiskId)
				$request_args['RamdiskId']	= $ramdiskId;
			
			if (!empty($securityGroup))
			{
				$n = 0;
				foreach ((array)$securityGroup as $sg)
				{
					$n++;
					$request_args['SecurityGroup.'.$n]	= $sg;
				}
			}
			
			if ($userData)
			{				
				$request_args["UserData"]	= base64_encode($userData);
				$request_args["Version"]	= "1.0";
				$request_args["Encoding"]	= "base64";
			}
			
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_RunInstancesResponse($response);
		}
		
		
		///
		// Key pairs
		///
		
		public function deleteKeyPair($keyName)
		{
			$request_args = array(
				"Action" => "DeleteKeyPair",
				"KeyName"	=> $keyName 
			);
			
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		public function describeKeyPairs($keys = array())
		{
			$request_args = array(
				"Action" => "DescribeKeyPairs", 
			);
			foreach ((array)$keys as $i=>$n)
				$request_args['KeyName.'.($i+1)] = $n;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeKeyPairsResponse($response);
		}
		
		public function createKeyPair($keyName)
		{
			$request_args = array(
				"Action" => "CreateKeyPair",
				"KeyName"	=> $keyName 
			);
			
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_CreateKeyPairResponse($response);
		}
		
		
		///
		// Security groups
		///
		public function revokeSecurityGroupIngress($groupName, $ipProtocol = null, $fromPort = null, $toPort = null, $cidrIp = null, $sourceSecurityGroupName = null, $sourceSecurityGroupOwnerId = null)
		{
			$request_args = array(
				"Action" => "RevokeSecurityGroupIngress",
				"GroupName"	=> $groupName 
			);
			
			if ($cidrIp)
			{
				$request_args['CidrIp'] = $cidrIp;
				$request_args['IpProtocol'] = $ipProtocol;
				$request_args['FromPort'] = $fromPort;
				$request_args['ToPort'] = $toPort;
			}
			else
			{
				$request_args['SourceSecurityGroupName'] = $sourceSecurityGroupName;
				$request_args['SourceSecurityGroupOwnerId'] = $sourceSecurityGroupOwnerId;
			}
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		
		public function authorizeSecurityGroupIngress($groupName, $ipProtocol = null, $fromPort = null, $toPort = null, $cidrIp = null, $sourceSecurityGroupName = null, $sourceSecurityGroupOwnerId = null)
		{
			$request_args = array(
				"Action" => "AuthorizeSecurityGroupIngress",
				"GroupName"	=> $groupName 
			);
			
			if ($cidrIp)
			{
				$request_args['CidrIp'] = $cidrIp;
				$request_args['IpProtocol'] = $ipProtocol;
				$request_args['FromPort'] = $fromPort;
				$request_args['ToPort'] = $toPort;
			}
			else
			{
				$request_args['SourceSecurityGroupName'] = $sourceSecurityGroupName;
				$request_args['SourceSecurityGroupOwnerId'] = $sourceSecurityGroupOwnerId;
			}
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		public function describeSecurityGroups($groups = array())
		{
			$request_args = array(
				"Action" => "DescribeSecurityGroups", 
			);
			foreach ((array)$groups as $i=>$n)
				$request_args['GroupName.'.($i+1)] = $n;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeSecurityGroupsResponse($response);
		}
		
		public function createSecurityGroup($name, $description)
		{
			$request_args = array(
				"Action" => "CreateSecurityGroup",
				"GroupName" => $name,
				"GroupDescription" => $description 
			);
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		
		///
		// Elastic IP addresses
		///
		
		public function releaseAddress($ip)
		{
			$request_args = array(
				"Action" => "ReleaseAddress",
				"PublicIp" => $ip 
			);
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		public function describeAddresses($ips = array())
		{
			$request_args = array(
				"Action" => "DescribeAddresses", 
			);
			foreach ((array)$ips as $i=>$n)
				$request_args['PublicIp.'.($i+1)] = $n;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeAddressesResponse($response);
		}
		
		public function allocateAddress()
		{
			$request_args = array(
				"Action" => "AllocateAddress", 
			);
				
			$response = $this->Request("GET", $this->uri, $request_args);
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_AllocateAddressResponse($response);
		}
		
		
		///
		// Images
		///
		
		public function deregisterImage($imageId)
		{
			$request_args = array(
				"Action" => "DeregisterImage",
				"ImageId" => $imageId 
			);
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return ((string)$response->return == 'true') ? true : false;
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param unknown_type $executableBy
		 * @param unknown_type $imageId
		 * @param unknown_type $owner
		 * @return Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeImagesResponse
		 */
		public function describeImages($executableBy = array(), $imageId = array(), $owner = array()) 
		{
			$request_args = array(
				"Action" => "DescribeImages", 
			);
			foreach ((array)$executableBy as $i=>$n)
				$request_args['ExecutableBy.'.($i+1)] = $n;
			foreach ((array)$imageId as $i=>$n)
				$request_args['ImageId.'.($i+1)] = $n;
			foreach ((array)$owner as $i=>$n)
				$request_args['Owner.'.($i+1)] = $n;
				
			$response = $this->Request("GET", $this->uri, $request_args);
			
			return new Scalr_Service_Cloud_Aws_Ec2_20090404_DescribeImagesResponse($response);
		}
	}

?>