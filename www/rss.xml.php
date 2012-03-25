<?
	require(dirname(__FILE__)."/../src/prepend.inc.php"); 
    
    try {
    	$dbFarm = DBFarm::LoadByID($_REQUEST['farmid']);
    }
    catch(Exception $e) {
		die("Error (1)");   	
    }
        
    //
    // Auth user
    //
    if (!$_SERVER['PHP_AUTH_USER'] || !$_SERVER['PHP_AUTH_PW'])
    {
    	header('WWW-Authenticate: Basic realm="My Realm"');
    	header('HTTP/1.0 401 Unauthorized');
    	exit();
    }
    else
    {
    	try {
    		$user = Scalr_Account_User::init()->loadBySetting(Scalr_Account_User::SETTING_RSS_LOGIN, $_SERVER['PHP_AUTH_USER']);
    	} catch (Exception $e) {}
    	
    	if (!$user || $_SERVER['PHP_AUTH_PW'] != $user->getSetting(Scalr_Account_User::SETTING_RSS_PASSWORD))
    	{
    		header('WWW-Authenticate: Basic realm="My Realm"');
    		header('HTTP/1.0 401 Unauthorized');
    		exit();
    	}
    	
    	if (!$user->getPermissions()->check(Scalr_Environment::init()->loadById($dbFarm->EnvID)))
    		die("Error (2)");
    }
        
    header("Content-type: application/rss+xml");
    
    //
    // Check cache
    //
    $rss_cache_path = CACHEPATH."/rss.{$dbFarm->ID}.cxml";
    if (file_exists($rss_cache_path))
    {
        clearstatcache();
        $time = filemtime($rss_cache_path);
        
        if ($time > time()-CONFIG::$EVENTS_RSS_CACHE_LIFETIME)
        {
        	readfile($rss_cache_path);
        	exit();
        }
    }
    
    $today = gmdate("D, d M Y H:i:s T");
    
    $RSS = new DOMDocument('1.0', 'UTF-8');
	$RSS->loadXML("<rss version=\"2.0\"><channel></channel></rss>");
	$RSSChannel = $RSS->getElementsByTagName('channel')->item(0);
	
	//Set RSS title
	$RSSChannel->appendChild($RSS->createElement('title', "Scalr events for farm '{$dbFarm->Name}'"));
	// Set RSS link
	$RSSChannel->appendChild($RSS->createElement('link', "http://scalr.net"));
	// Set description
	$RSSChannel->appendChild($RSS->createElement('description', "Scalr events for farm '{$dbFarm->Name}'"));
	// Set language
	$RSSChannel->appendChild($RSS->createElement('language', "en-us"));
	// Set copyright
	$RSSChannel->appendChild($RSS->createElement('copyright', "Copyright 1997-2008 Scalr.net"));
	// Set dates
	$RSSChannel->appendChild($RSS->createElement('pubDate', $today));
	$RSSChannel->appendChild($RSS->createElement('lastBuildDate', $today));
	// Set generator
	$RSSChannel->appendChild($RSS->createElement('generator', 'http://scalr.net'));
	
	// Add items
    $events = $db->Execute("SELECT * FROM events WHERE farmid=? ORDER BY id DESC LIMIT 0,100", array($dbFarm->ID));
    while ($event = $events->FetchRow())
    {
  		$date = gmdate("D, d M Y H:i:s T", strtotime($event["dtadded"]));
  		
  		$item = $RSS->createElement('item');
  		$item->appendChild($RSS->createElement('title', $event['short_message']));
  		$item->appendChild($RSS->createElement('link', "http://scalr.net"));
  		$item->appendChild($RSS->createElement('guid'));
  		$item->appendChild($RSS->createElement('comments'));
  		$item->appendChild($RSS->createElement('description', $event['message']));
  		$item->appendChild($RSS->createElement('pubDate', $date));
		
		$RSSChannel->appendChild($item);
    }
    
    $contents = $RSS->saveXML();
    
    @file_put_contents($rss_cache_path, $contents);
    
    print $contents;
?>
