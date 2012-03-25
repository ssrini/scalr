<?php

	class Scalr_Net_Soap_WsseClient extends SoapClient
	{
	/**
		 * Path to an AWS certificate file
		 *
		 * @var string
		 */
		public $certPath;
		
		/**
		 * Path to Amazon private key file  
		 *
		 * @var string
		 */
		public $keyPath;
		
		protected $objKey;
		
		protected $binaryToken;
		
		public function setAuthKeys($key, $cert, $isfile)
		{
			/* create new XMLSec Key using RSA SHA-1 and type is private key */
			$this->objKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
		
			/* load the private key from file - last arg is bool if key in file (TRUE) or is string (FALSE) */
			$this->objKey->loadKey($key, $isfile);
			
			if ($isfile == true)		
				$this->binaryToken = file_get_contents($cert);
			else
				$this->binaryToken = $cert;
		}
		
		function __call($function_name, $arguments)
		{
			$result = parent::__call($function_name, $arguments);
			
			if ($result instanceof SoapFault)
				$result->faultstring = sprintf(_("Cloud provider error: %s"), $result->faultstring);
			
			return $result;
		}
		
		
		function __doRequest($request, $location, $saction, $version) 
		{		    			
			$doc = new DOMDocument('1.0');
			$doc->loadXML($request);
						
			$objWSSE = new WSSESoap($doc);
			#echo "<pre>"; var_dump($request); #die();
			/* add Timestamp with no expiration timestamp */
		 	$objWSSE->addTimestamp();
		 	
			try
			{
                /* Sign the message - also signs appropraite WS-Security items */
                $objWSSE->signSoapDoc($this->objKey);
			}
			catch (Exception $e)
			{
			    throw new Exception($e->getMessage()." (Please, check your keys)", E_ERROR);
			}
		
			/* Add certificate (BinarySecurityToken) to the message and attach pointer to Signature */
			$token = $objWSSE->addBinaryToken($this->binaryToken);
			$objWSSE->attachTokentoSig($token);

			for ($retry = 1; $retry <= 3; $retry++)
			{
				try
				{					
					$retval = parent::__doRequest($objWSSE->saveXML(), $location, $saction, $version);

					if ($retval)
						return $retval;
					
					$headers = $this->__getLastResponseHeaders();
					if ($headers && stristr($headers, "HTTP/1.1 200 OK"))
						return $retval;
				}
				catch (Exception $e)
				{				
					$exept = $e;
				}
				
				// Sleep for 2 seconds
				sleep(2);
			}
			
			if ($exept)
				throw new $exept;
		}
	}