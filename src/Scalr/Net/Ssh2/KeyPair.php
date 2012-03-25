<? 	
	class Scalr_Net_Ssh2_KeyPair 
	{
	    var $_math_obj;
    	var $_key_len;
    	var $_public_key;
    	var $_private_key;
    	var $_random_generator;
    	var $_attrs;

	    private function getAttributeNames() 
	    {
	        return array('version', 'n', 'e', 'd', 'p', 'q', 'dmp1', 'dmq1', 'iqmp');
	    }

	    private function asn1Parse($str, &$pos)
	    {
	        $max_pos = strlen($str);
	        if ($max_pos < 2) {
	            throw new Exception("ASN.1 string too short");
	        }
	
	        // get ASN.1 tag value
	        $tag = ord($str[$pos++]) & 0x1f;
	        if ($tag == 0x1f) {
	            $tag = 0;
	            do {
	                $n = ord($str[$pos++]);
	                $tag <<= 7;
	                $tag |= $n & 0x7f;
	            } while (($n & 0x80) && $pos < $max_pos);
	        }
	        if ($pos >= $max_pos) {
	            throw new Exception("ASN.1 string too short");
	        }
	
	        // get ASN.1 object length
	        $len = ord($str[$pos++]);
	        if ($len & 0x80) {
	            $n = $len & 0x1f;
	            $len = 0;
	            while ($n-- && $pos < $max_pos) {
	                $len <<= 8;
	                $len |= ord($str[$pos++]);
	            }
	        }
	        if ($pos >= $max_pos || $len > $max_pos - $pos) {
	            throw new Exception("ASN.1 string too short");
	        }
	
	        // get string value of ASN.1 object
	        $str = substr($str, $pos, $len);
	
	        return array(
	            'tag' => $tag,
	            'str' => $str,
	        );
	    }

	    private function asn1ParseInt($str, &$pos)
	    {
	        $tmp = $this->asn1Parse($str, $pos);
	        if ($tmp['tag'] != 0x02) {
	            throw new Exception (sprintf("wrong ASN tag value: 0x%02x. Expected 0x02 (INTEGER)", $tmp['tag']));
	        }
	        $pos += strlen($tmp['str']);
	
	        return strrev($tmp['str']);
	    }

    	private function asn1Store($str, $tag, $is_constructed = false, $is_private = false)
    	{
	        $out = '';
	
	        // encode ASN.1 tag value
	        $tag_ext = ($is_constructed ? 0x20 : 0) | ($is_private ? 0xc0 : 0);
	        if ($tag < 0x1f) {
	            $out .= chr($tag | $tag_ext);
	        } else {
	            $out .= chr($tag_ext | 0x1f);
	            $tmp = chr($tag & 0x7f);
	            $tag >>= 7;
	            while ($tag) {
	                $tmp .= chr(($tag & 0x7f) | 0x80);
	                $tag >>= 7;
	            }
	            $out .= strrev($tmp);
	        }
	
	        // encode ASN.1 object length
	        $len = strlen($str);
	        if ($len < 0x7f) {
	            $out .= chr($len);
	        } else {
	            $tmp = '';
	            $n = 0;
	            while ($len) {
	                $tmp .= chr($len & 0xff);
	                $len >>= 8;
	                $n++;
	            }
	            $out .= chr($n | 0x80);
	            $out .= strrev($tmp);
	        }
	
	        return $out . $str;
    	}

	    private function asn1StoreInt($str)
	    {
	        $str = strrev($str);
	        return $this->asn1Store($str, 0x02);
	    }

    	function __construct($key_len, $wrapper_name = 'default', $random_generator = null)
    	{
        	/*
	        // try to load math wrapper
	        $obj = &Crypt_RSA_MathLoader::loadWrapper($wrapper_name);
	        if ($this->isError($obj)) {
	            // error during loading of math wrapper
	            $this->pushError($obj);
	            return;
	        }
	        $this->_math_obj = &$obj;
	        */

	        // set random generator
	        if (!$this->setRandomGenerator($random_generator)) {
	            // error in setRandomGenerator() function
	            return;
	        }

        	if (is_array($key_len)) {
	            // ugly BC hack - it is possible to pass RSA private key attributes [version, n, e, d, p, q, dmp1, dmq1, iqmp]
	            // as associative array instead of key length to Crypt_RSA_KeyPair constructor
	            $rsa_attrs = $key_len;

	            // convert attributes to big integers
	            $attr_names = $this->getAttributeNames();
	            foreach ($attr_names as $attr) {
	                if (!isset($rsa_attrs[$attr])) {
	                    throw new Exception("missing required RSA attribute [{$attr}]");
	                }
	                ${$attr} = $this->_math_obj->bin2int($rsa_attrs[$attr]);
	            }
	
	            // check primality of p and q
	            if (!$this->_math_obj->isPrime($p)) {
	                throw new Exception("[p] must be prime");
	                return;
	            }
	            if (!$this->_math_obj->isPrime($q)) {
	                throw new Exception("[q] must be prime");
	                return;
	            }
	
	            // check n = p * q
	            $n1 = $this->_math_obj->mul($p, $q);
	            if ($this->_math_obj->cmpAbs($n, $n1)) {
	                throw new Exception("n != p * q");
	                return;
	            }
	
	            // check e * d = 1 mod (p-1) * (q-1)
	            $p1 = $this->_math_obj->dec($p);
	            $q1 = $this->_math_obj->dec($q);
	            $p1q1 = $this->_math_obj->mul($p1, $q1);
	            $ed = $this->_math_obj->mul($e, $d);
	            $one = $this->_math_obj->mod($ed, $p1q1);
	            if (!$this->_math_obj->isOne($one)) {
	                throw new Exception("e * d != 1 mod (p-1)*(q-1)");
	                return;
	            }
	
	            // check dmp1 = d mod (p-1)
	            $dmp = $this->_math_obj->mod($d, $p1);
	            if ($this->_math_obj->cmpAbs($dmp, $dmp1)) {
	                throw new Exception("dmp1 != d mod (p-1)");
	                return;
	            }
	
	            // check dmq1 = d mod (q-1)
	            $dmq = $this->_math_obj->mod($d, $q1);
	            if ($this->_math_obj->cmpAbs($dmq, $dmq1)) {
	                throw new Exception("dmq1 != d mod (q-1)");
	                return;
	            }
	
	            // check iqmp = 1/q mod p
	            $q1 = $this->_math_obj->invmod($iqmp, $p);
	            if ($this->_math_obj->cmpAbs($q, $q1)) {
	                throw new Exception("iqmp != 1/q mod p");
	                return;
	            }
	
	            // try to create public key object
	            $this->_public_key = new Crypt_RSA_Key($rsa_attrs['n'], $rsa_attrs['e'], 'public', $wrapper_name);
	
	            // try to create private key object
	            $this->_private_key = &new Crypt_RSA_Key($rsa_attrs['n'], $rsa_attrs['d'], 'private', $wrapper_name);
	
	            $this->_key_len = $this->_public_key->getKeyLength();
	            $this->_attrs = $rsa_attrs;
	        } else {
	            // generate key pair
	            if (!$this->generate($key_len)) {
	                // error during generating key pair
	                return;
	            }
	        }
    	}

	    function generate($key_len = null)
	    {
	        if (is_null($key_len)) {
	            // use an old key length
	            $key_len = $this->_key_len;
	            if (is_null($key_len)) {
	                $this->pushError('missing key_len parameter', CRYPT_RSA_ERROR_MISSING_KEY_LEN);
	                return false;
	            }
	        }
	
	        // minimal key length is 8 bit ;)
	        if ($key_len < 8) {
	            $key_len = 8;
	        }
	        // store key length in the _key_len property
	        $this->_key_len = $key_len;
	
	        // set [e] to 0x10001 (65537)
	        $e = $this->_math_obj->bin2int("\x01\x00\x01");
	
	        // generate [p], [q] and [n]
	        $p_len = intval(($key_len + 1) / 2);
	        $q_len = $key_len - $p_len;
	        $p1 = $q1 = 0;
	        do {
	            // generate prime number [$p] with length [$p_len] with the following condition:
	            // GCD($e, $p - 1) = 1
	            do {
	                $p = $this->_math_obj->getPrime($p_len, $this->_random_generator);
	                $p1 = $this->_math_obj->dec($p);
	                $tmp = $this->_math_obj->GCD($e, $p1);
	            } while (!$this->_math_obj->isOne($tmp));
	            // generate prime number [$q] with length [$q_len] with the following conditions:
	            // GCD($e, $q - 1) = 1
	            // $q != $p
	            do {
	                $q = $this->_math_obj->getPrime($q_len, $this->_random_generator);
	                $q1 = $this->_math_obj->dec($q);
	                $tmp = $this->_math_obj->GCD($e, $q1);
	            } while (!$this->_math_obj->isOne($tmp) && !$this->_math_obj->cmpAbs($q, $p));
	            // if (p < q), then exchange them
	            if ($this->_math_obj->cmpAbs($p, $q) < 0) {
	                $tmp = $p;
	                $p = $q;
	                $q = $tmp;
	                $tmp = $p1;
	                $p1 = $q1;
	                $q1 = $tmp;
	            }
	            // calculate n = p * q
	            $n = $this->_math_obj->mul($p, $q);
	        } while ($this->_math_obj->bitLen($n) != $key_len);
	
	        // calculate d = 1/e mod (p - 1) * (q - 1)
	        $pq = $this->_math_obj->mul($p1, $q1);
	        $d = $this->_math_obj->invmod($e, $pq);
	
	        // calculate dmp1 = d mod (p - 1)
	        $dmp1 = $this->_math_obj->mod($d, $p1);
	
	        // calculate dmq1 = d mod (q - 1)
	        $dmq1 = $this->_math_obj->mod($d, $q1);
	
	        // calculate iqmp = 1/q mod p
	        $iqmp = $this->_math_obj->invmod($q, $p);
	
	        // store RSA keypair attributes
	        $this->_attrs = array(
	            'version' => "\x00",
	            'n' => $this->_math_obj->int2bin($n),
	            'e' => $this->_math_obj->int2bin($e),
	            'd' => $this->_math_obj->int2bin($d),
	            'p' => $this->_math_obj->int2bin($p),
	            'q' => $this->_math_obj->int2bin($q),
	            'dmp1' => $this->_math_obj->int2bin($dmp1),
	            'dmq1' => $this->_math_obj->int2bin($dmq1),
	            'iqmp' => $this->_math_obj->int2bin($iqmp),
	        );
	
	        $n = $this->_attrs['n'];
	        $e = $this->_attrs['e'];
	        $d = $this->_attrs['d'];
	
	        // try to create public key object
	        $obj = &new Crypt_RSA_Key($n, $e, 'public', $this->_math_obj->getWrapperName(), $this->_error_handler);
	        if ($obj->isError()) {
	            // error during creating public object
	            $this->pushError($obj->getLastError());
	            return false;
	        }
	        $this->_public_key = &$obj;
	
	        // try to create private key object
	        $obj = &new Crypt_RSA_Key($n, $d, 'private', $this->_math_obj->getWrapperName(), $this->_error_handler);
	        if ($obj->isError()) {
	            // error during creating private key object
	            $this->pushError($obj->getLastError());
	            return false;
	        }
	        $this->_private_key = &$obj;
	
	        return true; // key pair successfully generated
	    }

    /**
     * Returns public key from the pair
     *
     * @return object  public key object of class Crypt_RSA_Key
     * @access public
     */
    function getPublicKey()
    {
        return $this->_public_key;
    }

    /**
     * Returns private key from the pair
     *
     * @return object   private key object of class Crypt_RSA_Key
     * @access public
     */
    function getPrivateKey()
    {
        return $this->_private_key;
    }

    /**
     * Sets name of random generator function for key generation.
     * If parameter is skipped, then sets to default random generator.
     *
     * Random generator function must return integer with at least 8 lower
     * significant bits, which will be used as random values.
     *
     * @param string $random_generator name of random generator function
     *
     * @return bool                     true on success or false on error
     * @access public
     */
    function setRandomGenerator($random_generator = null)
    {
        static $default_random_generator = null;

        if (is_string($random_generator)) {
            // set user's random generator
            if (!function_exists($random_generator)) {
                $this->pushError("can't find random generator function with name [{$random_generator}]");
                return false;
            }
            $this->_random_generator = $random_generator;
        } else {
            // set default random generator
            $this->_random_generator = is_null($default_random_generator) ?
                ($default_random_generator = create_function('', '$a=explode(" ",microtime());return(int)($a[0]*1000000);')) :
                $default_random_generator;
        }
        return true;
    }

    /**
     * Returns length of each key in the key pair
     *
     * @return int  bit length of each key in key pair
     * @access public
     */
    function getKeyLength()
    {
        return $this->_key_len;
    }

    /**
     * Retrieves RSA keypair from PEM-encoded string, containing RSA private key.
     * Example of such string:
     * -----BEGIN RSA PRIVATE KEY-----
     * MCsCAQACBHtvbSECAwEAAQIEeYrk3QIDAOF3AgMAjCcCAmdnAgJMawIDALEk
     * -----END RSA PRIVATE KEY-----
     *
     * Wrapper: Name of math wrapper, which will be used to
     * perform different operations with big integers.
     * See contents of Crypt/RSA/Math folder for examples of wrappers.
     * Read docs/Crypt_RSA/docs/math_wrappers.txt for details.
     *
     * @param string $str           PEM-encoded string
     * @param string $wrapper_name  Wrapper name
     * @param string $error_handler name of error handler function
     *
     * @return Crypt_RSA_KeyPair object on success, PEAR_Error object on error
     * @access public
     * @static
     */
    function &fromPEMString($str, $wrapper_name = 'default', $error_handler = '')
    {
        if (isset($this)) {
            if ($wrapper_name == 'default') {
                $wrapper_name = $this->_math_obj->getWrapperName();
            }
            if ($error_handler == '') {
                $error_handler = $this->_error_handler;
            }
        }
        $err_handler = &new Crypt_RSA_ErrorHandler;
        $err_handler->setErrorHandler($error_handler);

        // search for base64-encoded private key
        if (!preg_match('/-----BEGIN RSA PRIVATE KEY-----([^-]+)-----END RSA PRIVATE KEY-----/', $str, $matches)) {
            $err_handler->pushError("can't find RSA private key in the string [{$str}]");
            return $err_handler->getLastError();
        }

        // parse private key. It is ASN.1-encoded
        $str = base64_decode($matches[1]);
        $pos = 0;
        $tmp = Crypt_RSA_KeyPair::_ASN1Parse($str, $pos, $err_handler);
        if ($err_handler->isError()) {
            return $err_handler->getLastError();
        }
        if ($tmp['tag'] != 0x10) {
            $errstr = sprintf("wrong ASN tag value: 0x%02x. Expected 0x10 (SEQUENCE)", $tmp['tag']);
            $err_handler->pushError($errstr);
            return $err_handler->getLastError();
        }

        // parse ASN.1 SEQUENCE for RSA private key
        $attr_names = Crypt_RSA_KeyPair::_get_attr_names();
        $n = sizeof($attr_names);
        $rsa_attrs = array();
        for ($i = 0; $i < $n; $i++) {
            $tmp = Crypt_RSA_KeyPair::_ASN1ParseInt($str, $pos, $err_handler);
            if ($err_handler->isError()) {
                return $err_handler->getLastError();
            }
            $attr = $attr_names[$i];
            $rsa_attrs[$attr] = $tmp;
        }

        // create Crypt_RSA_KeyPair object.
        $keypair = &new Crypt_RSA_KeyPair($rsa_attrs, $wrapper_name, $error_handler);
        if ($keypair->isError()) {
            return $keypair->getLastError();
        }

        return $keypair;
    }

    /**
     * converts keypair to PEM-encoded string, which can be stroed in 
     * .pem compatible files, contianing RSA private key.
     *
     * @return string PEM-encoded keypair on success, false on error
     * @access public
     */
    function toPEMString()
    {
        // store RSA private key attributes into ASN.1 string
        $str = '';
        $attr_names = $this->_get_attr_names();
        $n = sizeof($attr_names);
        $rsa_attrs = $this->_attrs;
        for ($i = 0; $i < $n; $i++) {
            $attr = $attr_names[$i];
            if (!isset($rsa_attrs[$attr])) {
                $this->pushError("Cannot find value for ASN.1 attribute [$attr]");
                return false;
            }
            $tmp = $rsa_attrs[$attr];
            $str .= Crypt_RSA_KeyPair::_ASN1StoreInt($tmp);
        }

        // prepend $str by ASN.1 SEQUENCE (0x10) header
        $str = Crypt_RSA_KeyPair::_ASN1Store($str, 0x10, true);

        // encode and format PEM string
        $str = base64_encode($str);
        $str = chunk_split($str, 64, "\n");
        return "-----BEGIN RSA PRIVATE KEY-----\n$str-----END RSA PRIVATE KEY-----\n";
    }

    /**
     * Compares keypairs in Crypt_RSA_KeyPair objects $this and $key_pair
     *
     * @param Crypt_RSA_KeyPair $key_pair  keypair to compare
     *
     * @return bool  true, if keypair stored in $this equal to keypair stored in $key_pair
     * @access public
     */
    function isEqual($key_pair)
    {
        $attr_names = $this->_get_attr_names();
        foreach ($attr_names as $attr) {
            if ($this->_attrs[$attr] != $key_pair->_attrs[$attr]) {
                return false;
            }
        }
        return true;
    }	
	}
?>