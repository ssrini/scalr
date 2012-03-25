<?php

class Scalr_UI_Controller_Admin_Accounts extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'accountId';
	
	public static function getApiDefinitions()
	{
		return array('xRemove', 'xSave', 'xListAccounts', 'xGetInfo');
	}
	
	public function hasAccess()
	{
		return $this->user && ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN);
	}

	public function defaultAction()
	{
		$this->viewAction();
	}
	
	public function viewAction()
	{
		$this->response->page('ui/admin/accounts/view.js');
	}
	
	public function xListAccountsAction()
	{
		$this->request->defineParams(array(
			'sort' => array('type' => 'string', 'default' => 'id'),
			'dir' => array('type' => 'string', 'default' => 'ASC'),
			'accountId' => array('type' => 'int')
		));

		$sql = "SELECT id, name, dtadded, status FROM clients WHERE 1=1";
		
		if ($this->getParam('accountId'))
			$sql .= ' AND id = '.$this->db->qstr($this->getParam('accountId'));
		
		$chunks = explode("=", $this->getParam('query'));
			
		if ($chunks[0] == 'farm') {
			$sql .= ' AND id IN (SELECT clientid FROM farms WHERE id LIKE '.$this->db->qstr("%".$chunks[1]."%").')';
			$this->request->setParams(array('query' => ''));
		}
		
		if ($chunks[0] == 'owner') {
			$sql .= ' AND id IN (SELECT account_id FROM account_users WHERE `type` = '.$this->db->qstr(Scalr_Account_User::TYPE_ACCOUNT_OWNER).' AND email LIKE '.$this->db->qstr("%".$chunks[1]."%").')';
			$this->request->setParams(array('query' => ''));
		}
			
		$response = $this->buildResponseFromSql($sql, array("id", "name"));
		foreach ($response['data'] as &$row) {
			$account = Scalr_Account::init()->loadById($row['id']);
			
			try {
				$row['ownerEmail'] = $account->getOwner()->getEmail();
			} catch (Exception $e){
				$row['ownerEmail'] = '*No owner*';
			}
			$row['dtadded'] = Scalr_Util_DateTime::convertTz($row['dtadded']);
			
			$limit = Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_ENVIRONMENTS, $row['id']);
			$row['envs'] = $limit->getCurrentUsage();
			$row['limitEnvs'] = $limit->getLimitValue() > -1 ? $limit->getLimitValue() : '-';

			$limit = Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_FARMS, $row['id']);
			$row['farms'] = $limit->getCurrentUsage();
			$row['limitFarms'] = $limit->getLimitValue() > -1 ? $limit->getLimitValue() : '-';
			
			$limit = Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_USERS, $row['id']);
			$row['users'] = $limit->getCurrentUsage();
			$row['limitUsers'] = $limit->getLimitValue() > -1 ? $limit->getLimitValue() : '-';
			
			$limit = Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_SERVERS, $row['id']);
			$row['servers'] = $limit->getCurrentUsage();
			$row['limitServers'] = $limit->getLimitValue() > -1 ? $limit->getLimitValue() : '-';
		}

		$this->response->data($response);
	}

	public function xRemoveAction()
	{
		$this->request->defineParams(array(
			'accounts' => array('type' => 'json')
		));
		
		foreach ($this->getParam('accounts') as $dd) {
			Scalr_Account::init()->loadById($dd)->delete();
		}
		
		$this->response->success("Selected account(s) successfully removed");
	}
	
	public function createAction()
	{
		$this->response->page('ui/admin/accounts/edit.js', array(
			'account' => array(
				'id' => 0,
				'name' => '',
				'comments' => '',
		
				'limitEnv' => -1,
				'limitFarms' => -1,
				'limitServers' => -1,
				'limitUsers' => -1,
		
				'featureApi' => '1',
				'featureScripting' => '1',
				'featureCsm' => '1'
			)
		));
	}
	
	public function getAccount()
	{
		$account = Scalr_Account::init()->loadById($this->getParam(self::CALL_PARAM_NAME));
		return array(
			'id' => $account->id,
			'name' => $account->name,
			'comments' => $account->comments,
	
			'limitEnv' => Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_ENVIRONMENTS, $account->id)->getLimitValue(),
			'limitFarms' => Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_FARMS, $account->id)->getLimitValue(),
			'limitServers' => Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_SERVERS, $account->id)->getLimitValue(),
			'limitUsers' => Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_USERS, $account->id)->getLimitValue()
		);
	}
	
	public function editAction()
	{
		$this->response->page('ui/admin/accounts/edit.js', array(
			'account' => $this->getAccount()
		));
	}

	public function xGetInfoAction()
	{
		$this->response->data(array('account' => $this->getAccount()));
	}

	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'id' => array('type' => 'int'),
			'name' => array('type' => 'string', 'validator' => array(
				Scalr_Validator::NOHTML => true,
				Scalr_Validator::REQUIRED => true
			)),
			'comments' => array('type' => 'string')
		));
		
		$account = Scalr_Account::init();
		
		if ($this->getParam('id')) {
			$account->loadById($this->getParam('id'));
		} else {
			$account->status = Scalr_Account::STATUS_ACTIVE;
			
			$this->request->defineParams(array(
				'ownerEmail' => array('type' => 'string', 'validator' => array(
					Scalr_Validator::REQUIRED => true,
					Scalr_Validator::EMAIL => true
				)),
				'ownerPassword' => array('type '=> 'string', 'validator' => array(
					Scalr_Validator::MINMAX => array('min' => 6)
				))
			));
		}
		
		if (! $this->request->validate()->isValid()) {
			$this->response->failure();
			$this->response->data($this->request->getValidationErrors());
			return;
		}
			
		$this->db->BeginTrans();
		try {
			
			$account->name = $this->getParam('name');
			$account->comments = $this->getParam('comments');
			
			$account->save();

			$account->setLimits(array(
				Scalr_Limits::ACCOUNT_ENVIRONMENTS => $this->getParam('limitEnv'),
				Scalr_Limits::ACCOUNT_FARMS => $this->getParam('limitFarms'),
				Scalr_Limits::ACCOUNT_SERVERS => $this->getParam('limitServers'),
				Scalr_Limits::ACCOUNT_USERS => $this->getParam('limitUsers')
			));

			if (!$this->getParam('id')) {
				$account->createEnvironment("default", true);
				$account->createUser($this->getParam('ownerEmail'), $this->getParam('ownerPassword'), Scalr_Account_User::TYPE_ACCOUNT_OWNER);
			}
		} catch (Exception $e) {
			$this->db->RollbackTrans();
			throw $e;
		}
		
		$this->db->CommitTrans();
		$this->response->data(array('accountId' => $account->id));
	}
	
	public function loginAsOwnerAction()
	{
		$account = Scalr_Account::init()->loadById($this->getParam(self::CALL_PARAM_NAME));
		$owner = $account->getOwner();
		
		Scalr_Session::create($owner->getAccountId(), $owner->getId(), Scalr_AuthToken::ACCOUNT_ADMIN);
		
		UI::Redirect("/#/dashboard");
	}
}
