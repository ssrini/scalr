<?php
class Scalr_Scripting_Manager
{
	/**
	 * 
	 * Enter description here ...
	 * @param unknown_type $eventName
	 * @param DBServer $eventServer
	 * @param DBServer $targetServer
	 * @return array
	 */
	public static function getEventScriptList(Event $event, DBServer $eventServer, DBServer $targetServer) 
	{
		$db = Core::GetDBInstance();
		
		$scripts = $db->GetAll("SELECT * FROM farm_role_scripts WHERE event_name=? AND farmid=? ORDER BY order_index ASC", array($event->GetName(), $eventServer->farmId));
		$retval = array();
		foreach ($scripts as $scriptSettings) {
			if ($scriptSettings['target'] == SCRIPTING_TARGET::INSTANCE && $eventServer->serverId != $targetServer->serverId)
				continue;
				
			if ($scriptSettings['target'] == SCRIPTING_TARGET::ROLE && $eventServer->farmRoleId != $targetServer->farmRoleId)
				continue;

			if ($scriptSettings['target'] == SCRIPTING_TARGET::FARM && $eventServer->farmRoleId != $scriptSettings['farm_roleid'])
				continue;
				
			if ($scriptSettings['target'] != SCRIPTING_TARGET::FARM && $targetServer->farmRoleId != $scriptSettings['farm_roleid'])
				continue;
				
			if ($scriptSettings['target'] == "")
				continue;
				
			if ($scriptSettings['version'] == 'latest') {
				$version = (int)$db->GetOne("SELECT MAX(revision) FROM script_revisions WHERE scriptid=?",
					array($scriptSettings['scriptid'])
				);
			}
			else
				$version = (int)$scriptSettings['version'];
				
			$template = $db->GetRow("SELECT name,id FROM scripts WHERE id=?", 
				array($scriptSettings['scriptid'])
			);
			$template['timeout'] = $scriptSettings['timeout'];
			$template['issync'] = $scriptSettings['issync'];
			
			
			$template['body'] = $db->GetOne("SELECT script FROM script_revisions WHERE scriptid=? AND revision=?",
				array($template['id'], $version)
			);
			
			if (!$template['body'])
				continue;
				
			$params = array_merge($targetServer->GetScriptingVars(), (array)unserialize($scriptSettings['params']));
						
			foreach ($eventServer->GetScriptingVars() as $k => $v) {
				$params["event_{$k}"] = $v;
			} 
			
			foreach ($event->GetScriptingVars() as $k=>$v)
				$params[$k] = $event->{$v};
			
			// Prepare keys array and array with values for replacement in script
			$keys = array_keys($params);
			$f = create_function('$item', 'return "%".$item."%";');
			$keys = array_map($f, $keys);
			$values = array_values($params);
			
			// Generate script contents
			$script_contents = str_replace($keys, $values, $template['body']);
			$template['body'] = str_replace('\%', "%", $script_contents);
			$template['name'] = preg_replace("/[^A-Za-z0-9]+/", "_", $template['name']);
				
			$retval[] = $template;
		}
		
		return $retval;
	}
}