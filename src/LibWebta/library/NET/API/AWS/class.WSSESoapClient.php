<?
    /**
     * This file is a part of LibWebta, PHP class library.
     *
     * LICENSE
     *
	 * This source file is subject to version 2 of the GPL license,
	 * that is bundled with this package in the file license.txt and is
	 * available through the world-wide-web at the following url:
	 * http://www.gnu.org/copyleft/gpl.html
     *
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */ 

	Core::Load("NET/API/AWS/WSSESOAP");
	
    /**
     * @name WSSESoapClient
     * @category   LibWebta
     * @package    NET_API
     * @subpackage AWS
     * @version 1.0
     * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
     */	    
	class WSSESoapClient extends SoapClient
	{
		
		/**
		 * Path to an AWS certificate file
		 *
		 * @var string
		 */
		public $CertPath;
		
		/**
		 * Path to Amazon private key file  
		 *
		 * @var string
		 */
		public $KeyPath;
		
		protected $ObjKey;
		
		protected $BinaryToken;
		
		public function SetAuthKeys($key, $cert, $isfile)
		{
			/* create new XMLSec Key using RSA SHA-1 and type is private key */
			$this->ObjKey = new XMLSecurityKey(XMLSecurityKey::RSA_SHA1, array('type'=>'private'));
		
			/* load the private key from file - last arg is bool if key in file (TRUE) or is string (FALSE) */
			$this->ObjKey->loadKey($key, $isfile);
			
			if ($isfile == true)		
				$this->BinaryToken = file_get_contents($cert);
			else
				$this->BinaryToken = $cert;
		}
		
		function __call($function_name, $arguments)
		{
			$result = parent::__call($function_name, $arguments);
			
			if ($result instanceof SoapFault)
				$result->faultstring = sprintf(_("AWS error [{$this->location}]: %s"), $result->faultstring);
			
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
                $objWSSE->signSoapDoc($this->ObjKey);
			}
			catch (Exception $e)
			{
			    throw new Exception("[".__METHOD__."] ".$e->getMessage()." (Please, check your keys)", E_ERROR);
			}
		
			/* Add certificate (BinarySecurityToken) to the message and attach pointer to Signature */
			$token = $objWSSE->addBinaryToken($this->BinaryToken);
			$objWSSE->attachTokentoSig($token);

			for ($retry = 1; $retry <= 3; $retry++)
			{
				try
				{					
					$retval = parent::__doRequest($objWSSE->saveXML(), $location, $saction, $version);

					if ($retval instanceOf SoapFault && stristr($retval->faultstring, "Could not connect to host"))
						throw new Exception("[{$retry}] ".$retval->getMessage());
					
					if ($retval)
						return $retval;
					
					$headers = $this->__getLastResponseHeaders();
					if ($headers && stristr($headers, "HTTP/1.1 200 OK"))
						return $retval;
					
					if ($this->__soap_fault && stristr($this->__soap_fault->faultstring, "Could not connect to host"))
						throw new Exception("[{$retry}] ".$this->__soap_fault->faultstring);
				}
				catch (Exception $e)
				{				
					$exept = $e;
				}
				
				// Sleep for 2 seconds
				sleep(1);
			}
			
			if ($exept)
				throw new $exept;
		}
	}
?>
