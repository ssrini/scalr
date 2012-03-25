<?
	require(dirname(__FILE__)."/../../src/prepend.inc.php"); 
    @session_destroy();
    
    class API_PROVIDER
    {
    	const SOAP = 'soap';
    	const REST = 'rest';
    }
    
    $api_provider = API_PROVIDER::REST;
    
    try
    {
    
	    if ($req_Version != '2.0.0' && $req_Version != '2.1.0' && $req_Version != '2.2.0' && $req_Version != '2.3.0')
	    	throw new Exception("All versions prior to 2.0.0 are no longer supported");
    	
    	$API = ScalrAPICoreFactory::GetCore($req_Version); //TODO:    
	    $request = @file_get_contents("php://input");
	        
	    if ($api_provider == API_PROVIDER::SOAP)
	    	$API->BuildSoapServer($request);
	    elseif ($api_provider == API_PROVIDER::REST)
	    	$API->BuildRestServer(array_merge($_POST, $_GET));
    }
    catch(Exception $e)
    {
    	header("HTTP/1.0 400 Bad Request {$e->getMessage()}");
    }
    	
    exit();
?>