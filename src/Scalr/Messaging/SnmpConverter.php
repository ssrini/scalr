<?php
class Scalr_Messaging_SnmpConverter {
	private static $instance;
	
	private $trapMap = array();
	
	private $trapVars = array();
	
	private $eventNoticeMessages = array();
	
	private $eventNoticeMsgNameMap = array();
	
	function __construct () {
		$this->trapMap = array(
			'HostUp' => 'SNMPv2-MIB::snmpTrap.11.1 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "{behaviour}" SNMPv2-MIB::sysLocation.0 s "{localIp}" SNMPv2-MIB::sysDescr.0 s "{roleName}"',
			'HostDown' => 'SNMPv2-MIB::snmpTrap.11.0 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "{behaviour}" SNMPv2-MIB::sysLocation.0 s "{localIp}" SNMPv2-MIB::sysDescr.0 s "{isFirstInRole}" SNMPv2-MIB::sysContact.0 s "{roleName}"',
			'HostInitResponse' => 'SNMPv2-MIB::snmpTrap.12.1 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "{awsAccountId}"',
			'MountPointsReconfigure' => 'SNMPv2-MIB::snmpTrap.5.0 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "{device}" SNMPv2-MIB::sysDescr.0 s "{mountPoint}" SNMPv2-MIB::sysLocation.0 s "{createFs}"',
			'Rebundle' => 'SNMPv2-MIB::snmpTrap.12.0 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "{roleName}" SNMPv2-MIB::sysLocation.0 s "{bundleTaskId}"',
			'ScalarizrUpdateAvailable' => 'SNMPv2-MIB::snmpTrap.10.2 SNMPv2-MIB::sysUpTime.0 s "{messageId}"',
			'VhostReconfigure' => 'SNMPv2-MIB::snmpTrap.11.2 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "{vhostName}" SNMPv2-MIB::sysDescr.0 s "{isSslVhost}"',
			'Mysql_CreateBackup' => 'SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "backup"',
			'Mysql_CreateDataBundle' => 'SNMPv2-MIB::snmpTrap.12.2 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "bundle"',
			'Mysql_CreatePmaUser' => 'SNMPv2-MIB::snmpTrap.10.2 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "MySQLPMACredentials" SNMPv2-MIB::sysLocation.0 s "{farmRoleId}" SNMPv2-MIB::sysDescr.0 s "{pmaServerIp}" SNMPv2-MIB::sysContact.0 s ""',
			'Mysql_NewMasterUp' => 'SNMPv2-MIB::snmpTrap.10.1 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "{localIp}" SNMPv2-MIB::sysLocation.0 s "{snapPlacement}" SNMPv2-MIB::sysDescr.0 s "{roleName}"'
		);
		
		$this->eventNoticeMessages = array(
			'BeforeHostTerminate', 
			'BeforeInstanceLaunch', 
			'HostInit',
			'DnsZoneUpdated', 
			'IpAddressChanged', 
			'ExecScript',
			'RebootFinish',
			'BlockDeviceAttached',
			'BlockDeviceMounted'
		);
		$this->eventNoticeMsgNameMap = array(
			'DnsZoneUpdated' => 'DNSZoneUpdated',
			'IpAddressChanged' => 'IPAddressChanged',
			'RebootFinish' => 'RebootComplete',
			'BlockDeviceAttached' => 'EBSVolumeAttached',
			'BlockDeviceMounted' => 'EBSVolumeMounted'
		);
		$eventNoticeTrap = 'SNMPv2-MIB::snmpTrap.5.1 SNMPv2-MIB::sysUpTime.0 s "{messageId}" SNMPv2-MIB::sysName.0 s "{localIp}" SNMPv2-MIB::sysLocation.0 s "{eventId}" SNMPv2-MIB::sysDescr.0 s "{roleName}" SNMPv2-MIB::sysContact.0 s "{eventName}"';
		foreach ($this->eventNoticeMessages as $msgName) {
			$this->trapMap[$msgName] = $eventNoticeTrap;
		}
		$this->trapMap['__IntEventNotice'] = $eventNoticeTrap;		

	}
	
	/**
	 * @return Scalr_Messaging_SnmpConverter
	 */
	static function getInstance () {
		if (self::$instance === null) {
			self::$instance = new Scalr_Messaging_SnmpConverter();
		}
		return self::$instance;
	}
	
	function convert (Scalr_Messaging_Msg $msg, $isEventNotice=false) {
		$msgName = $msg->getName();

		if (!array_key_exists($msgName, $this->trapMap)) {
			throw new Scalr_Messaging_SnmpConverterException(sprintf(
					"There is no SNMP trap for message class '%s'", $msgName));
		}
		
		$vars = $this->extractVars($msg);

		if (in_array($msgName, $this->eventNoticeMessages) || $isEventNotice) {
			$vars = array_merge($vars, $this->extractEventNotice($msg));
		}
		
		$extractMethod = "extract{$msgName}";
		if (method_exists($this, $extractMethod)) {
			$vars = array_merge($vars, $this->{$extractMethod}($msg));
		}
		
		$search = array();
		$replace = array();
		foreach ($this->getTrapVars($isEventNotice ? "__IntEventNotice" : $msgName) as $var => $holder) {
			$search[] = $holder;
			$replace[] = $vars[$var];
		}
		
		return str_replace($search, $replace, $this->trapMap[$isEventNotice ? "__IntEventNotice" : $msgName]);
	}
	
	private function getTrapVars ($msgName) {
		if (!array_key_exists($msgName, $this->trapVars)) {
			preg_match_all("/\{([A-Za-z0-9-]+)\}/", $this->trapMap[$msgName], $matches);
			$this->trapVars[$msgName] = array();
			foreach ($matches[1] as $i => $var) {
				$this->trapVars[$msgName][$var] = $matches[0][$i];
			}
		}
		
		return $this->trapVars[$msgName];
	}
	
	private function extractVars ($msg) {
		$ret = array();		
		foreach (array_keys($this->getTrapVars($msg->getName())) as $var) {
			if ($var == 'behaviour') {
				$ret[$var] = $msg->{$var}[0];
			} else {
				$ret[$var] = $msg->{$var};
			}
		}

		return $ret;
	}
	
	private function extractEventNotice ($msg) {
		$ret = array();
		$ret["eventName"] = array_key_exists($msg->getName(), $this->eventNoticeMsgNameMap) ?
				$this->eventNoticeMsgNameMap[$msg->getName()] : $msg->getName();
		$ret["eventId"] = $msg->meta[Scalr_Messaging_MsgMeta::EVENT_ID];
		if (!property_exists($msg, "localIp") || !$msg->localIp) {
			$ret["localIp"] = '0.0.0.0';
		}
		return $ret;
	}
}
