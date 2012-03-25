<?php

class Scalr_Integration_ZohoCrm_Task extends Task {
	
	const OP_CREATE_CLIENT = "createClient";
	const OP_UPDATE_CLIENT = "updateClient";
	const OP_DELETE_CLIENT = "deleteClient";
	const OP_ADD_PAYMENT = "addMonthlyPayment";
	
	private $operation;
	
	private $params;
	
	function __construct ($operation, $params) {
		$this->operation = $operation;
		$this->params = $params;
	}
	
	function run () {
		$mediator = Scalr_Integration_ZohoCrm_Mediator::getDefaultMediator();			
		if (!$mediator) {
			throw new Scalr_Integration_Exception("No default mediator configured");
		}
		
		switch ($this->operation) {
			case self::OP_CREATE_CLIENT:
				$client = Client::Load($this->params["clientId"]);				
				$mediator->addClient($client);
				break;
				
			case self::OP_UPDATE_CLIENT:
				$client = Client::Load($this->params["clientId"]);				
				$mediator->updateClient($client, $this->params["skipRelations"]);
				break;
				
			case self::OP_DELETE_CLIENT:
				$mediator->deleteClient($this->params["deletedClient"]);
				break;
				
			case self::OP_ADD_PAYMENT:
				$client = Client::Load($this->params["clientId"]);
				$mediator->addPayment($client, $this->params["invoiceId"]);
				break;
				
			default:
				throw new Scalr_Integration_Exception("No handler for operation '{$this->operation}'");
		}
	}
	
	function getOperation () {
		return $this->operation;
	}
	
	function getParams () {
		return $this->params;
	}
}