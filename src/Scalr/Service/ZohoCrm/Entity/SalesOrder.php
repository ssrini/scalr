<?php

/**
 * 
 * @author Marat Komarov
 *
 * @property string $salesOrderOwner
 * @property int $soNumber
 * @property string $subject
 * @property string $potentialName
 * @property string $customerNo
 * @property string $purchaseOrder
 * @property string $quoteName
 * @property string $contactName
 * @property string $dueDate
 * @property string $carrier
 * @property string $pending
 * @property string $status
 * @property number $salesCommission
 * @property string $exciseDuty
 * @property string $accountName
 * @property number $accountId
 * @property string $assignedTo
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
 * @property float $adjustment
 * @property float $grandTotal
 * @property float $subTotal
 * @property float $tax
 * @property float $discount
 * @property string $termsAndConditions
 * @property string $description
 */
class Scalr_Service_ZohoCrm_Entity_SalesOrder 
		extends Scalr_Service_ZohoCrm_Entity {
	
	private $productDetails = array();
			
	function __construct () {
		parent::__construct();
		
		$this->nameLabelMap = array_merge($this->nameLabelMap, array(
			"id" => "SALESORDERID",
			"salesOrderOwner" => "Sales Order Owner",	
			"soNumber" => "SO Number",
			"subject" => "Subject",
			"potentialName" => "Potential Name",
			"customerNo" => "Customer No",		
			"purchaseOrder" => "Purchase Order",
			"quoteName" => "Quote Name",
			"contactName" => "Contact Name",
			"dueDate" => "Due Date",
			"carrier" => "Carrier",
			"pending" => "Pending",
			"status" => "Status",
			"salesCommission" => "Sales Commission",
			"exciseDuty" => "Excise Duty",
			"accountName" => "Account Name",
			"accountId" => "ACCOUNTID",
			"assignedTo" => "Assigned To",
		
			"billingStreet" => "Billing Street",
			"billingCity" => "Billing City",
			"billingState" => "Billing State",
			"billingCode" => "Billing Zip",
			"billingCountry" => "Billing Country",
		
			"shippingStreet" => "Shipping Street",
			"shippingCity" => "Shipping City",
			"shippingState" => "Shipping State",
			"shippingCode" => "Shipping Zip",
			"shippingCountry" => "Shipping Country",
			
			"adjustment" => "Adjustment",
			"grandTotal" => "Grand Total",
			"subTotal" => "Sub Total",
			"tax" => "Tax",
			"discount" => "Discount",		
			"termsAndConditions" => "Terms & Conditions",
			"description" => "Description"					
		));
	}

	function decode (DOMElement $container) {
		parent::decode($container);
		
		// Decode product details
		$xpath = new DOMXPath($container->ownerDocument);
		
		$products = $xpath->query("./fieldlabel[@value='Product Details']/product", $container);
		foreach ($products as $product) {
			$productDetail = new Scalr_Service_ZohoCrm_Entity_ProductDetail();
			$productDetail->decode($product);
			$this->addProductDetail($productDetail);
		}
	}
	
	function encode (DOMElement $container, DOMDocument $doc) {
		parent::encode($container, $doc);
		
		if ($this->productDetails) {
			$detailsNode = $doc->createElement("fieldlabel");
			$detailsNode->setAttribute("value", "Product Details");
			
			foreach ($this->productDetails as $i => $productDetail) {
				$row = $doc->createElement("product");
				$row->setAttribute("no", $i+1);
				$productDetail->encode($row, $doc);
				$detailsNode->appendChild($row);
			}
			
			$container->appendChild($detailsNode);
		}
	}

	/**
	 * @param Scalr_Service_ZohoCrm_Entity_ProductDetail $productDetail
	 * @return bool
	 */
	function addProductDetail ($productDetail) {
		if (false === array_search($productDetail, $this->productDetails)) {
			$this->productDetails[] = $productDetail;
			return true;
		}
		return false;
	}
	
	/**
	 * @param Scalr_Service_ZohoCrm_Entity_ProductDetail $productDetail
	 * @return bool
	 */
	function removeProductDetail ($productDetail) {
		if (false !== ($i = array_search($productDetail, $this->productDetails))) {
			array_splice($this->productDetails, $i, 1);
			return true;
		}
		return false;
	}
	
	function getProductDetails () {
		return $this->productDetails;
	}
}