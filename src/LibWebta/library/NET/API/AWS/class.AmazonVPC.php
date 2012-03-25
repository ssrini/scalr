<?php
	class FilterType				
	{	
		public $name;				  // Name of a filter. Type: String
		public $valueSet;	
		
		public function __construct($name = null, $values = array())
		{			
			$this->name = $name;
			$this->valueSet = new stdClass();
			$this->valueSet->item = array();
			foreach ($values as $value)
			{
				$this->valueSet->item[]->value = $value;
			}		
		}
	}
	
	
	class DescribeVpcs
	{
		public $vpcSet = array();			// One or more sets of IDs. Type: VpcIdSetType	
		public $filterSet;					// One or more filters for limiting the results. Type: FilterSetType	
		
		public function __construct(FilterType $filterSet = null, $vpcSet = null)
		{				
			$this->vpcSet = new stdClass();			
			$this->vpcSet->item = array(); 
			foreach ($vpcSet as $vpcId)   
			{
				$this->vpcSet->item[]->vpcId = $vpcId;
			}
			if($filterSet !== null)
			{
				$this->filterSet = new stdClass();
				$this->filterSet->item = array();
				foreach ($filterSet as $filter)
				{
					$this->filterSet->item[] = $filter;
				}
			}
			
		}
	}
	
	class DescribeVpnConnections
	{
		public $vpnConnectionSet = array();		// One or more sets of IDs. Type: VpnConnectionIdSetType	
		public $filterSet;						// One or more filters for limiting the results. Type: FilterSetType	
		
		public function __construct(FilterType $filterSet = null,  $vpnConnectionSet = null)
		{			
		
			$this->vpnConnectionSet = new stdClass();			
			$this->vpnConnectionSet->item = array();
			 
			foreach ($vpnConnectionSet as $setId)   
			{
				$this->vpnConnectionSet->item[]->vpnConnectionId = $setId;
			}
			
			if($filterSet !== null)
			{
				$this->filterSet = new stdClass();
				$this->filterSet->item = array();
				foreach ($filterSet as $filter)
				{
					$this->filterSet->item[] = $filter;
				}
			}
					
		}
	}
	
	class CreateSubnet
	{
		public $availabilityZone;		    // The Availability Zone you want the subnet in. Type: xsd:string
											// Default: AWS selects a zone for you (recommended)
		public $cidrBlock;				    // The CIDR block you want the subnet to cover. Type: xsd:string	
		public $vpcId;					    // The ID of the VPC where you want to create the subnet.Type: xsd:string
		
		public function __construct($vpcID,$cidrBlock,$Zone)
		{
			$this->vpcId			= $vpcID;
			$this->cidrBlock		= $cidrBlock;
			$this->availabilityZone = $Zone;
			
		}
		
	}
	
	class DescribeSubnets
	{
		public $subnetIdSet = array();		// An array of subnet IDs. You can specify more than one in the request.Type: String
		public $filterSet;     				// One or more filters for limiting the results. Type: FilterSetType
		
		public function __construct(FilterType $filterSet = null, $subnetIdSet = null)
		{
			$this->subnetIdSet = new stdClass();			
			$this->subnetIdSet->item = array();
			 
			foreach ($subnetIdSet as $setId)   
			{
				$this->subnetIdSet ->item[]->subnetIdSet = $setId;
			}
			
			if($filterSet !== null)
			{
				$this->filterSet = new stdClass();
				$this->filterSet->item = array();
				foreach ($filterSet as $filter)
				{
					$this->filterSet->item[] = $filter;
				}
			}
		}
	}
	
	class CreateVpnConnection 
	{
		public $customerGatewayId;			// The ID of the customer gateway. Type: String
		public $type;						// The type of VPN connection. Type: String
		public $vpnGatewayId;				// The ID of the VPN gateway. Type: String
		
		public function __construct($customerGatewayId,$vpnGatewayId, $type)
		{
			$this->customerGatewayId = $customerGatewayId;
			$this->type				 = $type;
			$this->vpnGatewayId		 = $vpnGatewayId;		
		}
	}
	
		
	class DescribeDhcpOptions
	{
		public $dhcpOptionsSet = array();  			// The set of DHCP options. Type: dhcpConfigurationSet	
			
			public function __construct($dhcpOptionsSet)
			{			
				$this->dhcpOptionsSet = new stdClass();
				$this->dhcpOptionsSet->item = array();
				foreach($dhcpOptionsSet as $dhcpOptionsId)
				{
					$this->dhcpOptionsSet->item[]->dhcpOptionsId = $dhcpOptionsId;
				}				
			}
	}
	class CreateDhcpOptions
	{
		public $dhcpConfigurationSet = array();  			// The set of DHCP options. Type: dhcpConfigurationSet	
		
		public function __construct($dhcpConfigurationSet)
		{
			
			$this->dhcpConfigurationSet = new stdClass(); // dhcpConfigurationSet
			$this->dhcpConfigurationSet->item = array();  // single DHCP option, type: DhcpConfigurationItemType
					
			foreach ($dhcpConfigurationSet as $ConfigurationSet)
			{
				$this->dhcpConfigurationSet->item[] = $ConfigurationSet;
			}
			
			
		}
	}
	
	class AssociateDhcpOptions
	{		
		public $dhcpOptionsId;				// The ID of the DHCP options you want to 
		// associate with the VPC, or "default"  if you 
		// want to associate the default DHCP options with 
		// the VPC. Type: String
		public $vpcId;						// The ID of the VPC you want to associate the DHCP options with. Type: String
		
		public function __construct($vpcID, $dhcpOptionsId)
		{
			$this->vpcId = $vpcID;
			$this->dhcpOptionsId = $dhcpOptionsId;
		}
	}
	
	
	class DhcpConfigurationItemType
	{
		public $key;								// The name of a DHCP option. Type: String
		public $valueSet;							// A set of values for a DHCP option. Type: DhcpValueSetType
		
		public function __construct($key, $valueSet = array())
		{
			$this->key = $key;		
			$this->valueSet = new stdClass();		// DhcpValueSetType
			$this->valueSet->item = array();		// DhcpValueType
			foreach ($valueSet as $value)			
			{
				$this->valueSet->item[]->value = $value;	//	<item> 
															// 		<value> 192.168.1.11</value>
															//	</<item>
			}
		}	
	}
	

	
	class CreateCustomerGateway
	{
		public $type;						// The type of VPN connection this customer gateway supports. Type: String
		public $ipAddress;					// The Internet-routable IP address for the customer gateway's outside interface. The address must be static. Type: String	
		public $bgpAsn;						// The customer gateway's Border Gateway Protocol (BGP) Autonomous System Number (ASN).Type: Integer
		
		public function __construct($type,$ipAddress,$bgpAsn)
		{
			$this->bgpAsn = (int)$bgpAsn;
			$this->ipAddress = $ipAddress;
			$this->type = $type;
		}
	}
	
	class DescribeCustomerGateways
	{
		public $customerGatewaySet = array();	// One or more sets of IDs.Type: CustomerGatewayIdSetType
		public $filterSet;						// One or more filters for limiting the results. Type: FilterSetType
		
		public function __construct(FilterType $filterSet = null,$customerGatewaySet = null)
		{
			$this->customerGatewaySet = new stdClass();
			$this->customerGatewaySet->item = array();
			foreach ($customerGatewaySet as $customerGatewayId)
			{
				$this->customerGatewaySet->item[]->customerGatewayId = $customerGatewayId;
			}	
			
			if($filterSet !== null)
			{
				$this->filterSet = new stdClass();
				$this->filterSet->item = array();
				foreach ($filterSet as $filter)
				{
					$this->filterSet->item[] = $filter;
				}
			}	
		}	
	}
	
	class CreateVpnGateway
	{
		public $type;						// The type of VPN connection this VPN gateway supports. Type: String
		public $availabilityZone;			// The Availability Zone where you want the VPN gateway. Type: String	
		
		public function __construct($type,$zone = null)
		{
			$this->availabilityZone = $zone;
			$this->type	= $type;		
		}
	}
	
	class AttachVpnGateway
	{
		public $vpcId;						// The ID of the VPC you want to attach to the VPN gateway. Type: String
		public $vpnGatewayId;				// The ID of the VPN gateway you want to attach to the VPC. Type: String	
		
		public function __construct($vpcId,$vpnGatewayId)
		{
			$this->vpcId = $vpcId;
			$this->vpnGatewayId = $vpnGatewayId;
		}
	}
	
	class DescribeVpnGateways
	{
		public $vpnGatewaySet = array();	// One or more sets of IDs. Type: VpnGatewayIdSetType
		public $filterSet;					// One or more filters for limiting the results. Type: FilterSetType
		
		public function __construct(FilterType $filterSet = null, $vpnGatewaySet = null)
		{
			$this->vpnGatewaySet = new stdClass();
			$this->vpnGatewaySet->item = array();
			foreach ($vpnGatewaySet as $vpnGatewayId)
			{
				$this->vpnGatewaySet->item[]->vpnGatewayId = $vpnGatewayId;
			}	
					
			if($filterSet !== null)
			{
				$this->filterSet = new stdClass();
				$this->filterSet->item = array();
				foreach ($filterSet as $filter)
				{
					$this->filterSet->item[] = $filter;
				}
			}
				
		}	
		
	}
	
	class DetachVpnGateway
	{
		public $vpnGatewayId;				// The ID of the VPN gateway you want to detach from the VPC. Type: String
		public $vpcId;						// The ID of the VPC you want to detach the VPN gateway from. Type: String
		
		public function __construct($vpcId,$vpnGatewayId)
		{
			$this->vpcId = $vpcId;
			$this->vpnGatewayId = $vpnGatewayId;
		}	
	}
	
	class AmazonVPC  
	{
		
		const WSDL = 'http://s3.amazonaws.com/ec2-downloads/2009-07-15.ec2.wsdl';
		const KEY_PATH = '/dev/aws-pk.pem';
		const CERT_PATH = '/dev/aws-cert.pem';
		const USER_AGENT = 'Libwebta AWS Client (http://webta.net)';
		const CONNECTION_TIMEOUT = 15;	
		
		public $EC2SoapClient = NULL;
		private static $instances;
		
		public function __construct($api_url = 'https://ec2.amazonaws.com/')
		{							
			$this->EC2SoapClient  = new WSSESoapClient(AmazonVPC::WSDL, array(
						'connection_timeout' => self::CONNECTION_TIMEOUT, 
						'trace' => true, 
						'exceptions'=> false, 
						'user_agent' => AmazonVPC::USER_AGENT)
				);
					
			/* Force location path - MUST INCLUDE trailing slash
				BUG in ext/soap that does not automatically add / if URL does not contain path. this causes POST header to be invalid
				Seems like will be fixed in PHP 5.2 Release*/
			
			if (substr($apiUrl, -1) != '/')
				$apiUrl = "{$apiUrl}/";
			
			$this->EC2SoapClient->location = $api_url;
		}
		
		public function SetAuthKeys($key = null, $cert = null, $isfile = false)
		{
			// Defaultize
			if ($key == null || $cert == null)
				$isfile = true;
			
			$key = $key == null ? self::KEY_PATH : $key;
			$cert = $cert == null ? self::CERT_PATH : $cert;
			
			$this->EC2SoapClient->SetAuthKeys($key, $cert, $isfile);
		}
		
		public static function GetInstance($API_URL = 'https://ec2.amazonaws.com/')
		{
			if (!self::$instances[$API_URL])
				self::$instances[$API_URL] = new AmazonVPC($API_URL);
			
			return self::$instances[$API_URL];
		}
		
		
		/**
		 * This method creates a VPC with the CIDR block you specify.
		 *
		 * The smallest VPC you can create uses a /28 netmask (16 IP addresses),
		 * and the largest uses a /18 netmask (16,384 IP addresses).
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  string $cidrBlock
		 * @return stdClass
		 */
		
		public function CreateVpc($cidrBlock)
		{		
			try
			{
				$stdClass = new stdClass();
				$stdClass->cidrBlock = $cidrBlock;			
				$response = $this->EC2SoapClient->CreateVpc($stdClass);			
				
				if ($response instanceof SoapFault)			
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)			
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		
		/**
		 * Deletes a VPC.
		 *
		 * You must terminate all running instances and delete all subnets before 
		 * deleting the VPC, otherwise Amazon VPC returns an error. AWS might
		 * delete any VPC if you leave it inactive for an extended period of 
		 * time(inactive means that there are no running Amazon EC2
		 * instances in the VPC).\
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 *
		 * @param  string $vpcId
		 * @return stdClass
		 */
		public function DeleteVpc($vpcId)
		{
			try
			{
				$stdClass = new stdClass();
				$stdClass->vpcId = $vpcId;
				
				$response = $this->EC2SoapClient->DeleteVpc($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		
		/**
		 * This is method gives you information about your subnets.
		 *
		 * You can filter the results to return information  only about VPCs that
		 * match criteria you specify. 
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  DescribeVpcs $vpcInfo
		 * @return stdClass
		 *
		 */
		public function DescribeVpcs(DescribeVpcs $vpcInfo)
		{
			try
			{
				$response = $this->EC2SoapClient->DescribeVpcs($vpcInfo);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		
		/**
		 * Creates a subnet in an existing VPC.
		 *
		 * Creates a subnet in an existing VPC.You can create up to 20 subnets 
		 * in a VPC.If you add more than one subnet to a VPC, they're set up in 
		 * a star topology with a logical router in the middle. 
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  CreateSubnetType $subnet 
		 * @return stdClass	 
		 */
		public function CreateSubnet(CreateSubnet $subnetType)
		{
			try
			{			
				$response = $this->EC2SoapClient->CreateSubnet($subnetType);		
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		
		/**
		 * Deletes a subnet from a VPC.
		 *
		 * You must terminate all running instances in the subnet before deleting it,
		 * otherwise Amazon VPC returns an error. AWS might delete any subnet if you 
		 * leave it inactive for an extended period of time (inactive means that there 
		 * are no running Amazon EC2 instances in the subnet).
		  * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  string $subnetId 
		 * @return stdClass
		 *
		 */
		public function DeleteSubnet($subnetId)
		{
			try
			{
				$stdClass = new stdClass();
				$stdClass->subnetId = $subnetId;
				
				$response = $this->EC2SoapClient->DeleteSubnet($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		
		/**
		 * Gives you information about your subnets. 
		 *
		 * You can filter the results to return information only about subnets that
		 * match criteria you specify. For example, you could ask to get information
		 * about a particular subnet (or all) only if the subnet's state is 
		 * available. You can specify multiple filters.
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  DescribeSubnetsType $subnetDescription 
		 * @return stdClass
		 *
		 */
		public function DescribeSubnets(DescribeSubnets $subnetDescription = null)
		{
			try
			{	
				$response = $this->EC2SoapClient->DescribeSubnets($subnetDescription);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		
		/**
		 * Creates a new VPN connection between an existing VPN gateway and customer gateway. 
		 *
		 * The only supported connection type is ipsec.1. The response includes information 
		 * that you need to configure your customer gateway, in XML format.
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  CreateVpnConnectionType $vpnConnection This is a description
		 * @return stdClass
		 *
		 */
		public function CreateVpnConnection(CreateVpnConnection $vpnConnection)
		{
			try
			{
				$response = $this->EC2SoapClient->CreateVpnConnection($vpnConnection);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}	
		
		
		/**
		 * Deletes a VPN connection.
		 * 
		 * Use this if you want to delete a VPC and all its associated components.
		 * Another reason to use this operation is if you believe the tunnel 
		 * credentials for your VPN connection have been compromised.
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  DeleteVpnConnection $vpnConnection 
		 * @return stdClass
		 *
		 */
		public function DeleteVpnConnection($vpnConnectionId)
		{		
			try
			{
				
				$stdClass = new stdClass();
				$stdClass->vpnConnectionId = $vpnConnectionId;
						
				$response = $this->EC2SoapClient->DeleteVpnConnection($stdClass);			
						
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		
		/**
		 * Gives you information about your VPN connections.
		 *
		 * You can filter the results to return information only about VPN
		 * connections that match criteria you specify.
		 * We strongly recommend you use HTTPS when calling this operation!
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  string $vpnConnection
		 * @return stdClass
		 *
		 */
		public function DescribeVpnConnections($vpnConnection = null)
		{
			try
			{
				$response = $this->EC2SoapClient->DescribeVpnConnections($vpnConnection);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;
		}
		
		
		/**
		 * Associates a set of DHCP options (that you've previously created) with the 
		 * specified VPC, or associates the default DHCP options with the VPC. 
		 *
		 * The default set consists of the standard EC2 host name, no domain name, 
		 * no DNS server, no NTP server, and no NetBIOS server or node type.
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  AssociateDhcpOptions $dhcpOptions
		 * @return stdClass
		 *
		 */
		public function AssociateDhcpOptions(AssociateDhcpOptions $dhcpOptions)
		{
			try
			{
				$response = $this->EC2SoapClient->AssociateDhcpOptions($dhcpOptions);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;		
		}
		
		
		/**
		 * Creates a set of DHCP options that you can then associate with one or more VPCs,
		 * causing all existing and new instances that you launch in those VPCs to use the
		 * set of DHCP options.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  CreateDhcpOptions $dhcpOptions
		 * @return stdClass
		 *
		 */
		public function CreateDhcpOptions(CreateDhcpOptions $dhcpOptions)
		{	
			try
			{  
				$response = $this->EC2SoapClient->CreateDhcpOptions($dhcpOptions);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		
		
		/**
		 * Deletes a set of DHCP options that you specify.
		 * 
		 * Amazon VPC returns an error if the set of options you specify is currently 
		 * associated with a VPC. You can disassociate the set of options by 
		 * ssociating either a new set of options or the default options with the VPC.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  string $dhcpOptionsId
		 * @return stdClass
		 */
		public function DeleteDhcpOptions( $dhcpOptionsId )	
		{
			try
			{
				$stdClass = new stdClass();
				$stdClass->dhcpOptionsId = $dhcpOptionsId;
				
				$response = $this->EC2SoapClient->DeleteDhcpOptions($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;		
		}
		
		
		/**
		 * Gives you information about one or more sets of DHCP options.
		 *
		 * You can specify one or more DHCP options set IDs, or no IDs 
		 * (to describe all your sets of DHCP options).
		 * 
		 * @param  string $dhcpOptionsSet
		 * @return stdClass
		 */
		public function DescribeDhcpOptions(DescribeDhcpOptions $dhcpOptionsSet)
		{
			try
			{	
				$response = $this->EC2SoapClient->DescribeDhcpOptions($dhcpOptionsSet);
								
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		
		
		/**
		 * Provides information to AWS about your customer gateway device.
		 *
		 * The customer gateway is the appliance at your end of the VPN connection 
		 * compared to the VPN gateway, which is the device at the AWS side of the 
		 * VPN connection). 
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  CreateCustomerGateway $CustomerGateway
		 * @return stdClass
		 *
		 */
		public function  CreateCustomerGateway(CreateCustomerGateway $CustomerGateway)
		{
			try
			{	
				
				$response = $this->EC2SoapClient->CreateCustomerGateway($CustomerGateway);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		/**
		 * Deletes a Customer gateway.
		 * 
		 * Use this when you want to delete a customer gateway and all its associated components
		 * because you no longer need them.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 *  
		 * @param  string $customerGatewayId
		 * @return stdClass
		 *
		 */
		public function DeleteCustomerGateway($customerGatewayId)
		{
			try
			{			
				$stdClass = new stdClass();
				$stdClass->customerGatewayId = $customerGatewayId;
				
				$response = $this->EC2SoapClient->DeleteCustomerGateway($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		
		/**
		 * Gives you information about your customer gateways.
		 *
		 * You can filter the results to return information only about customer gateways
		 * that match criteria you specify. For example, you could ask to get information 
		 * about a particular customer gateway (or all) only if the gateway's state is 
		 * pending or available.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  DescribeCustomerGateways $CustomerGateways
		 * @return stdClass	 
		 */
		public function DescribeCustomerGateways(DescribeCustomerGateways $customerGateways = null)
		{
			try
			{				
				$response = $this->EC2SoapClient->DescribeCustomerGateways($customerGateways);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		
		
		/**
		 * Attaches a VPN gateway to a VPC.
		 *
		 * This is the last step required to get your VPC fully connected to your data center before
		 * launching instances in it.
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param  AttachVpnGateway $vpnGateway
		 * @return stdClass
		 *
		 */
		public function AttachVpnGateway(AttachVpnGateway $vpnGateway)	
		{
			try
			{				
				$response = $this->EC2SoapClient->AttachVpnGateway($vpnGateway);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		
		
		/**
		 * Creates a new VPN gateway. 
		 * 
		 * A VPN gateway is the VPC-side endpoint for your VPN connection.
		 * You can create a VPN gateway before creating the VPC itself.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 *  
		 * @param  CreateVpnGateway $VpnGateway
		 * @return stdClass
		 *
		 */
		public function CreateVpnGateway(CreateVpnGateway $vpnGateway)
		{
			try
			{								
				$response = $this->EC2SoapClient->CreateVpnGateway($vpnGateway);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		
		
		/**
		 * Deletes a VPN gateway.
		 * 
		 * Use this when you want to delete a VPC and all its associated components
		 * because you no longer need them.
		 *
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 *  
		 * @param  string $vpnGatewayId
		 * @return stdClass
		 *
		 */
		public function DeleteVpnGateway($vpnGatewayId)
		{
			try
			{			
				$stdClass = new stdClass();
				$stdClass->vpnGatewayId = $vpnGatewayId;
				
				$response = $this->EC2SoapClient->DeleteVpnGateway($stdClass);
				
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		
		
		/**
		 * Gives you information about your VPN gateways. 
		 * 
		 * You can filter the results to return information only about 
		 * VPN gateways that match criteria you specify. 
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 *
		 * @param DescribeVpnGateways $vpnGateways
		 * @return stdClass
		 *
		 */
		public function DescribeVpnGateways(DescribeVpnGateways $vpnGateways)
		{
			try
			{	
				$response = $this->EC2SoapClient->DescribeVpnGateways($vpnGateways);
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;	
		}
		
		
		/**
		 * Detaches a VPN gateway from a VPC.
		 * 
		 * You do this if you're planning to turn off the VPC and not use it anymore.
		 * You can confirm a VPN gateway has been completely detached from a VPC by
		 * describing the VPN gateway (any attachments to the VPN gateway are also described).
		 * 
		 * @link http://docs.amazonwebservices.com/AmazonVPC/latest/APIReference/ learn more
		 * 
		 * @param DetachVpnGateway $vpnGateway This
		 * @return stdClass
		 *
		 */
		
		public function DetachVpnGateway(DetachVpnGateway $vpnGateway)
		{
			try
			{	
				$response = $this->EC2SoapClient->DetachVpnGateway($vpnGateway);
				if ($response instanceof SoapFault)
					throw new Exception($response->faultstring, E_ERROR);
			}
			catch (SoapFault $e)
			{
				throw new Exception($e->getMessage(), E_ERROR);
			}
			
			return $response;			
		}
	}	

?>