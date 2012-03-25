<?php

/**
 * 
 * @author Marat Komarov
 * 
 * @property string $leadOwner
 * @property string $salutation
 * @property string $firstName
 * @property string $title
 * @property string $lastName *
 * @property string $company *
 * @property string $leadSource
 * @property string $industry
 * @property int $annualRevenue
 * @property string $phone
 * @property string $mobile
 * @property string $fax
 * @property string $email
 * @property string $skypeId
 * @property string $website
 * @property string $leadStatus
 * @property string $rating
 * @property int $employees
 * @property bool $emailOptOut
 * @property string $campaignSource
 * @property string $street
 * @property string $city
 * @property string $state
 * @property string $zipCode
 * @property string $country
 * @property string $description
 */
class Scalr_Service_ZohoCrm_Entity_Lead 
		extends Scalr_Service_ZohoCrm_Entity {
	
	function __construct () {
		parent::__construct();
		
		$this->nameLabelMap = array_merge($this->nameLabelMap, array(
			"id" => "LEADID",
			"leadOwner" => "Lead Owner",
			"salutation" => "Salutation",
			"firstName" => "First Name",
			"title" => "Title",
			"lastName" => "Last Name",
			"company" => "Company",
			"leadSource" => "Lead Source",		
			"industry" => "Industry",		
			"annualRevenue" => "Annual Revenue",		
			"phone" => "Phone",
			"mobile" => "Mobile",
			"fax" => "Fax",
			"email" => "Email",
			"skypeId" => "Skype ID",
			"website" => "Web site",				
			"leadStatus" => "Lead Status",
			"rating" => "Rating",		
			"employees" => "No of Employees", 
			"emailOptOut" => "Email Opt-out",			
			"campaignSource" => "Campaign Source",			
			"street" => "Street",
			"city" => "City",
			"state" => "State",
			"zipCode" => "ZIP Code",
			"country" => "Country",
			"description" => "Description"
		));
	}
}