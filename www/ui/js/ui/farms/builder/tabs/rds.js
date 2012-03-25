Scalr.regPage('Scalr.ui.farms.builder.tabs.rds', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'RDS settings',

		isEnabled: function (record) {
			return record.get('platform') == 'rds';
		},

		getDefaultValues: function (record) {
			return {
				'rds.availability_zone': '',
				'rds.instance_class': 'db.m1.small',
				'rds.storage': 5,
				'rds.master-user': 'root',
				'rds.port': '3306',
				'rds.engine': 'MySQL5.1'
			};
		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			if (this.cacheExist(['availabilityZonesRDS', cloudLocation]))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/platforms/ec2/xGetAvailZones',
					params: {
						cloudLocation: cloudLocation
					},
					scope: this,
					success: function (response) {
						response.data.unshift({ id: 'x-scalr-diff', name: 'Place in different zones' });
						response.data.unshift({ id: '', name: 'Choose randomly' });
						this.cacheSet(response.data, ['availabilityZonesRDS', cloudLocation]);
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="rds.instance_class"]').store.load({ data: [ 'db.m1.small','db.m1.large','db.m1.xlarge','db.m2.2xlarge','db.m2.4xlarge' ] });
			this.down('[name="rds.instance_class"]').setValue(settings['rds.instance_class'] || 'db.m1.small');

			this.down('[name="rds.availability_zone"]').store.load({ data: this.cacheGet(['availabilityZonesRDS', record.get('cloud_location')]) });
			this.down('[name="rds.availability_zone"]').setValue(settings['rds.availability_zone'] || '');

			this.down('[name="rds.storage"]').setValue(settings['rds.storage'] || '5');
			this.down('[name="rds.master-user"]').setValue(settings['rds.master-user'] || 'root');
			this.down('[name="rds.master-pass"]').setValue(settings['rds.master-pass'] || '');
			this.down('[name="rds.port"]').setValue(settings['rds.port'] || '3306');
			this.down('[name="rds.engine"]').setValue(settings['rds.engine'] || 'MySQL5.1');

			if (settings['rds.multi-az'] == 1) {
				this.down('[name="rds.multi-az"]').setValue(true);
			} else {
				this.down('[name="rds.multi-az"]').setValue(false);
			}
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['rds.instance_class'] 		= this.down('[name="rds.instance_class"]').getValue();
			settings['rds.availability_zone'] 	= this.down('[name="rds.availability_zone"]').getValue();
			settings['rds.storage'] 			= this.down('[name="rds.storage"]').getValue();
			settings['rds.master-user'] 		= this.down('[name="rds.master-user"]').getValue();
			settings['rds.master-pass'] 		= this.down('[name="rds.master-pass"]').getValue();
			settings['rds.port'] 				= this.down('[name="rds.port"]').getValue();
			settings['rds.multi-az'] 			= this.down('[name="rds.multi-az"]').getValue() ? 1 : 0;
			settings['rds.engine'] 				= this.down('[name="rds.engine"]').getValue();

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			defaults: {
				labelWidth: 120,
				width: 320
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
				name: 'rds.availability_zone'
			}, {
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				fieldLabel: 'Instance class',
				valueField: 'name',
				displayField: 'name',
				editable: false,
				queryMode: 'local',
				name: 'rds.instance_class'
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'displayfield',
					value: 'Allocated storage (5-1024 GB)'
				}, {
					xtype: 'textfield',
					width: 70,
					name: 'rds.storage',
					hideLabel: true,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'GB'
				}]
			}, {
				fieldLabel: 'Port',
				xtype: 'textfield',
				name: 'rds.port'
			}, {
				fieldLabel: 'Master username',
				xtype: 'textfield',
				name: 'rds.master-user'
			}, {
				fieldLabel: 'Master password',
				xtype: 'textfield',
				name: 'rds.master-pass'
			}, {
				xtype: 'combo',
				store: [[ 'MySQL5.1', 'MySQL5.1' ]],
				fieldLabel: 'Engine',
				editable: false,
				queryMode: 'local',
				name: 'rds.engine'
			}, {
				boxLabel: 'Enable <a target="_blank" href="http://aws.amazon.com/about-aws/whats-new/2010/05/18/announcing-multi-az-deployments-for-amazon-rds/">MultiAZ</a>',
				xtype: 'checkbox',
				name: 'rds.multi-az',
				value: 1
			}]
		}]
	});
});
