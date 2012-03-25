<?php

class Scalr_Messaging_CryptoTool extends Scalr_Util_CryptoTool {
	const CRYPTO_ALGO = MCRYPT_TRIPLEDES;
	const CIPHER_MODE = MCRYPT_MODE_CBC;
	const CRYPTO_KEY_SIZE = 24;
	const CRYPTO_BLOCK_SIZE = 8;
	
	const HASH_ALGO = 'SHA256';
	
	static private $instance;
	
	/**
	 * @return Scalr_Messaging_CryptoTool
	 */
	static function getInstance() {
		if (self::$instance === null) {
			self::$instance = new Scalr_Messaging_CryptoTool(self::CRYPTO_ALGO, self::CIPHER_MODE, self::CRYPTO_KEY_SIZE, self::CRYPTO_BLOCK_SIZE);
		}
		return self::$instance;
	}

	function sign ($data, $key, $timestamp=null) {
		$date = date("c", $timestamp ? $timestamp : time());
		$canonical_string = $data . $date;
		$hash = base64_encode(hash_hmac(self::HASH_ALGO, $canonical_string, $key, 1));
		return array($hash, $date);
	}
}
