<?php
	class ScriptingEventObserver extends EventObserver
	{
		public $ObserverName = 'Scripting';
		
		function __construct()
		{
			parent::__construct();			
		}

		public function OnHostDown(HostDownEvent $event)
		{
			if ($event->DBServer->IsRebooting())
				return;

			try {
				$DBFarmRole = $event->DBServer->GetFarmRoleObject();
				if ($DBFarmRole)
				{
					$behaviors = $DBFarmRole->GetRoleObject()->getBehaviors();
					$role_name = $DBFarmRole->GetRoleObject()->name;
				}
			}
			catch(Exception $e)
			{
				$role_name = '*Unknown*';
				$behaviors = '*Unknown*';
			}
			
			$msg = new Scalr_Messaging_Msg_HostDown(
				$behaviors, 
				$role_name, 
				$event->DBServer->localIp, 
				$event->DBServer->remoteIp
			);
				
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		public function OnHostUp(HostUpEvent $event)
		{						
			$msg = new Scalr_Messaging_Msg_HostUp(
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name, 
				$event->DBServer->localIp, 
				$event->DBServer->remoteIp
			);
			
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		public function OnHostInit(HostInitEvent $event)
		{									
			$msg = new Scalr_Messaging_Msg_HostInit(
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name, 
				$event->DBServer->localIp, 
				$event->DBServer->remoteIp
			);
			
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		public function OnRebootComplete(RebootCompleteEvent $event)
		{			
			$msg = new Scalr_Messaging_Msg_RebootFinish(
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name, 
				$event->DBServer->localIp, 
				$event->DBServer->remoteIp
			);
			
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		public function OnIPAddressChanged(IPAddressChangedEvent $event)
		{
			$msg = new Scalr_Messaging_Msg_IpAddressChanged(
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name, 
				$event->DBServer->localIp, 
				$event->DBServer->remoteIp,
				$event->NewIPAddress
			);
			
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		public function OnNewMysqlMasterUp(NewMysqlMasterUpEvent $event)
		{
			$msg = new Scalr_Messaging_Msg_Mysql_NewMasterUp(
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name, 
				$event->DBServer->localIp, 
				$event->DBServer->remoteIp,
				$event->SnapURL
			);
			
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		public function OnEBSVolumeMounted(EBSVolumeMountedEvent $event)
		{
			$msg = new Scalr_Messaging_Msg_BlockDeviceMounted(
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name, 
				$event->DBServer->localIp, 
				$event->DBServer->remoteIp,
				$event->VolumeID,
				$event->DeviceName,
				$event->Mountpoint
			);
			
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		public function OnBeforeInstanceLaunch(BeforeInstanceLaunchEvent $event)
		{			
			$msg = new Scalr_Messaging_Msg_BeforeInstanceLaunch(
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name
			);
			
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		public function OnBeforeHostTerminate(BeforeHostTerminateEvent $event)
		{			
			if ($event->DBServer->localIp && $event->DBServer->remoteIp)
			{
				$msg = new Scalr_Messaging_Msg_BeforeHostTerminate(
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
					$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name, 
					$event->DBServer->localIp, 
					$event->DBServer->remoteIp
				);
				
				$this->SendExecMessage($event->DBServer, $event, $msg);
			}
		}
		
		public function OnDNSZoneUpdated(DNSZoneUpdatedEvent $event)
		{			
			$msg = new Scalr_Messaging_Msg_DnsZoneUpdated($event->ZoneName);
			
			$this->SendExecMessage(null, $event, $msg);
		}
		
		public function OnEBSVolumeAttached(EBSVolumeAttachedEvent $event)
		{			
			$msg = new Scalr_Messaging_Msg_BlockDeviceAttached(
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->getBehaviors(), 
				$event->DBServer->GetFarmRoleObject()->GetRoleObject()->name, 
				$event->DBServer->localIp, 
				$event->DBServer->remoteIp,
				$event->VolumeID,
				$event->DeviceName
			);
			
			$this->SendExecMessage($event->DBServer, $event, $msg);
		}
		
		private function SendExecMessage(DBServer $DBServer, Event $Event, Scalr_Messaging_Msg $msg)
		{			
			$c = $this->DB->GetOne("SELECT COUNT(*) FROM farm_role_scripts WHERE farmid=? AND event_name=?",
				array($Event->GetFarmID(), $Event->GetName())
			);
			if ($c == 0)
				return;
			
			$DBFarm = DBFarm::LoadByID($Event->GetFarmID());
				
			$servers = $DBFarm->GetServersByFilter(array(), array('status' => SERVER_STATUS::TERMINATED));
			
			foreach ($servers as $farmDBServer)
			{
				// For some events we must sent trap to all instances include instance where ip adress changed.
				// For other events we must exclude instance that fired event from trap list.
				if ($DBServer && $DBServer->serverId == $farmDBServer->serverId)
				{
					if (!in_array($Event->GetName(), array(
						EVENT_TYPE::INSTANCE_IP_ADDRESS_CHANGED, 
						EVENT_TYPE::EBS_VOLUME_MOUNTED, 
						EVENT_TYPE::BEFORE_INSTANCE_LAUNCH,
						EVENT_TYPE::BEFORE_HOST_TERMINATE
						))) 
					{
						continue;
					}
				}
				
				if (!$farmDBServer->IsSupported("0.5"))
					$farmDBServer->SendMessage($msg, true);
			}
		}
	}
?>