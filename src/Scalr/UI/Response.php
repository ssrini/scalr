<?php

class Scalr_UI_Response
{
	public
		$body = '',
		$headers = array(),
		$httpResponseCode = 200,
		$jsResponse = array('success' => true),
		$jsResponseFlag = false;

	private static $_instance = null;

	/**
	 * 
	 * @return Scalr_UI_Response
	 */
	public static function getInstance()
	{
		if (self::$_instance === null)
			self::$_instance = new Scalr_UI_Response();

		return self::$_instance;
	}

	public function pageNotFound()
	{
		$this->setHttpResponseCode(404);
	}

	public function pageAccessDenied()
	{
		$this->setHttpResponseCode(403);
		//throw new Exception('Access denied');
	}

	/*
	 *Normalizes a header name to X-Capitalized-Names
	 */
	protected function normalizeHeader($name)
	{
		$filtered = str_replace(array('-', '_'), ' ', (string) $name);
		$filtered = ucwords(strtolower($filtered));
		$filtered = str_replace(' ', '-', $filtered);
		return $filtered;
	}

	public function setHeader($name, $value, $replace = false)
	{
		$name = $this->normalizeHeader($name);
		$value = (string) $value;

		if ($replace) {
			foreach ($this->headers as $key => $header) {
				if ($name == $header['name'])
					unset($this->headers[$key]);
			}
		}

		$this->headers[] = array(
			'name' => $name,
			'value' => $value,
			'replace' => $replace
		);
	}

	public function setBody($body)
	{
		$this->body = $body;
	}

	public function setRedirect($url, $code = 302)
	{
		$this->setHeader('Location', $url, true);
		$this->setHttpResponseCode($code);
	}

	public function setHttpResponseCode($code)
	{
		$this->httpResponseCode = $code;
	}

	public function setResponse($value)
	{
		$this->body = $value;
	}

	public function setJsonResponse($value, $type = "javascript")
	{
		$this->setResponse(json_encode($value));

		if ($type == "javascript")
			$this->setHeader('content-type', 'text/javascript', true);
		elseif ($type == "text")
			$this->setHeader('content-type', 'text/html'); // hack for ajax file uploads
	}

	public function sendResponse()
	{
		foreach ($this->headers as $header) {
			header($header['name'] . ': ' . $header['value'], $header['replace']);
		}

		if ($this->jsResponseFlag)
			$this->prepareJsonResponse();

		header("HTTP/1.0 {$this->httpResponseCode}");
		echo $this->body;
	}

	/* JS response methods */
	public function prepareJsonResponse()
	{
		$this->setResponse(json_encode($this->jsResponse));

		if (isset($_REQUEST['X-Requested-With']) && $_REQUEST['X-Requested-With'] == 'XMLHttpRequest')
			$this->setHeader('content-type', 'text/javascript', true);
		else
			$this->setHeader('content-type', 'text/html'); // hack for ajax file uploads and other cases
	}

	private function getModuleName($name)
	{
		$fl = APPPATH . "/www/ui/js/{$name}";
		if (file_exists($fl))
			$tm = filemtime(APPPATH . "/www/ui/js/{$name}");
		else
			throw new Scalr_UI_Exception_NotFound(sprintf('Js file not found'));

		$nameTm = str_replace('.js', "-{$tm}.js", $name);
		$nameTm = str_replace('.css', "-{$tm}.css", $nameTm);

		return "/ui/js/{$nameTm}";
	}

	public function page($name, $params = array(), $requires = array(), $requiresCss = array())
	{
		$this->jsResponse['moduleName'] = $this->getModuleName($name);
		$this->jsResponse['moduleParams'] = $params;

		if (count($requires)) {
			foreach ($requires as $key => $value)
				$this->jsResponse['moduleRequires'][] = $this->getModuleName($value);
		}

		if (count($requiresCss)) {
			foreach ($requiresCss as $key => $value)
				$this->jsResponse['moduleRequiresCss'][] = $this->getModuleName($value);
		}

		$this->jsResponseFlag = true;
	}

	public function success($message = null)
	{
		if ($message)
			$this->jsResponse['successMessage'] = $message;

		$this->jsResponseFlag = true;
	}

	public function failure($message = null)
	{
		if ($message)
			$this->jsResponse['errorMessage'] = $message;

		$this->jsResponse['success'] = false;
		$this->jsResponseFlag = true;
	}

	public function warning($message = null)
	{
		if ($message)
			$this->jsResponse['warningMessage'] = $message;

		$this->jsResponseFlag = true;
	}

	public function data($arg)
	{
		$this->jsResponse = array_merge($this->jsResponse, $arg);
		$this->jsResponseFlag = true;
	}

	public function jsonDump($value, $name = 'var')
	{
		$this->setHeader('X-Scalr-Debug', $name . ': ' . json_encode($value));
	}

	public function varDump($value, $name = 'var')
	{
		$this->setHeader('X-Scalr-Debug', $name . ': ' . print_r($value, true));
	}
}
