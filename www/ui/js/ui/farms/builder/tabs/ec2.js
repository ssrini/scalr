Scalr.regPage('Scalr.ui.farms.builder.tabs.ec2', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'EC2 options',

		isEnabled: function (record) {
			return record.get('platform') == 'ec2';
		},

		getDefaultValues: function (record) {
			return {
				'aws.additional_security_groups': "",
				'aws.aki_id' : "",
				'aws.ari_id' : "",
				'aws.cluster_pg': ""
			};
		},

		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="aws.additional_security_groups"]').setValue(settings['aws.additional_security_groups']);
			this.down('[name="aws.aki_id"]').setValue(settings['aws.aki_id']);
			this.down('[name="aws.ari_id"]').setValue(settings['aws.ari_id']);
			this.down('[name="aws.cluster_pg"]').setValue(settings['aws.cluster_pg']);
		},

		hideTab: function (record) {
			var settings = record.get('settings');
			settings['aws.additional_security_groups'] = this.down('[name="aws.additional_security_groups"]').getValue();
			settings['aws.aki_id'] = this.down('[name="aws.aki_id"]').getValue();
			settings['aws.ari_id'] = this.down('[name="aws.ari_id"]').getValue();
			settings['aws.cluster_pg'] = this.down('[name="aws.cluster_pg"]').getValue();
			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			items: [{
				xtype: 'textfield',
				anchor: '100%',
				labelWidth: 200,
				fieldLabel: 'Security groups (comma separated)',
				name: 'aws.additional_security_groups'
			}, {
				xtype: 'textfield',
				anchor: '100%',
				labelWidth: 200,
				fieldLabel: 'AKI id',
				name: 'aws.aki_id'
			}, {
				xtype: 'textfield',
				anchor: '100%',
				labelWidth: 200,
				fieldLabel: 'ARI id',
				name: 'aws.ari_id'
			}, {
				xtype: 'textfield',
				anchor: '100%',
				labelWidth: 200,
				fieldLabel: 'Cluster placement group',
				name: 'aws.cluster_pg'
			}]
		}]
	});
});
