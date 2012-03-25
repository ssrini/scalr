Scalr.regPage('Scalr.ui.farms.builder.tabs.cloudstack', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Cloudstack settings',

		isEnabled: function (record) {
			return record.get('platform') == 'cloudstack';
		},

		getDefaultValues: function (record) {
			return {
				'cloudstack.service_offering_id': '',
				'cloudstack.network_id': ''
			};
		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			if (this.cacheExist('serviceOfferings'))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/platforms/cloudstack/xGetOfferingsList/',
					scope: this,
					params: {
						cloudLocation: cloudLocation
					},
					success: function (response) {
						this.cacheSet(response.data['serviceOfferings'], 'cloudtsack.serviceOfferings');
						this.cacheSet(response.data['networks'], 'cloudtsack.networks');
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},


		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="cloudstack.service_offering_id"]').store.load({ data: this.cacheGet('cloudtsack.serviceOfferings') });
			this.down('[name="cloudstack.service_offering_id"]').setValue(parseInt(settings['cloudstack.service_offering_id']));
			
			this.down('[name="cloudstack.network_id"]').store.load({ data: this.cacheGet('cloudtsack.networks') });
			this.down('[name="cloudstack.network_id"]').setValue(parseInt(settings['cloudstack.network_id']));
		},

		hideTab: function (record) {
			var settings = record.get('settings');
			settings['cloudstack.service_offering_id'] = this.down('[name="cloudstack.service_offering_id"]').getValue();			
			settings['cloudstack.network_id'] = this.down('[name="cloudstack.network_id"]').getValue();
			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			defaults: {
				labelWidth: 150,
				width: 500
			},
			items: [{
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				fieldLabel: 'Service offering',
				editable: false,
				queryMode: 'local',
				name: 'cloudstack.service_offering_id'
			}, {
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				fieldLabel: 'Network',
				editable: false,
				queryMode: 'local',
				name: 'cloudstack.network_id'
			}]
		}]
	});
});
