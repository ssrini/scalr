Scalr.regPage('Scalr.ui.farms.builder.tabs.servicesconfig', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Services config',

		isEnabled: function (record) {
			return record.get('platform') != 'rds';
		},

		beforeShowTab: function (record, handler) {
			var behaviors = record.get('behaviors').split(','), beh = [], me = this;

			Ext.Array.each(behaviors, function (behavior) {
				if (! me.cacheExist(['behaviors', behavior]))
					beh.push(behavior);
			});

			if (! beh.length)
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					params: {
						behaviors: Ext.encode(beh)
					},
					url: '/services/configurations/presets/xGetList',
					scope: this,
					success: function (response) {
						for (var i in response.data) {
							response.data[i].unshift({ id: 0, name: 'Service defaults' });
							this.cacheSet(response.data[i], ['behaviors', i]);
						}

						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			var behaviors = record.get('behaviors').split(','), config_presets = record.get('config_presets') || {}, fieldset = this.down('#servicesconfig'), me = this;

			Ext.Array.each(behaviors, function (behavior) {
				fieldset.add({
					xtype: 'combo',
					store: {
						fields: [ 'id', 'name' ],
						proxy: 'object',
						data: me.cacheGet(['behaviors', behavior])
					},
					fieldLabel: behavior,
					valueField: 'id',
					displayField: 'name',
					editable: false,
					queryMode: 'local',
					behavior: behavior,
					value: config_presets[behavior] || 0
				});
			});
		},

		hideTab: function (record) {
			var config_presets = {}, fieldset = this.down('#servicesconfig');

			fieldset.items.each(function (item) {
				var value = item.getValue();
				if (value != '0')
					config_presets[item.behavior] = value;
			});

			fieldset.removeAll();
			record.set('config_presets', config_presets);
		},

		items: [{
			xtype: 'fieldset',
			itemId: 'servicesconfig',
			defaults: {
				labelWidth: 100,
				width: 400
			}
		}]
	});
});
