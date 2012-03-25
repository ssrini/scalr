Scalr.regPage('Scalr.ui.environments.edit', function (loadParams, moduleParams) {
	var environment = moduleParams['environment'], params = environment['params'];

	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 700,
		title: 'Environments &raquo; Edit &raquo; ' + environment.name,
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
			xtype: 'fieldset',
			hidden: Scalr.flags.needEnvConfig,
			labelWidth: 100,
			title: 'Date & Time settings',
			items: [{
				xtype: 'combo',
				fieldLabel: 'Timezone',
				store: moduleParams.timezones,
				allowBlank: false,
				editable: true,
				name: 'timezone',
				value: params['timezone'],
				queryMode: 'local'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Platforms',
			itemId: 'platforms'
		}]
	});

	if (! Scalr.flags.needEnvConfig)
		form.addDocked({
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
							url: '/environments/xSave/',
							params: {
								envId: environment.id
							},
							success: function () {
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
				text: 'Cancel',
				handler: function() {
					Scalr.event.fireEvent('close');
				}
			}]
		});

	for (var i in moduleParams['platforms']) {
		var disabled = environment['enabledPlatforms'].indexOf(i) == -1;
		form.down('#platforms').add({
			xtype: 'button',
			style: 'padding: 10px; margin: 10px;padding-top: 14px;padding-bottom: 14px',
			text: moduleParams['platforms'][i],
			platform: i,
			cls: 'scalr-btn-icon-ultra-large',
			scale: 'large',
			icon: '/ui/images/icons/platform/' + i + (disabled ? '_disabled' : '') + '_64x64.png',
			iconAlign: 'top',
			handler: function () {
				Scalr.event.fireEvent('redirect', '#/environments/' + environment.id + '/platform/' + this.platform, false, true);
			}
		});
	};

	Scalr.event.on('update', function (type, platform, enabled) {
		if (type == '/environments/' + environment.id + '/edit') {
			var b = form.down('#platforms').down('[platform="' + platform + '"]');
			if (b)
				b.setIcon('/ui/images/icons/platform/' + platform + (enabled ? '' : '_disabled') + '_64x64.png');

		}
	}, form);

	return form;
});
