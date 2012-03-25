<?php
class Scalr_UI_Controller_Dashboard_Widget_Lasterrors extends Scalr_UI_Controller_Dashboard_Widget
{
	public function getDefinition()
	{
		return array(
			'type' => 'local'
		);
	}

	public function getContent($params = array())
	{
		$sql = 'SELECT message, time FROM logentries WHERE severity = 4 GROUP BY message, source ORDER BY message limit 0, 10';
		$results = $this->db->getAll($sql);
		foreach ($results as &$value) {
			$value['time'] = date('H:i:s, M d',$value["time"]);
		}
		return $results;
	}
}
