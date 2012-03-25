<?php

class Scalr_UI_Controller_Guest extends Scalr_UI_Controller
{	
	public function logoutAction()
	{
		Scalr_Session::destroy();
		$this->response->setRedirect('/');
	}

	public function hasAccess()
	{
		return true;
	}
	
	public function xInitAction()
	{
		$initParams = array();
		
		if ($this->user) {
			require_once (dirname(__FILE__) . "/../../../class.XmlMenu.php");
			$Menu = new XmlMenu();
			
			if ($this->user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN) {
    			$Menu->LoadFromFile(dirname(__FILE__)."/../../../../etc/admin_nav.xml");
			} else {
				$Menu->LoadFromFile(dirname(__FILE__)."/../../../../etc/client_nav4.xml");

				// get XML document to add new children as farms names
		    	$clientMenu = $Menu->GetXml();
		
				foreach ($this->db->GetAll("SELECT name, id  FROM farms WHERE env_id=? ORDER BY `name` ASC",
					array($this->getEnvironmentId())) as $row)
				{
				    $farm_info[] = array(
				    	'name' =>$row['name'],
				    	'id' => $row['id']
				    );
				}
		
				// creates a list of farms for server farms in main menu
				$nodeServerFarms = $clientMenu->xpath("//node[@id='server_farms']");
		
				if(count($farm_info) > 0)
					$nodeServerFarms[0]->addChild('separator');
		
				foreach($farm_info as $farm_row) {
					$farmList = $nodeServerFarms[0]->addChild('node');
					$farmList->addAttribute('title', $farm_row['name']);
		
					$itemFarm = $farmList->addChild('item','Manage');
						$itemFarm->addAttribute('href', "#/farms/{$farm_row['id']}/view");
					$itemFarm = $farmList->addChild('item','Edit');
						$itemFarm->addAttribute('href', "#/farms/{$farm_row['id']}/edit");
					$itemFarm = $farmList->addChild('separator');
					$itemFarm = $farmList->addChild('item',"Roles");
						$itemFarm->addAttribute('href', "#/farms/{$farm_row['id']}/roles");
					$itemFarm = $farmList->addChild('item',"Servers");
						$itemFarm->addAttribute('href', "#/farms/{$farm_row['id']}/servers");
					$itemFarm = $farmList->addChild('item', "DNS zones");
						$itemFarm->addAttribute('href', "#/farms/{$farm_row['id']}/dnszones");
		
					$itemFarm = $farmList->addChild('item',"Apache vhosts");
					$itemFarm->addAttribute('href', "#/farms/{$farm_row['id']}/vhosts");
			    }
		    }
		    $initParams['menu'] = $Menu->GetExtJSMenuItems();
			
			$initParams['user'] = array(
				'userId' => $this->user->getId(),
				'clientId' => $this->user->getAccountId(),
				'userName' => $this->user->getEmail(),
				'envId' => $this->getEnvironment() ? $this->getEnvironmentId() : 0,
				'type' => $this->user->getType()
			);

			$initParams['flags'] = array();

			if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER) {

				if (! $this->user->getAccount()->getSetting(Scalr_Account::SETTING_DATE_ENV_CONFIGURED)) {
					if (count($this->environment->getEnabledPlatforms()) == 0)					
						$initParams['flags']['needEnvConfig'] = Scalr_Environment::init()->loadDefault($this->user->getAccountId())->id;
				}
			}
		} else {
			$locationHash = $this->getParam('locationHash');
			$locationChunks = explode("/", trim($locationHash, "#"));
			
			switch($locationChunks[1]) {
				case "confirmPasswordReset":
					
					$user = Scalr_Account_User::init()->loadBySetting(Scalr_Account::SETTING_OWNER_PWD_RESET_HASH, $locationChunks[2]);
					if ($user) {
						$initParams['initWindow'] = 'newPasswordForm';
						$initParams['initWindowParams'] = array('confirmHash' => $locationChunks[2]);
					} else {
						$initParams['errorMessage'] = "Invalid confirmation link";
					}
					break;
			}
		}

		$version = 'extjs-4.0';
		$initParams['extjs'] = array(
			$this->getModuleName("init.js"),
			$this->getModuleName("theme.js"),
			$this->getModuleName("override.js"),
			$this->getModuleName("utils.js"),
			$this->getModuleName("ui-plugins.js"),
			$this->getModuleName("ui.js"),
			$this->getModuleName("highlightjs/highlight.pack.js")
		);

