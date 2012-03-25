<?php

	$lTime = microtime(true);

	$c = 0;
	
	while (true) {
		
		if ($c % 50 == 0) {
			@file_put_contents(dirname(__FILE__)."/tmp.lock", "1");
		}
		
		$c++;
		$mysqli = mysqli_init();
		if (!$mysqli) {
		    die('mysqli_init failed');
		}
		
		$startTime = microtime(true);
		
		if (!$mysqli->real_connect('50.23.217.34', 'scalr2','Yq1{L+cl)fCiIO9J', 'scalr')) {
			
			$time = microtime(true) - $startTime;
			
		    die('Connect Error (' . mysqli_connect_errno() . ') '
		            . mysqli_connect_error() . ' Time: ' . $time);
		}
		
		$time = round(microtime(true) - $startTime, 4);
		
		/* Select queries return a resultset */
		if ($result = $mysqli->query("SELECT * FROM config")) {
			
			if ($time >= 1) {
				$ftime = round(microtime(true) - $lTime, 2);
		    	print("[{$time} ({$ftime})]\n");
		    	$lTime = microtime(true);
			}
		    else 
		    	print(".");
		
		    /* free result set */
		    $result->close();
		}
		
		$mysqli->close();
		
		usleep(100000);
	}
	
