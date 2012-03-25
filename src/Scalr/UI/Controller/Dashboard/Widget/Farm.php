<?php
class Scalr_UI_Controller_Dashboard_Widget_Farm extends Scalr_UI_Controller_Dashboard_Widget
{
	public function getDefinition()
	{
		return array(
			'type' => 'local'
		);
	}

	public function getContent($params = array())
	{
		if (! $params['farmId'])
			throw new Exception(_('Server not found'));

		$dbFarm = DBFarm::LoadByID($params['farmId']);
		$this->user->getPermissions()->validate($dbFarm);
		$farmRoles = array();
		foreach ($dbFarm->GetFarmRoles() as $dbFarmRole) {
			$servCount = $this->db->GetOne("SELECT COUNT(*) FROM servers WHERE farm_roleid = ? AND status IN ('Pending', 'Initializing', 'Running', 'Temporary')", array($dbFarmRole->ID));
			if($servCount)
				$farmRoles[] = array(
					'farmId' 		=> $params['farmId'],
					'farmRoleId' 	=> $dbFarmRole->ID,
					'group'			=> ROLE_GROUPS::GetConstByBehavior($dbFarmRole->GetRoleObject()->getBehaviors()),
					'behaviors'		=> implode(",", $dbFarmRole->GetRoleObject()->getBehaviors()),
					'servCount'		=> $servCount
				);
		}
		return array('servers'=>$farmRoles, 'name'=>$dbFarm->Name);
	}
}
