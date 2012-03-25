<?php

class Scalr_UI_Controller_Dnszones extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'dnsZoneId';

	public static function getPermissionDefinitions()
	{
		return array(
			'xSaveSettings' => 'Edit',
			'create' => 'Edit',
			'xSave' => 'Edit',
			'xRemoveZones' => 'Edit'
		);
	}

	public function hasAccess()
	{
		return true;
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function viewAction()
	{
		$this->response->page('ui/dnszones/view.js');
	}

	public function defaultRecordsAction()
	{
		if ($this->user->getType() == Scalr_Account_User::TYPE_TEAM_USER)
			throw new Scalr_Exception_InsufficientPermissions();

		$records = $this->db->GetAll("SELECT * FROM default_records WHERE clientid=? ORDER BY `type`", array($this->user->getAccountId()));
		$this->response->page('ui/dnszones/defaultRecords.js', array('records' => $records), array('ui/dnszones/dnsfield.js'));
	} 

	public function xSaveDefaultRecordsAction()
	{
		$this->request->defineParams(array(
			'records' => array('type' => 'json')
		));

		if ($this->user->getType() == Scalr_Account_User::TYPE_TEAM_USER)
			throw new Scalr_Exception_InsufficientPermissions();

		$records = array();
		foreach ($this->getParam('records') as $key => $r) {
			if (($r['name'] || $r['value'])) {
				$r['name'] = str_replace("%hostname%", "fakedomainname.qq", $r['name']);
				$r['value'] = str_replace("%hostname%", "fakedomainname.qq", $r['value']);
				if (!$r['ttl'])
					$r['ttl'] = 14400;

				$records[$key] = $r;
			}
		}

		$recordsValidation = Scalr_Net_Dns_Zone::validateRecords($records);
		if ($recordsValidation === true) {
			$this->db->Execute("DELETE FROM default_records WHERE clientid=?", array($this->user->getAccountId()));

			foreach ($records as $r) {
				$r['name'] = str_replace('fakedomainname.qq', '%hostname%', $r['name']);
				$r['value'] = str_replace('fakedomainname.qq', '%hostname%', $r['value']);

				$this->db->Execute("INSERT INTO default_records SET clientid=?, `type`=?, `ttl`=?, `priority`=?, `value`=?, `name`=?", 
					array($this->user->getAccountId(), $r['type'], (int)$r['ttl'],
					(int)$r['priority'], $r['value'], $r['name'])
				);
			}

			$this->response->success('Default records successfully changed');
		} else {
			$this->response->failure();
			$this->response->data(array('errors' => $recordsValidation));
		}
	}

	public function settingsAction()
	{
		$this->request->defineParams(array(
			'dnsZoneId' => array('type' => 'int')
		));

		$DBDNSZone = DBDNSZone::loadById($this->getParam('dnsZoneId'));
		$this->user->getPermissions()->validate($DBDNSZone);

		$this->response->page('ui/dnszones/settings.js', array(
			'axfrAllowedHosts'			=> $DBDNSZone->axfrAllowedHosts,
			'allowManageSystemRecords'	=> $DBDNSZone->allowManageSystemRecords,
			'allowedAccounts'			=> $DBDNSZone->allowedAccounts
		));
	}

	public function xSaveSettingsAction()
	{
		$this->request->defineParams(array(
			'dnsZoneId' => array('type' => 'int'),
			'axfrAllowedHosts' => array('type' => 'string'),
			'allowedAccounts' => array('type' => 'string'),
			'allowManageSystemRecords' => array('type' => 'int')
		));

		$DBDNSZone = DBDNSZone::loadById($this->getParam('dnsZoneId'));
		$this->user->getPermissions()->validate($DBDNSZone);

		$Validator = new Validator();

		if ($this->getParam('axfrAllowedHosts') != '') {
			$hosts = explode(";", $this->getParam('axfrAllowedHosts'));
			foreach ($hosts as $host) {
				$chunks = explode("/", $host);
				$ip_chunks = explode(".", $chunks[0]);
				if (!$Validator->IsIPAddress($chunks[0]) || ($chunks[1] && !$Validator->IsNumeric($chunks[1])) || count($chunks) > 2 || count($ip_chunks) != 4)
					$errors['axfrAllowedHosts'] = sprintf(_("'%s' is not valid IP address or CIDR"), $host);
			}
		}

		if ($this->getParam('allowedAccounts')) {
			$accounts = explode(";", $this->getParam('allowedAccounts'));
			foreach ($accounts as $account) {
				if (!$Validator->IsEmail($account))
					$errors['allowedAccounts'] = sprintf(_("'%s' is not valid Email address"), $account);
			}
		}

		if (count($errors) == 0) {
			if ($this->getParam('axfrAllowedHosts') != $DBDNSZone->axfrAllowedHosts) {
				$DBDNSZone->axfrAllowedHosts = $this->getParam('axfrAllowedHosts');
				$DBDNSZone->isZoneConfigModified = 1;
			}

			$DBDNSZone->allowManageSystemRecords = $this->getParam('allowManageSystemRecords');
			$DBDNSZone->allowedAccounts = $this->getParam('allowedAccounts');
			$DBDNSZone->save();

			$this->response->success('Changes have been saved. They will become active in few minutes.');
		}
		else {
			$this->response->failure();
			$this->response->data(array('errors' => $errors));
		}
	}

	public function createAction()
	{
		
		$farms = self::loadController('Farms')->getList();
		$farms[0] = array('id'=>0, 'name'=>'');
		$records = array();
		$nss = $this->db->GetAll("SELECT * FROM nameservers WHERE isbackup='0'");
		foreach ($nss as $ns)
			$records[] = array("id" => "c".rand(10000, 999999), "type" => "NS", "ttl" => 14400, "value" => "{$ns["host"]}.", "name" => "%hostname%.", "issystem" => 0);

		$defRecords = $this->db->GetAll("SELECT * FROM default_records WHERE clientid=?", array($this->user->getAccountId()));
		foreach ($defRecords as $record)
			$records[] = $record;

		$this->response->page('ui/dnszones/create.js', array(
			'farms' => $farms,
			'farmRoles' => array(),
			'action' => 'create',
			'allowManageSystemRecords' => '0',
			'zone' => array(
				'domainName' => Scalr::GenerateUID() . '.' . CONFIG::$DNS_TEST_DOMAIN_NAME,
				'domainType' => 'scalr',
				'soaRetry' => '7200',
				'soaRefresh' => '14400',
				'soaExpire' => '86400'
			),
			'records' => $records
		), array('ui/dnszones/dnsfield.js'));
	}

	public function editAction()
	{
		$this->request->defineParams(array(
			'dnsZoneId' => array('type' => 'int')
		));

		$DBDNSZone = DBDNSZone::loadById($this->getParam('dnsZoneId'));
		$this->user->getPermissions()->validate($DBDNSZone);

		$farms = self::loadController('Farms')->getList();
		$farms[0] = array('id'=>0, 'name'=>'');
		$farmRoles = array();

		if ($DBDNSZone->farmId) {
			$this->request->setParams(array('farmId' => $DBDNSZone->farmId));

			$farmRoles = self::loadController('Roles', 'Scalr_UI_Controller_Farms')->getList();
			if (count($farmRoles))
				$farmRoles[0] = array('id' => 0, 'name' => '');
		}

		$this->response->page('ui/dnszones/create.js', array(
			'farms' => $farms,
			'farmRoles' => $farmRoles,
			'action' => 'edit',
			'allowManageSystemRecords' => $DBDNSZone->allowManageSystemRecords,
			'zone' => array(
				'domainId' => $DBDNSZone->id,
				'domainName' => $DBDNSZone->zoneName,
				'soaRetry' => $DBDNSZone->soaRetry,
				'soaRefresh' => $DBDNSZone->soaRefresh,
				'soaExpire' => $DBDNSZone->soaExpire,
				'domainFarm' => $DBDNSZone->farmId,
				'domainFarmRole' => $DBDNSZone->farmRoleId
			),
			'records' => $DBDNSZone->getRecords()
		), array('ui/dnszones/dnsfield.js'));
	}
		
	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'domainId' => array('type' => 'int'),

			'domainName', 'domainType',

			'domainFarm' => array('type' => 'int'),
			'domainFarmRole' => array('type' => 'int'),

			'soaRefresh' => array('type' => 'int'),
			'soaExpire' => array('type' => 'int'),
			'soaRetry' => array('type' => 'int'),

			'records' => array('type' => 'json')
		));

		$errors = array();

		// validate farmId, farmRoleId
		$farmId = 0;
		$farmRoleId = 0;
		if ($this->getParam('domainFarm')) {
			$DBFarm = DBFarm::LoadByID($this->getParam('domainFarm'));

			if (! $this->user->getPermissions()->check($DBFarm))
				$errors['domainFarm'] = _('Farm not found');
			else {
				$farmId = $DBFarm->ID;

				if ($this->getParam('domainFarmRole')) {
					$DBFarmRole = DBFarmRole::LoadByID($this->getParam('domainFarmRole'));
					if ($DBFarmRole->FarmID != $DBFarm->ID)
						$errors['domainFarmRole'] = _('Role not found');
					else
						$farmRoleId = $DBFarmRole->ID;
				}
			}
		}

		// validate domain name
		$domainName = '';
		if (! $this->getParam('domainId')) {
			if ($this->getParam('domainType') == 'own') {
				$Validator = new Validator();
				if (! $Validator->IsDomain($this->getParam('domainName')))
					$errors['domainName'] = _("Invalid domain name");
				else {
					$domainChunks = explode(".", $this->getParam('domainName'));
					$chkDmn = '';

					while (count($domainChunks) > 0) {
						$chkDmn = trim(array_pop($domainChunks).".{$chkDmn}", ".");
						$chkDomainId = $this->db->GetOne("SELECT id FROM dns_zones WHERE zone_name=? AND client_id != ?", array($chkDmn, $this->user->getAccountId()));
						if ($chkDomainId) {
							if ($chkDmn == $this->getParam('domainName'))
								$errors['domainName'] = sprintf(_("%s already exists on scalr nameservers"), $this->getParam('domainName'));
							else {
								$chkDnsZone = DBDNSZone::loadById($chkDomainId);
								$access = false;
								foreach (explode(";", $chkDnsZone->allowedAccounts) as $email) {
									if ($email == $this->user->getEmail())
										$access = true;
								}

								if (!$access)
									$errors['domainName'] = sprintf(_("You cannot use %s domain name because top level domain %s does not belong to you"), $this->getParam('domainName'), $chkDmn);
							}
						}
					}

					//if (! $errors['domainName'])
						$domainName = $this->getParam('domainName');
				}
			} else
				$domainName = Scalr::GenerateUID() . '.' . CONFIG::$DNS_TEST_DOMAIN_NAME;

			// check in DB
			$rez = $this->db->GetOne("SELECT id FROM dns_zones WHERE zone_name = ?", array($domainName));
			if ($rez)
				$errors['domainName'] = 'Domain name already exist in database';
		}

		$records = array();
		foreach ($this->getParam('records') as $key => $r) {
			if (($r['name'] || $r['value']) && $r['issystem'] == 0) {
				$r['name'] = str_replace("%hostname%", "{$domainName}", $r['name']);
				$r['value'] = str_replace("%hostname%", "{$domainName}", $r['value']);

				$records[$key] = $r;
			}
		}

		$recordsValidation = Scalr_Net_Dns_Zone::validateRecords($records);
		if ($recordsValidation !== true)
			$errors = array_merge($errors, $recordsValidation);

		if (count($errors) == 0) {
			if ($this->getParam('domainId')) {
				$DBDNSZone = DBDNSZone::loadById($this->getParam('domainId'));
				$this->user->getPermissions()->validate($DBDNSZone);

				$DBDNSZone->soaRefresh = $this->getParam('soaRefresh');
				$DBDNSZone->soaExpire = $this->getParam('soaExpire');
				$DBDNSZone->soaRetry = $this->getParam('soaRetry');

				$this->response->success("DNS zone successfully updated. It could take up to 5 minutes to update it on NS servers.");
			} else {
				$DBDNSZone = DBDNSZone::create(
					$domainName,
					$this->getParam('soaRefresh'),
					$this->getParam('soaExpire'),
					str_replace('@', '.', $this->user->getEmail()),
					$this->getParam('soaRetry')
				);

				$DBDNSZone->clientId = $this->user->getAccountId();
				$DBDNSZone->envId = $this->getEnvironmentId();

				$this->response->success("DNS zone successfully added to database. It could take up to 5 minutes to setup it on NS servers.");
			}

			if ($DBDNSZone->farmRoleId != $farmRoleId || $DBDNSZone->farmId != $farmId) {
				$DBDNSZone->farmId = 0;
				$DBDNSZone->updateSystemRecords();
			}

			$DBDNSZone->farmRoleId = $farmRoleId;
			$DBDNSZone->farmId = $farmId;

			$DBDNSZone->setRecords($records);
			$DBDNSZone->save(true);
		} else {
			$this->response->failure();
			$this->response->data(array('errors' => $errors));
		}
	}

	public function xGetFarmRolesAction()
	{
		$farmRoles = self::loadController('Roles', 'Scalr_UI_Controller_Farms')->getList();
		if (count($farmRoles))
			$farmRoles[0] = array('id' => 0, 'name' => '');

		$this->response->data(array('farmRoles' => $farmRoles));
	}


	public function xRemoveZonesAction()
	{
		$this->request->defineParams(array(
			'zones' => array('type' => 'json')
		));

		foreach ($this->getParam('zones') as $dd) {
			$zone = DBDNSZone::loadById($dd);
			if (! $this->user->getPermissions()->check($zone))
				continue;

			$zone->status = DNS_ZONE_STATUS::PENDING_DELETE;
			$zone->save();
		}

		$this->response->success();
	}

	public function xListZonesAction()
	{
		$this->request->defineParams(array(
			'clientId' => array('type' => 'int'),
			'farmRoleId' => array('type' => 'int'),
			'farmId' => array('type' => 'int'),
			'dnsZoneId' => array('type' => 'int'),
			'sort' => array('type' => 'json', 'default' => array('property' => 'id', 'direction' => 'ASC'))
		));

		$sql = "select * FROM dns_zones WHERE env_id=" . $this->db->qstr($this->getEnvironmentId());

		if ($this->getParam('clientId'))
			$sql .= " AND client_id=".$this->db->qstr($this->getParam('clientId'));

		if ($this->getParam('farmRoleId'))
			$sql .= " AND farm_roleid=".$this->db->qstr($this->getParam('farmRoleId'));

		if ($this->getParam('farmId'))
			$sql .= " AND farm_id=".$this->db->qstr($this->getParam('farmId'));

		if ($this->getParam('dnsZoneId'))
			$sql .= " AND id=".$this->db->qstr($this->getParam('dnsZoneId'));


		$response = $this->buildResponseFromSql($sql, array("zone_name", "id", "farm_id", "farm_roleid"));

		foreach ($response["data"] as &$row) {
			if ($row['farm_roleid']) {
				try {
					$DBFarmRole = DBFarmRole::LoadByID($row['farm_roleid']);

					$row['role_name'] = $DBFarmRole->GetRoleObject()->name;
					$row['farm_name'] = $DBFarmRole->GetFarmObject()->Name;
					$row['farm_id'] = $DBFarmRole->FarmID;
				} catch(Exception $e) {
					$row['farm_roleid'] = false;
				}
			}

			if ($row['farm_id'] && !$row['farm_name']) {
				$DBFarm = DBFarm::LoadByID($row['farm_id']);

				$row['farm_name'] = $DBFarm->Name;
				$row['farm_id'] = $DBFarm->ID;
			}
		}

		$this->response->data($response);
	}
}
