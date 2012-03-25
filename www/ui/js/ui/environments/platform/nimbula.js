Scalr.regPage('Scalr.ui.environments.platform.nimbula', function (loadParams, moduleParams) {
	var params = moduleParams['params'];

	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		scalrOptions: {
			'modal': true
		},
		width: 600,
		title: 'Environments &raquo; ' + moduleParams.env.name + '&raquo; Nimbula',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side',
			labelWidth: 120
		},

		items: [{
			xtype: 'checkbox',
			name: 'nimbula.is_enabled',
			checked: params['nimbula.is_enabled'],
			hideLabel: true,
			boxLabel: 'I want to use Nimbula',
			listeners: {
				'change': function () {
					if (this.getValue()) {
						form.down('[name="nimbula.username"]').show();
						form.down('[name="nimbula.api_url"]').show();
						form.down('[name="nimbula.password"]').show();
					} else {
						form.down('[name="nimbula.username"]').hide();
						form.down('[name="nimbula.api_url"]').hide();
						form.down('[name="nimbula.password"]').hide();
					}
				}
			}
		}, {
			xtype: 'textfield',
			fieldLabel: 'Username',
			name: 'nimbula.username',
			value: params['nimbula.username'],
			hidden: !params['nimbula.is_enabled']
		}, {
			xtype: 'textfield',
			fieldLabel: 'Password',
			name: 'nimbula.password',
			value: params['nimbula.password'],
			hidden: !params['nimbula.is_enabled']
		}, {
			xtype: 'textfield',
			fieldLabel: 'API URL',
			name: 'nimbula.api_url',
			value: params['nimbula.api_url'],
			hidden: !params['nimbula.is_enabled']
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
							url: '/environments/' + moduleParams.env.id + '/platform/xSaveNimbula',
							success: function (data) {
								var flag = Scalr.flags.needEnvConfig && data.enabled;
								Scalr.event.fireEvent('update', '/environments/' + moduleParams.env.id + '/edit', 'nimbula', data.enabled);
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
				width: 280,
				hidden: !Scalr.flags.needEnvConfig,
				margin: {
					left: 5
				},
				text: "I'm not using Nimbula, let me configure another cloud",
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
