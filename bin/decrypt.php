<?php

require_once('../src/prepend.inc.php');

$crypto =  new Scalr_Util_CryptoTool(MCRYPT_TRIPLEDES, MCRYPT_MODE_CFB, 24, 8);
$key = file_get_contents(dirname(__FILE__) . "/../etc/.cryptokey");
$str = file_get_contents('php://stdin');

print $crypto->decrypt($str, $key) . "\n";
