<?
	$_REQUEST['role_name'] = $_REQUEST['role'];

	$STATS_URL = 'http://monitoring.scalr.net';
	
	print str_replace('"type":"ok"', '"success":true', @file_get_contents("{$STATS_URL}/server/statistics.php?".http_build_query($_REQUEST)));
?>