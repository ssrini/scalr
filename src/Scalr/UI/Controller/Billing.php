<?php

class Scalr_UI_Controller_Billing extends Scalr_UI_Controller
{	
	const CALL_PARAM_NAME = 'billingItemId';
	
	/**
	 * 
	 * @var Scalr_Billing
	 */
	private $billing;

	public function hasAccess()
	{
		if (parent::hasAccess()) {
			return $this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER ? true : false;
		} else
			return false;
	}

	public function defaultAction()
	{
		$this->detailsAction();
	}

	public function init()
	{
		$this->billing = Scalr_Billing::init()->loadByAccount($this->user->getAccount());
	}
	
	public function reactivateAction()
	{
		$this->response->page('ui/billing/reactivate.js', array('billing' => $this->billing->getInfo()));
	}
	
	public function xReactivateAction()
	{
		$this->billing->reactivateSubscription();
		$this->response->success();
	}
	
	public function invoicesListAction()
	{
		$invoices = $this->billing->getInvoices(false);
		$this->response->page('ui/billing/invoices.js', array('invoices' => $invoices));
	}
	
	public function xApplyCouponCodeAction()
	{
		$this->billing->applyCoupon($this->getParam('couponCode'));			
		$this->response->success("Coupon successfully applied to your subscription");
	}
	
	public function applyCouponCodeAction()
	{
		$this->response->page('ui/billing/applyCouponCode.js');	
	}
	
