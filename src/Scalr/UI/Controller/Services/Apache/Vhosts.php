<?php
class Scalr_UI_Controller_Services_Apache_Vhosts extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'vhostId';

	public static function getPermissionDefinitions()
	{
		return array();
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function viewAction()
	{
		$this->response->page('ui/services/apache/vhosts/view.js');
	}

	public function xRemoveAction()
	{
		$this->request->defineParams(array(
			'vhosts' => array('type' => 'json')
		));

		foreach ($this->getParam('vhosts') as $vhostId) {
			$dbFarmId = $this->db->GetOne("SELECT farm_id FROM apache_vhosts WHERE id = ? AND env_id = ?",
				array($vhostId, $this->getEnvironmentId())
			);

			if ($dbFarmId) {
				$this->db->Execute("DELETE FROM apache_vhosts WHERE id = ? AND env_id = ?",
					array($vhostId, $this->getEnvironmentId())
				);

				$dbFarm = DBFarm::LoadByID($dbFarmId);

				$servers = $dbFarm->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
				foreach ($servers as $dBServer)
				{
					if ($dBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::NGINX) ||
						$dBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::APACHE))
						$dBServer->SendMessage(new Scalr_Messaging_Msg_VhostReconfigure());
				}
			}
		}

		$this->response->success();
	}

	public function editAction()
	{
		$vHost = Scalr_Service_Apache_Vhost::init()->loadById($this->getParam('vhostId'));
		$this->user->getPermissions()->validate($vHost);

		$options = unserialize($vHost->templateOptions);

		$farms = self::loadController('Farms')->getList();

		$this->request->setParams(array('farmId' => $vHost->farmId));
		$farmRoles = self::loadController('Roles', 'Scalr_UI_Controller_Farms')->getList();

		if ($vHost->isSslEnabled) {
			$info = openssl_x509_parse($vHost->sslCert, false);
			$sslCertName = $info["name"];

			$info = openssl_x509_parse($vHost->caCert, false);
			$caCertName = $info["name"];

			if ($vHost->sslKey)
				$sslKeyName = '*Private key uploaded*';
		}

		$this->response->page('ui/services/apache/vhosts/create.js', array(
			'farms' => $farms,
			'farmRoles' => $farmRoles,
			'farmId' => $vHost->farmId,
			'farmRoleId' => $vHost->farmRoleId,
			'vhostId' => $vHost->id,
			'domainName' => $vHost->domainName,
			'isSslEnabled' => (int)$vHost->isSslEnabled,
			'documentRoot' => $options['document_root'],
			'logsDir' => $options['logs_dir'],
			'sslCertName' => $sslCertName,
			'caCertName' => $caCertName,
			'sslKeyName' => $sslKeyName,
			'serverAdmin' => $options['server_admin'],
			'serverAlias' => $options['server_alias'],
			'nonSslTemplate' => $vHost->httpdConf,
			'sslTemplate' => $vHost->httpdConfSsl ? $vHost->httpdConfSsl : @file_get_contents("../templates/services/apache/ssl.vhost.tpl")
		));
	}

	public function createAction()
	{
		$farms = self::loadController('Farms')->getList();

		$this->response->page('ui/services/apache/vhosts/create.js', array(
			'farms' => $farms,
			'farmRoles' => array(),
			'farmId' => 0,
			'farmRoleId' => 0,
			'vhostId' => 0,
			'domainName' => '',
			'isSslEnabled' => 0,
			'documentRoot' => '/var/www',
			'logsDir' => '/var/log',
			'serverAdmin' => $this->user->getEmail(),
			'serverAlias' => "",
			'nonSslTemplate' => @file_get_contents("../templates/services/apache/nonssl.vhost.tpl"),
			'sslTemplate' => @file_get_contents("../templates/services/apache/ssl.vhost.tpl")
		));
	}

	public function xSaveAction()
	{
		$validator = new Validator();

		try {
			if(!$validator->IsDomain($this->getParam('domainName')))
				$err['domainName'] = _("Domain name is incorrect");

			if (!$this->getParam('farmId'))
				$err['farmId'] = _("Farm required");
			else {
				$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
				$this->user->getPermissions()->validate($dbFarm);
			}

			if (!$this->getParam('farmRoleId'))
				$err['farmRoleId'] = _("Role required");
			else {
				$dbFarmRole = DBFarmRole::LoadByID($this->getParam('farmRoleId'));

				if($dbFarmRole->FarmID != $dbFarm->ID)
					$err['farmRoleId'] = _("Role not found");
			}

			if(!$validator->IsEmail($this->getParam('serverAdmin')))
				$err['serverAdmin'] = _("Server admin's email is incorrect or empty ");

			if(!$this->getParam('documentRoot'))
				$err['documentRoot'] = _("Document root required");

			if(!$this->getParam('logsDir'))
				$err['logsDir'] = _("Logs directory required");

			if ($this->db->GetOne("SELECT id FROM apache_vhosts WHERE env_id=? AND `name` = ? AND id != ?", array($this->getEnvironmentId(), $this->getParam('domainName'), $this->getParam('vhostId'))))
				$err['domainName'] = "'{$this->getParam('domainName')}' virtualhost already exists";

		} catch (Exception $e) {
			$err[] = $e->getMessage();
		}

		if (count($err) == 0) {
			$vHost = Scalr_Service_Apache_Vhost::init();
			if ($this->getParam('vhostId')) {
				$vHost->loadById($this->getParam('vhostId'));
				$this->user->getPermissions()->validate($vHost);
			} else {
				$vHost->envId = $this->getEnvironmentId();
				$vHost->clientId = $this->user->getAccountId();
			}

			$vHost->domainName = $this->getParam('domainName');
			$vHost->isSslEnabled = $this->getParam('isSslEnabled') == 'on' ? true : false;
			$vHost->farmId = $this->getParam('farmId');
			$vHost->farmRoleId = $this->getParam('farmRoleId');

			$vHost->httpdConf = $this->getParam("nonSslTemplate");

			$vHost->templateOptions = serialize(array(
				"document_root" 	=> trim($this->getParam('documentRoot')),
				"logs_dir"			=> trim($this->getParam('logsDir')),
				"server_admin"		=> trim($this->getParam('serverAdmin')),
				"server_alias"		=> trim($this->getParam('serverAlias'))
			));

			//SSL stuff
			if ($vHost->isSslEnabled) {
				if ($_FILES['certificate']['tmp_name'])
					$vHost->sslCert = file_get_contents($_FILES['certificate']['tmp_name']);

				if ($_FILES['privateKey']['tmp_name'])
					$vHost->sslKey = file_get_contents($_FILES['privateKey']['tmp_name']);

				if ($_FILES['certificateChain']['tmp_name'])
					$vHost->caCert = file_get_contents($_FILES['certificateChain']['tmp_name']);

				$vHost->httpdConfSsl = $this->getParam("sslTemplate");
			} else {
				$vHost->sslCert = "";
				$vHost->sslKey = "";
				$vHost->caCert = "";
				$vHost->httpdConfSsl = "";
			}

			$vHost->save();

			$servers = $dbFarm->GetServersByFilter(array('status' => array(SERVER_STATUS::INIT, SERVER_STATUS::RUNNING)));
			foreach ($servers as $dBServer)
			{
				if ($dBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::NGINX) ||
					($dBServer->GetFarmRoleObject()->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::APACHE) && $dBServer->farmRoleId == $vHost->farmRoleId)) {
						$dBServer->SendMessage(new Scalr_Messaging_Msg_VhostReconfigure());
					}
			}

			$this->response->success(_('Virtualhost successfully saved'));
		} else {
			$this->response->failure();
			$this->response->data(array('errors' => $err));
		}
	}

	public function xListVhostsAction()
	{
		$this->request->defineParams(array(
			'farmRoleId' => array('type' => 'int'),
			'farmId' => array('type' => 'int'),
			'vhostId' => array('type' => 'int'),
			'sort' => array('type' => 'json')
		));

		$sql = "SELECT * FROM `apache_vhosts` WHERE 1=1";

		$sql .= " AND env_id = '{$this->getEnvironmentId()}'";

		if ($this->getParam('farmId'))
			$sql .= " AND `farm_id` = " . $this->db->qstr($this->getParam('farmId'));

		if ($this->getParam('farmRoleId'))
			$sql .= " AND `farm_roleid` = " . $this->db->qstr($this->getParam('farmRoleId'));

		if ($this->getParam('vhostId'))
			$sql .= " AND `id` = " . $this->db->qstr($this->getParam('vhostId'));

		$response = $this->buildResponseFromSql($sql, array("name"));

		foreach ($response['data'] as &$row) {
			$row['last_modified'] = Scalr_Util_DateTime::convertTz($row['last_modified']);
			if ($row['farm_roleid'])
			{
				try {
					$DBFarmRole = DBFarmRole::LoadByID($row['farm_roleid']);
	
					$row['farm_name'] 		= $DBFarmRole->GetFarmObject()->Name;
					$row['role_name'] 		= $DBFarmRole->GetRoleObject()->name;
	
				} catch(Exception $e)
				{
					if (stristr($e->getMessage(), "not found"))
						$this->db->Execute ("DELETE FROM apache_vhosts WHERE id=?", array($row['id']));
				}
			}
		}

		$this->response->data($response);
	}
}
