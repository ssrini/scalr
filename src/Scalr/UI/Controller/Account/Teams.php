<?php

class Scalr_UI_Controller_Account_Teams extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'teamId';

	public static function getApiDefinitions()
	{
		return array('xListTeams', 'xCreate', 'xRemove', 'xAddUser', 'xRemoveUser', 'xAddEnvironment', 'xRemoveEnvironment');
	}

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function viewAction()
	{
		$this->response->page('ui/account/teams/view.js', array(
			'teamManage' => ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER),
			'permissionsManage' => $this->user->getAccount()->isFeatureEnabled(Scalr_Limits::FEATURE_USERS_PERMISSIONS)
		), array(), array('ui/account/teams/view.css'));
	}
	
	public function listAction()
	{
		$this->response->page('ui/account/teams/list.js', array(), array(), array('ui/account/teams/list.css'));
	}
	
	public function xListTeamsAction()
	{
		$this->request->defineParams(array(
			'sort' => array('type' => 'json')
		));
		
		// account owner, team owner
		if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $this->user->isTeamOwner())
			$sql = "SELECT id, name FROM account_teams WHERE account_id='" . $this->user->getAccountId() . "'";
		else
			// team user
			$sql = 'SELECT account_teams.id, name FROM account_teams
				JOIN account_team_users ON account_teams.id = account_team_users.team_id WHERE user_id="' . $this->user->getId() . '"';

		$response = $this->buildResponseFromSql($sql, array('id', 'name'));
		foreach ($response["data"] as &$row) {
			try {
				$team = Scalr_Account_Team::init();
				$team->loadById($row['id']);
				$row['owner'] = array(
					'id' => $team->getOwner()->getId(),
					'email' => $team->getOwner()->getEmail()
				);

				$row['environments'] = $team->getEnvironments();
			} catch (Exception $e) {}
			
			$row['ownerTeam'] = $this->user->isTeamOwner($row['id']);
			$team = Scalr_Account_Team::init()->loadById($row['id']);
			$row['groups'] = $team->getGroups();
			$users = $team->getUsers();
			foreach ($users as &$user) {
				$user['groups'] = $team->getUserGroups($user['id']);
			}
			$row['users'] = $users;
		}
		$this->response->data($response);
	}
	
	public function createAction()
	{
		if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER) {
			$users = $this->db->GetAll("SELECT id, email, fullname FROM account_users WHERE type = ? AND status = ? AND account_id = ?", array(
				Scalr_Account_User::TYPE_TEAM_USER,
				Scalr_Account_User::STATUS_ACTIVE,
				$this->user->getAccountId()
			));
			
			$envs = $this->db->GetAll("SELECT id, name FROM client_environments WHERE client_id = ?", array(
				$this->user->getAccountId()
			));
	
			$this->response->page('ui/account/teams/create.js', array(
				'users' => $users,
				'envs' => $envs,
				'teamManage' => true,
				'teamCreate' => true
			));
		} else
			throw new Scalr_Exception_InsufficientPermissions();
	}
	
	public function diffUsersCmp($u1, $u2)
	{
		$v = ($u1['id'] == $u2['id']) ? 0 : (($u1['id'] > $u2['id']) ? 1 : -1);
		return $v;
	}

	public function editAction()
	{
		$team = Scalr_Account_Team::init();
		$team->loadById($this->getParam('teamId'));
		
		if ($team->accountId == $this->user->getAccountId() &&
			($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $this->user->isTeamOwner($team->id))
		) {
			$users = $this->db->GetAll("SELECT id, email, fullname FROM account_users WHERE type = ? AND status = ? AND account_id = ?", array(
				Scalr_Account_User::TYPE_TEAM_USER,
				Scalr_Account_User::STATUS_ACTIVE,
				$this->user->getAccountId()
			));
			$teamUsers = $team->getUsers();
			
			foreach ($teamUsers as $key => $user) {
				$teamUsers[$key]['groups'] = $team->getUserGroups($user['id']);
			}
			
			$envs = $this->db->GetAll("SELECT id, name FROM client_environments WHERE client_id = ?", array(
				$this->user->getAccountId()
			));
			
			$users = array_udiff($users, $teamUsers, array($this, 'diffUsersCmp'));
			sort($users);
			
			$this->response->page('ui/account/teams/create.js', array(
				'users' => $users,
				'envs' => $envs,
				'teamManage' => $this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER ? true : false,
				'permissionsManage' => $this->user->getAccount()->isFeatureEnabled(Scalr_Limits::FEATURE_USERS_PERMISSIONS),
				'teamCreate' => false,
				'team' => array(
					'id' => $team->id,
					'name' => $team->name,
					'envs' => $team->getEnvironments(),
					'users' => $teamUsers,
					'groups' => $team->getGroups()
				)
			));
		} else
			throw new Scalr_Exception_InsufficientPermissions();
	}
	
	public function xCreateAction()
	{
		if ($this->user->getType() != Scalr_Account_User::TYPE_ACCOUNT_OWNER)
			throw new Scalr_Exception_InsufficientPermissions();
		
		$this->request->defineParams(array(
			'name' => array('type' => 'string', 'validator' => array(
				Scalr_Validator::NOHTML => true,
				Scalr_Validator::REQUIRED => true
			)),
			'ownerId' => array('type' => 'int'), 'validator' => array(
				Scalr_Validator::REQUIRED => true
			),
			'envId' => array('type' => 'int')
		));
		
		$this->request->validate();
		
		try {
			$user = Scalr_Account_User::init();
			$user->loadById($this->getParam('ownerId'));
			if ($user->getAccountId() != $this->user->getAccountId())
				throw new Scalr_Exception_InsufficientPermissions();
		} catch (Exception $e) {
			$this->request->addValidationErrors('ownerId', array($e->getMessage()));
		}
		
		try {
			if ($this->getParam('envId')) {
				$env = Scalr_Environment::init();
				$env->loadById($this->getParam('envId'));
				if ($env->clientId != $this->user->getAccountId())
					throw new Scalr_Exception_InsufficientPermissions();
			}
		} catch (Exception $e) {
			$this->request->addValidationErrors('envId', array($e->getMessage()));
		}

		if (! $this->request->isValid()) {
			$this->response->failure();
			$this->response->data($this->request->getValidationErrors());
			return;
		}
		
		$team = Scalr_Account_Team::init();
		$team->name = $this->getParam('name');
		$team->accountId = $this->user->getAccountId();
		$team->save();
		$team->addUser($this->getParam('ownerId'), Scalr_Account_Team::PERMISSIONS_OWNER);
		if ($this->getParam('envId'))
			$team->addEnvironment($this->getParam('envId'));
		
		$this->response->success('Team successfully saved');
		$this->response->data(array('teamId' => $team->id));
	}
	
	/**
	 * 
	 * @return Scalr_Account_Team 
	 */
	public function getTeam()
	{
		$team = Scalr_Account_Team::init();
		$team->loadById($this->getParam('teamId'));
		
		if ($team->accountId != $this->user->getAccountId())
			throw new Scalr_Exception_InsufficientPermissions();
			
		return $team;
	}
	
	public function xAddUserAction()
	{
		$this->request->defineParams(array(
			'userId' => array('type' => 'int'),
			'userPermissions' => array('type' => 'string', 'validator' => array(
				Scalr_Validator::REQUIRED => true,
				Scalr_Validator::RANGE => array(
					Scalr_Account_Team::PERMISSIONS_FULL,
					Scalr_Account_Team::PERMISSIONS_GROUPS,
					Scalr_Account_Team::PERMISSIONS_OWNER
				)
			)),
			'userGroups' => array('type' => 'json') 
		));

		$team = $this->getTeam();
		if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $this->user->isTeamOwner($team->id)) {
			if ($this->request->validate()->isValid()) {
				$user = Scalr_Account_User::init();
				$user->loadById($this->getParam('userId'));
				$this->user->getPermissions()->validate($user);
				
				if ($this->user->getType() != Scalr_Account_User::TYPE_ACCOUNT_OWNER) {
					if ($this->getParam('userPermissions') == Scalr_Account_Team::PERMISSIONS_OWNER ||
						$team->isTeamOwner($user->id)
					) {
						throw new Scalr_Exception_InsufficientPermissions();
					}
				}
				
				$team->addUser($user->id, $this->getParam('userPermissions'));
				
				$groups = array();
				foreach ($this->getParam('userGroups') as $id) {
					if ($team->isTeamGroup($id))
						$groups[] = $id;
				}
				$team->setUserGroups($user->id, $groups);
				
				$this->response->success('User successfully added to team');
			} else {
				$this->response->failure();
				$this->response->data($this->request->getValidationErrors());
				return;
			}
		} else {
			throw new Scalr_Exception_InsufficientPermissions();
		}
	}
	
	public function xRemoveUserAction()
	{
		$team = $this->getTeam();
		if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $this->user->isTeamOwner($team->id)) {
			$user = Scalr_Account_User::init();
			$user->loadById($this->getParam('userId'));
			$this->user->getPermissions()->validate($user);
			
			if ($this->user->getType() != Scalr_Account_User::TYPE_ACCOUNT_OWNER) {
				if ($team->isTeamOwner($user->id)) {
					throw new Scalr_Exception_InsufficientPermissions();
				}
			}
			
			$team->removeUser($user->id);
			$this->response->success('User successfully removed from team');
		} else {
			throw new Scalr_Exception_InsufficientPermissions();
		}
	}
	
	public function xAddEnvironmentAction()
	{
		$team = $this->getTeam();
		if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER) {
			$env = Scalr_Environment::init();
			$env->loadById($this->getParam('envId'));
			$this->user->getPermissions()->validate($env);
			
			$team->addEnvironment($env->id);
			$this->response->success('Environment successfully added to team');
		} else {
			throw new Scalr_Exception_InsufficientPermissions();
		}
	}
	
	public function xRemoveEnvironmentAction()
	{
		$team = $this->getTeam();
		if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER) {
			$env = Scalr_Environment::init();
			$env->loadById($this->getParam('envId'));
			$this->user->getPermissions()->validate($env);
			
			$team->removeEnvironment($env->id);
			$this->response->success('Environment successfully removed from team');
		} else {
			throw new Scalr_Exception_InsufficientPermissions();
		}
	}
	
	public function xSaveAction()
	{
		$this->request->defineParams(array(
			'envs' => array('type' => 'json'),
			'users' => array('type' => 'json'),
			'teamName', 'teamOwner'
		));
		
		if (! $this->getParam('teamName'))
			throw new Exception('Team name should not be empty');
		
		$team = Scalr_Account_Team::init();
		if ($this->getParam('teamId')) {
			$team->loadById($this->getParam('teamId'));
			if (! ($team->accountId == $this->user->getAccountId() &&
					($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $this->user->isTeamOwner($team->id))
			))
				throw new Scalr_Exception_InsufficientPermissions();
		} else {
			if ($this->user->getType() != Scalr_Account_User::TYPE_ACCOUNT_OWNER)
				throw new Scalr_Exception_InsufficientPermissions();
			$team->accountId = $this->user->getAccountId();
		}
		
		$this->db->BeginTrans();

		try {
			if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER) {
				$team->name = $this->getParam('teamName');
			}
			$team->save();

			if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER) {
				$team->clearUsers();
				foreach ($this->getParam('users') as $user) {
					$team->addUser($user['id'], $user['permissions']);
					
					$gr = array();
					if ($user['permissions'] == Scalr_Account_Team::PERMISSIONS_GROUPS) {
						foreach ($user['groups'] as $value) {
							if ($team->isTeamGroup($value['id']))
								$gr[] = $value['id'];
						}
					}
					$team->clearUserGroups($user['id']);
					$team->setUserGroups($user['id'], $gr);
				}

				$team->clearEnvironments();
				foreach ($this->getParam('envs') as $id)
					$team->addEnvironment($id);
			} else {
				$owner = $team->getOwner();
				$team->clearUsers();
				foreach ($this->getParam('users') as $user) {
					if (! in_array($user['permissions'], array(
						Scalr_Account_Team::PERMISSIONS_FULL,
						Scalr_Account_Team::PERMISSIONS_GROUPS,
						Scalr_Account_Team::PERMISSIONS_OWNER
					)))
						$user['permissions'] = Scalr_Account_Team::PERMISSIONS_GROUPS;
					
					if ($user['permissions'] == Scalr_Account_Team::PERMISSIONS_OWNER && $user['id'] != $owner->id)
						$user['permissions'] = Scalr_Account_Team::PERMISSIONS_GROUPS;
						
					$team->addUser($user['id'], $user['permissions']);
					
					$gr = array();
					if ($user['permissions'] == Scalr_Account_Team::PERMISSIONS_GROUPS) {
						foreach ($user['groups'] as $value) {
							if ($team->isTeamGroup($value['id']))
								$gr[] = $value['id'];
						}
					}
					$team->clearUserGroups($user['id']);
					$team->setUserGroups($user['id'], $gr);
				}
			}
		} catch (Exception $e) {
			$this->db->RollbackTrans();
			throw $e;
		}
		
		$this->db->CommitTrans();
		$this->response->success('Team successfully saved');
	}
	
	public function xRemoveAction()
	{
		$team = Scalr_Account_Team::init();
		$team->loadById($this->getParam('teamId'));
		if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER && $team->accountId == $this->user->getAccountId())
			$team->delete();
		else
			throw new Scalr_Exception_InsufficientPermissions();
			
		$this->response->success();
	}

	/*
	 * Permission Groups
	 */
	public function getPermissions($path)
	{
		$result = array();
		foreach(scandir($path) as $p) {
			if ($p == '.' || $p == '..' || $p == '.svn')
				continue;
				
			$p1 = $path . '/' . $p;
				
			if (is_dir($p1)) {
				$result = array_merge($result, $this->getPermissions($p1));
				continue;
			}
			
			$p1 = str_replace(SRCPATH . '/', '', $p1);
			$p1 = str_replace('.php', '', $p1);
			$p1 = str_replace('/', '_', $p1);
			
			if (method_exists($p1, 'getPermissionDefinitions'))
				$result[str_replace('Scalr_UI_Controller_', '', $p1)] = array_values(array_unique(array_values($p1::getPermissionDefinitions())));
		}

		return $result;
	}
	
	
	public function xCreatePermissionGroupAction()
	{
		$team = Scalr_Account_Team::init()->loadById($this->getParam(self::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($team);
		if (! ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $team->isTeamOwner($this->user->getId())))
			throw new Scalr_Exception_InsufficientPermissions();
			
		$this->request->defineParams(array(
			'name' => array('type' => 'string', 'validator' => array(
				Scalr_Validator::REQUIRED => true,
				Scalr_Validator::NOHTML => true
			))
		));
		
		$this->request->validate();
		if ($this->request->isValid()) {
			$group = Scalr_Account_Group::init();
			$group->name = $this->getParam('name');
			$group->teamId = $team->id;
			$group->isActive = 1;
			$group->save();
		
			$this->response->data(array('group' => array('id' => $group->id, 'name' => $group->name)));
		} else {
			$this->response->failure($this->request->getValidationErrorsMessage());
		}
	}
	
	public function permissionGroupAction()
	{
		$team = Scalr_Account_Team::init()->loadById($this->getParam(self::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($team);
		if (! ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $team->isTeamOwner($this->user->getId())))
			throw new Scalr_Exception_InsufficientPermissions();
		
		$group = Scalr_Account_Group::init();
		$group->loadById($this->getParam('groupId'));
		if ($group->teamId != $team->id)
			throw new Scalr_Exception_InsufficientPermissions();			

		$this->response->page('ui/account/teams/permissiongroups.js', array(
			'permissions' => $this->getPermissions(SRCPATH . '/Scalr/UI/Controller'),
			'group' => $group->getPermissions(),
			'teamId' => $team->id,
			'teamName' => $team->name,
			'groupName' => $group->name,
			'groupId' => $group->id
		));
	}
	
	public function xSavePermissionGroupAction()
	{
		$this->request->defineParams(array(
			'access' => array('type' => 'array'),
			'permission' => array('type' => 'array'),
			'controller' => array('type' => 'array')
		));
		
		$team = Scalr_Account_Team::init()->loadById($this->getParam(self::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($team);
		if (! ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $team->isTeamOwner($this->user->getId())))
			throw new Scalr_Exception_InsufficientPermissions();
		
		$group = Scalr_Account_Group::init();
		$group->loadById($this->getParam('groupId'));
		if ($group->teamId != $team->id)
			throw new Scalr_Exception_InsufficientPermissions();

		$permissions = $this->getPermissions(SRCPATH . '/Scalr/UI/Controller');
		$rules = array();
		$access = $this->getParam('access');
		$perms = $this->getParam('permission');
		$controller = $this->getParam('controller');
		
		foreach ($controller as $key => $value) {
			if (array_key_exists($key, $permissions)) {
				$rules[$key] = array();
				
				if ($access[$key] == 'FULL')
					$rules[$key][] = 'FULL';
				else if ($access[$key] == 'VIEW') {
					$rules[$key][] = 'VIEW';
					if (isset($perms[$key])) {
						foreach ($perms[$key] as $k => $val) {
							if (in_array($k, $permissions[$key]))
								$rules[$key][] = $k;
						}
					}
				}
			}
		}
		
		foreach ($rules as $key => $value)
			$rules[$key] = implode(',', $value);
		
		$group->setPermissions($rules);
		$this->response->success();
	}

	public function xRemovePermissionGroupAction()
	{
		$team = Scalr_Account_Team::init()->loadById($this->getParam(self::CALL_PARAM_NAME));
		$this->user->getPermissions()->validate($team);
		if (! ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER || $team->isTeamOwner($this->user->getId())))
			throw new Scalr_Exception_InsufficientPermissions();
		
		$group = Scalr_Account_Group::init();
		$group->loadById($this->getParam('groupId'));
		if ($group->teamId != $team->id)
			throw new Scalr_Exception_InsufficientPermissions();
		
		$group->delete();
		
		$this->response->success();
	}
	
	public function xGetUsersAction()
	{
		$sql = "SELECT id, email, fullname FROM account_users WHERE account_id = {$this->user->getAccountId()} ORDER BY email";
		$users = $this->db->GetAll($sql);
		$sql = "SELECT user_id FROM account_team_users WHERE team_id = {$this->getParam('teamId')}";
		$teamUsers = $this->db->GetAll($sql);
		$user = array();
		foreach ($users as $key => $userValue) {
			$inTeam = false;
			foreach ($teamUsers as $key => $teamValue) {
				if($userValue['id'] == $teamValue['user_id'])
					$inTeam = true;
			}
			if(!$inTeam)
				$user[] = $userValue;
		}
		$this->response->data(array('data'=>$user));
	}
}
