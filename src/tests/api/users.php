<?php

require_once 'api.php';

class Users extends APITests
{
	protected $API_ACCESS_KEY = '8189af1f4e85c136';
	protected $API_SECRET_KEY = 'BcaCtCUqqAmXFn44F66s5WbBv9db9A+HYQT/WezUOO9ZAcG9yeP+0U6wHkUxptH0giwsWJxG+ecFc7YadAYlGEwTCaEBDurpk2wxQC67DAe6dG1X80fDmGcwpPw2/j+T0GjCm7I1qQLuGWDxe/9Z0DA0x476iawvOtrETK7IrDw=';
	
	protected $userId, $teamId;
	protected $debug = true;

	public function constructor()
	{
		
	}

	public function testCreateUser()
	{
		$r = $this->request('/account/users/xSave', array(
			'email' => 'ui-test@test.com',
			'password' => '123',
			'status' => 'Active',
			'fullname' => 'ui-test',
			'comments' => 'For testing'
		));

		if ($this->assertTrue($r->success && $r->user->id, (!$r->success) ? $r->errorMessage : "")) {
			$this->userId = $r->user->id;
		}
	}

	public function testGetUser()
	{
		$r = $this->request('/account/users/xGetInfo', array('userId' => $this->userId));
		$this->assertTrue($r->success && $r->user->id, (!$r->success) ? $r->errorMessage : "");

		$r = $this->request('/account/users/xGetApiKeys', array('userId' => $this->userId));
		$this->assertTrue($r->success && $r->accessKey && $r->secretKey, (!$r->success) ? $r->errorMessage : "");
	}

	public function testListUsers()
	{
		$r = $this->request("/account/users/xListUsers");
		if ($this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "")) {
			
		}
	}
	
	public function testCreateTeam()
	{
		$r = $this->request("/account/teams/xCreate", array(
			'name' => 'test team',
			'ownerId' => $this->userId,
			'envId' => 99 // replace for founded default environment
		));

		if ($this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "")) {
			$this->teamId = $r->teamId;
		}
	}

	public function teamRemoveTeam()
	{
		$r = $this->request("/account/users/xRemove", array('teamId' => $this->teamId));
		$this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "");
	}

	public function testRemoveUser()
	{
		$r = $this->request("/account/users/xRemove", array('userId' => $this->userId));
		$this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "");
	}
}
