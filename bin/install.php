<?php

	$key = file_get_contents('/dev/urandom', null, null, 0, 512);
	if (! $key)
		throw new Exception("Null key generated");

	$key = substr(base64_encode($key), 0, 512);
	
	print $key;
	
	//file_put_contents(dirname(__FILE__)."/../etc/.cryptokey", $key);
	
	
	//ALTER TABLE  `scalr`.`elastic_ips` ADD INDEX  `server_id` (  `server_id` ( 36 ) )
	//ALTER TABLE  `scalr`.`ec2_ebs` ADD INDEX  `server_id` (  `server_id` ( 36 ) )
	//ALTER TABLE  `scalr`.`servers` ADD INDEX  `role_id` (  `role_id` )
	//ALTER TABLE  `scalr`.`dns_zones` ADD INDEX  `iszoneconfigmodified` (  `iszoneconfigmodified` )
	//ALTER TABLE  `scalr`.`bundle_tasks` ADD INDEX  `status` (  `status` ( 30 ) )
	//ALTER TABLE  `scalr`.`storage_snapshots` ADD INDEX  `farm_roleid` (  `farm_roleid` )
	//ALTER TABLE  `scalr`.`ebs_snaps_info` ADD INDEX  `snapid` (  `snapid` ( 50 ) )
	//ALTER TABLE  `scalr`.`ec2_ebs` ADD INDEX  `farm_roleid_index` (  `farm_roleid` ,  `server_index` )
	/*
	ALTER TABLE  `server_properties` ADD FOREIGN KEY (  `server_id` ) REFERENCES  `scalr`.`servers` (
	`server_id`
	) ON DELETE CASCADE ON UPDATE NO ACTION ;
	
	ALTER TABLE  `farm_role_scripts` DROP INDEX  `UniqueIndex` , ADD INDEX  `UniqueIndex` (  `scriptid` ,  `farmid` ,  `event_name` ,  `farm_roleid` )
	*/
	
	