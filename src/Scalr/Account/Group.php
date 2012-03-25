<?php
class Scalr_Account_Group extends Scalr_Model
{	
	protected $dbTableName = 'account_groups';
	protected $dbPrimaryKey = "id";
	protected $dbMessageKeyNotFound = "Group #%s not found in database";

	protected $dbPropertyMap = array(
		'id'			=> 'id',
		'team_id'		=> 'teamId',
		'name'			=> 'name',
		'is_active'		=> 'isActive'
	);
	
	public
		$teamId,
		$name,
		$isActive;

	/**
	 * 
	 * @return Scalr_Account_Group
	 */
	public static function init() {
		return parent::init();
	}
	
	public function getPermissions()
	{
		$result = array();
		foreach ($this->db->getAll('SELECT controller, permissions FROM account_group_permissions WHERE group_id = ?', array($this->id)) as $value) {
			$result[$value['controller']] = explode(',', $value['permissions']);
		}
		return $result;
	}
	
	public function setPermissions($perms)
	{
		$this->clearPermissions();
		foreach ($perms as $controller => $rule)
			$this->db->Execute('INSERT INTO `account_group_permissions` (group_id, controller, permissions) VALUES (?,?,?)', array(
				$this->id, $controller, $rule
			));
	}
	
	public function clearPermissions()
	{
		$this->db->Execute('DELETE FROM `account_group_permissions` WHERE group_id = ?', array($this->id));
	}
	
	public function delete()
	{
		parent::delete();

		$this->clearPermissions();
		$this->db->Execute('DELETE FROM `account_user_groups` WHERE group_id = ?', array($this->id));
	}
}
