Scalr.regPage('Scalr.ui.environments.platform.rackspace', function (loadParams, moduleParams) {
	var params = moduleParams['params'];

	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		scalrOptions: {
			'modal': true
		},
		width: 600,
		title: 'Environments &raquo; ' + moduleParams.env.name + '&raquo; Rackspace',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
			xtype: 'fieldset',
			title: 'Enable US cloud location (Datacenter: ORD)',
			collapsed: !params['rs-ORD1'],
			checkboxName: 'rackspace.is_enabled.rs-ORD1',
			checkboxToggle: true,
			labelWidth: 80,
			items: [{
				xtype: 'textfield',
				fieldLabel: 'Username',
				name: 'rackspace.username.rs-ORD1',
				value: (params['rs-ORD1']) ? params['rs-ORD1']['rackspace.username'] : ''
			}, {
				xtype: 'textfield',
				fieldLabel: 'API Key',
				name: 'rackspace.api_key.rs-ORD1',
				value: (params['rs-ORD1']) ? params['rs-ORD1']['rackspace.api_key'] : ''
			}, {
				xtype: 'checkbox',
				name: 'rackspace.is_managed.rs-ORD1',
				checked: (params['rs-ORD1'] && params['rs-ORD1']['rackspace.is_managed']) ? true : false,
				hideLabel: true,
				boxLabel: 'Check this checkbox if your account is managed'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Enable UK cloud location (Datacenter: LON)',
			collapsed: !params['rs-LONx'],
			checkboxName: 'rackspace.is_enabled.rs-LONx',
			checkboxToggle: true,
			labelWidth: 80,
			items: [{
				xtype: 'textfield',
				fieldLabel: 'Username',
				name: 'rackspace.username.rs-LONx',
				value: (params['rs-LONx']) ? params['rs-LONx']['rackspace.username'] : ''
			}, {
				xtype: 'textfield',
				fieldLabel: 'API Key',
				name: 'rackspace.api_key.rs-LONx',
				value: (params['rs-LONx']) ? params['rs-LONx']['rackspace.api_key'] : ''
			}, {
				xtype: 'checkbox',
				name: 'rackspace.is_managed.rs-LONx',
				checked: (params['rs-LONx'] && params['rs-LONx']['rackspace.is_managed']) ? true : false,
				hideLabel: true,
				boxLabel: 'Check this checkbox if your account is managed'
			}]
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
							url: '/environments/' + moduleParams.env.id + '/platform/xSaveRackspace',
							success: function (data) {
								var flag = Scalr.flags.needEnvConfig && data.enabled;
								Scalr.event.fireEvent('update', '/environments/' + moduleParams.env.id + '/edit', 'rackspace', data.enabled);
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
				text: "I'm not using Rackspace, let me configure another cloud",
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
