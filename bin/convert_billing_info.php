<?php
	require_once('../src/prepend.inc.php');
	
	set_time_limit(0);
	
	$stats = array();
    $accounts = $db->Execute("SELECT id FROM clients");
	while ($dbclient = $accounts->FetchRow())
    {
		$account = Scalr_Account::init()->loadById($dbclient['id']);
            	
		$cId = $account->getSetting("billing.chargify.customer_id");
		$sId = $account->getSetting("billing.chargify.subscription_id");
            		
		if ($cId) $account->setSetting(Scalr_Billing::SETTING_CGF_CID, $cId);
		if ($sId) $account->setSetting(Scalr_Billing::SETTING_CGF_SID, $sId);
            		
		if ($cId)
			$account->setSetting(Scalr_Billing::SETTING_IS_NEW_BILLING, 1);
			
		$account->clearSettings("billing.%");
    }
?>