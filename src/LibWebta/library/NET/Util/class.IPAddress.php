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
     * @subpackage Util
     * @copyright  Copyright (c) 2003-2007 Webta Inc, http://www.gnu.org/licenses/gpl.html
     * @license    http://www.gnu.org/licenses/gpl.html
     */	

    /**
	 * @name IPAddress
	 * @package NET
	 * @subpackage Util
	 * @version 1.0
	 * @author Alex Kovalyov <http://webta.net/company.html>
     * @author Igor Savchenko <http://webta.net/company.html>
	 *
	 */	
    class IPAddress extends Core 
    {
        /**
         * IP address
         *
         * @var string
         */
        public $IP;
        
        /**
         * Validaror Instance
         *
         * @var Validator
         */
        private $Validator;
        
        
        /**
         * IPAddress Constructor
         *
         * @param string $ip IP address string, q.x.y.z notation.
         * @uses Validaror
         */
        function __construct($ip)
        {
            $this->Validator = Core::GetInstance("Validator");
            $this->IP = $ip;
                       
            if (!$this->Validator->IsIPAddress($ip))
                $this->IP = false;
        }
        
        
        /**
         * Either $this->IP belongs to internal network
         *
         * @return bool
         */
        public function IsInternal()
        {
            return !$this->Validator->IsExternalIPAddress($this->IP);
        }
        
        
        /**
         * Either $this->IP belongs to external network
         *
         * @return bool
         */
        public function IsExternal()
        {
            return $this->Validator->IsExternalIPAddress($this->IP);
        }
        
        
        /**
         * Return a sting IP address representation, in q.x.y.z notation
         *
         * @return string
         */
        function __toString()
        {
            return $this->IP;
        }
    }
?>