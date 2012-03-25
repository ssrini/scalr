<?php

require_once 'api.php';

class AdminAccounts extends APITests
{
	protected $API_ACCESS_KEY = "95208ee94e71d7b3";
	protected $API_SECRET_KEY = "vx1mln2F/+rXvlOVi9m3xPGg8ziLtupGv2E/YbnAIRPjtMc7LosmmT4y5e+Dhl0MmmMJk7XjLKCj24FJgwqCkIj+UPMQZO0KAF3o5KJpUrcTHjlZa4sMIgJscSy4njOWpN0fI7BH2j5NhsY90hAMp60jarzFZAduXVRNxkz1dVw=";

	public $accountId;

	public function testCreateAccount()
	{
		$r = $this->request("/admin/accounts/xSave", array(
			'name' => 'unittestcase',
			'comments' => 'Automatic test account',
			'ownerEmail' => 'unittestcase@test.com',
			'ownerPassword' => 'testtest'
		));
		$this->assertTrue($r->success && $r->accountId, (!$r->success) ? $r->errorMessage : "");
		$this->accountId = $r->accountId;
	}
	
	public function testCreateErrorAccount()
	{
		$r = $this->request("/admin/accounts/xSave", array(
			'name' => 'unittestcase',
			'comments' => 'Automatic test account',
		));
		$this->assertFalse($r->success);
	}
	
	public function testCreateError2Account()
	{
		$r = $this->request("/admin/accounts/xSave", array(
			'comments' => 'Automatic test account',
			'ownerEmail' => 'unittestcase@test.com',
			'ownerPassword' => 'testtest'
		));
		$this->assertFalse($r->success);
	}
	
	public function testCreateError3Account()
	{
		$r = $this->request("/admin/accounts/xSave", array(
			'name' => 'unittestcase',
			'comments' => 'Automatic test account',
			'ownerEmail' => 'unittestcase@test.com',
			'ownerPassword' => 'testtest'
		));
		$this->assertFalse($r->success);
	}
	
	public function testListAccounts()
	{
		$r = $this->request("/admin/accounts/xListAccounts");
		if ($this->assertTrue($r->success && $r->total, (!$r->success) ? $r->errorMessage : "")) {
			foreach ($r->data as $value) {
				if ($value->ownerEmail == 'unittestcase@test.com') {
					$this->assertTrue($value->id == $this->accountId, 'Double check for find');
				}
			}
		}
	}
	
	public function testGetAccount()
	{
		$r = $this->request('/admin/accounts/xGetInfo', array('accountId' => $this->accountId));
		if ($this->assertTrue($r->success && $r->account->name == 'unittestcase', (!$r->success) ? $r->errorMessage : "")) {
			$account = (array)$r->account;
			
			$account['limitServers'] = 5;
			$r = $this->request('/admin/accounts/xSave', $account);
			$this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "");
		}
	}
	
	public function testRemoveAccount()
	{
		$r = $this->request('/admin/accounts/xRemove', array('accounts' => array($this->accountId)));
		$this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "");
	}
}
