<?php
class Scalr_UI_Controller_Dm_Sources extends Scalr_UI_Controller
{
	const CALL_PARAM_NAME = 'sourceId';

	public function defaultAction()
	{
		$this->viewAction();
	}

	public function saveAction()
	{
		$this->request->defineParams(array(
			'sourceId' => array('type' => 'string'),
			'url','type','login','password','sshPrivateKey'
		));

		$source = Scalr_Dm_Source::init();

		$authInfo = new stdClass();
		if ($this->getParam('type') == 'svn')
		{
			$authInfo->login = $this->getParam('login');
			
			if ($this->getParam('password') != '******')
				$authInfo->password	= $this->getParam('password');
				
			$authType = Scalr_Dm_Source::AUTHTYPE_PASSWORD;
		}
        else if ($this->getParam('type') == 'git')
        {
            $authInfo->sshPrivateKey = $this->getParam('sshPrivateKey');
            $authType = Scalr_Dm_Source::AUTHTYPE_CERT;
        }

		if (!$this->getParam('sourceId'))
			$source->envId = $this->getEnvironmentId();
		else {
			$source->loadById($this->getParam('sourceId'));
			$this->user->getPermissions()->validate($source);
		}

		$source->url = $this->getParam('url');
		$source->type = $this->getParam('type');
		$source->authType = $authType;
		$source->setAuthInfo($authInfo);
		$result = $source->save();
        $source = array();
        $source['id'] = $result->id;
        $source['name'] = $result->url;
		$this->response->success('Deployment source successfully saved');
        $this->response->data(array('source'=>$source));
	}

	public function createAction()
	{
		$this->response->page('ui/dm/sources/create.js');
	}

	public function getList()
	{
		$retval = array();
		$s = $this->db->execute("SELECT url, id FROM dm_sources WHERE env_id = ?", array($this->getEnvironmentId()));
		while ($source = $s->fetchRow()) {
			$retval[$source['id']] = $source['url'];
		}

		return $retval;
	}

	public function editAction()
	{
		$this->request->defineParams(array(
			'sourceId' => array('type' => 'string')
		));

		$source = Scalr_Dm_Source::init()->loadById($this->getParam('sourceId'));
		$this->user->getPermissions()->validate($source);

		$this->response->page('ui/dm/sources/create.js', array(
			'source' => array(
				'authInfo' => $source->getAuthInfo(),
				'url'	   => $source->url,
				'type'	   => $source->type
			)
		));
	}

	public function viewAction()
	{
		$this->response->page('ui/dm/sources/view.js');
	}

	public function xRemoveSourcesAction()
	{
		$this->request->defineParams(array(
			'sourceId' => array('type' => 'int')
		));

		$source = Scalr_Dm_Source::init()->loadById($this->getParam('sourceId'));
		$this->user->getPermissions()->validate($source);

		// Check template usage
		$dsCount = $this->db->GetOne("SELECT COUNT(*) FROM dm_applications WHERE dm_source_id=?",
			array($this->getParam('sourceId'))
		);

		// If script used redirect and show error
		if ($dsCount > 0)
			throw new Exception(_("Selected source is used by application(s) and cannot be removed"));

		$source->delete();

		$this->response->success();
	}

	public function xListSourcesAction()
	{
		$this->request->defineParams(array(
			'sort' => array('type' => 'json'),
			'sourceId' => array('type' => 'int'),
		));

		$sql = "SELECT * from dm_sources WHERE env_id = '{$this->getEnvironmentId()}'";

		if ($this->getParam('sourceId'))
			$sql .= ' AND id = '.$this->db->qstr($this->getParam('sourceId'));

		$response = $this->buildResponseFromSql($sql, array("type", "url"));

		$this->response->data($response);
	}
}
