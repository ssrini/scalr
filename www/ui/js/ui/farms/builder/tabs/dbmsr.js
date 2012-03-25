Scalr.regPage('Scalr.ui.farms.builder.tabs.dbmsr', function () {
	
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Database settings',
		itemId: 'dbmsr',

		isEnabled: function (record) {
			return ((record.get('behaviors').match('postgresql') || record.get('behaviors').match('redis')) &&
				(
					record.get('platform') == 'ec2' ||
					record.get('platform') == 'rackspace' ||
					record.get('platform') == 'cloudstack'
				)
			);
		},

		getDefaultValues: function (record) {
			if (record.get('platform') == 'ec2')
				var default_storage_engine = 'ebs';
			else if (record.get('platform') == 'rackspace')
				var default_storage_engine = 'eph';
			else if (record.get('platform') == 'cloudstack')
				var default_storage_engine = 'csvol';

			return {
				'db.msr.data_bundle.enabled': 1,
				'db.msr.data_bundle.every': 24,
				'db.msr.data_bundle.timeframe.start_hh': '05',
				'db.msr.data_bundle.timeframe.start_mm': '00',
				'db.msr.data_bundle.timeframe.end_hh': '09',
				'db.msr.data_bundle.timeframe.end_mm': '00',
				
				'db.msr.data_storage.engine': default_storage_engine,
				'db.msr.data_storage.ebs.size': 100,
				'db.msr.data_storage.ebs.snaps.enable_rotation' : 1,
				'db.msr.data_storage.ebs.snaps.rotate' : 5,
				
				'db.msr.data_backup.enabled': 1,
				'db.msr.data_backup.every' : 720,
				'db.msr.data_backup.timeframe.start_hh': '05',
				'db.msr.data_backup.timeframe.start_mm': '00',
				'db.msr.data_backup.timeframe.end_hh': '09',
				'db.msr.data_backup.timeframe.end_mm': '00'
			};
		},

		showTab: function (record) {
			
			if (record.get('platform') == 'ec2') {
				this.down('[name="db.msr.data_storage.engine"]').store.load({
					data: ['ebs']
				});
			} else if (record.get('platform') == 'rackspace') {
				this.down('[name="db.msr.data_storage.engine"]').store.load({
					data: ['eph']
				});
			} else if (record.get('platform') == 'cloudstack') {
				this.down('[name="db.msr.data_storage.engine"]').store.load({
					data: ['csvol']
				});
			}
			
			var settings = record.get('settings');
			
			if (record.get('behaviors').match('redis')) {
				this.down('[name="db.msr.redis.persistence_type"]').store.load({
					data: [
						{name:'aof', description:'Append Only File'},
						{name:'snapshotting', description:'Snapshotting'}
					]
				});
				
				this.down('[name="db.msr.redis.persistence_type"]').setValue(settings['db.msr.redis.persistence_type'] || 'snapshotting');
				
				this.down('[name="redis_settings"]').show();
			} else {
				this.down('[name="redis_settings"]').hide();
			}
			
			this.down('[name="db.msr.data_storage.raid.type"]').store.load({
				data: [
					{name:'raid0', description:'RAID 0 (block-level striping without parity or mirroring)'},
					{name:'raid1', description:'RAID 1 (mirroring without parity or striping)'},
					{name:'raid5', description:'RAID 5 (block-level striping with distributed parity)'},
				]
			});

			if (settings['db.msr.data_bundle.enabled'] == 1)
				this.down('[name="db.msr.data_bundle.enabled"]').expand();
			else
				this.down('[name="db.msr.data_bundle.enabled"]').collapse();

			this.down('[name="db.msr.data_bundle.every"]').setValue(settings['db.msr.data_bundle.every']);
			this.down('[name="db.msr.data_bundle.timeframe.start_hh"]').setValue(settings['db.msr.data_bundle.timeframe.start_hh']);
			this.down('[name="db.msr.data_bundle.timeframe.start_mm"]').setValue(settings['db.msr.data_bundle.timeframe.start_mm']);
			this.down('[name="db.msr.data_bundle.timeframe.end_hh"]').setValue(settings['db.msr.data_bundle.timeframe.end_hh']);
			this.down('[name="db.msr.data_bundle.timeframe.end_mm"]').setValue(settings['db.msr.data_bundle.timeframe.end_mm']);

			if (settings['db.msr.data_backup.enabled'] == 1)
				this.down('[name="db.msr.data_backup.enabled"]').expand();
			else
				this.down('[name="db.msr.data_backup.enabled"]').collapse();

			this.down('[name="db.msr.data_backup.every"]').setValue(settings['db.msr.data_backup.every']);
			this.down('[name="db.msr.data_backup.timeframe.start_hh"]').setValue(settings['db.msr.data_backup.timeframe.start_hh']);
			this.down('[name="db.msr.data_backup.timeframe.start_mm"]').setValue(settings['db.msr.data_backup.timeframe.start_mm']);
			this.down('[name="db.msr.data_backup.timeframe.end_hh"]').setValue(settings['db.msr.data_backup.timeframe.end_hh']);
			this.down('[name="db.msr.data_backup.timeframe.end_mm"]').setValue(settings['db.msr.data_backup.timeframe.end_mm']);

			if (settings['db.msr.data_storage.engine'] == 'ebs' || settings['db.msr.data_storage.engine'] == 'csvol') {

				if (record.get('new'))
					this.down('[name="db.msr.data_storage.ebs.size"]').setReadOnly(false);
				else
					this.down('[name="db.msr.data_storage.ebs.size"]').setReadOnly(true);

				if (settings['db.msr.data_storage.ebs.snaps.enable_rotation'] == 1) {
					this.down('[name="db.msr.data_storage.ebs.snaps.enable_rotation"]').setValue(true);
					this.down('[name="db.msr.data_storage.ebs.snaps.rotate"]').enable();
				} else {
					this.down('[name="db.msr.data_storage.ebs.snaps.enable_rotation"]').setValue(false);
					this.down('[name="db.msr.data_storage.ebs.snaps.rotate"]').disable();
				}
				this.down('[name="db.msr.data_storage.ebs.snaps.rotate"]').setValue(settings['db.msr.data_storage.ebs.snaps.rotate']);
				this.down('[name="db.msr.data_storage.ebs.size"]').setValue(settings['db.msr.data_storage.ebs.size']);

			}

			this.down('[name="db.msr.data_storage.engine"]').setValue(settings['db.msr.data_storage.engine']);
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			if (record.get('behaviors').match('redis')) {
				settings['db.msr.redis.persistence_type'] = this.down('[name="db.msr.redis.persistence_type"]').getValue();
			}

			if (! this.down('[name="db.msr.data_bundle.enabled"]').collapsed) {
				settings['db.msr.data_bundle.enabled'] = 1;
				settings['db.msr.data_bundle.every'] = this.down('[name="db.msr.data_bundle.every"]').getValue();
				settings['db.msr.data_bundle.timeframe.start_hh'] = this.down('[name="db.msr.data_bundle.timeframe.start_hh"]').getValue();
				settings['db.msr.data_bundle.timeframe.start_mm'] = this.down('[name="db.msr.data_bundle.timeframe.start_mm"]').getValue();
				settings['db.msr.data_bundle.timeframe.end_hh'] = this.down('[name="db.msr.data_bundle.timeframe.end_hh"]').getValue();
				settings['db.msr.data_bundle.timeframe.end_mm'] = this.down('[name="db.msr.data_bundle.timeframe.end_mm"]').getValue();
			} else {
				settings['db.msr.data_bundle.enabled'] = 0;
				delete settings['db.msr.data_bundle.every'];
				delete settings['db.msr.data_bundle.timeframe.start_hh'];
				delete settings['db.msr.data_bundle.timeframe.start_mm'];
				delete settings['db.msr.data_bundle.timeframe.end_hh'];
				delete settings['db.msr.data_bundle.timeframe.end_mm'];
			}

			if (! this.down('[name="db.msr.data_backup.enabled"]').collapsed) {
				settings['db.msr.data_backup.enabled'] = 1;
				settings['db.msr.data_backup.every'] = this.down('[name="db.msr.data_backup.every"]').getValue();
				settings['db.msr.data_backup.timeframe.start_hh'] = this.down('[name="db.msr.data_backup.timeframe.start_hh"]').getValue();
				settings['db.msr.data_backup.timeframe.start_mm'] = this.down('[name="db.msr.data_backup.timeframe.start_mm"]').getValue();
				settings['db.msr.data_backup.timeframe.end_hh'] = this.down('[name="db.msr.data_backup.timeframe.end_hh"]').getValue();
				settings['db.msr.data_backup.timeframe.end_mm'] = this.down('[name="db.msr.data_backup.timeframe.end_mm"]').getValue();
			} else {
				settings['db.msr.data_backup.enabled'] = 0;
				delete settings['db.msr.data_backup.every'];
				delete settings['db.msr.data_backup.timeframe.start_hh'];
				delete settings['db.msr.data_backup.timeframe.start_mm'];
				delete settings['db.msr.data_backup.timeframe.end_hh'];
				delete settings['db.msr.data_backup.timeframe.end_mm'];
			}

			if (settings['db.msr.data_storage.engine'] == 'ebs' || settings['db.msr.data_storage.engine'] == 'csvol') {
				if (record.get('new'))
					settings['db.msr.data_storage.ebs.size'] = this.down('[name="db.msr.data_storage.ebs.size"]').getValue();

				if (this.down('[name="db.msr.data_storage.ebs.snaps.enable_rotation"]').getValue()) {
					settings['db.msr.data_storage.ebs.snaps.enable_rotation'] = 1;
					settings['db.msr.data_storage.ebs.snaps.rotate'] = this.down('[name="db.msr.data_storage.ebs.snaps.rotate"]').getValue();
				} else {
					settings['db.msr.data_storage.ebs.snaps.enable_rotation'] = 0;
					delete settings['db.msr.data_storage.ebs.snaps.rotate'];
				}
			} else {
				delete settings['db.msr.data_storage.ebs.size'];
				delete settings['db.msr.data_storage.ebs.snaps.enable_rotation'];
				delete settings['db.msr.data_storage.ebs.snaps.rotate'];
			}

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			checkboxToggle:  true,
			name: 'db.msr.data_bundle.enabled',
			title: 'Bundle and save data snapshot',
			defaults: {
				labelWidth: 150
			},
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'displayfield',
					value: 'Perform data bundle every'
				}, {
					xtype: 'textfield',
					width: 40,
					margin: {
						left: 3
					},
					name: 'db.msr.data_bundle.every'
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'hours'
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						afterrender: function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html:
									'DB snapshots contain a hotcopy of database data directory, file that holds binary log position and debian.cnf' +
									'<br>' +
									'When farm starts:<br>' +
									'1. Database master dowloads and extracts a snapshot from storage depends on cloud platfrom<br>' +
									'2. When data is loaded and master starts, slaves download and extract a snapshot as well<br>' +
									'3. Slaves are syncing with master for some time'
							});
						}
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Preferred bundle window',
				items: [{
					xtype: 'textfield',
					name: 'db.msr.data_bundle.timeframe.start_hh',
					width: 40
				}, {
					xtype: 'displayfield',
					value: ':',
					margin: {
						left: 3
					}
				}, {
					xtype: 'textfield',
					name: 'db.msr.data_bundle.timeframe.start_mm',
					width: 40,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					value: '-',
					margin: {
						left: 3
					}
				}, {
					xtype: 'textfield',
					name: 'db.msr.data_bundle.timeframe.end_hh',
					width: 40,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					value: ':',
					margin: {
						left: 3
					}
				},{
					xtype: 'textfield',
					name: 'db.msr.data_bundle.timeframe.end_mm',
					width: 40,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					value: 'Format: hh24:mi - hh24:mi',
					bodyStyle: 'font-style: italic',
					margin: {
						left: 3
					}
				}]
			}]
		}, {
			xtype: 'fieldset',
			checkboxToggle:  true,
			name: 'db.msr.data_backup.enabled',
			title: 'Backup data (gziped database dump)',
			defaults: {
				labelWidth: 150
			},
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'displayfield',
					value: 'Perform backup every'
				}, {
					xtype: 'textfield',
					width: 40,
					margin: {
						left: 3
					},
					name: 'db.msr.data_backup.every'
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'hours'
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Preferred backup window',
				items: [{
					xtype: 'textfield',
					name: 'db.msr.data_backup.timeframe.start_hh',
					width: 40
				}, {
					xtype: 'displayfield',
					value: ':',
					margin: {
						left: 3
					}
				}, {
					xtype: 'textfield',
					name: 'db.msr.data_backup.timeframe.start_mm',
					width: 40,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					value: '-',
					margin: {
						left: 3
					}
				}, {
					xtype: 'textfield',
					name: 'db.msr.data_backup.timeframe.end_hh',
					width: 40,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					value: ':',
					margin: {
						left: 3
					}
				},{
					xtype: 'textfield',
					name: 'db.msr.data_backup.timeframe.end_mm',
					width: 40,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					value: 'Format: hh24:mi - hh24:mi',
					bodyStyle: 'font-style: italic',
					margin: {
						left: 3
					}
				}]
			}]
		}, {
			xtype: 'fieldset',
			name: 'redis_settings',
			hidden: true,
			title: 'Redis settings',
			items: [{ 
				xtype: 'combo',
				name: 'db.msr.redis.persistence_type',
				fieldLabel: 'Persistence type',
				editable: false,
				store: {
					fields: [ 'name', 'description' ],
					proxy: 'object'
				},
				valueField: 'name',
				displayField: 'description',
				width: 400,
				labelWidth: 160,
				queryMode: 'local'
			}]
		}, {
			xtype: 'fieldset',
			title: 'Settings',
			items: [{ 
				xtype: 'combo',
				name: 'db.msr.data_storage.engine',
				fieldLabel: 'Storage engine',
				editable: false,
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				valueField: 'name',
				displayField: 'name',
				width: 400,
				labelWidth: 160,
				queryMode: 'local',
				listeners:{
					change:function(){
						this.up('#dbmsr').down('[name="ebs_settings"]').hide();
						this.up('#dbmsr').down('[name="raid_settings"]').hide();
						
						if (this.getValue() == 'ebs' || this.getValue() == 'csvol') {
							this.up('#dbmsr').down('[name="ebs_settings"]').show();
						} else if (this.getValue() == 'raid') {
							this.up('#dbmsr').down('[name="raid_settings"]').show();
						}
					}
				}
			}]
		}, {
			xtype:'fieldset',
			name: 'ebs_settings',
			title: 'Block Storage settings',
			hidden: true,
			items: [{
				xtype: 'textfield',
				fieldLabel: 'Storage size (max. 1000 GB)',
				labelWidth: 160,
				width: 200,
				name: 'db.msr.data_storage.ebs.size'
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				name: 'ebs_rotation_settings',
				items: [{
					xtype: 'checkbox',
					hideLabel: true,
					name: 'db.msr.data_storage.ebs.snaps.enable_rotation',
					boxLabel: 'Snapshots are rotated',
					handler: function (checkbox, checked) {
						if (checked)
							this.next('[name="db.msr.data_storage.ebs.snaps.rotate"]').enable();
						else
							this.next('[name="db.msr.data_storage.ebs.snaps.rotate"]').disable();
					}
				}, {
					xtype: 'textfield',
					hideLabel: true,
					name: 'db.msr.data_storage.ebs.snaps.rotate',
					width: 40,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					value: 'times before being removed.',
					margin: {
						left: 3
					}
				}]
			}]
		}, {
			xtype:'fieldset',
			name: 'raid_settings',
			title: 'RAID storage settings',
			hidden: true,
			items: [{ 
				xtype: 'combo',
				name: 'db.msr.data_storage.raid.type',
				fieldLabel: 'RAID type',
				editable: false,
				store: {
					fields: [ 'name', 'description' ],
					proxy: 'object'
				},
				valueField: 'name',
				displayField: 'description',
				width: 500,
				labelWidth: 160,
				queryMode: 'local',
				listeners:{
					change:function(){
						//TODO:
					}
				}
			}, {
				xtype: 'textfield',
				fieldLabel: 'Number of volumes',
				labelWidth: 160,
				width: 200,
				name: 'db.msr.data_storage.raid.volumes_count'
			}, {
				xtype: 'textfield',
				fieldLabel: 'Each volume size',
				labelWidth: 160,
				width: 200,
				name: 'db.msr.data_storage.raid.volume_size'
			}, {
				xtype: 'fieldcontainer',
				layout:'hbox',
				hideLabel: true,
				items:[ {
						xtype:"displayfield",
						hideLabel: true,
						value:"Available space on the raid: "
					}, {
						xtype:"displayfield",
						hideLabel: true,
						style:{fontWeight:'bold'},
						value:"",
						margin:{left: 3}
					}, {
						xtype:"displayfield",
						hideLabel: true,
						value:" GB",
						margin:{left: 3}
					}
				]
			}]
		}]
	});
});
