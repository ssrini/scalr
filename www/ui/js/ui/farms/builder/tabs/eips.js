Scalr.regPage('Scalr.ui.farms.builder.tabs.eips', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Elastic IPs',

		isEnabled: function (record) {
			return record.get('platform') == 'ec2';
		},

		getDefaultValues: function (record) {
			return {
				'aws.use_elastic_ips': 0
			};
		},

		showTab: function (record) {
			var settings = record.get('settings');

			if (settings['aws.use_elastic_ips'] == 1) {
				this.down('[name="aws.use_elastic_ips"]').setValue(true);
			} else {
				this.down('[name="aws.use_elastic_ips"]').setValue(false);
			}
		},

		hideTab: function (record) {
			var settings = record.get('settings');
			settings['aws.use_elastic_ips'] = this.down('[name="aws.use_elastic_ips"]').getValue() ? 1 : 0;
			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			items: {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'checkbox',
					name: 'aws.use_elastic_ips',
					boxLabel: 'Use Elastic IPs'
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						afterrender: function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html:
									'If this option is enabled, ' +
									'Scalr will assign Elastic IPs to all instances of this role. It usually takes few minutes for IP to assign. ' +
									'The amount of allocated IPs increases when new instances start, ' +
									'but not decreases when instances terminated. ' +
									'Elastic IPs are assigned after instance initialization. ' +
									'This operation takes few minutes to complete. During this time instance is not available from ' +
									'The outside and not included in application DNS zone.'
							});
						}
					}
				}]
			}
		}]
	});
});
