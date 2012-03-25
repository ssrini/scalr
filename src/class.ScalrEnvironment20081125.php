<?
	class ScalrEnvironment20081125 extends ScalrEnvironment
    {    	
    	protected function ListScripts()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$ScriptsDOMNode = $ResponseDOMDocument->createElement("scripts");
    		
    		/************/
    		// Get and Validate Event name
    		$instance_events = array(
            	"hostinit" 			=> "HostInit",
            	"hostup" 			=> "HostUp",
            	"rebootfinish" 		=> "RebootComplete",
            	"newmysqlmaster"	=> "NewMysqlMasterUp",
    			"ebsvolumeattached"	=> "EBSVolumeAttached",
    		
    			"blockdeviceattached" => "EBSVolumeAttached",
    			"blockdevicemounted"  => "EBSVolumeMounted"
            );

            $Reflect = new ReflectionClass("EVENT_TYPE");
            $scalr_events = $Reflect->getConstants();
            
            if (!in_array($this->GetArg("event"), $scalr_events))
            	$event_name = $instance_events[strtolower($this->GetArg("event"))];
            else
            	$event_name = $this->GetArg("event");

            if (!$event_name && preg_match("/^(Custom|API)Event-[0-9]+-[0-9]+$/si", $this->GetArg("event")))
            	$custom_event_name = $this->GetArg("event");

            try {
	            if ($this->GetArg("event_id"))
	            {
	            	if (preg_match("/^FRSID-([0-9]+)$/", $this->GetArg("event_id"), $matches))
	            	{
	            		$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND id=? ORDER BY order_index ASC",
							array($this->DBServer->farmId, $matches[1])
						);
	            	}
	            	else
	            	{
	            		$event_info = $this->DB->GetRow("SELECT * FROM events WHERE event_id=?", array($this->GetArg("event_id")));
	            		if ($event_info)
	            		{
	            			$Event = unserialize($event_info['event_object']);
	            			if ($Event->DBServer)
	            			{
	            				if ($Event->DBServer->serverId == $this->DBServer->serverId)
									$is_target = '1';
								else
									$is_target = '0';	
	            				
	            				$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? 
				            		AND event_name=? AND (target = ? OR (target = ? AND 1 = {$is_target} AND farm_roleid=?) OR (target = ? AND farm_roleid=?)) ORDER BY order_index ASC",
									array(
										$Event->GetFarmID(), 
										$Event->GetName(), 
										SCRIPTING_TARGET::FARM,
										SCRIPTING_TARGET::INSTANCE, 
										$Event->DBServer->farmRoleId,
										SCRIPTING_TARGET::ROLE,
										$Event->DBServer->farmRoleId
									)
								);
								
								$TargetDBFarmRole = $Event->DBServer->GetFarmRoleObject();
								$target_instance_id = $Event->DBServer->serverId;
	            			}
	            			else
	            			{
	            				$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? 
				            		AND event_name=? AND target = ? ORDER BY order_index ASC",
									array(
										$Event->GetFarmID(), 
										$Event->GetName(), 
										SCRIPTING_TARGET::FARM
									)
								);
	            			}
	            		}
	            	}
	            }
            } catch(Exception $e){ return $ResponseDOMDocument; }
    		/************/
		            	
            
			/***********************************************************/
            /** Instance from which request has come **/    		
    		try {
    			$DBFarmRole = $this->DBServer->GetFarmRoleObject();
    		}
    		catch(Exception $e) { return $ResponseDOMDocument; }
    		
			$DBFarm = $this->DBServer->GetFarmObject();
			
			if (!$scripts)
			{
				// Check context and get list of scripts
	    		if (!$this->GetArg("target_ip"))
	            {      	
	            	//
	            	// Build a list of scripts to be executed on that particular instance 
	            	//
	            	
	            	if ($event_name == EVENT_TYPE::HOST_INIT)
	            	{
	            		$this->DBServer->remoteIp = $_SERVER['REMOTE_ADDR'];
	            		$this->DBServer->localIp = $this->GetArg('local_ip');
	            	}
	            		
	            	$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? AND farm_roleid=? 
	            		AND event_name=? ORDER BY order_index ASC",
						array($this->DBServer->farmId, $DBFarmRole->ID, $event_name)
					);
	            }
	            else
	            {
	            	if ($event_name == EVENT_TYPE::HOST_INIT && $this->GetArg("target_ip") == $this->GetArg('local_ip'))
	            	{
	            		$this->DBServer->remoteIp = $_SERVER['REMOTE_ADDR'];
	            		$this->DBServer->localIp = $this->GetArg('local_ip');
	            		
	            		//
		            	// Build a list of scripts to be executed upon event from another instance.
		            	//
		            	$targetDBServer = $this->DBServer;
	            	}
	            	else
	            	{
		            	//
		            	// Build a list of scripts to be executed upon event from another instance.
		            	//
		            	try {
		            		$targetDBServer = DBServer::LoadByLocalIp($this->GetArg("target_ip"));
		            	} catch (Exception $e) {
		            		return $ResponseDOMDocument;
		            	}		
	            	}
	            	
	            	if ($this->GetArg("target_ip") == $this->GetArg('local_ip'))
						$is_target = '1';
					else
						$is_target = '0';	
	            	
	            	if (!$targetDBServer)
		            	exit();
		            	
		            try {
		            	$TargetDBFarmRole = $targetDBServer->GetFarmRoleObject();
		            } catch (Exception $e){
		            	return $ResponseDOMDocument;
		            }
		            						
					$scripts = $this->DB->GetAll("SELECT * FROM farm_role_scripts WHERE farmid=? 
	            		AND event_name=? AND (target = ? OR (target = ? AND 1 = {$is_target} AND farm_roleid=?) OR (target = ? AND farm_roleid=?)) ORDER BY order_index ASC",
						array(
							$this->DBServer->farmId, 
							$event_name, 
							SCRIPTING_TARGET::FARM,
							SCRIPTING_TARGET::INSTANCE,
							$TargetDBFarmRole->ID, 
							SCRIPTING_TARGET::ROLE,
							$TargetDBFarmRole->ID
						)
					);
	            }
			}
            
    		/***********************************************************/
            // Build XML list of scripts
    		if (count($scripts) > 0)
    		{
	    		foreach ($scripts as $script)
	            {
	            	if ($script['target'] == SCRIPTING_TARGET::INSTANCE && $targetDBServer)
	            	{
	            		if ($targetDBServer->serverId != $this->DBServer->serverId)
	            			continue;
	            	}
	            	
	            	if ($script['target'] == SCRIPTING_TARGET::ROLE && $TargetDBFarmRole)
	            	{
	            		if ($TargetDBFarmRole->ID != $DBFarmRole->ID)
	            			continue;
	            	}
	            	
	            	if ($script['version'] == 'latest')
					{
						$version = (int)$this->DB->GetOne("SELECT MAX(revision) FROM script_revisions WHERE scriptid=?",
							array($script['scriptid'])
						);
					}
					else
						$version = (int)$script['version'];
					
					$template = $this->DB->GetRow("SELECT * FROM scripts WHERE id=?", 
						array($script['scriptid'])
					);
					
					$template['script'] = $this->DB->GetOne("SELECT script FROM script_revisions WHERE scriptid=? AND revision=?",
						array($template['id'], $version)
					);
					
					if (!$template['script'])
						throw new Exception("Script {$template['name']}:{$version} doesn't exist or inactive. Make sure that is does exist and is approved.");	
					
					if ($template)
					{
						$params = array_merge($this->DBServer->GetScriptingVars(), (array)unserialize($script['params']));
						
						if ($Event)
						{
							foreach ($Event->GetScriptingVars() as $k=>$v)
								$params[$k] = $Event->{$v};
						}
						
						// Prepare keys array and array with values for replacement in script
						$keys = array_keys($params);
						$f = create_function('$item', 'return "%".$item."%";');
						$keys = array_map($f, $keys);
						$values = array_values($params);
						
						// Generate script contents
						$script_contents = str_replace($keys, $values, $template['script']);
						$script_contents = str_replace('\%', "%", $script_contents);
						$name = preg_replace("/[^A-Za-z0-9]+/", "_", $template['name']);
						
						$ScriptDOMNode = $ResponseDOMDocument->createElement("script");
						$ScriptDOMNode->setAttribute("asynchronous", ($script['issync'] == 1) ? '0' : '1');
						$ScriptDOMNode->setAttribute("exec-timeout", $script['timeout']);
						$ScriptDOMNode->setAttribute("name", $name);
						
						$BodyDOMNode = $ResponseDOMDocument->createElement("body");
						$BodyDOMNode->appendChild($ResponseDOMDocument->createCDATASection($script_contents));
						
						$ScriptDOMNode->appendChild($BodyDOMNode);
					}
					else
						throw new Exception(sprintf(_("Script template ID: %s not found."), $script['scriptid']));
						
					$ScriptsDOMNode->appendChild($ScriptDOMNode);
	            }
    		}
    		
    		$ResponseDOMDocument->documentElement->appendChild($ScriptsDOMNode);
    		
    		return $ResponseDOMDocument;
    	}
    	
    	protected function ListVirtualhosts()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$Smarty = Core::GetSmartyInstance();
    		
    		$type = $this->GetArg("type");
    		$name = $this->GetArg("name");
    		$https = $this->GetArg("https");
    		
    		$virtual_hosts = $this->DB->GetAll("SELECT * FROM apache_vhosts WHERE farm_roleid=?",
    			array($this->DBServer->farmRoleId)
    		);
    		
    		$VhostsDOMNode = $ResponseDOMDocument->createElement("vhosts");
    		
			$DBFarmRole = $this->DBServer->GetFarmRoleObject();
			
			if ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::NGINX))
			{
				$vhost_info = $this->DB->GetRow("SELECT * FROM apache_vhosts WHERE farm_id=? AND is_ssl_enabled='1'", 
	            	array($this->DBServer->farmId)
	            );
				
	            if ($vhost_info)
	            {
					$template = $this->DB->GetOne("SELECT value FROM farm_role_options WHERE hash IN ('nginx_https_vhost_template','nginx_https_host_template') AND farm_roleid=?", 
	            		array($DBFarmRole->ID)
	            	);
	            	if (!$template)
	            	{
	            		$template = $this->DB->GetOne("SELECT defval FROM role_parameters WHERE role_id=? AND hash IN ('nginx_https_vhost_template','nginx_https_host_template')", 
	            			array($DBFarmRole->RoleID)
	            		);
	            	}
	            	
	            	if ($template)
	            	{
	            		$Smarty->assign(unserialize($vhost_info['httpd_conf_vars']));
            			$Smarty->assign(array("host" => $vhost_info['name']));
	            		
	            		$contents = $Smarty->fetch("string:{$template}");
	            		
	            		$VhostDOMNode =  $ResponseDOMDocument->createElement("vhost");
		    			$VhostDOMNode->setAttribute("hostname", $vhost_info['name']);
		    			$VhostDOMNode->setAttribute("https", "1");
		    			$VhostDOMNode->setAttribute("type", "nginx");
		    			
		    			$RawDOMNode = $ResponseDOMDocument->createElement("raw");
		    			$RawDOMNode->appendChild($ResponseDOMDocument->createCDATASection($contents));
		    			
		    			$VhostDOMNode->appendChild($RawDOMNode);
		    			$VhostsDOMNode->appendChild($VhostDOMNode);
	            	}
	            	else
	            		throw new Exception("Virtualhost template not found in database. (farm roleid: {$DBFarmRole->ID})");
	            }
			}
			elseif ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::APACHE))
			{
	    		while (count($virtual_hosts) > 0)
	    		{
	    			$virtualhost = array_shift($virtual_hosts);
	    			
	    			if ($virtualhost['is_ssl_enabled'])
	    			{
	    				$nonssl_vhost = $virtualhost;
	    				$nonssl_vhost['is_ssl_enabled'] = 0;
	    				array_push($virtual_hosts, $nonssl_vhost);
	    			}
	    			
	    			//Filter by name
	    			if ($this->GetArg("name") !== null && $this->GetArg("name") != $virtualhost['name'])
	    				continue;
	    				
	    			// Filter by https
	    			if ($this->GetArg("https") !== null && $virtualhost['is_ssl_enabled'] != $this->GetArg("https"))
	    				continue;
	    			
	    			$VhostDOMNode =  $ResponseDOMDocument->createElement("vhost");
	    			$VhostDOMNode->setAttribute("hostname", $virtualhost['name']);
	    			$VhostDOMNode->setAttribute("https", $virtualhost['is_ssl_enabled']);
	    			$VhostDOMNode->setAttribute("type", "apache");
	    			
            		$Smarty->assign(unserialize($virtualhost['httpd_conf_vars']));
            		$Smarty->assign(array("host" => $virtualhost['name']));        

            		if (!$virtualhost['is_ssl_enabled'])
            			$contents = $Smarty->fetch("string:{$virtualhost['httpd_conf']}");
            		else
            			$contents = $Smarty->fetch("string:{$virtualhost['httpd_conf_ssl']}");
	    			
	    			$RawDOMNode = $ResponseDOMDocument->createElement("raw");
	    			$RawDOMNode->appendChild($ResponseDOMDocument->createCDATASection($contents));
	    			
	    			$VhostDOMNode->appendChild($RawDOMNode);
	    			$VhostsDOMNode->appendChild($VhostDOMNode);
	    		}
			}
    		
    		$ResponseDOMDocument->documentElement->appendChild($VhostsDOMNode);
    		
    		return $ResponseDOMDocument;
    	}
    	
    	protected function ListRoleParams()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		$DBFarmRole = $this->DBServer->GetFarmRoleObject();
    		
    		$sql_query = "SELECT * FROM farm_role_options WHERE farm_roleid=?";
    		$sql_params = array($DBFarmRole->ID);
    		
			if ($this->GetArg("name"))
			{
				$sql_query .= " AND hash=?";
				array_push($sql_params, $this->GetArg("name"));
			}
    		
			$options = $this->DB->GetAll($sql_query, $sql_params);
    		
    		$ParamsDOMNode = $ResponseDOMDocument->createElement("params");
    		
    		if (count($options) > 0)
    		{
    			foreach ($options as $option)
    			{
    				$ParamDOMNode = $ResponseDOMDocument->createElement("param");
    				$ParamDOMNode->setAttribute("name", $option['hash']);
    				
    				$ValueDomNode = $ResponseDOMDocument->createElement("value");
    				$ValueDomNode->appendChild($ResponseDOMDocument->createCDATASection($option['value']));
    				
    				$ParamDOMNode->appendChild($ValueDomNode);
    				$ParamsDOMNode->appendChild($ParamDOMNode);
    			}
    		}
    		
    		$ResponseDOMDocument->documentElement->appendChild($ParamsDOMNode);
    		
    		return $ResponseDOMDocument;
    	}
    	
    	/**
    	 * Return HTTPS certificate and private key
    	 * @return DOMDocument
    	 */
    	protected function GetHttpsCertificate()
    	{
    		$ResponseDOMDocument = $this->CreateResponse();
    		
    		if ($this->DBServer->status == SERVER_STATUS::PENDING_TERMINATE || $this->DBServer->status == SERVER_STATUS::TERMINATED)
    			return $ResponseDOMDocument;
    		
    		$hostName = $this->GetArg("hostname") ? " AND name=".$this->qstr($this->GetArg("hostname")) : "";
    			
    		if ($this->DBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::NGINX))
			{
	    		$vhost_info = $this->DB->GetRow("SELECT * FROM apache_vhosts WHERE farm_id=? AND is_ssl_enabled='1' {$hostName}", 
	            	array($this->DBServer->farmId)
	            );
			}
			else 
			{
				$vhost_info = $this->DB->GetRow("SELECT * FROM apache_vhosts WHERE farm_roleid=? AND is_ssl_enabled='1' {$hostName}", 
	            	array($this->DBServer->farmRoleId)
	            );
			}
            
            if ($vhost_info)
            {
				$vhost = $ResponseDOMDocument->createElement("virtualhost");
				$vhost->setAttribute("name", $vhost_info['name']);
            	
            	$vhost->appendChild(
					$ResponseDOMDocument->createElement("cert", $vhost_info['ssl_cert'])
				);
				$vhost->appendChild(
					$ResponseDOMDocument->createElement("pkey", $vhost_info['ssl_key'])
				);
				$vhost->appendChild(
					$ResponseDOMDocument->createElement("ca_cert", $vhost_info['ca_cert'])
				);
				
				$ResponseDOMDocument->documentElement->appendChild(
					$vhost
				);
            }
            
            return $ResponseDOMDocument;
    	}
    	
    	/**
    	 * List farm roles and hosts list for each role
    	 * Allowed args: role=(String Role Name) | behaviour=(app|www|mysql|base|memcached)
    	 * @return DOMDocument
    	 */
    	protected function ListRoles()
    	{
			$ResponseDOMDocument = $this->CreateResponse();
    		
			$RolesDOMNode = $ResponseDOMDocument->createElement('roles');
			$ResponseDOMDocument->documentElement->appendChild($RolesDOMNode);
			
    		$sql_query = "SELECT id FROM farm_roles WHERE farmid=?";
			$sql_query_args = array($this->DBServer->farmId);
    		
			// Filter by behaviour
			if ($this->GetArg("behaviour"))
			{
				$sql_query .= " AND role_id IN (SELECT role_id FROM role_behaviors WHERE behavior=?)";
				array_push($sql_query_args, $this->GetArg("behaviour"));
			}
			
    		// Filter by role
			if ($this->GetArg("role"))
			{
				$sql_query .= " AND role_id IN (SELECT id FROM roles WHERE name=?)";
				array_push($sql_query_args, $this->GetArg("role"));
			}
			
    		if ($this->GetArg("role-id"))
			{
				$sql_query .= " AND role_id = ?";
				array_push($sql_query_args, $this->GetArg("role-id"));
			}
			
    		if ($this->GetArg("farm-role-id"))
			{
				$sql_query .= " AND id = ?";
				array_push($sql_query_args, $this->GetArg("farm-role-id"));
			}
			
    		$farm_roles = $this->DB->GetAll($sql_query, $sql_query_args);
    		foreach ($farm_roles as $farm_role)
    		{
    			$DBFarmRole = DBFarmRole::LoadByID($farm_role['id']);
    			
    			$roleId = $DBFarmRole->NewRoleID ? $DBFarmRole->NewRoleID : $DBFarmRole->RoleID;
    			
    			// Create role node
    			$RoleDOMNode = $ResponseDOMDocument->createElement('role');
    			$RoleDOMNode->setAttribute('behaviour', implode(",", $DBFarmRole->GetRoleObject()->getBehaviors()));
    			$RoleDOMNode->setAttribute('name', DBRole::loadById($roleId)->name);
    			$RoleDOMNode->setAttribute('id', $DBFarmRole->ID);
    			$RoleDOMNode->setAttribute('role-id', $roleId);
    			
    			$HostsDomNode = $ResponseDOMDocument->createElement('hosts');
    			$RoleDOMNode->appendChild($HostsDomNode);
    			
    			// List instances (hosts)
    			$serversSql = "SELECT server_id FROM servers WHERE farm_roleid=?";
    			$serversArgs = array($farm_role['id'], SERVER_STATUS::RUNNING);
    			
    			if ($this->GetArg("showInitServers")) {
    				$serversSql .= " AND status IN (?,?)";
    				$serversArgs[] = SERVER_STATUS::INIT;
    			} else {
    				$serversSql .= " AND status=?";
    			}
    			
    			$servers = $this->DB->GetAll($serversSql, $serversArgs);
    			
    			// Add hosts to response
    			if (count($servers) > 0)
    			{
    				foreach ($servers as $server)
    				{
    					$DBServer = DBServer::LoadByID($server['server_id']);
    					
    					$HostDOMNode = $ResponseDOMDocument->createElement("host");
    					$HostDOMNode->setAttribute('internal-ip', $DBServer->localIp);
    					$HostDOMNode->setAttribute('external-ip', $DBServer->remoteIp);
    					$HostDOMNode->setAttribute('index', $DBServer->index);
    					$HostDOMNode->setAttribute('status', $DBServer->status);
    					
    					if ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MONGODB))
    					{
    						$HostDOMNode->setAttribute('replica-set-index', (int)$DBServer->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_REPLICA_SET_INDEX));
    						$HostDOMNode->setAttribute('shard-index', (int)$DBServer->GetProperty(Scalr_Role_Behavior_MongoDB::SERVER_SHARD_INDEX));
    					}
    					
    					if ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::MYSQL))
    						$HostDOMNode->setAttribute('replication-master', (int)$DBServer->GetProperty(SERVER_PROPERTIES::DB_MYSQL_MASTER));

    					if ($DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::POSTGRESQL) || $DBFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::REDIS))
    						$HostDOMNode->setAttribute('replication-master', (int)$DBServer->GetProperty(Scalr_Db_Msr::REPLICATION_MASTER));
    						
    					$HostsDomNode->appendChild($HostDOMNode);
    				}
    			}
    			
    			// Add role node to roles node
    			$RolesDOMNode->appendChild($RoleDOMNode);
    		}
    		
    		return $ResponseDOMDocument;
    	}
    }

?>