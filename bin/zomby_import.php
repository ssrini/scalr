<?php
	require_once('../src/prepend.inc.php');
	
	$server_id = Scalr::GenerateUID();
	
	$db->BeginTrans();
	
	try
	{
		$db->Execute("INSERT INTO servers SET
			`id`			= null,
			`server_id`		= ?,
			`farm_id`		= ?,
			`farm_roleid`	= ?,
			`client_id`		= ?,
			`role_id`		= ?,
			`platform`		= ?,
			`status`		= ?,
			`remote_ip`		= ?,
			`local_ip`		= ?,
			`dtadded`		= ?,
			`index`			= ?
		", array(
			$server_id,
			'3411',
			'10584',
			'902',
			'14522',
			SERVER_PLATFORMS::EC2,
			SERVER_STATUS::RUNNING,
			'184.73.120.27',
			'10.209.210.131',
			'2010-05-11 05:03:20',
			1
		));
		
		$props = array(
			EC2_SERVER_PROPERTIES::AMIID 			=> 'ami-17fb127e',
			EC2_SERVER_PROPERTIES::ARCHITECTURE 	=> 'i386',
			EC2_SERVER_PROPERTIES::AVAIL_ZONE 		=> 'us-east-1c',
			EC2_SERVER_PROPERTIES::DB_MYSQL_MASTER 	=> 0,
			EC2_SERVER_PROPERTIES::INSTANCE_ID		=> 'i-61eabb0a',
			EC2_SERVER_PROPERTIES::INSTANCE_TYPE	=> 'm1.small',
			EC2_SERVER_PROPERTIES::REGION			=> 'us-east-1',
			EC2_SERVER_PROPERTIES::SZR_VESION		=> '0.2.109' 
		);
		
		foreach ($props as $k=>$v)
		{
			$db->Execute("INSERT INTO server_properties SET
				`server_id`	= ?,
				`name`		= ?,
				`value`		= ?
			", array(
				$server_id, $k, $v
			));
		}
		
		$zones = DBDNSZone::loadByFarmId('3411');
		foreach ($zones as $DBDNSZone)
		{
			if (!$skip_status_check && ($DBDNSZone->status == DNS_ZONE_STATUS::PENDING_DELETE || $DBDNSZone->status == DNS_ZONE_STATUS::INACTIVE))
				continue;
			
			if (!$reset_all_system_records)
			{
				$DBDNSZone->updateSystemRecords($server_id);
				$DBDNSZone->save();
			}
			else
			{
				$DBDNSZone->save(true);
			}
		}
		
		$db->CommitTrans();
	}
	catch(Exception $e)
	{
		$db->RollbackTrans();
		
		var_dump($e->getMessage());
	}
?>