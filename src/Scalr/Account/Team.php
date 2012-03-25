<?php

class Scalr_Account_Team extends Scalr_Model
{
	protected $dbTableName = 'account_teams';
	protected $dbPrimaryKey = "id";
	protected $dbMessageKeyNotFound = "Team #%s not found in database";
	
	const PERMISSIONS_OWNER = 'owner';
	const PERMISSIONS_FULL = 'full';
	const PERMISSIONS_GROUPS = 'groups';

	protected $dbPropertyMap = array(
		'id'			=> 'id',
		'account_id'	=> 'accountId',
		'name'			=> 'name'
	);

	public
		$accountId,
		$name;

	/**
	 *
	 * @return Scalr_Account_Team
	 */
	public static function init() {
		return parent::init();
	}
	
	public function save()
	{
		$id = $this->db->getOne('SELECT id FROM `account_teams` WHERE name = ? AND account_id = ?', array($this->name, $this->accountId));
		if ($id && $this->id != $id)
			throw new Exception('Team with such name already exists');

		return parent::save();
	}
	
	public function delete()
	{
		parent::delete();
		
		foreach ($this->getGroups() as $group)
			$this->db->Execute('DELETE FROM `account_group_permissions` WHERE group_id = ?', array($group['id']));
			
		$this->db->Execute('DELETE FROM `account_groups` WHERE team_id = ?', array($this->id));
			
		$this->db->Execute('DELETE FROM `account_team_users` WHERE team_id = ?', array($this->id));
		$this->db->Execute('DELETE FROM `account_team_envs` WHERE team_id = ?', array($this->id));
		
	}
	
	public function getGroups()
	{
		return $this->db->getAll('SELECT id, name FROM account_groups WHERE team_id = ?', array($this->id));
	}
	
	public function getUsers()
	{
		return $this->db->getAll('SELECT account_users.id, email, fullname, permissions FROM `account_users` JOIN `account_team_users`
			ON account_users.id = account_team_users.user_id WHERE account_team_users.team_id = ?', array($this->id));
	}
	
	public function getUserGroups($userId)
	{
		return $this->db->getAll('SELECT account_groups.id, account_groups.name FROM account_user_groups
			JOIN account_groups ON account_groups.id = account_user_groups.group_id
			WHERE account_user_groups.user_id = ? AND account_groups.team_id = ?', array(
			$userId, $this->id
		));
	}
	
	public function setUserGroups($userId, $groups)
	{
		$this->clearUserGroups($userId);

		foreach ($groups as $value)
			$this->db->Execute('INSERT INTO account_user_groups (user_id, group_id) VALUES(?,?)', array(
				$userId, $value
			));
	}
	
	// deprecated
	public function clearUserGroups($userId)
	{
		$this->db->Execute('DELETE FROM account_user_groups WHERE user_id = ? AND group_id IN (SELECT id FROM account_groups WHERE team_id = ?)', array(
			$userId, $this->id
		));
	}

	// deprecated ?
	public function clearUsers()
	{
		foreach ($this->getUsers() as $user)
			$this->clearUserGroups($user['id']);

		$this->db->Execute('DELETE FROM `account_team_users` WHERE team_id = ?', array($this->id));
	}
	
	// deprecated ?
	public function clearEnvironments()
	{
		$this->db->Execute('DELETE FROM `account_team_envs` WHERE team_id = ?', array($this->id));
	}
	
	/**
	 * 
	 * @var bool $onlyFirst
	 * @return Scalr_Account_User
	 */
	public function getOwner($onlyFirst = true)
	{
		$id = $this->db->getOne('SELECT user_id FROM `account_team_users` WHERE permissions = "owner" AND team_id = ?', array($this->id));
		return Scalr_Account_User::init()->loadById($id);
	}
	
	public function isTeamOwner($userId)
	{
		return $this->db->getOne('SELECT user_id FROM `account_team_users` WHERE permissions = "owner" AND team_id = ? AND user_id = ?', array($this->id, $userId)) ? true : false;
	}
	
	public function isTeamUser($userId)
	{
		return $this->db->getOne('SELECT user_id FROM `account_team_users` WHERE team_id = ? AND user_id = ?', array($this->id, $userId)) ? true : false;
	}
	
	public function isTeamGroup($groupId)
	{
		return $this->db->getOne('SELECT id FROM `account_groups` WHERE team_id = ? AND id = ?', array($this->id, $groupId)) ? true : false;
	}
	
	/**
	 * 
	 * @return array of Scalr_Environment
	 */
	public function getEnvironments()
	{
		$result = array();
		foreach($this->db->getAll('SELECT env_id FROM `account_team_envs` WHERE team_id = ?', array($this->id)) as $r) {
			$env = Scalr_Environment::init()->loadById($r['env_id']);
			$result[] = array('id' => $env->id, 'name' => $env->name);
		}
		return $result;
	}
	
	public function addUser($userId, $permissions)
	{
		$user = Scalr_Account_User::init();
		$user->loadById($userId);

		if ($user->getAccountId() == $this->accountId) {
			$this->removeUser($userId);
			$this->db->Execute('INSERT INTO `account_team_users` (team_id, user_id, permissions) VALUES(?,?,?)', array(
				$this->id, $userId, $permissions
			));
		} else
			throw new Exception('This user doesn\'t belongs to this account');
	}
	
	public function removeUser($userId)
	{
		$this->clearUserGroups($userId);
		$this->db->Execute('DELETE FROM `account_team_users` WHERE user_id = ? AND team_id = ?', array($userId, $this->id));
	}
	
	public function addEnvironment($envId)
	{
		$env = Scalr_Environment::init();
		$env->loadById($envId);
		
		if ($this->accountId == $env->clientId) {
			$this->removeEnvironment($envId);
			$this->db->Execute('INSERT INTO `account_team_envs` (team_id, env_id) VALUES(?,?)', array(
				$this->id, $envId
			));
		} else 
			throw new Exception('This environment doesn\'t belongs to this account');
	}
	
	public function removeEnvironment($envId)
	{
		$this->db->Execute('DELETE FROM `account_team_envs` WHERE env_id = ? AND team_id = ?', array($envId, $this->id));
	}
}
