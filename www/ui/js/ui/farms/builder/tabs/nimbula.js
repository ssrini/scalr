Scalr.regPage('Scalr.ui.farms.builder.tabs.nimbula', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Nimbula settings',

		isEnabled: function (record) {
			return record.get('platform') == 'nimbula';
		},

		getDefaultValues: function (record) {
			return {
				'nimbula.shape': 'small'
			};
		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			if (this.cacheExist('shapes'))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/platforms/nimbula/xGetShapes/',
					scope: this,
					success: function (response) {
						this.cacheSet(response.data, 'shapes');
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},


		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="nimbula.shape"]').store.load({ data: this.cacheGet('shapes') });
			this.down('[name="nimbula.shape"]').setValue(settings['nimbula.shape'] || 'small');
		},

		hideTab: function (record) {
			var settings = record.get('settings');
			settings['nimbula.shape'] = this.down('[name="nimbula.shape"]').getValue();
			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			defaults: {
				labelWidth: 50,
				width: 300
			},
			items: [{
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				fieldLabel: 'Shape',
				editable: false,
				queryMode: 'local',
				name: 'nimbula.shape'
			}]
		}]
	});
});
