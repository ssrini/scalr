<?php

class Scalr_UI_Controller_Admin_Logs extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		return $this->user && ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN);
	}
	
	public function defaultAction()
	{
		$this->viewAction();
	}
	
	public function viewAction() 
	{
		$farms = array();
		foreach ($this->db->GetAll("SELECT id, name FROM farms") as $key => $value) {
			$farms[$value['id']] = $value['name'];
		}
		$farms[0] = 'All farms';
		$this->response->page('ui/admin/logs/view.js', array(
			'farms' => $farms
		));
	}

	public function xListLogsAction()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int'),
			'severity' => array('type' => 'array')
		));
		$sql = "SELECT transactionid, dtadded, message FROM syslog WHERE :FILTER:";
		$where = array();

		if ($this->getParam('farmId'))
			$where[] = "farmId = '" . $this->getParam('farmId') . "'";

		if (count($this->getParam('severity'))) {
			$severity = array();
			foreach ($this->getParam('severity') as $key => $value) {
				if ($value == '1' && in_array($value, array('INFO', 'WARN', 'ERROR', 'FATAL')))
					$severity[] = $key;
			}

			if (count($severity))
				$where[] = "severity IN (" . join($severity, ',') . ")";
		}

		if ($this->getParam('toDate'))
			$where[] = "TO_DAYS(dtadded) = TO_DAYS(" . $this->db->qstr($this->getParam('toDate')) . ")";

		if (count($where))
			$sql .= ' AND ' . join($where, ' AND ');

		$sql .= ' GROUP BY transactionid';

		$response = $this->buildResponseFromSql($sql, array('dtadded'), array('message'));
		foreach ($response['data'] as &$row) {
			$meta = $this->db->GetRow('SELECT * FROM syslog_metadata WHERE transactionid = ?', array($value['transactionid']));
			$row['warn'] = $meta['warnings'] ? $meta['warnings'] : 0;
			$row['err'] = $meta['errors'] ? $meta['errors'] : 0;
		}

		$this->response->data($response);
	}
	
	public function detailsAction() 
	{
		$this->response->page('ui/admin/logs/details.js');
	}
	
	public function xListDetailsAction()
	{
		$sql = "SELECT id, dtadded, message, severity, transactionid, caller FROM syslog WHERE transactionid = ? AND :FILTER:";

		if (count($this->getParam('severity'))) {
			$severity = array();
			foreach ($this->getParam('severity') as $key => $value) {
				if ($value == '1' && in_array($value, array('INFO', 'WARN', 'ERROR', 'FATAL')))
					$severity[] = $key;
			}

			if (count($severity))
				$sql .= " AND severity IN (" . join($severity, ',') . ")";
		}

		$response = $this->buildResponseFromSql($sql, array('transactionid'), array('caller', 'message'), array($this->getParam('trnId')));
		$this->response->data($response);
	}
}