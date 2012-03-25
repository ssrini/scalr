<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$regions = array('us-east-1', 'us-west-1', 'eu-west-1');
	
	$clients = $db->GetAll("SELECT id FROM clients WHERE isactive='1'");
	foreach ($clients as $client)
	{
		$Client = Client::Load($client['id']);
		$client_instances = array();
		
		foreach ($regions as $region)
		{
			if (!$client_instances[$region])
			{
				try
				{
					$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($region));
					$AmazonEC2Client->SetAuthKeys($Client->AWSPrivateKey, $Client->AWSCertificate);
				
					$client_instances[$region] = $AmazonEC2Client->DescribeInstances();
				}
				catch(Exception $e)
				{
					continue;
				}
			}
			
			$instances = $client_instances[$region]->reservationSet->item;
			
			if (!is_array($instances))
				$instances = array($instances);
			
			foreach ($instances as $instance)
			{
				$groups = $instance->groupSet->item;
				
				if (!is_array($groups))
					$groups = array($groups);
					
				$igroups = "";
				foreach ($groups as $g)
				{
					$igroups .= "{$g->groupId} ";
				}
				
				$key_name = $instance->instancesSet->item->keyName;
				$state = $instance->instancesSet->item->instanceState->name;
				$dtlaunched = (string)$instance->instancesSet->item->launchTime;
				$ipaddress = (string)$instance->instancesSet->item->ipAddress;
				if (stristr($key_name, "FARM-"))
				{					
					$instance_id = $instance->instancesSet->item->instanceId;
					$farm_id = str_replace("FARM-", "", $key_name);
					
					$farminfo = $db->GetRow("SELECT name,status FROM farms WHERE id=?", array($farm_id));
					if (!$farminfo)
					{
						//print "FarmID: {$farm_id}, InstanceID: {$instance_id} ({$dtlaunched}) ({$igroups}) [NO_FARM]\n";
					}
					elseif ($farminfo['status'] != FARM_STATUS::RUNNING)
					{
						if (stristr($igroups, "scalr."))
							print "FarmID: {$farm_id}, InstanceID: {$instance_id} ({$ipaddress}) ({$dtlaunched}) ({$igroups}) [FARM_TERMINATED]\n";
					}
					else
					{
						try
						{
							$DBServer = DBServer::LoadByPropertyValue(EC2_SERVER_PROPERTIES::INSTANCE_ID, $instance_id);
						}
						catch(Exception $e)
						{	
							if (stristr($igroups, "scalr."))
								print "FarmID: {$farm_id}, InstanceID: {$instance_id} ({$ipaddress}) ({$dtlaunched}) ({$igroups}) [NO_SERVER]\n";
						}
					}
				}
			}
		}
	}
	
	/*
	
	$eips = $db->Execute("SELECT * FROM elastic_ips");
	while ($eip = $eips->FetchRow())
	{
		$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($eip['farmid']));
		
		$client = Client::Load($farminfo['clientid']);
			
		if ($client->AWSCertificate && $client->AWSPrivateKey)
		{
			try
			{
				$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
				$AmazonEC2Client->SetAuthKeys($client->AWSPrivateKey, $client->AWSCertificate);
				
				
				$DescribeAddressType = new DescribeAddressesType();
				$DescribeAddressType->AddAddress($eip['ipaddress']);
				$info = $AmazonEC2Client->DescribeAddresses($DescribeAddressType);
				print "{$eip['ipaddress']}: OK\n";
			}
			catch(Exception $e)
			{
				print "{$eip['ipaddress']}: Error ({$e->getMessage()})\n";
				if (stristr($e->getMessage(), "not found"))
				{
					$db->Execute("DELETE FROM elastic_ips WHERE id=?", array($eip['id']));
				}
				continue;
			}
		}
	}
	
	/*s
	$ebss = $db->Execute("SELECT * FROM farm_ebs");
	while ($ebs = $ebss->FetchRow())
	{
		if ($ebs['farmid'])
		{
			$farminfo = $db->GetRow("SELECT * FROM farms WHERE id=?", array($ebs['farmid']));
			
			if ($farminfo)
			{
				try
				{
					$client = Client::Load($farminfo['clientid']);
						
					if ($client->AWSCertificate && $client->AWSPrivateKey)
					{
					
						$AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farminfo['region']));
						$AmazonEC2Client->SetAuthKeys($client->AWSPrivateKey, $client->AWSCertificate);
						
						$info = $AmazonEC2Client->DescribeVolumes($ebs['volumeid']);
						print "{$ebs['volumeid']}: OK\n";
					}
				}
				catch(Exception $e)
				{
					print "{$ebs['volumeid']}: Error ({$e->getMessage()})\n";
					if (stristr($e->getMessage(), "does not exist") || stristr($e->getMessage(), " not found in database"))
					{
						$db->Execute("DELETE FROM farm_ebs WHERE id=?", array($ebs['id']));
					}
					continue;
				}
			}
		}
	}
	*/
?>