<?php
class Scalr_Api_Controller extends Scalr_UI_Controller
{

/*	static public function handleRequest($pathChunks, $params)
	{
		parent::handleRequest($pathChunks, $params);
	}
		//$apiControllerClassName = "Scalr_Api_{$params['version']}_Controller"; 
		

		try {
			Scalr_UI_Request::getInstance()->setParams($params);
			$controller = self::loadController(array_shift($pathChunks), 'Scalr_UI_Controller', true);

			if (! Scalr_Session::getInstance()->isAuthenticated() && get_class($controller) != 'Scalr_UI_Controller_Guest') {
				throw new Scalr_UI_Exception_AccessDenied();
			} else {
				$controller->uiCacheKeyPattern = '';

				$controller->addUiCacheKeyPatternChunk(strtolower((array_pop(explode('_', get_class($controller))))));
				$controller->call($pathChunks);
			}

		} catch (Scalr_UI_Exception_AccessDenied $e) {
			Scalr_UI_Response::getInstance()->setHttpResponseCode(403);
		
		
		
		if (!class_exists($apiControllerClassName))
			throw new Exception(sprintf("API version '%s' not supported", $params['version']));
		
		$path = "/". implode("/", $pathChunks);
		self::authtenticate($keyId, $token, $path, $envId);
		
		array_shift($pathChunks);
		
		parent::handleRequest($pathChunks, $params);
	}*/
}
