<?php
	class Scalr_Exception_LimitExceeded extends Exception {
		function __construct($limitName) {
			parent::__construct(sprintf(_("%s limit exceeded for your account"), $limitName));
		}
	}