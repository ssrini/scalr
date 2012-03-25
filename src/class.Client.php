<?php

	class Client
	{
		public $ID;
		public $IsActive;
		public $Email;
		public $Fullname;
		public $AddDate;
		public $DueDate;
		public $IsBilled;
		public $Organization;
		public $Country;
		public $State;
		public $City;
		public $ZipCode;
		public $Address1;
		public $Address2;
		public $Phone;
		public $Fax;
		public $Comments;
		
		private $DB;
		
		private static $ClientsCache = array();
		
		private static $FieldPropertyMap = array(
			'id' 			=> 'ID',
			'isactive'		=> 'IsActive',
			'fullname'		=> 'Fullname',
			'dtadded'		=> 'AddDate',
			'dtdue'			=> 'DueDate',
			'isbilled'		=> 'IsBilled',
			'org' => 'Organization',
  			'country' => 'Country',
  			'state' => 'State',
  			'city' => 'City',
  			'zipcode' => 'ZipCode',
  			'address1' => 'Address1',
  			'address2' => 'Address2',
  			'phone' => 'Phone',
  			'fax' => 'Fax',
			'comments' => 'Comments'		
		);
		
		/**
		 * Constructor
		 */
		public function __construct($email, $password)
		{
			$this->Email = $email;
			$this->Password = $password;
			
			$this->DB = Core::GetDBInstance();
		}
		
		/**
		 * Load Client Object by ID
		 * @param integer $id
		 * @return Client $Client
		 */
		public static function Load($id)
		{
			if (!self::$ClientsCache[$id])
			{
				$db = Core::GetDBInstance();
								
				$clientinfo = $db->GetRow("SELECT * FROM clients WHERE id=?", array($id));
				if (!$clientinfo)
					throw new Exception(sprintf(_("Client ID#%s not found in database"), $id));
					
				$Client = new Client($clientinfo['email'], $clientinfo['password']);

				foreach(self::$FieldPropertyMap as $k=>$v)
				{
					if (isset($clientinfo[$k]))
						$Client->{$v} = $clientinfo[$k];
				}
				
				self::$ClientsCache[$id] = $Client;
			}

			return self::$ClientsCache[$id];
		}
		
		/**
		 * Load Client Object by E-mail
		 * @param string $email
		 * @return Client $Client
		 */
		public static function LoadByEmail($email)
		{
			$db = Core::GetDBInstance();
			
			$clientid = $db->GetOne("SELECT id FROM clients WHERE email=?", array($email));
			if (!$clientid)
				throw new Exception(sprintf(_("Client with email=%s not found in database"), $email));
				
			return self::Load($clientid);
		}
		
		/**
		 * Returns client setting value by name
		 * 
		 * @param string $name
		 * @return mixed $value
		 */
		public function GetSettingValue($name)
		{
			return $this->DB->GetOne("SELECT value FROM client_settings WHERE clientid=? AND `key`=?",
				array($this->ID, $name)
			);
		}
		
		/**
		 * Set client setting
		 * @param string $name
		 * @param mixed $value
		 * @return void
		 */
		public function SetSettingValue($name, $value)
		{
			$this->DB->Execute("REPLACE INTO client_settings SET `key`=?, `value`=?, clientid=?",
				array($name, $value, $this->ID)
			);
		}

		public function ClearSettings ($filter)
		{
			$this->DB->Execute(
				"DELETE FROM client_settings WHERE `key` LIKE '{$filter}' AND clientid = ?",
				array($this->ID)
			);
		}
	}
	
?>