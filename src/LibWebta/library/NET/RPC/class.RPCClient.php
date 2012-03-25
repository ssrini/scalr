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
     * @package    NET
     * @subpackage RPC
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */

	Core::Load("NET/HTTP/HTTPClient");

	/**
	 * @name RPCClient
	 * @package NET
	 * @subpackage RPC
	 * @version 1.0
     * @author Sergey Koksharov <http://webta.net/company.html>
	 *
	 */
	class RPCClient extends HTTPClient
	{
		/**
		 * XML RPC Host
		 * with username, password if needed
		 * @var string Host
		 */
		private $Host;
		
		/**
		 * RPC Client Constructor
		 * 
		 * @param string hostname
		 * @param string xml rpc username (optional)
		 * @param string xml rpc password (optional)
		 */
		function __construct($host, $user = "", $pass = "")
		{
			parent::__construct();
			
			$host = str_replace("http://", "", $host);			
			if ($user)
				$host = "{$user}:{$pass}@{$host}";
			
			$this->Host = "http://{$host}";
		}
		
		/**
		 * Call server RPC method
		 * 
		 * @param string method name
		 * @param array arguments
		 * @return string
		 */
		function __call($function, $argv) 
		{
			$request = xmlrpc_encode_request($function, $argv);

			$headers = array(
				'Content-Type: text/xml',
				'Content-Length: '.strlen($request) . "\r\n\r\n" . $request
			);
			
			$this->SetHeaders($headers);
			$this->Fetch($this->Host, array(), true);			
			return xmlrpc_decode($this->Result);
		}
		
	}
	
?>