<?php

	//@TODO: optimize
	register_shutdown_function(function () {
		$error = error_get_last();
		if ($error && (
			$error['type'] == E_ERROR ||
			$error['type'] == E_PARSE ||
			$error['type'] == E_COMPILE_ERROR
		)) {
			if (! headers_sent()) {
				header("HTTP/1.0 500");
			}
		}
	});

	$path = trim(str_replace("?{$_SERVER['QUERY_STRING']}", "", $_SERVER['REQUEST_URI']), '/');

	define('SCALR_NOT_CHECK_SESSION', 1);
	try {
		require("src/prepend.inc.php");

		$session = Scalr_Session::getInstance();
		try {
			$request = Scalr_UI_Request::initializeInstance(Scalr_UI_Request::REQUEST_TYPE_UI, $session->getUserId(), $session->getEnvironmentIdS());
		} catch (Exception $e) {
			if ($path == 'guest/logout') {
				// hack
				Scalr_Session::destroy();
				Scalr_UI_Response::getInstance()->setRedirect('/');
				Scalr_UI_Response::getInstance()->sendResponse();
				exit;
			}
			$message = $e->getMessage() . ' <a href="/guest/logout">Click here to login as another user</a>';
			throw new Exception($message);
		}

		if ($session->isAuthenticated()) {
			$session->setEnvironmentId($request->getEnvironment()->id);
		}

		//@session_write_close();

		Scalr_UI_Controller::handleRequest(explode('/', $path), $_REQUEST);
	} catch (Exception $e) {
		Scalr_UI_Response::getInstance()->failure($e->getMessage());
		Scalr_UI_Response::getInstance()->sendResponse();
	}
