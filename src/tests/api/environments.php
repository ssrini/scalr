<?php

require_once 'api.php';

class Environments extends APITests
{
	protected $API_ACCESS_KEY = '8189af1f4e85c136';
	protected $API_SECRET_KEY = 'BcaCtCUqqAmXFn44F66s5WbBv9db9A+HYQT/WezUOO9ZAcG9yeP+0U6wHkUxptH0giwsWJxG+ecFc7YadAYlGEwTCaEBDurpk2wxQC67DAe6dG1X80fDmGcwpPw2/j+T0GjCm7I1qQLuGWDxe/9Z0DA0x476iawvOtrETK7IrDw=';
	
	protected $envId;
	protected $credentials;
	
	public function __construct()
	{
		//$this->debug = true;
		$this->credentials = parse_ini_file(SRCPATH . '/../etc/tests/environments.ini', true);
	}

	public function testCreateEnvironment()
	{
		$r = $this->request('/environments/xCreate', array('name' => 'ui-auto-test'));
		if ($this->assertTrue($r->success && $r->envId, (!$r->success) ? $r->errorMessage : "")) {
			$this->envId = $r->envId;
		}
	}

	public function testListEnvironments()
	{
		$r = $this->request('/environments/xListEnvironments');
		if ($this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "")) {
			foreach ($r->data as $value) {
				if ($value->name == 'ui-auto-test') {
					$this->assertTrue(true);
				}
			}
		}
	}

	public function testGetEnvironment()
	{
		$r = $this->request('/environments/' . $this->envId . '/xGetInfo');
		if ($this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "")) { }
	}

	public function testSetEc2Environment()
	{
		if ($this->credentials['ec2']) {
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveEc2', array(
				'ec2.is_enabled' => '1',
				'ec2.account_id' => $this->credentials['ec2']['ec2.account_id'],
				'ec2.access_key' => $this->credentials['ec2']['ec2.access_key'],
				'ec2.secret_key' => $this->credentials['ec2']['ec2.secret_key']
			), array(
				'ec2.certificate' => SRCPATH . $this->credentials['ec2']['ec2.certificate'],
				'ec2.private_key' => SRCPATH . $this->credentials['ec2']['ec2.private_key']
			));
			$this->assertTrue($r->success && $r->enabled, (!$r->success) ? $r->errorMessage : "");

			$r = $this->request('/environments/' . $this->envId . '/platform/xGetEc2');
			$r->ec2 = (array) $r->ec2;
			$this->assertTrue(
				$r->success &&
				$r->ec2 &&
				$r->ec2['ec2.account_id'] == $this->credentials['ec2']['ec2.account_id'] &&
				$r->ec2['ec2.access_key'] == $this->credentials['ec2']['ec2.access_key'] &&
				$r->ec2['ec2.secret_key'] == '******' &&
				$r->ec2['ec2.certificate'] == 'Uploaded' &&
				$r->ec2['ec2.private_key'] == 'Uploaded' &&
				$r->ec2['ec2.is_enabled'] == '1',
				(!$r->success) ? $r->errorMessage : ""
			);

			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveEc2', array('ec2.is_enabled' => ''));
			$this->assertTrue($r->success && !$r->enabled, (!$r->success) ? $r->errorMessage : "");

			$r = $this->request('/environments/' . $this->envId . '/platform/xGetEc2');
			$r->ec2 = (array) $r->ec2;
			$this->assertTrue(
				$r->success &&
				$r->ec2 &&
				$r->ec2['ec2.is_enabled'] == '',
				(!$r->success) ? $r->errorMessage : ""
			);
		}
	}

	public function testSetRackspaceEnvironment()
	{
		if ($this->credentials['rackspace']) {
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveRackspace', $this->credentials['rackspace']);
			$this->assertTrue($r->success && $r->enabled, (!$r->success) ? $r->errorMessage : "");

			$r = $this->request('/environments/' . $this->envId . '/platform/xGetRackspace');
			$r->rackspace = (array) $r->rackspace;
			$this->assertTrue(
				$r->success &&
				$r->rackspace &&
				$r->rackspace['test1']['rackspace.is_enabled']
				$r->ec2['ec2.is_enabled'] == '',
				(!$r->success) ? $r->errorMessage : ""
			);

			$this->credentials['rackspace']['rackspace.is_enabled.rs-LONx'] = "";
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveRackspace', $this->credentials['rackspace']);
			$this->assertTrue($r->success && $r->enabled, (!$r->success) ? $r->errorMessage : "");

			$this->credentials['rackspace']['rackspace.is_enabled.rs-ORD1'] = "";
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveRackspace', $this->credentials['rackspace']);
			$this->assertTrue($r->success && !$r->enabled, (!$r->success) ? $r->errorMessage : "");
		}
	}

	public function testSetNimbulaEnvironment()
	{
		if ($this->credentials['nimbula']) {
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveNimbula', $this->credentials['nimbula']);
			$this->assertTrue($r->success && $r->enabled, (!$r->success) ? $r->errorMessage : "");

			$this->credentials['nimbula']['nimbula.is_enabled'] = "";
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveNimbula', $this->credentials['nimbula']);
			$this->assertTrue($r->success && !$r->enabled, (!$r->success) ? $r->errorMessage : "");
		}
	}

	public function testSetCloudstackEnvironment()
	{
		if ($this->credentials['cloudstack']) {
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveCloudstack', $this->credentials['cloudstack']);
			$this->assertTrue($r->success && $r->enabled, (!$r->success) ? $r->errorMessage : "");

			$this->credentials['cloudstack']['cloudstack.is_enabled'] = "";
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveCloudstack', $this->credentials['cloudstack']);
			$this->assertTrue($r->success && !$r->enabled, (!$r->success) ? $r->errorMessage : "");
		}
	}

	public function testSetEucalyptusEnvironment()
	{
		if ($this->credentials['eucalyptus']) {
			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveEucalyptus', array(
				'clouds' => array('test1', 'test2'),
				'eucalyptus.account_id.test1' => $this->credentials['eucalyptus']['eucalyptus.account_id.test1'],
				'eucalyptus.access_key.test1' => $this->credentials['eucalyptus']['eucalyptus.access_key.test1'],
				'eucalyptus.secret_key.test1' => $this->credentials['eucalyptus']['eucalyptus.secret_key.test1'],
				'eucalyptus.ec2_url.test1' => $this->credentials['eucalyptus']['eucalyptus.ec2_url.test1'],
				'eucalyptus.s3_url.test1' => $this->credentials['eucalyptus']['eucalyptus.s3_url.test1'],

				'eucalyptus.account_id.test2' => $this->credentials['eucalyptus']['eucalyptus.account_id.test2'],
				'eucalyptus.access_key.test2' => $this->credentials['eucalyptus']['eucalyptus.access_key.test2'],
				'eucalyptus.secret_key.test2' => $this->credentials['eucalyptus']['eucalyptus.secret_key.test2'],
				'eucalyptus.ec2_url.test2' => $this->credentials['eucalyptus']['eucalyptus.ec2_url.test2'],
				'eucalyptus.s3_url.test2' => $this->credentials['eucalyptus']['eucalyptus.s3_url.test2']
			), array(
				'eucalyptus.private_key.test1' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.private_key.test1'],
				'eucalyptus.certificate.test1' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.certificate.test1'],
				'eucalyptus.cloud_certificate.test1' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.cloud_certificate.test1'],

				'eucalyptus.private_key.test2' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.private_key.test2'],
				'eucalyptus.certificate.test2' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.certificate.test2'],
				'eucalyptus.cloud_certificate.test2' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.cloud_certificate.test2']
			));
			$this->assertTrue($r->success && $r->enabled, (!$r->success) ? $r->errorMessage : "");

			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveEucalyptus', array(
				'clouds' => array('test1'),
				'eucalyptus.account_id.test1' => $this->credentials['eucalyptus']['eucalyptus.account_id.test1'],
				'eucalyptus.access_key.test1' => $this->credentials['eucalyptus']['eucalyptus.access_key.test1'],
				'eucalyptus.secret_key.test1' => $this->credentials['eucalyptus']['eucalyptus.secret_key.test1'],
				'eucalyptus.ec2_url.test1' => $this->credentials['eucalyptus']['eucalyptus.ec2_url.test1'],
				'eucalyptus.s3_url.test1' => $this->credentials['eucalyptus']['eucalyptus.s3_url.test1']
			), array(
				'eucalyptus.private_key.test1' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.private_key.test1'],
				'eucalyptus.certificate.test1' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.certificate.test1'],
				'eucalyptus.cloud_certificate.test1' => SRCPATH . $this->credentials['eucalyptus']['eucalyptus.cloud_certificate.test1']
			));
			$this->assertTrue($r->success && $r->enabled, (!$r->success) ? $r->errorMessage : "");

			$r = $this->request('/environments/' . $this->envId . '/platform/xSaveEucalyptus');
			$this->assertTrue($r->success && !$r->enabled, (!$r->success) ? $r->errorMessage : "");
		}
	}

	public function testRemoveEnvironment()
	{
		$r = $this->request('/environments/xRemove', array('envId' => $this->envId));
		$this->assertTrue($r->success, (!$r->success) ? $r->errorMessage : "");
	}
}