	public function showInvoiceAction()
	{
		print '<!DOCTYPE HTML PUBLIC "-//W3C//DTD HTML 4.01 Transitional//EN" "http://www.w3.org/TR/html4/loose.dtd"><html><head><title>Statement</title><style type="text/css">
body, div, span, applet, object, iframe,h1, h2, h3, h4, h5, h6, p, blockquote, pre,a, abbr, acronym, address, big, cite, code,del, dfn, em, font, img, ins, kbd, q, s, samp,small, strike, strong, sub, sup, tt, var,b, u, i, center,dl, dt, dd, ol, ul, li,fieldset, form, label, legend,table, caption, tbody, tfoot, thead, tr, th, td {
margin: 0;padding: 0;border: 0;outline: 0;font-size: 100%;vertical-align: baseline;background: transparent;color: #000;}
table {width: 100%;border-spacing: 0;border-collapse: collapse;}
body {background-color: #ffffff;}
body, #billing_statement_wrapper {font-family: Verdana, Arial, sans-serif;font-size: 12px;text-align: left;}
#billing_statement_wrapper {width: 580px;background-color: #ffffff;padding: 10px 10px 0 10px;margin: 0px auto;border: 2px solid #d5d5d5;}
#billing_statement {width: 580px;}
#billing_statement td {padding: 0 0 15px 0;text-align: left;vertical-align: top;}
#billing_statement table.billing_statement_listing {margin: 0 0 15px 0;}
#billing_statement table.billing_statement_listing td, #billing_statement table.billing_statement_listing th {padding: 3px 5px 3px 5px;text-align: left;vertical-align: bottom;}
#billing_statement table.billing_statement_listing th {font-weight: normal;border-bottom: 1px solid #666666;}
#billing_statement table.billing_statement_listing td {border-bottom: 1px solid #e5e5e5;}
#billing_statement table.billing_statement_detail_listing th.billing_statement_listing_cell_datetime {width: 20%;}
#billing_statement table.billing_statement_detail_listing th.billing_statement_listing_cell_type {width: 15%;}
#billing_statement table.billing_statement_detail_listing th.billing_statement_listing_cell_detail {width: 50%;}
#billing_statement table.billing_statement_detail_listing th.billing_statement_listing_cell_money {width: 15%;}
#billing_statement table.billing_statement_listing tr.billing_statement_listing_tfoot td {font-weight: bold;text-align: right;border: none;border-bottom: 1px solid #666666;}
#billing_statement table.billing_statement_listing td.billing_statement_listing_cell_money, #billing_statement table.billing_statement_listing th.billing_statement_listing_cell_money {text-align: right;}
h2.billing_statement_section_title {background-color: #e5e5e5;padding: 2px 5px;margin: 0 0 15px 0;font-size: 13px;}
h3 {margin: 0 0 5px 0;}
#billing_statement_merchant_information, #billing_statement_information, #billing_statement_account_information, #billing_statement_summary {width: 50%;}
#billing_statement_account_information h2.billing_statement_section_title {margin: 0 10px 15px 0;} 
#billing_statement_account_information .adr {margin: 15px 0;}
#billing_statement_account_information .adr h3 {margin: 0;}
#billing_statement_information div {text-align: right;}
#billing_statement_merchant_name, #billing_statement_title {font-size: 18px;font-weight: bold;}
#billing_statement_summary_usage_period, #billing_statement_summary_balance_summary {padding: 0 0 5px 0;}
#billing_statement_summary_balance_paid_stamp {text-align: right;color: #ff0000;font-size: 18px;}
</style></head><body><div id="billing_statement_wrapper">';
		
		$invoices = $this->billing->getInvoices(true);
		foreach ($invoices as $invoice)
		{
			if ($invoice['id'] == $this->getParam(self::CALL_PARAM_NAME))
			{
				print $invoice['text'];
				
				print "</div></body></html>";
				exit();
			}
		}
		
		print "Invoice not found";
		exit();
	}
	
	public function xBuyEnvironmentsAction()
	{
		$this->billing->setComponentValue(6027, $this->getParam('amount')-1);
		$this->response->success();
	}
	
	public function buyEnvironmentsAction()
	{
		$limit = Scalr_Limits::init()->Load(Scalr_Limits::ACCOUNT_ENVIRONMENTS, $this->user->getAccountId());
		$this->response->page('ui/billing/buyEnvironments.js', array('currentLimit' => $limit->getLimitValue()));
	}
	
	public function xChangePlanAction()
	{
		if (!$this->getParam('package'))
			$this->response->failure("z");
		
		if ($this->billing->package == $this->getParam('package'))
			$this->response->success();
		
		if ($this->getParam('package') == 'cancel')
		{
			if ($this->billing->subscriptionId) {
				$this->billing->cancelSubscription();
			}
			
			$this->response->success("Subscription successfully cancelled and scalr won't monitor your instances any longer. You can reactivate your account at any time.");
			return;
		}
			
		if ($this->billing->subscriptionId) {
			
			$this->billing->changePackage($this->getParam('package'));			
			$this->response->success("Subscription successfully updated");
			
		} else {
			
			if (!$this->getParam('postalCode')) {
				$this->response->failure("Billing postal code is required");
				exit();
			}
			
			$this->billing->createSubscription(
				$this->getParam('package'), 
				$this->getParam('ccNumber'), 
				$this->getParam('ccExpMonth'), 
				$this->getParam('ccExpYear'), 
				$this->getParam('ccCvv'), 
				$this->getParam('firstName'), 
				$this->getParam('lastName'),
				$this->getParam('postalCode')
			);
			$this->response->success("Subscription successfully created");
		}
	}
	
	public function changePlanAction()
	{
		$info = $this->billing->getInfo();
		
		$this->response->page('ui/billing/changePlan.js', array(
			'subscriptionId' => $this->billing->subscriptionId, 
			'currentPackage' => $info['productHandle'],
			'availablePackages' => $this->billing->getAvailablePackages()
		), array(), array('ui/billing/changePlan.css'));
	}
	
	public function xSetEmergSupportAction()
	{
		$value = ($this->getParam('action') == 'subscribe') ? '1' : '0';
		
		$this->billing->setComponentValue(6026, $value);
		
		$this->response->success();
	}
	
	public function xUpdateCreditCardAction()
	{
		if (!$this->getParam('postalCode')) {
			$this->response->failure("Billing postal code is required");
			exit();
		}
		
		$this->billing->updateCreditCard(
			$this->getParam('ccNumber'),
			$this->getParam('ccCvv'),
			$this->getParam('ccExpMonth'),
			$this->getParam('ccExpYear'),
			$this->getParam('firstName'),
			$this->getParam('lastName'),
			$this->getParam('postalCode')
		);
		
		$this->response->success("Credit card details successfully updated");
	}
	
	public function updateCreditCardAction()
	{
		$c = explode(" ", $this->user->fullname);
		$fName = array_shift($c);
		$lName = implode(" ", $c);
		
		$this->response->page('ui/billing/updateCreditCard.js', array(
			'billing' => $this->billing->getInfo(), 
			'firstName'	=> $fName,
			'lastName'	=> $lName
		));
	}
	
	public function invoiceAction()
	{
		
	}
	
	public function detailsAction()
	{
		$f = Scalr_Limits::getFeatures();
		foreach ($f as $featureKey => $featureName) {
			$features[$featureName] = $this->user->getAccount()->isFeatureEnabled($featureKey);
		}
		
		$this->response->page('ui/billing/details.js', array(
			'billing' => $this->billing->getInfo(), 
			'limits' => $this->user->getAccount()->getLimits(),
			'features' => $features
		));
	}

    public function billingInfoAction()
    {
        $this->response->data($this->billing->getInfo());
    }
}