		$initParams['css'] = array(
			$this->getModuleName("theme.css"),
			$this->getModuleName("ui.css"),
			$this->getModuleName("utils.css"),
			$this->getModuleName("highlightjs/styles/solarized_light.css")
		);

		if ($this->user) {
			$t1 = array('text' => $initParams['user']['userName'], 'iconCls' => 'scalr-menu-icon-login', 'menu' => array(
				array('href' => '#/core/api', 'text' => 'API access', 'iconCls' => 'scalr-menu-icon-api'),
				array('xtype' => 'menuseparator'),
				array('href' => '#/core/profile', 'text' => 'Profile', 'iconCls' => 'scalr-menu-icon-profile'),
				array('href' => '#/core/settings', 'text' => 'Settings', 'iconCls' => 'scalr-menu-icon-settings')
			));

			$t1['menu'][] = array('xtype' => 'menuseparator');
			$t1['menu'][] = array('href' => '/guest/logout', 'text' => 'Logout', 'iconCls' => 'scalr-menu-icon-logout');
			$initParams['menu'][] = '->';
			$initParams['menu'][] = $t1;
			
			if ($this->getEnvironment()) {
				$envs = array();
				foreach($this->user->getEnvironments() as $env) {
					$envs[] = array(
						'text' => $env['name'],
						'checked' => $env['id'] == $this->getEnvironmentId(),
						'group' => 'environment',
						'envId' => $env['id']
					);
				}

				if ($this->user->getType() == Scalr_Account_User::TYPE_ACCOUNT_OWNER) {
					$envs[] = array('xtype' => 'menuseparator');
					$envs[] = array('href' => '#/environments/view', 'text' => 'Manage');
					$envs[] = array('href' => '#/environments/create', 'text' => 'Add new');
				}
				
				$initParams['menu'][] = array(
					'iconCls' => 'scalr-menu-icon-environment',
					'text' => $this->getEnvironment()->name,
					'menu' => $envs,
					'environment' => 'true',
					'tooltip' => 'Environment'
				);
				
				$initParams['farms'] = $this->db->getAll('SELECT * FROM farms WHERE env_id = ?', array($this->getEnvironmentId()));
			}
			
			$m = array();
			
			$m[] = array('href' => '#/account/teams/view', 'text' => 'Teams');
			$m[] = array('href' => '#/account/users/view', 'text' => 'Users');

			$initParams['menu'][] = array(
				'iconCls' => 'scalr-menu-icon-account',
				'tooltip' => 'Accounting',
				'menu' => $m
			);
			
			$initParams['menu'][] = array(
				'iconCls' => 'scalr-menu-icon-help',
				'tooltip' => 'Help',
				'menu' => array(
					array('href' => 'http://wiki.scalr.net', 'text' => 'Wiki'),
					array('href' => 'http://groups.google.com/group/scalr-discuss', 'text' => 'Support')
				)
			);

		}
		
