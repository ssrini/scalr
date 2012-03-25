<?
require_once("../src/externals/simpletest/autorun.php");
require_once("../src/prepend.inc.php");

class AllTests extends TestSuite {
	public function __construct() {
		$this->TestSuite('API tests');
		//$this->addFile(SRCPATH . '/tests/api/accounts.php');
		$this->addFile(SRCPATH . '/tests/api/environments.php');
		//$this->addFile(SRCPATH . '/tests/api/users.php');
	}
}
