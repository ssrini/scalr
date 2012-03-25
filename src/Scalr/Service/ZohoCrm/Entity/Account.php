<?php

/**
 * 
 * @author Marat Komarov
 * 
 * @property string $accountName *
 * @property int $accountOwner
 * @property string $website
 * @property string $tickerSymbol
 * @property int $parentAccount
 * @property int $employees
 * @property string $ownership
 * @property string $industry
 * @property string $accountType
 * @property int $accountNumber
 * @property string $accountSite
 * @property string $phone
 * @property string $fax
 * @property string $email
 * @property string $rating
 * @property int $sicCode
 * @property int $annualRevenue
 * @property string $billingStreet
 * @property string $billingCity
 * @property string $billingState
 * @property string $billingCode
 * @property string $billingCountry
 * @property string $shippingStreet
 * @property string $shippingCity
 * @property string $shippingState
 * @property string $shippingCode
 * @property string $shippingCountry
 * @property string $description
 */
class Scalr_Service_ZohoCrm_Entity_Account 
		extends Scalr_Service_ZohoCrm_Entity {
	
	function __construct () {
		parent::__construct();
		
		$this->nameLabelMap = array_merge($this->nameLabelMap, array(
			"id" => "ACCOUNTID",
			"accountName" => "Account Name",
			"accountOwner" => "Account Owner",
			"website" => "Website",
			"tickerSymbol" => "Ticker Symbol",
			"parentAccount" => "Parent Account",
			"employees" => "Employees",
			"ownership" => "Ownership",
			"industry" => "Industry",
			"accountType" => "Account Type",
			"accountNumber" => "Account Number",
			"accountSite" => "Account Site",
			"phone" => "Phone",
			"fax" => "Fax",
			"email" => "Email",
			"rating" => "Rating",
			"sicCode" => "SIC Code",
			"annualRevenue" => "Annual Revenue",
		
			"billingStreet" => "Billing Street",
			"billingCity" => "Billing City",
			"billingState" => "Billing State",
			"billingCode" => "Billing Code",
			"billingCountry" => "Billing Country",
		
			"shippingStreet" => "Shipping Street",
			"shippingCity" => "Shipping City",
			"shippingState" => "Shipping State",
			"shippingCode" => "Shipping Code",
			"shippingCountry" => "Shipping Country",
		
			"description" => "Description"
		));
	}
}