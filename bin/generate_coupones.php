<?php
	require_once('../src/prepend.inc.php');
	
	$coupon = 'APPSUMO123QAZ123PO';
	
	$result = '';
	
	for ($i = 1; $i <= 1000; $i++) {
		$c = Scalr_Util_CryptoTool::sault(10);
		
		$db->Execute("INSERT INTO billing.coupons SET id=?, chargify_coupon_id=?", array($c, $coupon));
		
		$result .= "{$c}\n";
	}
	
	print $result;
?>