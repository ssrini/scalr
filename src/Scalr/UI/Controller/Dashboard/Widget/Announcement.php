<?php
class Scalr_UI_Controller_Dashboard_Widget_Announcement extends Scalr_UI_Controller_Dashboard_Widget
{
	public function getDefinition()
	{
		return array(
			'type' => 'local'
		);
	}

	public function getContent($params = array())
	{
		$rssCachePath = CACHEPATH."/rss.announcement.cxml";
		$data = array();
		if (file_exists($rssCachePath) && (time() - filemtime($rssCachePath) < 86400)) {
			clearstatcache();
			$time = filemtime($rssCachePath);
			$data = json_decode(file_get_contents($rssCachePath));
		} else {
			$feedUrl = 'http://feeds.feedburner.com/scalr/qmzj';
			$feedContent = "";
			$curl = curl_init();
			curl_setopt($curl, CURLOPT_URL, $feedUrl);
			curl_setopt($curl, CURLOPT_TIMEOUT, 10);
			curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
			curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);

			$feedContent = curl_exec($curl);
			curl_close($curl);
			if($feedContent && !empty($feedContent)) {
				$feedXml = simplexml_load_string($feedContent);
				if($feedXml) {
					$i = 0;
					foreach ($feedXml->channel->item as $key=>$item) {
						if($i < 5)
							$data[] = array(
								'text' =>  "".$item->title,
								'url'  =>  "".$item->guid,
								'time'  =>  "".date('M d',strtotime($item->pubDate))
							);
						$i++;
					}
				}
			}
			file_put_contents($rssCachePath, json_encode($data));
		}

		return $data;
	}

	public function getContentAction()
	{
		$this->response->data(array(
			'widgetContent' => $this->getContent()
		));
	}
}
