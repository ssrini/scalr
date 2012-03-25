<?php

/**
 * 
 * @author Marat Komarov
 * 
 * @property string $contactOwner
 * @property string $salutation
 * @property string $firstName
 * @property string $lastName *
 * @property string $accountId
 * @property string $accountName
 * @property string $vendorName
 * @property string $campaignSource
 * @property string $leadSource
 * @property string $title
 * @property string $department
 * @property string $birthDate
 * @property string $reportsTo
 * @property bool $emailOptOut
 * @property string $skypeId
 * @property string $assignedTo
 * @property string $phone
 * @property string $mobile
 * @property string $homePhone
 * @property string $otherPhone
 * @property string $fax
 * @property string $email
 * @property string $assistant
 * @property string $asstPhone
 * @property string $mailingStreet
 * @property string $mailingCity
 * @property string $mailingState
 * @property string $mailingCode
 * @property string $mailingCountry
 * @property string $otherStreet
 * @property string $otherCity
 * @property string $otherState
 * @property string $otherCode
 * @property string $otherCountry
 * @property string $description
 */
class Scalr_Service_ZohoCrm_Entity_Contact 
		extends Scalr_Service_ZohoCrm_Entity {
	
	function __construct () {
		parent::__construct();
		
		$this->nameLabelMap = array_merge($this->nameLabelMap, array(
			"id" => "CONTACTID",
			"contactOwner" => "Contact Owner",
			"salutation" => "Salutation",
			"firstName" => "First Name",
			"lastName" => "Last Name",
			"accountId" => "ACCOUNTID",
			"accountName" => "Account Name",
			"vendorName" => "Vendor Name",
			"campaignSource" => "Campaign Source",
			"leadSource" => "Lead Source",
			"title" => "Title",
			"department" => "Department",
			"birthDate" => "Date of Birth",
			"reportsTo" => "Reports To",
			"emailOptOut" => "Email Opt-out",
			"skypeId" => "Skype ID",
			"assignedTo" => "Assigned To",
			"phone" => "Phone",
			"mobile" => "Mobile",
			"homePhone" => "Home Phone",
			"otherPhone" => "Other Phone",
			"fax" => "Fax",
			"email" => "Email",
			"assistant" => "Assistant",
			"asstPhone" => "Asst Phone",
		
			"mailingStreet" => "Mailing Street",
			"mailingCity" => "Mailing City",
			"mailingState" => "Mailing State",
			"mailingCode" => "Mailing Zip",
			"mailingCountry" => "Mailing Country",
		
			"otherStreet" => "Other Street",
			"otherCity" => "Other City",
			"otherState" => "Other State",
			"otherCode" => "Other Zip",
			"otherCountry" => "Other Country",
		
			"description" => "Description"
		));
	}
}