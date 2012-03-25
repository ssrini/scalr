<?php
class Scalr_UI_Controller_Admin_Utils extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		return $this->user && ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN);
	}
	
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
			
			if (method_exists($p1, 'getPermissionDefinitions')) {
				$methods = $p1::getPermissionDefinitions();
				
				foreach (get_class_methods($p1) as $value) {
					if (strpos($value, 'Action') === FALSE)
						continue;
						
					$value = str_replace('Action', '', $value);
					$result[str_replace('Scalr_UI_Controller_', '', $p1)][] = array('name' => $value, 'permission' => array_key_exists($value, $methods) ? $methods[$value] : false);
				}
			} else {
				$result[str_replace('Scalr_UI_Controller_', '', $p1)] = 'Not covered'; 
			}
		}

		return $result;
	}		
	
	public function mapPermissionsAction()
	{
		$this->response->page('ui/admin/utils/mapPermissions.js', array('map' => $this->getPermissions(SRCPATH . '/Scalr/UI/Controller')));
	}
}