		$this->response->data(array('initParams' => $initParams));
	}
	
	public function xConfirmPasswordResetAction()
	{
		$user = Scalr_Account_User::init()->loadBySetting(Scalr_Account::SETTING_OWNER_PWD_RESET_HASH, $this->getParam('confirmHash'));
		
		if ($user) {
			$password = $this->getParam('password');
			$user->updatePassword($password);
			$user->save();
			
			$user->setSetting(Scalr_Account::SETTING_OWNER_PWD_RESET_HASH, "");

			//Scalr_Session::create($user->getAccountId(), $user->getId(), $user->getType());

			$this->response->success("Password has been reset. Please log in.");
		} else {
			$this->response->failure("Incorrect confirmation link");
		}
	}
	
	public function xResetPasswordAction()
	{
		global $Mailer; //FIXME:

		$user = Scalr_Account_User::init()->loadByEmail($this->getParam('email'));
		
		if ($user) {
			$hash = $this->getCrypto()->sault(10);
			
			$user->setSetting(Scalr_Account::SETTING_OWNER_PWD_RESET_HASH, $hash);
			
			$clientinfo = array(
				'email' => $user->getEmail(),
				'fullname'	=> $user->fullname
			);

			// Send welcome E-mail
			$Mailer->ClearAddresses();
			$res = $Mailer->Send("emails/password_change_confirm.eml",
				array("client" => $clientinfo, "pwd_link" => "https://{$_SERVER['HTTP_HOST']}/#/confirmPasswordReset/{$hash}"),
				$clientinfo['email'],
				$clientinfo['fullname']
			);

			$this->response->success("Confirmation email has been sent to you");
		}
		else
			$this->response->failure("Specified e-mail not found in our database");
	}

	private function loginUserGet()
	{
		$this->request->defineParams(array(
			'scalrLogin', 'scalrPass'
		));

		if ($this->getParam('scalrLogin') != '' && $this->getParam('scalrPass') != '') {
			try {
				$user = Scalr_Account_User::init()->loadByEmail($this->getParam('scalrLogin'));
				if (!$user)
					throw new Exception("No such user");

				$user->validatePassword($this->getParam('scalrPass'));
				return $user;
			} catch (Exception $e) {}
		}

		throw new Exception('Incorrect login or password');
	}

	private function loginUserCreate($user)
	{
		$user->updateLastLogin();

		Scalr_Session::create($user->getAccountId(), $user->getId(),
			$user->getType() == Scalr_Account_User::TYPE_SCALR_ADMIN ? Scalr_AuthToken::SCALR_ADMIN : Scalr_AuthToken::ACCOUNT_ADMIN 
		);

		if ($this->getParam('scalrKeepSession') == 'on')
			Scalr_Session::keepSession();

		$this->response->data(array('userId' => $user->getId()));
	}

	public function xLoginAction()
	{
		$user = $this->loginUserGet();

		// check for 2-factor auth
		if ($user->getAccountId() && $user->getAccount()->isFeatureEnabled(Scalr_Limits::FEATURE_2FA)) {
			if ($user->getSetting(Scalr_Account_User::SETTING_SECURITY_2FA_GGL) == 1) {
				$this->response->data(array(
					'tfa' => array(
						array(
							'xtype' => 'textfield',
							'fieldLabel' => 'Code',
							'name' => 'tfaCode',
							'allowBlank' => false
						),
						array(
							'xtype' => 'hidden',
							'name' => 'tfaType',
							'value' => 'ggl'
						)
					)
				));
				$this->response->success();
				return;
			}
		}

		$this->loginUserCreate($user);
	}

	public function xLoginVerifyAction()
	{
		$user = $this->loginUserGet();

		if ($user->getAccount()->isFeatureEnabled(Scalr_Limits::FEATURE_2FA)) {
			switch ($this->getParam('tfaType')) {
				case 'ggl':
					if ($user->getSetting(Scalr_Account_User::SETTING_SECURITY_2FA_GGL) == 1) {
						$key = $this->getCrypto()->decrypt($user->getSetting(Scalr_Account_User::SETTING_SECURITY_2FA_GGL_KEY), $this->cryptoKey);

						if ($this->getParam('tfaCode') && Scalr_Util_Google2FA::verifyKey($key, $this->getParam('tfaCode'))) {
							$this->loginUserCreate($user);
						} else {
							$this->response->failure('Invalid code');
						}
					} else {
						$this->response->failure('Two-factor authentication not enabled for this user');
					}
					break;

				default:
					$this->response->failure('Error authentication');
					break;
			}
			
		} else {
			$this->response->failure('Two-factor authentication not enabled for this account');
		}
	}

	public function xNoopAction()
	{
		$this->xPerpetuumMobileAction();
	}

	public function xPerpetuumMobileAction()
	{
		
		$this->request->defineParams(array(
			'updateDashboard' => array('type' => 'json')
		));

		$result = array();

		if ($this->user) {
			if ($this->getParam('updateDashboard'))
				$result['updateDashboard'] = Scalr_UI_Controller::loadController('dashboard')->checkLifeCycle($this->getParam('updateDashboard'));
		}

		$equal = $this->user && ($this->user->getId() == $this->getParam('userId')) &&
			(($this->getEnvironment() ? $this->getEnvironmentId() : 0) == $this->getParam('envId'));

		$result['equal'] = $equal;
		$result['isAuthenticated'] = $this->user ? true : false;

		$this->response->data($result);
	}
}
