<?php

	abstract class ScalrRESTService
	{
		const HASH_ALGO = 'SHA1';
		
		protected $Request;
		
		/**
		 * Arguments
		 * @var array
		 */
    	protected $Args;
		
		protected $DB;
    	protected $Logger;
		
		public function __construct()
    	{
    		$this->DB = Core::GetDBInstance();
    		$this->Logger = Logger::getLogger(__CLASS__);
    	}
    	
		/**
    	 * Set request data
    	 * @param $request
    	 * @return void
    	 */
    	public function SetRequest($request)
    	{
    		$this->Request = $request;
			$this->Args = array_change_key_case($request, CASE_LOWER);	
    	}
		
		protected function GetArg($name)
		{
			return $this->Args[strtolower($name)];
		}
    	
		/**
    	 * Verify Calling Instance
    	 * @return DBServer
    	 */
    	protected function GetCallingInstance()
    	{
    		if (!$_SERVER['HTTP_X_SIGNATURE'])
    			return $this->ValidateRequestByFarmHash($this->GetArg('farmid'), $this->GetArg('instanceid'), $this->GetArg('authhash'));
    		else
    			return $this->ValidateRequestBySignature($_SERVER['HTTP_X_SIGNATURE'], $_SERVER['HTTP_DATE'], $_SERVER['HTTP_X_SERVER_ID']);
    	}
		
		protected function ValidateRequestByFarmHash($farmid, $instanceid, $authhash)
		{
			try
			{
				$DBFarm = DBFarm::LoadByID($farmid);
				$DBServer = DBServer::LoadByPropertyValue(EC2_SERVER_PROPERTIES::INSTANCE_ID, $instanceid);
			}
			catch(Exception $e)
			{
				if (!$DBServer)
					throw new Exception(sprintf(_("Cannot verify the instance you are making request from. Make sure that farmid, instance-id and auth-hash parameters are specified.")));				
			}
			
    		if ($DBFarm->Hash != $authhash || $DBFarm->ID != $DBServer->farmId)
    		{
    			throw new Exception(sprintf(_("Cannot verify the instance you are making request from. Make sure that farmid (%s), instance-id (%s) and auth-hash (%s) parameters are valid."),
    				$farmid, $instanceid, $authhash
    			));
    		}
    		
    		return $DBServer;
		}
		
		protected function ValidateRequestBySignature($signature, $timestamp, $serverid)
		{
			ksort($this->Request);
			$string_to_sign = "";
    		foreach ($this->Request as $k=>$v)
    			$string_to_sign.= "{$k}{$v}";
			
    		$string_to_sign .= $timestamp;
    		
    		$DBServer = DBServer::LoadByID($serverid);
    		$auth_key = $DBServer->GetKey(true);
    		    		
    		$valid_sign = base64_encode(hash_hmac(self::HASH_ALGO, $string_to_sign, $auth_key, 1));    		
    		if ($valid_sign != $signature)
    			throw new Exception("Signature doesn't match");
    			
    		return $DBServer;
		}
	}
?>