Scalr.regPage('Scalr.ui.farms.builder.tabs.rsplacement', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Placement and type',

		isEnabled: function (record) {
			return record.get('platform') == 'rackspace';
		},

		getDefaultValues: function (record) {
			return {
				'rs.flavor-id': 1
			};
		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			if (this.cacheExist(['flavorsRackspace', cloudLocation]))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/platforms/rackspace/xGetFlavors',
					params: {
						cloudLocation: cloudLocation
					},
					scope: this,
					success: function (response) {
						this.cacheSet(response.data, ['flavorsRackspace', cloudLocation]);
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="rs.flavor-id"]').store.load({ data: this.cacheGet(['flavorsRackspace', record.get('cloud_location')]) });
			this.down('[name="rs.flavor-id"]').setValue(parseInt(settings['rs.flavor-id']) || 1);
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['rs.flavor-id'] = this.down('[name="rs.flavor-id"]').getValue();

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			items: [{
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				fieldLabel: 'Flavor',
				editable: false,
				queryMode: 'local',
				name: 'rs.flavor-id',
				width: 300
			}]
		}]
	});
});
