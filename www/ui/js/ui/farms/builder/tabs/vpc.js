Scalr.regPage('Scalr.ui.farms.builder.tabs.vpc', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'VPC settings',

		isEnabled: function (record) {
			return record.get('platform') == 'ec2' && !record.get('behaviors').match("cf_");
		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			if (this.cacheExist(['subnets', cloudLocation]))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/tools/aws/vpc/subnets/xListViewSubnets',
					params: {
						cloudLocation: cloudLocation
					},
					scope: this,
					success: function (response) {
						response.data.unshift({ id: '' });
						this.cacheSet(response.data, ['subnets', cloudLocation]);
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="aws.vpc.subnetId"]').store.load({ data: this.cacheGet(['subnets', record.get('cloud_location')]) });
			this.down('[name="aws.vpc.subnetId"]').setValue(settings['aws.vpc.subnetId'] || '');
			this.down('[name="aws.vpc.privateIpAddress"]').setValue(settings['aws.vpc.privateIpAddress'] || '');
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['aws.vpc.subnetId'] = this.down('[name="aws.vpc.subnetId"]').getValue();
			settings['aws.vpc.privateIpAddress'] = this.down('[name="aws.vpc.privateIpAddress"]').getValue();

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			defaults: {
				labelWidth: 120,
				width: 400
			},
			items: [{
				fieldLabel: 'Private IP Address',
				xtype: 'textfield',
				name: 'aws.vpc.privateIpAddress'
			}, {
				xtype: 'combo',
				name: 'aws.vpc.subnetId',
				fieldLabel: 'VPC Subnet',
				editable: false,
				valueField: 'id',
				displayField: 'id',
				queryMode: 'local',
				store: {
					fields: [ 'id' ],
					proxy: 'object'
				}
			}]
		}]
	});
});
