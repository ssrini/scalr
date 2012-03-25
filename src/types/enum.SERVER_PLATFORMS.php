<?
	final class SERVER_PLATFORMS
	{
		const EC2		= 'ec2';
		const RDS		= 'rds';
		const RACKSPACE = 'rackspace';
		const EUCALYPTUS= 'eucalyptus';
		const NIMBULA	= 'nimbula';
		const CLOUDSTACK = 'cloudstack';
		const OPENSTACK = 'openstack';
		
		//FOR FUTURE USE
		const VPS		= 'vps';
		const GOGRID	= 'gogrid';
		const NOVACC	= 'novacc';
		
		
		public static function GetList()
		{
			return array(
				self::EC2 			=> 'Amazon EC2',
				self::RDS 			=> 'Amazon RDS',
				self::EUCALYPTUS 	=> 'Eucalyptus',
				self::RACKSPACE		=> 'Rackspace',
				self::NIMBULA		=> 'Nimbula',
				self::CLOUDSTACK	=> 'Cloudstack',
				self::OPENSTACK		=> 'Openstack'
			);
		}
		
		public static function GetName($const)
		{
			$list = self::GetList();
			
			return $list[$const];
		}
	}
?>