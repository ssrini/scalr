<?php
class Scalr_UI_Controller_Services_Rabbitmq extends Scalr_UI_Controller
{
	const REQUEST_TIMEOUT = 600;

	public static function getPermissionDefinitions()
	{
		return array();
	}

	/**
	 * @return DBFarmRole
	 */
	public function getFarmRole()
	{
		$this->request->defineParams(array(
			'farmId' => array('type' => 'int')
		));

		$dbFarm = DBFarm::LoadByID($this->getParam('farmId'));
		$this->user->getPermissions()->validate($dbFarm);

		foreach ($dbFarm->GetFarmRoles() as $dbFarmRole) {
			if ($dbFarmRole->GetRoleObject()->hasBehavior(ROLE_BEHAVIORS::RABBITMQ))
				return $dbFarmRole;
		}

		throw new Exception("Role not found");
	}

	public function statusAction()
	{
		$moduleParams['rabbitmq']['status'] = '';
		$moduleParams['rabbitmq']['showSetup'] = false;
		$moduleParams['rabbitmq']['showStatusLabel'] = true;

		$dbFarmRole = $this->getFarmRole();
		if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_URL)) {
			$serverId = $dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_SERVER_ID);
			try {
				$dbServer = DBServer::LoadByID($serverId);
				if ($dbServer->status == SERVER_STATUS::RUNNING) {
					$moduleParams['rabbitmq']['username'] = 'scalr';
					$moduleParams['rabbitmq']['password'] = $dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_PASSWORD);
					$moduleParams['rabbitmq']['url'] = $dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_URL);

					$url = $dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_URL);
					$url = str_replace('/mgmt/', '/api/overview', $url);

					$httpRequest = new HttpRequest();
					$httpRequest->setUrl($url);
					$httpRequest->setHeaders(array(
						'Authorization' => 'Basic ' . base64_encode(
							$moduleParams['rabbitmq']['username'] .
							':' .
							$moduleParams['rabbitmq']['password']
						)
					));
					$httpRequest->send();
					$data = $httpRequest->getResponseData();
					$result = json_decode($data['body'], true);
					if ($result)
						$moduleParams['rabbitmq']['overview'] = $result;
				} else {
					throw new ServerNotFoundException();
				}
			} catch (ServerNotFoundException $e) {
				$moduleParams['rabbitmq']['status'] = 'Control panel was installed, but server not found';
				$moduleParams['rabbitmq']['showSetup'] = true;
				$dbFarmRole->ClearSettings('rabbitmq.cp');
			} catch (Exception $e) {
				$moduleParams['rabbitmq']['status'] = "Error retrieving information about control panel: \"{$e->getMessage()}\"";
			}
		} else {
			if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUESTED) == '1') {
				if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_ERROR_MSG)) {
					$moduleParams['rabbitmq']['showSetup'] = true;
					$moduleParams['rabbitmq']['status'] = "Server return error: \"{$dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_ERROR_MSG)}\"";
				} else {
					if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUEST_TIME) > (time() - self::REQUEST_TIMEOUT)) {
						$moduleParams['rabbitmq']['status'] = "Request was sent at " . 
							Scalr_Util_DateTime::convertTz((int) $dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUEST_TIME)) .
							". Please wait...";
					} else {
						$moduleParams['rabbitmq']['showSetup'] = true;
						$moduleParams['rabbitmq']['status'] = "Request timeout exceeded. Request was sent at " .
							Scalr_Util_DateTime::convertTz((int) $dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUEST_TIME));
					}
				}
			} else {
				if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_PASSWORD)) {
					$moduleParams['rabbitmq']['showSetup'] = true;
				} else {
					$moduleParams['rabbitmq']['status'] = 'Rabbitmq cluster not initialized yet. Please wait ...';
					$moduleParams['rabbitmq']['showStatusLabel'] = false;
				}
			}
		}

		$moduleParams['farmId'] = $dbFarmRole->FarmID;
		$moduleParams['rabbitmq']['password'] = $dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_PASSWORD);
		$this->response->page('ui/services/rabbitmq/status.js', $moduleParams);
	}

	public function xSetupCpAction()
	{
		$dbFarmRole = $this->getFarmRole();

		if ($dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_URL)) {
			$this->response->failure("CP already installed");
		} else {
			if (($dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUESTED) == '1') &&
				($dbFarmRole->GetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUEST_TIME) > (time() - self::REQUEST_TIMEOUT))
			) {
				$this->response->failure("CP already installing");
			} else {
				$dbServers = $dbFarmRole->GetServersByFilter(array('status' => SERVER_STATUS::RUNNING));
				if (count($dbServers)) {
					// install panel
					$msg = new Scalr_Messaging_Msg_RabbitMq_SetupControlPanel();
					$dbServers[0]->SendMessage($msg);

					$dbFarmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUESTED, 1);
					$dbFarmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_REQUEST_TIME, time());
					$dbFarmRole->SetSetting(Scalr_Role_Behavior_RabbitMQ::ROLE_CP_ERROR_MSG, "");

					$this->response->success("CP installing");
					$this->response->data(array("status" => "Request was sent at " . Scalr_Util_DateTime::convertTz((int) time()) . ". Please wait..."));
				} else {
					$this->response->failure("No running server");
				}
			}
		}
	}
}
