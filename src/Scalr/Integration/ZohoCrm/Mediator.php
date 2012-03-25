<?php
abstract class Scalr_Integration_ZohoCrm_Mediator {

	static private $defaultMediator;
	
	/**
	 * @param Scalr_Integration_ZohoCrm_Mediator $defaultMediator
	 * @return void
	 */
	static function setDefaultMediator ($defaultMediator) {
		self::$defaultMediator = $defaultMediator;
	}
	
	/**
	 * @return Scalr_Integration_ZohoCrm_Mediator
	 */
	static function getDefaultMediator () {
		return self::$defaultMediator;
	}
	
	/**
	 * @param Client $client
	 */	
	abstract function addClient ($client);
	
	/**
	 * @param Client $client
	 */	
	abstract function updateClient ($client, $skipRelations=array());
	
	/**
	 * @param Client $client
	 */	
	abstract function deleteClient ($client);
	
	/**
	 * @param Client $client
	 */	
	abstract function addPayment ($client, $invoiceId);
	
}