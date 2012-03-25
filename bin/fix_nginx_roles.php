<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$param = new stdClass();
	$param->name = 'Nginx HTTPS Vhost Template';
	$param->required = '1';
	$param->defval = @file_get_contents(dirname(__FILE__)."/../templates/services/nginx/ssl.vhost.tpl");
	$param->type = 'textarea';
	
	$param = (array)$param;
	
	
	
	$i = $db->Execute("SELECT id FROM roles WHERE `behaviors` LIKE '%www%'");
	while ($s = $i->FetchRow()) 
	{
		$template = $db->GetOne("SELECT id FROM role_parameters WHERE `role_id` = ? and `hash` = ?", array($s['id'], 'nginx_https_vhost_template'));
		if (!$template) {
			$db->Execute("INSERT INTO role_parameters SET
				`role_id`		= ?,
				`name`			= ?,
				`type`			= ?,
				`isrequired`	= ?,
				`defval`		= ?,
				`allow_multiple_choice`	= 0,
				`options`		= '',
				`hash`			= ?,
				`issystem`		= 1
			", array(
				$s['id'],
				$param['name'],
				$param['type'],
				$param['required'],
				$param['defval'],
				str_replace(" ", "_", strtolower($param['name']))
			));

			print "Fixed: {$s['id']}\n";
		}
	}
?>