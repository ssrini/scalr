Scalr.regPage('Scalr.ui.environments.platform.cloudstack', function (loadParams, moduleParams) {
	var params = moduleParams['params'];

	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		scalrOptions: {
			'modal': true
		},
		width: 600,
		title: 'Environments &raquo; ' + moduleParams.env.name + '&raquo; Cloudstack',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side',
			labelWidth: 120
		},

		items: [{
			xtype: 'checkbox',
			name: 'cloudstack.is_enabled',
			checked: params['cloudstack.is_enabled'],
			hideLabel: true,
			boxLabel: 'I want to use Cloudstack',
			listeners: {
				'change': function () {
					if (this.getValue()) {
						form.down('[name="cloudstack.api_key"]').show();
						form.down('[name="cloudstack.api_url"]').show();
						form.down('[name="cloudstack.secret_key"]').show();
					} else {
						form.down('[name="cloudstack.api_key"]').hide();
						form.down('[name="cloudstack.api_url"]').hide();
						form.down('[name="cloudstack.secret_key"]').hide();
					}
				}
			}
		}, {
			xtype: 'textfield',
			fieldLabel: 'API key',
			name: 'cloudstack.api_key',
			value: params['cloudstack.api_key'],
			hidden: !params['cloudstack.is_enabled']
		}, {
			xtype: 'textfield',
			fieldLabel: 'Secret key',
			name: 'cloudstack.secret_key',
			value: params['cloudstack.secret_key'],
			hidden: !params['cloudstack.is_enabled']
		}, {
			xtype: 'textfield',
			fieldLabel: 'API URL',
			name: 'cloudstack.api_url',
			value: params['cloudstack.api_url'],
			hidden: !params['cloudstack.is_enabled']
		}],

		dockedItems: [{
			xtype: 'container',
			dock: 'bottom',
			cls: 'scalr-ui-docked-bottombar',
			layout: {
				type: 'hbox',
				pack: 'center'
			},
			items: [{
				xtype: 'button',
				text: 'Save',
				width: 80,
				handler: function() {
					if (form.getForm().isValid()) {
						Scalr.Request({
							processBox: {
								type: 'save'
							},
							form: form.getForm(),
							url: '/environments/' + moduleParams.env.id + '/platform/xSaveCloudstack',
							success: function (data) {
								var flag = Scalr.flags.needEnvConfig && data.enabled;
								Scalr.event.fireEvent('update', '/environments/' + moduleParams.env.id + '/edit', 'cloudstack', data.enabled);
								if (! flag)
									Scalr.event.fireEvent('close');
							}
						});
					}
				}
			}, {
				xtype: 'button',
				width: 80,
				margin: {
					left: 5
				},
				hidden: Scalr.flags.needEnvConfig,
				text: 'Cancel',
				handler: function() {
					Scalr.event.fireEvent('close');
				}
			}, {
				xtype: 'button',
				width: 300,
				hidden: !Scalr.flags.needEnvConfig,
				margin: {
					left: 5
				},
				text: "I'm not using Cloudstack, let me configure another cloud",
				handler: function () {
					Scalr.event.fireEvent('redirect', '#/environments/' + moduleParams.env.id + '/edit', false, true);
				}
			}, {
				xtype: 'button',
				width: 80,
				hidden: !Scalr.flags.needEnvConfig,
				margin: {
					left: 5
				},
				text: 'Do this later',
				handler: function () {
					sessionStorage.setItem('needEnvConfigLater', true);
					Scalr.event.fireEvent('unlock');
					Scalr.event.fireEvent('redirect', '#/dashboard');
				}
			}]
		}]
	});

	return form;
});
