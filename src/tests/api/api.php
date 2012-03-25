<?php

abstract class APITests extends UnitTestCase
{
	protected $API_ACCESS_KEY;
	protected $API_SECRET_KEY;
	protected $debug = false;

	protected function request($path, $args = array(), $files = array(), $envId = 0, $version = 'v1')
	{
		try {
			$httpRequest = new HttpRequest();
			$httpRequest->setMethod(HTTP_METH_POST);
			
			$postData = json_encode($args);
			
			$stringToSign = "/{$version}{$path}:" . $this->API_ACCESS_KEY . ":{$envId}:{$postData}:" . $this->API_SECRET_KEY;
			$validToken = Scalr_Util_CryptoTool::hash($stringToSign);

			$httpRequest->setHeaders(array(
				"X_SCALR_AUTH_KEY" => $this->API_ACCESS_KEY,
				"X_SCALR_AUTH_TOKEN" => $validToken,
				"X_SCALR_ENV_ID" => $envId
			));

			$httpRequest->setUrl("http://scalr-trunk.localhost/{$version}{$path}");
			$httpRequest->setPostFields(array('rawPostData' => $postData));

			foreach ($files as $name => $file) {
				$httpRequest->addPostFile($name, $file);
			}
			
			$httpRequest->send();
			
			if ($this->debug) {
				print "<pre>";
				var_dump($httpRequest->getRequestMessage());
				var_dump($httpRequest->getResponseCode());
				var_dump($httpRequest->getResponseData());
			}

			$data = $httpRequest->getResponseData();
			return @json_decode($data['body']);
		} catch (Exception $e) {
			echo "<pre>";
			if ($this->debug)
				var_dump($e);
			else
				var_dump($e->getMessage());
		}
	}
}
