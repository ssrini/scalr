<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrMigrate = new MigrateDB(130, 1494, true);
	$ScalrMigrate->Run(array("user" => "root", "password" => "fOwPrpuqDTCc0spcFaqe", "host" => "174.129.15.82", "name" => "scalr"), "&7h3bhd69g3vbd!dfdsA");
	
	class MigrateDB
	{
		private $ClientID;
		private $NewClientID;
		private $RecreateFarms;
		
		function __construct($clientid, $new_clientid, $recreate_farms = false)
		{
			$this->ClientID = $clientid;
			$this->NewClientID = $new_clientid;
			$this->RecreateFarms = $recreate_farms;
		}
		
		function Run($dbinfo, $admin_pwd)
		{
			$this->Migrate($dbinfo, $admin_pwd);
		}
		
		function Migrate($dbinfo, $admin_pwd)
		{
			global $db, $Crypto;

			$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
			
			try
			{
				$db2 = &NewADOConnection("mysqli://{$dbinfo['user']}:{$dbinfo['password']}@{$dbinfo['host']}/{$dbinfo['name']}");
				$db2->debug = false;
                $db2->cacheSecs = 0;
                $db2->SetFetchMode(ADODB_FETCH_ASSOC); 
			}
			catch(Exception $e)
			{		
				die("Service is temporary not available. Please try again in a minute. ({$e->getMessage()})");
			}
			
			
			
			$clients = $db2->Execute("SELECT * FROM clients WHERE id = ?", array($this->ClientID));
			while ($client = $clients->FetchRow())
			{
				$client['aws_accesskeyid'] = $Crypto->Decrypt($client['aws_accesskeyid'], $admin_pwd);
				$client['aws_accesskey'] = str_replace("'", "", $Crypto->Decrypt($client['aws_accesskey'], $admin_pwd));
				$client['aws_private_key_enc'] = $Crypto->Decrypt($client['aws_private_key_enc'], $admin_pwd);
				$client['aws_certificate_enc'] = $Crypto->Decrypt($client['aws_certificate_enc'], $admin_pwd);
								
				print "Migrating client '{$client['email']}'...\n";
				
				// Migrate clients
				try
                {
        		    $db->BeginTrans();
					
	    			print "Setting API keys...\n";
	    			
	    			// Set client API keys
	    			$keys = Client::GenerateScalrAPIKeys();
					$db->Execute("UPDATE clients SET scalr_api_keyid=?, scalr_api_key =? WHERE id=?",
						array($keys['id'], $keys['key'], $this->NewClientID)
					);
					
					print "Migrating client scripts...\n";
					$scripts = $db2->GetAll("SELECT * FROM scripts WHERE clientid=?", array($client['id']));
					$scripts_table = array();
					foreach ($scripts as $script)
					{
						$db->Execute("INSERT INTO scripts SET
							name		= ?,
							description	= ?,
							origin		= ?,
							dtadded		= ?,
							issync		= ?,
							clientid	= ?,
							approval_state	= ?
						", array(
							$script['name'],
							$script['description'],
							$script['origin'],
							$script['dtadded'],
							$script['issync'],
							$this->NewClientID,
							$script['approval_state']
						));
						
						$sid = $db->Insert_ID();
						
						$revisions = $db2->GetAll("SELECT * FROM script_revisions WHERE scriptid=?", array($script['id']));
						foreach ($revisions as $revision)
						{
							$db->Execute("INSERT INTO script_revisions SET
								scriptid	= ?,
								revision	= ?,
								script		= ?,
								dtcreated	= ?,
								approval_state	= ?
							", array(
								$sid,
								$revision['revision'],
								$revision['script'],
								$revision['dtcreated'],
								$revision['approval_state']
							));
						}
						
						$scripts_table[$script['id']] = $sid;
					}
					
					
					print "Migrating client roles...\n";
					
					// Migrate AMI Roles
					$roles = $db2->Execute("SELECT * FROM roles WHERE clientid=? AND iscompleted='1'",
						array($client['id'])
					);
					while ($role = $roles->FetchRow())
					{
						$db->Execute("INSERT INTO roles SET
							ami_id 		= ?,
							name   		= ?,
							roletype	= ?,
							clientid	= ?,
							iscompleted	= '1',
							comments	= ?,
							dtbuilt		= ?,
							description	= ?,
							default_minLA	= '2',
							default_maxLA	= '5',
							alias		= ?,
							instance_type	= ?,
							architecture	= ?,
							isstable	= '1',
							prototype_role	= ?,
							ismasterbundle	= ?,
							region		= ?
						", array(
							$role['ami_id'], $role['name'], $role['roletype'], $this->NewClientID, $role['comments'],
							$role['dtbuilt'], $role['description'], $role['alias'], $role['instance_type'],
							$role['architecture'], $role['prototype_role'], $role['ismasterbundle'], $role['region']
						));
						
						$roleid = $db->Insert_ID();
						
						$rules = $db2->GetAll("SELECT * FROM security_rules WHERE roleid=?", array($role['id']));
						foreach ($rules as $rule)
						{
							$db->Execute("INSERT INTO security_rules SET roleid=?, rule=?",
								array($roleid, $rule['rule'])
							);
						}
					}
					
					print "Migrating client default DNS records...\n";
					
					// Migrate default records
					$records = $db2->GetAll("SELECT * FROM default_records WHERE clientid=?", array($client['id']));
					foreach ($records as $record)
					{
						$db->Execute("INSERT INTO default_records SET clientid=?, rtype=?, ttl=?, rpriority=?, rvalue=?, rkey=?",
							array($this->NewClientID, $record['rtype'], $record['ttl'], $record['rpriority'], $record['rvalue'], $record['rkey'])
						);
					}
					
					print "Migrating client farms...\n";
					
					$farms = $db2->GetAll("SELECT * FROM farms WHERE clientid=?", array(
						$client['id']
					));
					
					foreach ($farms as $farm)
					{
						print "Migrating farm #{$farm['id']}...\n";
						
						$chk = $db->GetOne("SELECT id FROM farms WHERE id=?", array($farmid));
                        if (!$chk)
                        {
							print "Farm not exists on production. Copying...\n";
                        	
                        	$db->Execute("INSERT INTO farms SET
								id				= ?,
								clientid		= ?,
								name			= ?,
								iscompleted		= '0',
								hash			= ?,
								dtadded			= ?,
								status			= ?,
								dtlaunched		= ?,
								term_on_sync_fail = ?,
								region			= ?
							", 
							array(
								$farm['id'],
								$this->NewClientID,
								$farm['name'],
								$farm['hash'],
								$farm['dtadded'],
								FARM_STATUS::TERMINATED,
								$farm['dtlaunched'],
								$farm['term_on_sync_fail'],
								$farm['region'],
							));
							
							$farmid = $farm['id'];
                        }
                        else
                        {
                        	print "Farm exists on production... Cloning...\n";
                        	
                        	$db->Execute("INSERT INTO farms SET
								id				= null,
								clientid		= ?,
								name			= ?,
								iscompleted		= '0',
								hash			= ?,
								dtadded			= ?,
								status			= ?,
								dtlaunched		= ?,
								term_on_sync_fail = ?,
								region			= ?
							", 
							array(
								$this->NewClientID,
								$farm['name'],
								$farm['hash'],
								$farm['dtadded'],
								FARM_STATUS::TERMINATED,
								$farm['dtlaunched'],
								$farm['term_on_sync_fail'],
								$farm['region'],
							));
							
							$farmid = $db->Insert_ID();
							
							$bucket_name = "farm-{$farmid}-{$client['aws_accountid']}";
							
	                        $AmazonS3 = new AmazonS3($client['aws_accesskeyid'], $client['aws_accesskey']);
		                    $buckets = $AmazonS3->ListBuckets();
		                    $create_bucket = true;
		                    foreach ($buckets as $bucket)
		                    {
		                        if ($bucket->Name == $bucket_name)
		                        {
		                           $create_bucket = false;
		                           break;
		                        }
		                    }
		                    
		                    if ($create_bucket)
		                    {
		                       	print "Creating bucket...";
		                    	
		                    	if ($AmazonS3->CreateBucket($bucket_name, $farm['region']))
		                    		print "OK\n";
		                    	else
		                    		print "ERROR\n";
		                    }
							
                        }
							
						
						$DBFarm =  new DBFarm($farmid);
						
						$farm_settings = $db2->GetAll("SELECT * FROM farm_settings WHERE farmid=?", array($farm['id']));
						foreach ($farm_settings as $farm_setting)
							$DBFarm->SetSetting($farm_setting['name'], $farm_setting['value']);

							
                            
                        print "Migrating farm_amis...\n";
                        $farm_amis = $db2->GetAll("SELECT * FROM farm_roles WHERE farmid = ?", 
                        	array($farm['id'])
                        );
                        foreach ($farm_amis as $farm_ami)
                        {
                        	$db->Execute("INSERT INTO farm_roles SET
                        		farmid	= ?,
                        		ami_id	= ?,
                        		replace_to_ami	= '',
                        		dtlastsync		= ?,
                        		reboot_timeout	= ?,
                        		launch_timeout	= ?,
                        		status_timeout	= ?
                        	", array(
                        		$farmid,
                        		$farm_ami['ami_id'],
                        		$farm_ami['dtlastsync'],
                        		$farm_ami['reboot_timeout'],
                        		$farm_ami['launch_timeout'],
                        		$farm_ami['status_timeout']
                        	));
                        	$farmroleid = $db->Insert_ID();
                        	
                        	$DBFarmRole = new DBFarmRole($farmroleid);
                        	
                        	/******/
                        	$settings = $db2->GetAll("SELECT * FROM farm_role_settings WHERE farm_roleid = ?", array($farm_ami['id']));
                        	foreach ($settings as $setting)
                        	{
                        		$DBFarmRole->SetSetting($setting['name'], $setting['value']);
                        	}
							
                        	$options = $db2->GetAll("SELECT * FROM farm_role_options WHERE farm_roleid = ?", array($farm_ami['id']));
                        	foreach ($options as $option)
                        	{
                        		$db->Execute("INSERT INTO farm_role_options SET
                        			farmid 		= ?,
                        			name		= ?,
                        			value		= ?,
                        			hash		= ?,
                        			farm_roleid	= ?
                        		", array(
                        			$farmid,
                        			$option['name'],
                        			$option['value'],
                        			$option['hash'],
                        			$farmroleid
                        		));
                        	}
                        	
                        	$scripts = $db2->GetAll("SELECT * FROM farm_role_scripts WHERE farm_roleid = ?", array($farm_ami['id']));
                        	foreach ($scripts as $rscript)
                        	{
                        		$db->Execute("INSERT INTO farm_role_scripts SET
                        			scriptid	= ?,
                        			farmid 		= ?,
                        			ami_id		= ?,
                        			params		= ?,
                        			event_name	= ?,
                        			target		= ?,
                        			version		= ?,
                        			timeout		= ?,
                        			issync		= ?,
                        			ismenuitem	= ?,
                        			order_index	= ?,
                        			farm_roleid	= ?
                        		", array(
                        			$scripts_table[$rscript['scriptid']],
                        			$farmid,
                        			$rscript['ami_id'],
                        			$rscript['params'],
                        			$rscript['event_name'],
                        			$rscript['target'],
                        			$rscript['version'],
                        			$rscript['timeout'],
                        			$rscript['issync'],
                        			$rscript['ismenuitem'],
                        			$rscript['order_index'],
                        			$farmroleid
                        		));
                        	}
                        }
                        
						print "Migrating ips...\n";
                        $ips = $db2->GetAll("SELECT * FROM elastic_ips WHERE farmid=?", array($farm['id']));
                        foreach ($ips as $ip)
                        {
                        	$db->Execute("INSERT INTO elastic_ips SET
                        		farmid				= ?,
                        		role_name			= ?,
                        		ipaddress			= ?,
                        		state				= ?,
                        		instance_id			= ?,
                        		clientid			= ?,
                        		instance_index		= ?,
                        		farm_roleid			= ?
                        	", array(
                        		$farmid,
                        		$ip['role_name'],
                        		$ip['ipaddress'],
                        		$ip['state'],
                        		$ip['instance_id'],
                        		$this->NewClientID,
                        		$ip['instance_index'],
                        		$ip['farm_roleid']
                        	));
                        }
                        
                        print "Migrating ebs...\n";
                        $ebss = $db2->GetAll("SELECT * FROM farm_ebs WHERE farmid=?", array($farm['id']));
                        foreach ($ebss as $ebs)
                        {
                        	$db->Execute("INSERT INTO farm_ebs SET
                        		farmid				= ?,
                        		role_name			= ?,
                        		volumeid			= ?,
                        		state				= ?,
                        		instance_id			= ?,
                        		avail_zone			= ?,
                        		device				= ?,
                        		isfsexists			= ?,
                        		instance_index		= ?,
                        		ebs_arrayid			= ?,
                        		ismanual			= ?,
                        		ebs_array_part		= ?,
                        		region				= ?,
                        		mount				= ?,
                        		mountpoint			= ?,
                        		farm_roleid			= ?
                        	", array(
                        		$farmid,
                        		$ebs['role_name'],
                        		$ebs['volumeid'],
                        		$ebs['state'],
                        		$ebs['instance_id'],
                        		$ebs['avail_zone'],
                        		$ebs['device'],
                        		$ebs['isfsexists'],
                        		$ebs['instance_index'],
                        		$ebs['ebs_arrayid'],
                        		$ebs['ismanual'],
                        		$ebs['ebs_array_part'],
                        		$ebs['region'],
                        		$ebs['mount'],
                        		$ebs['mountpoint'],
                        		$ebs['farm_roleid']
                        	));
                        }
                        
                        print "Migrating vhosts...\n";
                        $vhosts = $db2->GetAll("SELECT * FROM vhosts WHERE farmid=?", array($farm['id']));
                        foreach ($vhosts as $vhost)
                        {
                        	$db->Execute("INSERT INTO vhosts SET
                        		name				= ?,
                        		document_root_dir	= ?,
                        		server_admin		= ?,
                        		issslenabled		= ?,
                        		farmid				= ?,
                        		logs_dir			= ?,
                        		aliases				= ?,
                        		role_name			= ?
                        	", array(
                        		$vhost['name'],
                        		$vhost['document_root_dir'],
                        		$vhost['server_admin'],
                        		$vhost['issslenabled'],
                        		$farmid,
                        		$vhost['logs_dir'],
                        		$vhost['aliases'],
                        		$vhost['role_name']
                        	));
                        }
                        
                        print "Migrating zones... \n";
                        $zones = $db2->GetAll("SELECT * FROM zones WHERE farmid=?", array($farm['id']));
                        foreach ($zones as $zone)
                        {
                        	$db->Execute("replace into zones (`zone`, `soa_owner`, `soa_ttl`, `soa_parent`, 
			    			`soa_serial`, `soa_refresh`, `soa_retry`, `soa_expire`, `min_ttl`, `dtupdated`, 
			    			`farmid`, `ami_id`, `clientid`, `role_name`, `status`)
							values (?,?,?,?,?,?,?,?,?,NOW(), ?, ?, ?, ?, ?)", 
							array(
								$zone['zone'], 
								"admin.scalr.com", 
								CONFIG::$DEF_SOA_TTL, 
								CONFIG::$DEF_SOA_PARENT, 
								date("Ymd")."01",
								CONFIG::$DEF_SOA_REFRESH,
								CONFIG::$DEF_SOA_RETRY, 
								$zone['soa_expire'],
								CONFIG::$DEF_SOA_MINTTL, 
								$farmid, 
								$zone['ami_id'], 
								$this->NewClientID, 
								$zone['role_name'], 
								ZONE_STATUS::PENDING)
							);
							$zoneid = $db->Insert_ID();
							
							$records = $db2->GetAll("SELECT * FROM records WHERE issystem != '1' AND zoneid=?", array($zone['id']));
							foreach ($records as $record)
							{
								$db->Execute("INSERT INTO records SET
									zoneid	= ?,
									rtype	= ?,
									rpriority	= ?,
									rvalue	= ?,
									rkey	= ?,
									issystem = '0',
									rweight	= ?,
									rport	= ?
								", array(
									$zoneid,
									$record['rtype'],
									$recortd['rpriority'],
									$recortd['rvalue'],
									$recortd['rkey'],
									$recortd['rweight'],
									$recortd['rport']
								));
							}
							
							TaskQueue::Attach(QUEUE_NAME::CREATE_DNS_ZONE)->AppendTask(new CreateDNSZoneTask($zoneid));
                        }
					}
    			}
                catch (Exception $e)
                {
                    $db->RollbackTrans();
                    print "--------- ERROR ({$e->getMessage()}) ---------\n";
                	continue;
                }
                
                print "--------- OK ---------\n";
                $db->CommitTrans();
			}
			
			print "MIGRATION COMPLETE!\n\n";
		}
	}
?>
