<?php

class Scalr_Service_Zookeeper_Exception extends Exception {
	const OK = 200;
	const OK_CREATED = 201;
	const OK_NO_CONTENT = 204;
	const BAD_REQUEST = 400;
	const NOT_FOUND = 404;
	const CONFLICT = 409;
	const BAD_VERSION = 412; // ZBADVERSION
	const UNSUPPORTED_MEDIA_TYPE = 	415;
	const INTERNAL_ERROR = 500;
	const NOT_IMPLEMENTED = 501; 
	const OTHER_ERR = 502;
	const SERVICE_UNAVAILABLE = 503;
	const TIMEOUT = 504;
}