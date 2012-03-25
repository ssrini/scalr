Scalr.regPage('Scalr.ui.farms.builder.tabs.timeouts', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Timeouts',

		getDefaultValues: function (record) {
			return {
				'system.timeouts.reboot': 360,
				'system.timeouts.launch': 2400
			};
		},

		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="system.timeouts.reboot"]').setValue(settings['system.timeouts.reboot'] || 360);
			this.down('[name="system.timeouts.launch"]').setValue(settings['system.timeouts.launch'] || 2400);
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['system.timeouts.reboot'] = this.down('[name="system.timeouts.reboot"]').getValue();
			settings['system.timeouts.launch'] = this.down('[name="system.timeouts.launch"]').getValue();

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'displayfield',
					value: "Terminate instance if it will not send 'rebootFinish' event after reboot in"
				}, {
					xtype: 'textfield',
					name: 'system.timeouts.reboot',
					hideLabel: true,
					margin: {
						left: 3
					},
					width: 50
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'seconds.'
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'displayfield',
					value: "Terminate instance if it will not send 'hostUp' or 'hostInit' event after launch in"
				}, {
					xtype: 'textfield',
					name: 'system.timeouts.launch',
					hideLabel: true,
					margin: {
						left: 3
					},
					width: 50
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'seconds.'
				}]
			}]
		}]
	});
});
