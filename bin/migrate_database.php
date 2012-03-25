<?php
	require_once(dirname(__FILE__).'/../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$ScalrMigrate = new MigrateDB();
	$ScalrMigrate->Run("scalr_cic", "trolden");
	
	class MigrateDB
	{
		function Run($DBName, $admin_pwd)
		{
			$this->Migrate($DBName, $admin_pwd);
		}
		
		function Migrate($DBName, $admin_pwd)
		{
			global $db, $Crypto;

			$cpwd = $Crypto->Decrypt(@file_get_contents(dirname(__FILE__)."/../etc/.passwd"));
			
			try
			{
				$db2 = &NewADOConnection("mysqli://{$db->user}:{$db->password}@{$db->host}/{$DBName}");
				$db2->debug = false;
                $db2->cacheSecs = 0;
                $db2->SetFetchMode(ADODB_FETCH_ASSOC); 
			}
			catch(Exception $e)
			{		
				die("Service is temporary not available. Please try again in a minute. ({$e->getMessage()})");
			}
			
			
			
			$clients = $db2->Execute("SELECT * FROM clients WHERE id NOT IN (3, 16)");
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
        		    
                	// Add user to database
        		    $db->Execute("INSERT INTO clients SET
						email           = ?,
						password        = ?,
						aws_accesskeyid = ?,
						aws_accesskey = ?,
						aws_accountid   = ?,
						farms_limit     = ?,
						fullname	= ?,
						org			= ?,
						country		= ?,
						state		= ?,
						city		= ?,
						zipcode		= ?,
						address1	= ?,
						address2	= ?,
						phone		= ?,
						fax			= ?,
						comments	= ?,
						dtadded		= ?,
						isbilled	= '0',
						isactive	= '1',
						aws_certificate_enc = ?,
						aws_private_key_enc = ?
        			 ", array(
        		    	$client['email'], 
        		    	$client['password'], 
        		    	$Crypto->Encrypt($client['aws_accesskeyid'], $cpwd), 
        		    	$Crypto->Encrypt($client['aws_accesskey'], $cpwd),
        		    	$client['aws_accountid'], 
        		    	0,
        		    	$client['name'], 
						$cleint['org'], 
						$cleint['country'], 
						$client['state'], 
						$client['city'], 
						$client['zipcode'], 
						$client['address1'], 
						$client['address2'],
						$client['phone'],
						$client['fax'],
						"Client migrated from the CloudInCode site.",
						$client['dtadded'],
						$Crypto->Encrypt($client['aws_certificate_enc'], $cpwd),
						$Crypto->Encrypt($client['aws_private_key_enc'], $cpwd)
        		    ));
        		
	                    	
    				$clientid = $db->Insert_ID();
    			
	    			print "Migrating client settings...\n";
    				
    				// Migrate client settings
	    			$settings = $db2->GetAll("SELECT * FROM client_settings WHERE clientid=?", array($client['id']));
	    			foreach ($settings as $setting)
	    			{
	    				$db->Execute("INSERT INTO client_settings SET clientid=?, `key`=?, `value`=?",
	    					array($clientid, $setting['key'], $setting['value'])
	    				);
	    			}
	    			
	    			print "Setting API keys...\n";
	    			
	    			// Set client API keys
	    			$keys = Client::GenerateScalrAPIKeys();
					$db->Execute("UPDATE clients SET scalr_api_keyid=?, scalr_api_key =? WHERE id=?",
						array($keys['id'], $keys['key'], $clientid)
					);
					
					
					print "Migrating client roles...\n";
					
					// Migrate AMI Roles
					$roles = $db2->Execute("SELECT * FROM ami_roles WHERE clientid=? AND iscompleted='1'",
						array($client['id'])
					);
					while ($role = $roles->FetchRow())
					{
						$db->Execute("INSERT INTO ami_roles SET
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
							$role['ami_id'], $role['name'], $role['roletype'], $clientid, $role['comments'],
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
							array($clientid, $record['rtype'], $record['ttl'], $record['rpriority'], $record['rvalue'], $record['rkey'])
						);
					}
					
					print "Migrating client farms...\n";
					
					$farms = $db2->GetAll("SELECT * FROM farms WHERE clientid=?", array(
						$client['id']
					));
					
					foreach ($farms as $farm)
					{
						print "Migrating farm #{$farm['id']}...\n";
						
						$db->Execute("INSERT INTO farms SET
							clientid		= ?,
							name			= ?,
							iscompleted		= '0',
							hash			= ?,
							dtadded			= ?,
							private_key		= ?,
							private_key_name= ?,
							public_key		= ?,
							status			= ?,
							mysql_bcp		= ?,
							mysql_bcp_every = ?,
							mysql_rebundle_every = ?,
							dtlastbcp		= ?,
							dtlastrebundle	= ?,
							isbcprunning	= ?,
							bcp_instance_id	= ?,
							dtlaunched		= ?,
							term_on_sync_fail = ?,
							mysql_bundle	= ?,
							region			= ?
						", 
						array(
							$clientid,
							$farm['name'],
							$farm['hash'],
							$farm['dtadded'],
							$farm['private_key'],
							$farm['private_key_name'],
							$farm['public_key'],
							FARM_STATUS::TERMINATED,
							$farm['mysql_bcp'],
							$farm['mysql_bcp_every'],
							$farm['mysql_rebundle_every'],
							$farm['dtlastbcp'],
							$farm['dtlastrebundle'],
							$farm['isbcprunning'],
							$farm['bcp_instance_id'],
							$farm['dtlaunched'],
							$farm['term_on_sync_fail'],
							$farm['mysql_bundle'],
							$farm['region'],
						));
						
						$farmid = $db->Insert_ID();
						
						$bucket_name = "farm-{$farmid}-{$client['aws_accountid']}";
		                $db->Execute("UPDATE farms SET bucket_name=? WHERE id=?",
		                	array($bucket_name, $farmid)
		                );		
		                
		                print "Creating S3 bucket ({$client['aws_accesskeyid']})...\n";
		                
	                    //
	                    // Create S3 Bucket (For MySQL, BackUs, etc.)
	                    //
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
	                       $is_europe = ($farm['region'] == 'eu-west-1') ? true : false;
	            
				           if ($AmazonS3->CreateBucket($bucket_name, $is_europe))
								$created_bucket = $bucket_name;
	                    }
	                
	                    
	                    print "Creating Key-pair...\n";
	                	//
	                    // Create FARM KeyPair
	                    //
                        $key_name = "FARM-{$farmid}";
                        
                        $AmazonEC2Client = AmazonEC2::GetInstance(AWSRegions::GetAPIURL($farm['region'])); 
						$AmazonEC2Client->SetAuthKeys($client['aws_private_key_enc'], $client['aws_certificate_enc']);
                        
                        $result = $AmazonEC2Client->CreateKeyPair($key_name);
                        
                        if ($result->keyMaterial)
                        {
                            $db->Execute("UPDATE farms SET private_key=?, private_key_name=? WHERE id=?", array($result->keyMaterial, $key_name, $farmid));
                            $created_key_name = $key_name;
                        }
                        else
                        	print "ERROR: CANNOT CREATE KEY-PAIR!!!\n";
                                                    
                            
                        print "Migrating farm_amis...\n";
                        $farm_amis = $db2->GetAll("SELECT * FROM farm_amis WHERE farmid = ?", 
                        	array($farm['id'])
                        );
                        foreach ($farm_amis as $farm_ami)
                        {
                        	$db->Execute("INSERT INTO farm_amis SET
                        		farmid	= ?,
                        		ami_id	= ?,
                        		replace_to_ami	= '',
                        		avail_zone	= ?,
                        		instance_type	= ?,
                        		use_elastic_ips	= ?,
                        		dtlastsync		= ?,
                        		reboot_timeout	= ?,
                        		launch_timeout	= ?,
                        		use_ebs			= ?,
                        		ebs_size		= ?,
                        		ebs_snapid		= ?,
                        		ebs_mountpoint	= ?,
                        		ebs_mount		= ?,
                        		status_timeout	= ?
                        	", array(
                        		$farmid,
                        		$farm_ami['ami_id'],
                        		$farm_ami['avail_zone'],
                        		$farm_ami['instance_type'],
                        		$farm_ami['use_elastic_ips'],
                        		$farm_ami['dtlastsync'],
                        		$farm_ami['reboot_timeout'],
                        		$farm_ami['launch_timeout'],
                        		$farm_ami['use_ebs'],
                        		$farm_ami['ebs_size'],
                        		$farm_ami['ebs_snapid'],
                        		$farm_ami['ebs_mountpoint'],
                        		$farm_ami['ebs_mount'],
                        		$farm_ami['status_timeout']
                        	));
                        	$farmroleid = $db->Insert_ID();
                        	
                        	$DBFarmRole = new DBFarmRole($farmroleid);
                        	
                        	/******/
                        	$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_POLLING_INTERVAL, 2);
							$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MAX_INSTANCES, $farm_ami['max_count']);
							$DBFarmRole->SetSetting(DBFarmRole::SETTING_SCALING_MIN_INSTANCES, $farm_ami['min_count']);
							
							$DBFarmRole->SetSetting(LAScalingAlgo::PROPERTY_MIN_LA, $farm_ami['min_LA']);
							$DBFarmRole->SetSetting(LAScalingAlgo::PROPERTY_MAX_LA, $farm_ami['max_LA']);
							$DBFarmRole->SetSetting("scaling.la.enabled", 1);
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
								"alex.intridea.com", 
								CONFIG::$DEF_SOA_TTL, 
								CONFIG::$DEF_SOA_PARENT, 
								date("Ymd")."01",
								CONFIG::$DEF_SOA_REFRESH,
								CONFIG::$DEF_SOA_RETRY, 
								$zone['soa_expire'],
								CONFIG::$DEF_SOA_MINTTL, 
								$farmid, 
								$zone['ami_id'], 
								$zone['clientid'], 
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