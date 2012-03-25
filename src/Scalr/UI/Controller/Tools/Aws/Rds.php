<?php

class Scalr_UI_Controller_Tools_Aws_Rds extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		$enabledPlatforms = $this->getEnvironment()->getEnabledPlatforms();
		if (!in_array(SERVER_PLATFORMS::EC2, $enabledPlatforms))
			throw new Exception("You need to enable RDS platform for current environment");

		return true;
	}
	
	public function logsAction()
	{
		$this->response->page('ui/tools/aws/rds/logs.js');
	}

	public function xListLogsAction()
	{
		$amazonRDSClient = Scalr_Service_Cloud_Aws::newRds(
		$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::ACCESS_KEY),
		$this->environment->getPlatformConfigValue(Modules_Platforms_Ec2::SECRET_KEY),
		$this->getParam('cloudLocation')
		);
		
		$aws_response = $amazonRDSClient->DescribeEvents($this->getParam('name'), $this->getParam('type'));
		$events = (array)$aws_response->DescribeEventsResult->Events;
		if (!is_array($events['Event']))
			$events['Event'] = array($events['Event']);
		foreach ($events['Event'] as $event) {
			if ($event->Message) {
				$logs[] = array(
						'Message'	=> (string)$event->Message,
						'Date'	=> (string)$event->Date,
						'SourceIdentifier'	=> (string)$event->SourceIdentifier,
						'SourceType'		=> (string)$event->SourceType
				);
			}
		}

		$response = $this->buildResponseFromData($logs, array('Date', 'Message'));
		foreach ($response['data'] as &$row) {
			$row['Date'] = Scalr_Util_DateTime::convertTz($row['Date']);
		}

		$this->response->data($response);
	}
}
