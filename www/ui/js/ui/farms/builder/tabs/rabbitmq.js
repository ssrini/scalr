Scalr.regPage('Scalr.ui.farms.builder.tabs.rabbitmq', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'RabbitMQ settings',
		itemId: 'rabbitmq',
		
		isEnabled: function(record){
			return record.get('behaviors').match('rabbitmq');
		},
		
		getDefaultValues: function(record){
			if (record.get('platform') == 'ec2') 
				var default_storage_engine = 'ebs';
			else if (record.get('platform') == 'rackspace') 
				var default_storage_engine = 'eph';
			else if (record.get('platform') == 'cloudstack') 
				var default_storage_engine = 'csvol';
			
			
			return {
				'rabbitmq.data_storage.engine': default_storage_engine,
				'rabbitmq.data_storage.ebs.size': 2,
				'rabbitmq.nodes_ratio': '10%'
			};
		},
		
		showTab: function(record){
			var settings = record.get('settings');
			
			if (record.get('platform') == 'ec2') {
				this.down('[name="rabbitmq.data_storage.engine"]').store.load({
					data: ['ebs']
				});
			}
			else if (record.get('platform') == 'rackspace') {
				this.down('[name="rabbitmq.data_storage.engine"]').store.load({
					data: ['eph']
				});
			}
			else if (record.get('platform') == 'cloudstack') {
				this.down('[name="rabbitmq.data_storage.engine"]').store.load({
					data: ['csvol']
				});
			}
			
			if (settings['rabbitmq.data_storage.engine'] == 'ebs' || settings['rabbitmq.data_storage.engine'] == 'csvol') {
			
				if (record.get('new')) 
					this.down('[name="rabbitmq.data_storage.ebs.size"]').setReadOnly(false);
				else 
					this.down('[name="rabbitmq.data_storage.ebs.size"]').setReadOnly(true);
				
				this.down('[name="rabbitmq.data_storage.ebs.size"]').setValue(settings['rabbitmq.data_storage.ebs.size']);
			}
			
			this.down('[name="rabbitmq.data_storage.engine"]').setValue(settings['rabbitmq.data_storage.engine']);
			
			this.down('[name="rabbitmq.nodes_ratio"]').setValue(settings['rabbitmq.nodes_ratio']);
		},
		
		hideTab: function(record){
			var settings = record.get('settings');
			
			settings['rabbitmq.data_storage.engine'] = this.down('[name="rabbitmq.data_storage.engine"]').getValue();
			
			settings['rabbitmq.nodes_ratio'] = this.down('[name="rabbitmq.nodes_ratio"]').getValue();
			
			if (settings['rabbitmq.data_storage.engine'] == 'ebs' || settings['rabbitmq.data_storage.engine'] == 'csvol') {
				if (record.get('new')) 
					settings['rabbitmq.data_storage.ebs.size'] = this.down('[name="rabbitmq.data_storage.ebs.size"]').getValue();
			}
			else {
				delete settings['rabbitmq.data_storage.ebs.size'];
			}
			
			record.set('settings', settings);
		},
		
		items: [{
			xtype: 'fieldset',
			title: 'RabbitMQ general settings',
			items: [{
				xtype: 'textfield',
				fieldLabel: 'Disk nodes / RAM nodes ratio',
				name: 'rabbitmq.nodes_ratio',
				value: '10%',
				labelWidth: 180
			}]
		}, {
			xtype: 'fieldset',
			title: 'RabbitMQ data storage settings',
			items: [{
				xtype: 'combo',
				name: 'rabbitmq.data_storage.engine',
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
						this.up('#rabbitmq').down('[name="ebs_settings"]').hide();
						
						if (this.getValue() == 'ebs' || this.getValue() == 'csvol') {
							this.up('#rabbitmq').down('[name="ebs_settings"]').show();
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
				name: 'rabbitmq.data_storage.ebs.size'
			}]
		}]
	});
});
