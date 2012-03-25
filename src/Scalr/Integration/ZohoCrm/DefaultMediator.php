<?php

class Scalr_Integration_ZohoCrm_DefaultMediator extends 
		Scalr_Integration_ZohoCrm_Mediator {
	
	/**
	 * @var Scalr_Service_ZohoCrm
	 */
	private $zohoCrm;
	
	private $db;
	
	private $chargifyConn;
	
	private $logger;

	/**
	 * @var Scalr_Integration_ZohoCrm_CustomFields
	 */
	private $zohoMappings;
	
	function __construct ($zohoCrm) {
		$this->zohoCrm = $zohoCrm;
		$this->db = Core::GetDBInstance();
		$this->zohoMappings = new Scalr_Integration_ZohoCrm_CustomFields();
		$this->logger = Logger::getLogger(__CLASS__);
	} 
	
	/**
	 * @param Client $client
	 */
	function addClient ($client) {
		// Create account
		$account = new Scalr_Service_ZohoCrm_Entity_Account();
		$this->bindAccount($account, $client);
				
		try {
			$this->logger->info(sprintf("Creating account (client: '%s', clientid: %d)", 
					$client->Fullname, $client->ID));
					
			$accountService = $this->zohoCrm->factory(Scalr_Service_ZohoCrm_ModuleName::ACCOUNT);
			$accountService->create($account);
			$client->SetSettingValue(CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID, $account->id);
			$client->SetSettingValue(CLIENT_SETTINGS::ZOHOCRM_LAST_MODIFY_TIME, time());						
			
		} catch (Scalr_Service_ZohoCrm_Exception $e) {
			throw new Scalr_Integration_Exception(sprintf(
					"Cannot create account (client: '%s', clientid: %d). <%s> exception: '%s'",
					$client->Fullname, $client->ID, get_class($e), $e->getMessage()));
		}
		
		
		// Create contact
		$contact = new Scalr_Service_ZohoCrm_Entity_Contact();
		$contact->accountId = $account->id;
		$this->bindContact($contact, $client);
		
		try {
			$this->logger->info(sprintf("Creating contact (client: '%s', clientid: %d)", 
					$client->Fullname, $client->ID));
					
			$contactService = $this->zohoCrm->factory(Scalr_Service_ZohoCrm_ModuleName::CONTACT);
			$contactService->create($contact);
			$client->SetSettingValue(CLIENT_SETTINGS::ZOHOCRM_CONTACT_ID, $contact->id);
			
		} catch (Scalr_Service_ZohoCrm_Exception $e) {
			throw new Scalr_Integration_Exception(sprintf(
					"Cannot create contact (client: '%s', clientid: %d). <%s> exception: '%s'", 
					$client->Fullname, $client->ID, get_class($e), $e->getMessage()));
		}
		
	}
	
	/**
	 * @param Client $client
	 */
	function updateClient ($client, $skipRelations=array()) {
		// Update account
		$account = new Scalr_Service_ZohoCrm_Entity_Account();
		if ($account->id = $client->GetSettingValue(CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID)) {
			$this->bindAccount($account, $client);
			
			try {
				$this->logger->info(sprintf(
						"Updating account (ZohoCRM accountid: '%s', client: '%s', clientid: %d)", 
						$account->id, $client->Fullname, $client->ID));
						
				$accountService = $this->zohoCrm->factory(Scalr_Service_ZohoCrm_ModuleName::ACCOUNT);
				$accountService->update($account);
				$client->SetSettingValue(CLIENT_SETTINGS::ZOHOCRM_LAST_MODIFY_TIME, time());
				
			} catch (Scalr_Service_ZohoCrm_Exception $e) {
				throw new Scalr_Integration_Exception(sprintf(
						"Cannot update account (ZohoCRM accountid: '%s', client: '%s', clientid: %d). "
						. "<%s> exception: '%s'", 
						$account->id, $client->Fullname, $client->ID, get_class($e), $e->getMessage()));
			}
		} else {
			return $this->addClient($client);
			/*
			throw new Scalr_Integration_Exception(sprintf(
					"Cannot update account. Client has no '%s' setting (client: '%s', clientid: %d)", 
					CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID, $client->Fullname, $client->ID));
			*/
		}
		
		
		// Update contact
		if (!in_array('contact', $skipRelations)) {
			$contact = new Scalr_Service_ZohoCrm_Entity_Contact();
			if ($contact->id = $client->GetSettingValue(CLIENT_SETTINGS::ZOHOCRM_CONTACT_ID)) {
				$this->bindContact($contact, $client);
				
				try {
					$this->logger->info(sprintf(
							"Updating contact (ZohoCRM contactid: '%s', client: '%s', clientid: %d)",
							$contact->id, $client->Fullname, $client->ID));
							
					$contactService = $this->zohoCrm->factory(Scalr_Service_ZohoCrm_ModuleName::CONTACT);
					$contactService->update($contact);
					
				} catch (Scalr_Service_ZohoCrm_Exception $e) {
					throw new Scalr_Integration_Exception(sprintf(
							"Cannot update contact (ZohoCRM contactid: '%s', client: '%s', clientid: %d). "
							. "<%s> exception: '%s'", 
							$contact->id, $client->Fullname, $client->ID, get_class($e), $e->getMessage()));
				}
			} else {
				throw new Scalr_Integration_Exception(sprintf(
						"Cannot update contact. Client has no '%s' setting (client: '%s', clientid: %d)", 
						CLIENT_SETTINGS::ZOHOCRM_CONTACT_ID, $client->Fullname, $client->ID));
			}
		}
	} 

	/**
	 * @param Client $client
	 */
	function deleteClient ($client) {
		if ($accountId = $client->GetSettingValue(CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID)) {
			try {
				$this->logger->info(sprintf(
						"Deleting account (ZohoCRM accountid: '%s', client: '%s', clientid: %d)",
						$accountId, $client->Fullname, $client->ID));
				
				$accountService = $this->zohoCrm->factory(Scalr_Service_ZohoCrm_ModuleName::ACCOUNT);
				$accountService->delete($accountId);
				$client->ClearSettings("external.zohocrm.%");
				
			} catch (Scalr_Service_ZohoCrm_Exception $e) {
				throw new Scalr_Integration_Exception(sprintf(
						"Cannot delete account (ZohoCRM accountid: '%s', client: '%s', clientid: %d). "
						. "<%s> exception: '%s'",
						$accountId, $client->Fullname, $client->ID, get_class($e), $e->getMessage()));
			}
		} else {
			throw new Scalr_Integration_Exception(sprintf(
					"Cannot delete account. Client has no '%s' setting (client: '%s', clientid: %d)", 
					CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID, $client->Fullname, $client->ID));
		}
	}
	
	/**
	 * @param Client $client
	 */
	function addPayment ($client, $invoiceId) {
		if ($accountId = $client->GetSettingValue(CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID)) {
			$salesOrder = new Scalr_Service_ZohoCrm_Entity_SalesOrder();
			$this->bindSalesOrder($salesOrder, $client, $invoiceId);
			
			try {
				$this->logger->info(sprintf(
						"Creating sales order (ZohoCRM accountid: '%s', client: '%s', "
						. "clientid: %d, invoiceid: %d)",
						$accountId, $client->Fullname, $client->ID, $invoiceId));
				
				$salesOrderService = $this->zohoCrm->factory(Scalr_Service_ZohoCrm_ModuleName::SALES_ORDER);
				$salesOrderService->create($salesOrder);
				
			} catch (Scalr_Service_ZohoCrm_Exception $e) {
				throw new Scalr_Integration_Exception(sprintf(
						"Cannot create sales order (ZohoCRM accountid: '%s', client: '%s', clientid: %d, "
						. "invoiceid: %d). <%s> exception: '%s'",
						$accountId, $client->Fullname, $client->ID, $invoiceId, get_class($e), $e->getMessage()));
			}

		} else {
			throw new Scalr_Integration_Exception(sprintf(
					"Cannot add client monthly payment. Client has no '%s' setting "
					. "(client: '%s', clientid: %d)", 
					CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID, $client->Fullname, $client->ID));
		}
	}	
	
	/**
	 * @param Scalr_Service_ZohoCrm_Entity_Account $account 
	 * @param Client $client
	 * @return void
	 */
	private function bindAccount ($account, $client) {
		$account->accountName = $client->Organization ? $client->Organization : $client->Fullname;
		$account->email = $client->Email;
		$account->description = $client->Comments;
		
		if ($client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_CID)) {
			$subscrId = $client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_SID);
			if ($subscrId) {
				$subscr = $this->getChargifyConnector()->getCustomerSubscription($subscrId);
				$productId = $subscr->getProduct()->getHandle();
			} else {
				$productId = 'development';
			}
			$subscrType = Scalr_Integration_ZohoCrm_CustomFields::$CGF_PRODUCT_SUBSCR_TYPE_MAP[$productId];
		} else {
			$packageId = $client->GetSettingValue(CLIENT_SETTINGS::BILLING_PACKAGE);
			if (!$packageId) {
				$packageId = 4; // Development ($0)
			}
			$subscrType = Scalr_Integration_ZohoCrm_CustomFields::$BILLING_PACKAGE_SUBSCR_TYPE_MAP[$packageId];	
		}
		
		
		$account->setProperty(Scalr_Integration_ZohoCrm_CustomFields::ACCOUNT_SUBSCR_TYPE, $subscrType);
				
		$account->setProperty(
				Scalr_Integration_ZohoCrm_CustomFields::ACCOUNT_FARMS,
				$this->db->GetOne("SELECT COUNT(*) FROM farms WHERE clientid = ?", array($client->ID)));
		
		$account->setProperty(
				Scalr_Integration_ZohoCrm_CustomFields::ACCOUNT_APPLICATIONS,
				$this->db->GetOne("SELECT COUNT(*) FROM dns_zones WHERE client_id = ?", array($client->ID)));
				
		$account->setProperty(
				Scalr_Integration_ZohoCrm_CustomFields::ACCOUNT_ACTIVE,
				(bool)$client->IsActive);
	}

	/**
	 * @param Scalr_Service_ZohoCrm_Entity_Contact $contact 
	 * @param Client $client
	 * @return void
	 */
	private function bindContact ($contact, $client) {
		list($contact->firstName, $contact->lastName) = explode(" ", $client->Fullname, 2);
		if (!$contact->lastName) {
			$contact->lastName = $contact->firstName;
			unset($contact->firstName);
		}

		$contact->email = $client->Email;
		$contact->phone = $client->Phone;
		$contact->fax = $client->Fax;

		$contact->mailingStreet = $client->Address1;
		$contact->mailingCity = $client->City;
		$contact->mailingState = $client->State;
		$contact->mailingCode = $client->ZipCode;
		if ($client->Country) {
			$contact->mailingCountry = $this->db->GetOne(
					"SELECT name FROM countries WHERE code = ?", array($client->Country));
		}
		
		$adPagesVisited = $client->GetSettingValue(CLIENT_SETTINGS::AD_PAGES_VISITED);
		$adCompaign = $client->GetSettingValue(CLIENT_SETTINGS::AD_COMPAIGN);
		if ($adPagesVisited) {
			$contact->leadSource = 'Adwords';
			$contact->setProperty(
				Scalr_Integration_ZohoCrm_CustomFields::CONTACT_AD_PAGES_VISITED,
				(int)$adPagesVisited
			);
			$contact->setProperty(
				Scalr_Integration_ZohoCrm_CustomFields::CONTACT_AD_VALUE_TRACK,
				$client->GetSettingValue(CLIENT_SETTINGS::AD_VALUE_TRACK)
			);
			$client->ClearSettings('adwords%');
			
		} elseif ($adCompaign) {
			$contact->leadSource = $adCompaign;
		} else {
			$packageId = $client->GetSettingValue(CLIENT_SETTINGS::BILLING_PACKAGE);
			if (!$packageId || $packageId == 4) {
				$contact->leadSource = "Development edition";
			} else {
				$contact->leadSource = "Production edition";
			}
		}
		
		
		$unsubscrDate = $client->GetSettingValue(CLIENT_SETTINGS::ZOHOCRM_UNSUBSCR_DATE);
		$contact->setProperty(
				Scalr_Integration_ZohoCrm_CustomFields::CONTACT_UNSUBSCRIBED_ACCOUNT,
				(bool)$unsubscrDate);
				
		$contact->setProperty(
				Scalr_Integration_ZohoCrm_CustomFields::CONTACT_DATE_UNSUBSCRIBED, 
				$unsubscrDate ? $unsubscrDate : null);
	}
	
	/**
	 * @param Scalr_Service_ZohoCrm_Entity_SalesOrder $salesOrder 
	 * @param Client $client
	 * @param int $invoiceId
	 */
	private function bindSalesOrder ($salesOrder, $client, $invoiceId) {
		$invoice = $this->db->GetRow("SELECT * FROM payments WHERE id = ?", array($invoiceId));
		$packageId = $client->GetSettingValue(CLIENT_SETTINGS::BILLING_PACKAGE);
		$package = $this->db->GetRow("SELECT * FROM billing_packages WHERE id = ?", array($packageId));
		

		$salesOrder->accountId = $client->GetSettingValue(CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID);		
		$salesOrder->subject = sprintf('Monthly fee $%s (%s)', $invoice["amount"], date("F y", strtotime($invoice["dtpaid"])));
		$salesOrder->discount = 0;
		$salesOrder->tax = 0;
		$salesOrder->subTotal = $package["cost"];
		$salesOrder->grandTotal = $package["cost"];
		$salesOrder->status = "Delivered";
		$salesOrder->setProperty(
				Scalr_Integration_ZohoCrm_CustomFields::PAYMENT_SUBSCRIPTION_ID,
				$invoice["subscriptionid"]);
		
		
		// Add product
		$productDetail = new Scalr_Service_ZohoCrm_Entity_ProductDetail();
		
		$productDetail->productId = 
				Scalr_Integration_ZohoCrm_CustomFields::$BILLING_PACKAGE_PRODUCT_ID_MAP[$packageId];
		$productDetail->quantity = 1;
		$productDetail->listPrice = $package["cost"];
		$productDetail->discount = 0;
		$productDetail->tax = 0;		
		$productDetail->total = $package["cost"];
		$productDetail->totalAfterDiscount = $package["cost"];
		$productDetail->netTotal = $package["cost"];		
		
		$salesOrder->addProductDetail($productDetail);
	}
	
	private function getChargifyConnector () {
		if (!$this->chargifyConn) {
			require_once(APPPATH . "/www/site/src/Lib/ChargifyClient/class.ChargifyConnector.php");
			require_once(APPPATH . "/www/site/src/Lib/ChargifyClient/class.ChargifyCreditCard.php");
			require_once(APPPATH . "/www/site/src/Lib/ChargifyClient/class.ChargifyCustomer.php");
			require_once(APPPATH . "/www/site/src/Lib/ChargifyClient/class.ChargifyProduct.php");
			require_once(APPPATH . "/www/site/src/Lib/ChargifyClient/class.ChargifySubscription.php");
			$this->chargifyConn = new ChargifyConnector(); 
		}
		return $this->chargifyConn;
	}
}