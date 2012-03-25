<?php
	class Scalr_Net_Dns_Bind_Transports_Ssh2_AuthInfo
	{
		const TYPE_PASSWORD = 'password';
		const TYPE_PUBKEY	= 'pubkey';
		
		public $type;
		public $login;
		public $password;
		public $pubKeyPath;
		public $privKeyPath;
		public $keyPassword;
		
		public function getType()
		{
			if ($this->password)
				return self::TYPE_PASSWORD;
			else
				return self::TYPE_PUBKEY;
		}
	}
?>