<?php

class Scalr_Integration_ZohoCrm_DeferredMediator 
		extends Scalr_Integration_ZohoCrm_Mediator {

	const TASK_QUEUE_NAME = "ZohoCRM";

	private $taskQueue;

	private $logger;
	
	function __construct () {
		$this->logger = Logger::getLogger(__CLASS__);
	}
	
	/**
	 * @param Client $client
	 */
	function addClient ($client) {
		$task = new Scalr_Integration_ZohoCrm_Task(
				Scalr_Integration_ZohoCrm_Task::OP_CREATE_CLIENT, 
				array("clientId" => $client->ID));
				
		$this->logger->info(sprintf("Enqueue add client (client: '%s', clientid: %d)", 
				$client->Fullname, $client->ID));
		$this->enqueueTask($task);
	}
	
	/**
	 * @param Client $client
	 */
	function updateClient ($client, $skipRelations=array()) {
		$task = new Scalr_Integration_ZohoCrm_Task(
				Scalr_Integration_ZohoCrm_Task::OP_UPDATE_CLIENT,
				array("clientId" => $client->ID, "skipRelations" => $skipRelations));
				
		$this->logger->info(sprintf("Enqueue update client (client: '%s', clientid: %d)", 
				$client->Fullname, $client->ID));
		$this->enqueueTask($task);
	}

	/**
	 * @param Client $client
	 */
	function deleteClient ($client) {
		$deletedClient = new Scalr_Integration_ZohoCrm_DeletedClient();
		$deletedClient->ID = $client->ID;
		$deletedClient->Fullname = $client->Fullname;
		$deletedClient->SetSettingValue(CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID, 
				$client->GetSettingValue(CLIENT_SETTINGS::ZOHOCRM_ACCOUNT_ID));
		
		$task = new Scalr_Integration_ZohoCrm_Task(
				Scalr_Integration_ZohoCrm_Task::OP_DELETE_CLIENT,
				array("deletedClient" => $deletedClient));
				
		$this->logger->info(sprintf("Enqueue delete client (client: '%s', clientid: %d)", 
				$client->Fullname, $client->ID));
		$this->enqueueTask($task);
	}
	
	/**
	 * @param Client $client
	 */
	function addPayment ($client, $invoiceId) {
		$task = new Scalr_Integration_ZohoCrm_Task(
				Scalr_Integration_ZohoCrm_Task::OP_ADD_PAYMENT,
				array("clientId" => $client->ID, "invoiceId" => $invoiceId));
		
		$this->logger->info(sprintf("Enqueue add payment (client: '%s', clientid: %d, invoiceid: %d)", 
				$client->Fullname, $client->ID, $invoiceId));
		$this->enqueueTask($task);
	}
	
	/**
	 * @param Scalr_Integration_ZohoCrm_Task $task
	 * @return void
	 */
	private function enqueueTask ($task) {
		if ($this->taskQueue === null) {
			$this->taskQueue = TaskQueue::Attach(self::TASK_QUEUE_NAME);
		}
		
		$this->taskQueue->AppendTask($task);
	}
	
	// Scalr listeners
	
	/**
	 * Scalr addClient event listener
	 * @param Client $client
	 */
	function onAddClient ($client) {
		$this->addClient($client);
	}
	
	/**
	 * Scalr updateClient event listener
	 * @param Client $client
	 */
	function onUpdateClient ($client) {
		$this->updateClient($client);
	}

	/**
	 * Scalr beforeDeleteClient event listener
	 * @param Client $client
	 */
	function onBeforeDeleteClient ($client) {
		$this->deleteClient($client);
	}
	
	/**
	 * Scalr addPayment event listener
	 * @param Client $client
	 * @param int $invoiceId
	 */
	function onAddPayment ($client, $invoiceId) {
		$this->addPayment($client, $invoiceId);
	}
	
	/**
	 * @param Client $client
	 * @param string $paypalSubscrId
	 */
	function onSubscrCancel ($client, $paypalSubscrId) {
		if ($client)
		{
			$client->SetSettingValue(
				CLIENT_SETTINGS::ZOHOCRM_UNSUBSCR_DATE, date("m/d/Y"));
			$this->updateClient($client, array('contact'));
		}
	}
	
	/**
	 * @param Client $client
	 * @param string $paypalSubscrId
	 */
	function onSubscrSignup ($client, $paypalSubscrId) {
		$client->ClearSettings(CLIENT_SETTINGS::ZOHOCRM_UNSUBSCR_DATE);
		$this->updateClient($client, array('contact'));
	}
	
	/**
	 * @param Client $client 
	 * @param string $paypalSubscrId
	 */
	function onSubscrEot ($client, $paypalSubscrId) {
		$this->onSubscrCancel($client, $paypalSubscrId);
	}
	
	function onSubscrUpdate($client) {
		$this->updateClient($client, array('contact'));
	}
}