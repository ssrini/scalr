Scalr.regPage('Scalr.ui.farms.builder.tabs.euca', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Placement and Type',

		isEnabled: function (record) {
			return record.get('platform') == 'eucalyptus';
		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			if (this.cacheExist(['availabilityZonesEuca', cloudLocation]))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/platforms/eucalyptus/xGetAvailZones',
					params: {
						cloudLocation: cloudLocation
					},
					scope: this,
					success: function (response) {
						response.data.unshift({ id: '', name: 'Default' });
						this.cacheSet(response.data, ['availabilityZonesEuca', cloudLocation]);
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			var settings = record.get('settings');

			if (record.get('arch') == 'i386') {
				this.down('[name="euca.instance_type"]').store.load({ data: ['m1.small', 'c1.medium'] });
				this.down('[name="euca.instance_type"]').setValue(settings['euca.instance_type'] || 'm1.small');
			} else {
				this.down('[name="euca.instance_type"]').store.load({ data: ['m1.large', 'm1.xlarge', 'c1.xlarge'] });
				this.down('[name="euca.instance_type"]').setValue(settings['euca.instance_type'] || 'm1.large');
			}

			this.down('[name="euca.availability_zone"]').store.load({ data: this.cacheGet(['availabilityZonesEuca', record.get('cloud_location')]) });
			this.down('[name="euca.availability_zone"]').setValue(settings['euca.availability_zone'] || '');
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['euca.instance_type'] = this.down('[name="euca.instance_type"]').getValue();
			settings['euca.availability_zone'] = this.down('[name="euca.availability_zone"]').getValue();

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			defaults: {
				fieldLabel: 200,
				width: 400
			},
			items: [{
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				fieldLabel: 'Placement',
				valueField: 'id',
				displayField: 'name',
				editable: false,
				queryMode: 'local',
				name: 'euca.availability_zone'
			},{
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				valueField: 'name',
				displayField: 'name',
				fieldLabel: 'Instances type',
				editable: false,
				queryMode: 'local',
				name: 'euca.instance_type'
			}]
		}]
	});
});
