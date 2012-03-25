<?php
class Scalr_UI_Controller_Dashboard extends Scalr_UI_Controller
{
	public function hasAccess()
	{
		return true;
	}

	public function defaultAction()
	{
		if ($this->getParam('beta')) {
			$loadJs = array('ui/dashboard/columns.js');

			/*if (! $this->user->getSetting(Scalr_Account_User::SETTING_DATE_DASHBOARD_CONFIGURED)) {
				$defaultPanel = '';
				$this->user->setData(Scalr_Account_User::DATA_DASHBOARD, $this->getEnvironmentId(), $defaultPanel);
				$this->user->setSetting(Scalr_Account_User::SETTING_DATE_DASHBOARD_CONFIGURED, time());
			}*/

			$panel = $this->user->getDashboard($this->getEnvironmentId());
			if (! is_array($panel))
				$panel = array();

			for ($i = 0; $i < count($panel); $i++) {
				if (is_array($panel[$i]) && is_array($panel[$i]['widgets'])) {
					$widgets = $panel[$i]['widgets'];
					for ($j = 0; $j < count($widgets); $j++) {
						if (isset($widgets[$j]['name'])) {
							$name = str_replace('dashboard.', '', $widgets[$j]['name']);
							try {
								$widget = Scalr_UI_Controller::loadController($name, 'Scalr_UI_Controller_Dashboard_Widget');
							} catch (Exception $e) {
								continue;
							}

							$info = $widget->getDefinition();

							if ($info['js'])
								$loadJs[] = $info['js'];

							if ($info['type'] == 'local')
								$panel[$i]['widgets'][$j]['widgetContent'] = $widget->getContent($widgets[$j]['params']);
						}
					}
				}
			}

            $this->response->page('ui/dashboard/view.js',
	            array(
		            'panel' => $panel
	            ),
	            $loadJs,
	            array('ui/dashboard/view.css')
            );
        }
		else if ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN)
			$this->response->page('ui/dashboard_admin.js');
		else
			$this->response->page('ui/dashboard.js', array('envId'=>$this->getEnvironmentId()), array(), array('ui/dashboard/dashboard.css'));
	}

    public function xSavePanelAction()
    {
	    $this->request->defineParams(array(
		   'panel' => array('type' => 'json')
	    ));

	    $this->user->setDashboard($this->getEnvironmentId(), $this->getParam('panel'));
        $this->response->data(array(
	        'success' => true,
	        'data' => $this->getParam('panel')
        ));
    }

	public function xUpdatePanelAction () {
		$this->request->defineParams(array(
			'widget' => array('type' => 'json')
		));

		$panel = $this->user->getDashboard($this->getEnvironmentId());

		if (! is_array($panel))
			$panel = array();

		if (is_array($panel[0]) && is_array($panel[0]['widgets'])) {
			$panel[0]['widgets'][count($panel[0]['widgets'])] = $this->getParam('widget');
		} else {
			$panel[0]['widgets'][0] = $this->getParam('widget');
		}
		$this->user->setDashboard($this->getEnvironmentId(), $panel);

		$this->response->success('New widget successfully added to dashboard');
		$this->response->data(array('panel' => $panel));
	}

	public function checkLifeCycle($widgets)
	{
		$result = array();

		foreach ($widgets as $id => $object) {
			$name = str_replace('dashboard.', '', $object['name']);

			try {
				$widget = Scalr_UI_Controller::loadController($name, 'Scalr_UI_Controller_Dashboard_Widget');
			} catch (Exception $e) {
				continue;
			}

			$result[$id] = $widget->getContent($object['params']);
		}

		return $result;
	}

	public function widgetAccountInfoAction()
	{
		require_once(APPPATH."/src/externals/chargify-client/class.ChargifyConnector.php");
		require_once(APPPATH."/src/externals/chargify-client/class.ChargifyCreditCard.php");
		require_once(APPPATH."/src/externals/chargify-client/class.ChargifyCustomer.php");
		require_once(APPPATH."/src/externals/chargify-client/class.ChargifyProduct.php");
		require_once(APPPATH."/src/externals/chargify-client/class.ChargifySubscription.php");

		$js_module = array();

		$clientId = $this->user->getAccountId();
		if ($clientId == 0) {
			array_push($js_module, array(
				'xtype' => 'displayfield',
				'fieldLabel' => 'Logged in as',
				'value' => 'SCALR ADMIN'
			));
		}
		else {
			$client = Client::Load($clientId);

			array_push($js_module, array(
				'xtype' => 'displayfield',
				'fieldLabel' => 'Logged in as',
				'value' => $client->Email
			));

			// PayPal users: users without Chargify Client ID property
			if (!$client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_CID)) {
				// Billing type
				array_push($js_module, array(
					'xtype' => 'displayfield',
					'fieldLabel' => 'Billing type',
					'value' => 'PayPal (Legacy)'
				));

				$sid = $this->db->GetOne("SELECT subscriptionid FROM subscriptions WHERE clientid=? AND status='Active'", array(
					$clientId
				));
				if ($sid) {
					array_push($js_module, array(
						'xtype' => 'displayfield',
						'fieldLabel' => 'Subscription ID',
						'value' => $sid
					));
				}

				$t = strtotime($client->DueDate);
				if ($t) {
					array_push($js_module, array(
						'xtype' => 'displayfield',
						'fieldLabel' => 'Due date',
						'value' => date("M j Y", $t)
					));
				}

				if ($this->db->GetOne("SELECT amount FROM payments WHERE clientid = ?", array($clientId)) == 50)
					$package = 'Beta-legacy';
				else
					$package = 'Production';

				if ($client->GetSettingValue(CLIENT_SETTINGS::BILLING_PACKAGE) == '3')
					$package = 'Mission Critical';

				array_push($js_module, array(
					'xtype' => 'displayfield',
					'fieldLabel' => 'Plan',
					'value' => $package
				));
			}
			else {
				if (!$client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_SID))
				{
					array_push($js_module, array(
						'xtype' => 'displayfield',
						'fieldLabel' => 'Plan',
						'value' => 'Development'
					));
				}
				else
				{
					$c = new ChargifyConnector();

					try
					{
						$subs = $c->getCustomerSubscription($client->GetSettingValue(CLIENT_SETTINGS::BILLING_CGF_SID));

						$color = (ucfirst($subs->getState()) != 'Active') ? 'red' : 'green';
						array_push($js_module, array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Status',
							'value' => "<span style='color:{$color}'>".ucfirst($subs->getState())."</span>"
						));

						array_push($js_module, array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Billing type',
							'value' => ucfirst($subs->getCreditCard()->getCardType()) . " (" . $subs->getCreditCard()->getMaskedCardNumber() . ")"
						));

						array_push($js_module, array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Plan',
							'value' => ucfirst($subs->getProduct()->getHandle())
						));

						array_push($js_module, array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Due date',
							'value' => date("M j Y", strtotime($subs->next_assessment_at))
						));

						array_push($js_module, array(
							'xtype' => 'displayfield',
							'fieldLabel' => 'Balance',
							'value' => "$".number_format($subs->getBalanceInCents()/100, 2)
						));
					}
					catch(Exception $e) {
						array_push($js_module, array(
							'xtype' => 'displayfield',
							'hideLabel' => true,
							'value' => "<span style='color:red;'>Billing information is not available at the moment</span>"
						));
					}
				}
			}
		}

		$this->response->data(array(
			'module' => $js_module
		));
	}
}
