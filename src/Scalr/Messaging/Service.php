<?php

class Scalr_Messaging_Service {
	const HASH_ALGO = 'SHA1';	
	
	private $cryptoTool;
	
	private $serializer;
	
	private $handlers = array();
	
	private $logger;
	
	function __construct () {
		$this->cryptoTool = Scalr_Messaging_CryptoTool::getInstance();
		$this->serializer = new Scalr_Messaging_XmlSerializer();
		$this->logger = Logger::getLogger(__CLASS__);
	}
	
	function addQueueHandler(IScalrQueueHandler $handler) {
		if (array_search($handler, $this->handlers) === false) {
			$this->handlers[] = $handler;
		}
	}	
	
	function handle ($queue, $payload) {
    	// Authenticate request
		try {
			$this->logger->info(sprintf("Validating server (server_id: %s)", $_SERVER["HTTP_X_SERVER_ID"]));
			try{
    			$DBServer = DBServer::LoadByID($_SERVER["HTTP_X_SERVER_ID"]);
			} catch (Exception $e) {
				throw new Exception(sprintf(_("Server '%s' is not known by Scalr"), $_SERVER["HTTP_X_SERVER_ID"]));
			}
			
	    	$cryptoKey = $DBServer->GetKey(true);
	    	$isOneTimeKey = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_KEY_TYPE) == SZR_KEY_TYPE::ONE_TIME;
	    	$isOneTimeKey = false; //FIXME:
	    	$keyExpired = $DBServer->GetProperty(SERVER_PROPERTIES::SZR_ONETIME_KEY_EXPIRED);
	    	if ($isOneTimeKey && $keyExpired) {
	    		throw new Exception(_("One-time crypto key expired"));
	    	}
	    	
			$this->logger->info(sprintf(_("Validating signature '%s'"), $_SERVER["HTTP_X_SIGNATURE"]));	    	
	    	$this->validateSignature($cryptoKey, $payload, $_SERVER["HTTP_X_SIGNATURE"], $_SERVER["HTTP_DATE"]);
	    	
	    	if ($isOneTimeKey) {
	    		$DBServer->SetProperty(SERVER_PROPERTIES::SZR_ONETIME_KEY_EXPIRED, 1);
	    	}
    	} 
    	catch (Exception $e) {
    		return array(401, $e->getMessage());
    	}
		
    	// Decrypt and decode message
		try {
			$this->logger->info(sprintf(_("Decrypting message '%s'"), $payload));
			$xmlString = $this->cryptoTool->decrypt($payload, $cryptoKey);
			
			$this->logger->info(sprintf(_("Unserializing message '%s'"), htmlspecialchars($xmlString)));
			$message = $this->serializer->unserialize($xmlString);
			
			if ($isOneTimeKey && !$message instanceof Scalr_Messaging_Msg_HostInit) {
				return array(401, _("One-time crypto key valid only for HostInit message"));	
			}
			
		}
		catch (Exception $e) {
			return array(400, $e->getMessage());
		}
		
		// Handle message
		$accepted = false;
		foreach ($this->handlers as $handler) {
			if ($handler->accept($queue)) {
				$this->logger->info("Notify handler " . get_class($handler));
				$handler->handle($queue, $message, $xmlString);
				$accepted = true;
			}
		}
		
		return $accepted ? 
				array(201, "Created") :
				array(400, sprintf("Unknown queue '%s'", $queue));
		
	}
	
	private function validateSignature($key, $payload, $signature, $timestamp) {
   		$string_to_sign = $payload . $timestamp;

    	$valid_sign = base64_encode(hash_hmac(self::HASH_ALGO, $string_to_sign, $key, 1)); 
    	if ($valid_sign != $signature) {
    		throw new Exception("Signature doesn't match");
    	}
	}	
}
