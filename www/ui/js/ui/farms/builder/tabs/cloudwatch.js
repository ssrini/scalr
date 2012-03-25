Scalr.regPage('Scalr.ui.farms.builder.tabs.cloudwatch', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'CloudWatch',

		isEnabled: function (record) {
			return record.get('platform') == 'ec2' && !record.get('behaviors').match("cf_");
		},

		getDefaultValues: function (record) {
			return {
				'aws.enable_cw_monitoring': 0
			};
		},

		showTab: function (record) {
			var settings = record.get('settings');

			if (settings['aws.enable_cw_monitoring'] == 1)
				this.down('[name="aws.enable_cw_monitoring"]').setValue(true);
			else
				this.down('[name="aws.enable_cw_monitoring"]').setValue(false);
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['aws.enable_cw_monitoring'] = this.down('[name="aws.enable_cw_monitoring"]').getValue() ? 1 : 0;

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			items: [{
				xtype: 'checkbox',
				boxLabel: 'Enable Detailed <a href="http://aws.amazon.com/cloudwatch/" target="_blank">CloudWatch</a> monitoring for instances of this role (1 min interval)',
				name: 'aws.enable_cw_monitoring'
			}]
		}]
	});
});
