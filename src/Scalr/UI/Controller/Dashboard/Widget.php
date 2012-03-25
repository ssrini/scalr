<?php
class Scalr_UI_Controller_Dashboard_Widget extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		return true;
	}

	public function getContent($params = array())
	{
		return array();
	}

	public function getContentAction()
	{
		$this->response->data(array(
			'widgetContent' => $this->getContent()
		));
	}
}
