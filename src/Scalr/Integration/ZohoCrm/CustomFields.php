<?php

final class Scalr_Integration_ZohoCrm_CustomFields {
	// text
	const ACCOUNT_ISSUES = "Issues";
	
	// list
	const ACCOUNT_WHY_UNSUBSCRIBE = "Why unsubscribe?";
	
	// list
	const ACCOUNT_SUBSCR_TYPE = "Subscription type";
	
	// bool
	const ACCOUNT_AWS_ACCOUNT = "AWS account";
	
	// number
	const ACCOUNT_APPLICATIONS = "Applications";

	// number	
	const ACCOUNT_FARMS = "Farms";
	
	// text
	const ACCOUNT_CUSTOM_ROLES = "Custom roles";
	
	// bool
	const ACCOUNT_WHO_USES_SCALR_PAGE = "Who uses Scalr? page";
	
	// bool
	const ACCOUNT_ACTIVE = "Active";
	
	static $BILLING_PACKAGE_SUBSCR_TYPE_MAP = array(
		1 => "Beta ($50)",
		2 => "Production ($99)",
		3 => "Mission Critical ($399)",
		4 => "Development ($0)",
		5 => "Mission Critical ($399)"		
	);
	
	static $CGF_PRODUCT_SUBSCR_TYPE_MAP = array(
		'development' => "Development ($0)",	
		'beta-legacy' => "Beta ($50)",
		'production' => "Production ($99)",
		'mission-critical' => "Mission Critical ($399)"
	);
	
	const PAYMENT_SUBSCRIPTION_ID = "Subscription ID";
	
	static $BILLING_PACKAGE_PRODUCT_ID_MAP = array(
		1 => "164258000000033075",
		2 => "164258000000033065",
		3 => "164258000000033063",
		4 => "164258000000033067",
		5 => "164258000000033063"		
	);
	
	
	
	// date
	const CONTACT_DATE_UNSUBSCRIBED = "Date Unsubscribed";
	
	// text
	const CONTACT_HOW_DID_HE_FIND_US = "How did he find us?";
	
	// text
	const CONTACT_REASON_FOR_SIGNING_UP = "Reason for signing up";
	
	// list
	const CONTACT_STAGE = "Stage";
	
	// bool
	const CONTACT_UNSUBSCRIBED_ACCOUNT = "Unsubscribed account";
	
	// text
	const CONTACT_WHAT_LED_HIM_TO_SIGN_UP = "What led him to sign up?";
	
	// int
	const CONTACT_AD_PAGES_VISITED = 'Ad Pages Visited';
	
	// text
	const CONTACT_AD_VALUE_TRACK = 'Ad ValueTrack';
}
