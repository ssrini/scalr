Scalr.regPage('Scalr.ui.farms.builder.tabs.dns', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'DNS',

		isEnabled: function (record) {
			return record.get('platform') != 'rds';
		},

		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="dns.exclude_role"]').setValue((settings['dns.exclude_role'] == 1) ? true : false);
			this.down('[name="dns.int_record_alias"]').setValue(settings['dns.int_record_alias'] || '');
			this.down('[name="dns.ext_record_alias"]').setValue(settings['dns.ext_record_alias'] || '');
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['dns.exclude_role'] = this.down('[name="dns.exclude_role"]').getValue() ? 1 : 0;
			settings['dns.int_record_alias'] = this.down('[name="dns.int_record_alias"]').getValue();
			settings['dns.ext_record_alias'] = this.down('[name="dns.ext_record_alias"]').getValue();

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			items: {
				xtype: 'checkbox',
				name: 'dns.exclude_role',
				boxLabel: 'Exclude role from DNS zone'
			}
		}, {
			xtype: 'fieldset',
			items: [{
				cls: 'scalr-ui-form-field-warning',
				border: false,
				html: 'Will affect only new records. Old ones WILL REMAIN the same.',
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'displayfield',
					value: 'Create'
				}, {
					xtype: 'textfield',
					name: 'dns.int_record_alias',
					margin: {
						left: 3
					},
					hideLabel: true
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'records instead of <b>int-%rolename%</b> ones'
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'displayfield',
					value: 'Create'
				}, {
					xtype: 'textfield',
					name: 'dns.ext_record_alias',
					margin: {
						left: 3
					},
					hideLabel: true
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'records instead of <b>ext-%rolename%</b> ones'
				}]
			}]
		}]
	});
});
