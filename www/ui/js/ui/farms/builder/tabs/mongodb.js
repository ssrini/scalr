Scalr.regPage('Scalr.ui.farms.builder.tabs.mongodb', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'MongoDB settings',
		itemId: 'mongodb',
		isEnabled: function (record) {
			return record.get('behaviors').match('mongodb');
		},

		getDefaultValues: function (record) {
			if (record.get('platform') == 'ec2') 
				var default_storage_engine = 'ebs';
			else if (record.get('platform') == 'rackspace') 
				var default_storage_engine = 'eph';
			else if (record.get('platform') == 'cloudstack') 
				var default_storage_engine = 'csvol';
			
			
			return {
				'mongodb.data_storage.engine': default_storage_engine,
				'mongodb.data_storage.ebs.size': 10
			};
		},

		showTab: function (record) {
			var settings = record.get('settings');
			
			if (record.get('platform') == 'ec2') {
				this.down('[name="mongodb.data_storage.engine"]').store.load({
					data: ['ebs']
				});
			}
			else if (record.get('platform') == 'rackspace') {
				this.down('[name="mongodb.data_storage.engine"]').store.load({
					data: ['eph']
				});
			}
			else if (record.get('platform') == 'cloudstack') {
				this.down('[name="mongodb.data_storage.engine"]').store.load({
					data: ['csvol']
				});
			}
			
			if (settings['mongodb.data_storage.engine'] == 'ebs' || settings['mongodb.data_storage.engine'] == 'csvol') {
			
				if (record.get('new')) 
					this.down('[name="mongodb.data_storage.ebs.size"]').setReadOnly(false);
				else 
					this.down('[name="mongodb.data_storage.ebs.size"]').setReadOnly(true);
				
				this.down('[name="mongodb.data_storage.ebs.size"]').setValue(settings['mongodb.data_storage.ebs.size']);
			}

			this.down('[name="mongodb.data_storage.engine"]').setValue(settings['mongodb.data_storage.engine']);
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			if (settings['mongodb.data_storage.engine'] == 'ebs' || settings['mongodb.data_storage.engine'] == 'csvol') {
				if (record.get('new')) 
					settings['mongodb.data_storage.ebs.size'] = this.down('[name="mongodb.data_storage.ebs.size"]').getValue();
			}
			else {
				delete settings['mongodb.data_storage.ebs.size'];
			}

			settings['mongodb.data_storage.engine'] = this.down('[name="mongodb.data_storage.engine"]').getValue();

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			title: 'MongoDB data storage settings',
			items: [{
				xtype: 'combo',
				name: 'mongodb.data_storage.engine',
				fieldLabel: 'Storage engine',
				editable: false,
				store: {
					fields: ['id', 'name'],
					proxy: 'object'
				},
				valueField: 'name',
				displayField: 'name',
				width: 400,
				labelWidth: 160,
				queryMode: 'local',
				listeners: {
					change: function(){
						this.up('#mongodb').down('[name="ebs_settings"]').hide();
						
						if (this.getValue() == 'ebs' || this.getValue() == 'csvol') {
							this.up('#mongodb').down('[name="ebs_settings"]').show();
						}
					}
				}
			}]
		}, {
			xtype: 'fieldset',
			name: 'ebs_settings',
			title: 'Block Storage settings',
			hidden: true,
			items: [{
				xtype: 'textfield',
				fieldLabel: 'Storage size (max. 1000 GB)',
				labelWidth: 160,
				width: 200,
				name: 'mongodb.data_storage.ebs.size'
			}]
		}]
	});
});
