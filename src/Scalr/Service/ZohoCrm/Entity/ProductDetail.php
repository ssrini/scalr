<?php

/**
 * 
 * @author Marat Komarov
 *
 * @property number $productId *
 * @property string $productName
 * @property float $unitPrice
 * @property int $quantity *
 * @property int $quantityInStock
 * @property float $total *
 * @property float $discount *
 * @property float $totalAfterDiscount *
 * @property float $listPrice *
 * @property float $netTotal *
 * @property float $tax *
 */
class Scalr_Service_ZohoCrm_Entity_ProductDetail 
	extends Scalr_Service_ZohoCrm_EntityPart {
		
	function __construct () {
		$this->nameLabelMap = array_merge($this->nameLabelMap, array(
			"productId" => "Product Id",
			"productName" => "Product Name",
			"unitPrice" => "Unit Price",
			"quantity" => "Quantity",
			"quantityInStock" => "Quantity in Stock",
			"total" => "Total",
			"discount" => "Discount",
			"totalAfterDiscount" => "Total After Discount",
			"listPrice" => "List Price",
			"netTotal" => "Net Total",
			"tax" => "Tax"
		));
	}
	
}