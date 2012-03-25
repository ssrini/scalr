<?
    declare(ticks = 1);
	define("NO_TEMPLATES", true);
	define("NO_SESSIONS", true);
	require_once(dirname(__FILE__)."/../src/prepend.inc.php");    

	CONTEXTS::$APPCONTEXT = APPCONTEXT::CRONJOB;
	
	Core::Load("IO/PCNTL/interface.IProcess.php");
	Core::Load("IO/PCNTL");
    Core::Load("System/Independent/Shell/ShellFactory");
    Core::Load("NET/SNMP");
        
    $fname = basename($argv[0]);

    $JobLauncher = new JobLauncher(dirname(__FILE__));
    
    // DBQueueEvent - it is a daemon process so we must skepp this check
    if ($JobLauncher->GetProcessName() != 'DBQueueEvent')
    {
	    $Shell = ShellFactory::GetShellInstance();
	    // Set terminal width
	    putenv("COLUMNS=200");

	    // Execute command
	    $parent_pid = posix_getppid();
		$ps = $Shell->QueryRaw("ps x -o pid,command | grep -v {$parent_pid} |grep -v ".posix_getpid()." | grep -v 'ps x' | grep '".dirname(__FILE__)."' | grep '\-\-{$JobLauncher->GetProcessName()}'");
		
		if ($ps)
		{
			$Logger->info("'{$fname} --{$JobLauncher->GetProcessName()}' already running. Exiting.");
			exit();
		}
    }

	$Logger->info(sprintf("Starting %s cronjob...", $JobLauncher->GetProcessName()));	
	//$JobLauncher->Launch(CONFIG::$CRON_PROCESSES_NUMBER, 180);
	$JobLauncher->Launch(7, 180);
?>
