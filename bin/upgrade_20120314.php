<?php
	define("NO_TEMPLATES",1);
		 
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrUpdate = new Update20120222();
	$ScalrUpdate->Run();
	
	class Update20120222
	{
		function Run()
		{
			global $db;
			
			$time = microtime(true);
			
			$db->Execute("ALTER TABLE  `clients` DROP  `aws_accesskeyid` ,
				DROP  `aws_accountid` ,
				DROP  `aws_accesskey` ,
				DROP  `farms_limit` ,
				DROP  `aws_private_key_enc` ,
				DROP  `aws_certificate_enc` ;
			");
			
			$db->Execute("ALTER TABLE  `clients` DROP  `scalr_api_keyid` , DROP  `scalr_api_key` ;");
			
			$db->Execute("ALTER TABLE  `clients` DROP  `email` , DROP  `password` ;");
			
			$db->Execute("ALTER TABLE  `comments` DROP  `clientid` ,
				DROP  `object_owner` ,
				DROP  `dtcreated` ,
				DROP  `object_type` ,
				DROP  `comment` ,
				DROP  `objectid` ,
				DROP  `isprivate` ;
			");
			
			$db->Execute("ALTER TABLE  `comments` ADD  `env_id` INT( 11 ) NOT NULL ,
				ADD  `rule` VARCHAR( 255 ) NOT NULL ,
				ADD  `sg_name` VARCHAR( 255 ) NOT NULL ,
				ADD  `comment` VARCHAR( 255 ) NOT NULL
			");
			
			$db->Execute("ALTER TABLE  `comments` ADD UNIQUE  `main` (  `env_id` ,  `sg_name` ( 255 ) ,  `rule` ( 255 ) )");

			print "Done.\n";
			
			$t = round(microtime(true)-$time, 2);
			
			print "Upgrade process took {$t} seconds\n\n\n";
		}
		
		function migrate()
		{
			
		}
	}
?>