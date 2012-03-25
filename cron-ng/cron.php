<?php

	declare(ticks = 1);
	define("NO_TEMPLATES", true);
	define("NO_SESSIONS", true);
	require_once (dirname(__FILE__) . "/../src/prepend.inc.php");
	
	$launcher = new Scalr_System_Cronjob_Launcher(array(
		"jobDir" => dirname(__FILE__) . "/jobs",
		"clsNamespace" => "Scalr_Cronjob"	
	));
	$launcher->launch();