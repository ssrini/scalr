Scalr.regPage('Scalr.ui.farms.builder.tabs.mysql', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'MySQL settings',

		isEnabled: function (record) {
			return (record.get('behaviors').match('mysql') && !record.get('behaviors').match('mysqlproxy') &&
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
				'mysql.enable_bundle': 1,
				'mysql.bundle_every': 24,
				'mysql.pbw1_hh': '05',
				'mysql.pbw1_mm': '00',
				'mysql.pbw2_hh': '09',
				'mysql.pbw2_mm': '00',
				'mysql.data_storage_engine': default_storage_engine,
				'mysql.ebs_volume_size': 100,
				'mysql.enable_bcp': 1,
				'mysql.bcp_every' : 720
				// Rotate 10 times. Re-think interface
			};
		},

		showTab: function (record) {
			var settings = record.get('settings');

			if (settings['mysql.enable_bundle'] == 1)
				this.down('[name="mysql.enable_bundle"]').expand();
			else
				this.down('[name="mysql.enable_bundle"]').collapse();

			this.down('[name="mysql.bundle_every"]').setValue(settings['mysql.bundle_every'] || 48);
			this.down('[name="mysql.pbw1_hh"]').setValue(settings['mysql.pbw1_hh'] || '05');
			this.down('[name="mysql.pbw1_mm"]').setValue(settings['mysql.pbw1_mm'] || '00');
			this.down('[name="mysql.pbw2_hh"]').setValue(settings['mysql.pbw2_hh'] || '09');
			this.down('[name="mysql.pbw2_mm"]').setValue(settings['mysql.pbw2_mm'] || '00');

			if (settings['mysql.enable_bcp'] == 1) {
				this.down('[name="mysql.enable_bcp"]').setValue(true);
				this.down('[name="mysql.bcp_every"]').enable();
			} else {
				this.down('[name="mysql.enable_bcp"]').setValue(false);
				this.down('[name="mysql.bcp_every"]').disable();
			}
			this.down('[name="mysql.bcp_every"]').setValue(settings['mysql.bcp_every'] || 360);

			if (settings['mysql.data_storage_engine'] == 'ebs' || settings['mysql.data_storage_engine'] == 'csvol') {
				this.down('[name="mysql.ebs_volume_size"]').show();
				this.down('[name="mysql.ebs.snaps_rotation"]').show();

				if (record.get('new'))
					this.down('[name="mysql.ebs_volume_size"]').setReadOnly(false);
				else
					this.down('[name="mysql.ebs_volume_size"]').setReadOnly(true);

				if (settings['mysql.ebs.rotate_snaps'] == 1) {
					this.down('[name="mysql.ebs.rotate_snaps"]').setValue(true);
					this.down('[name="mysql.ebs.rotate"]').enable();
				} else {
					this.down('[name="mysql.ebs.rotate_snaps"]').setValue(false);
					this.down('[name="mysql.ebs.rotate"]').disable();
				}
				this.down('[name="mysql.ebs.rotate"]').setValue(settings['mysql.ebs.rotate'] || 5);
				this.down('[name="mysql.ebs_volume_size"]').setValue(settings['mysql.ebs_volume_size'] || 100);

			} else {
				this.down('[name="mysql.ebs_volume_size"]').hide();
				this.down('[name="mysql.ebs.snaps_rotation"]').hide();
			}

			this.down('[name="mysql.storage_data_engine"]').setValue(settings['mysql.data_storage_engine']);
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			if (! this.down('[name="mysql.enable_bundle"]').collapsed) {
				settings['mysql.enable_bundle'] = 1;
				settings['mysql.bundle_every'] = this.down('[name="mysql.bundle_every"]').getValue();
				settings['mysql.pbw1_hh'] = this.down('[name="mysql.pbw1_hh"]').getValue();
				settings['mysql.pbw1_mm'] = this.down('[name="mysql.pbw1_mm"]').getValue();
				settings['mysql.pbw2_hh'] = this.down('[name="mysql.pbw2_hh"]').getValue();
				settings['mysql.pbw2_mm'] = this.down('[name="mysql.pbw2_mm"]').getValue();
			} else {
				settings['mysql.enable_bundle'] = 0;
				delete settings['mysql.bundle_every'];
				delete settings['mysql.pbw1_hh'];
				delete settings['mysql.pbw1_mm'];
				delete settings['mysql.pbw2_hh'];
				delete settings['mysql.pbw2_mm'];
			}

			if (this.down('[name="mysql.enable_bcp"]').getValue()) {
				settings['mysql.enable_bcp'] = 1;
				settings['mysql.bcp_every'] = this.down('[name="mysql.bcp_every"]').getValue();
			} else {
				settings['mysql.enable_bcp'] = 0;
				delete settings['mysql.bcp_every'];
			}

			if (settings['mysql.data_storage_engine'] == 'ebs' || settings['mysql.data_storage_engine'] == 'csvol') {
				if (record.get('new'))
					settings['mysql.ebs_volume_size'] = this.down('[name="mysql.ebs_volume_size"]').getValue();

				if (this.down('[name="mysql.ebs.rotate_snaps"]').getValue()) {
					settings['mysql.ebs.rotate_snaps'] = 1;
					settings['mysql.ebs.rotate'] = this.down('[name="mysql.ebs.rotate"]').getValue();
				} else {
					settings['mysql.ebs.rotate_snaps'] = 0;
					delete settings['mysql.ebs.rotate'];
				}
			} else {
				delete settings['mysql.ebs_volume_size'];
				delete settings['mysql.ebs.rotate_snaps'];
				delete settings['mysql.ebs.rotate'];
			}

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			checkboxToggle:  true,
			name: 'mysql.enable_bundle',
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
					name: 'mysql.bundle_every'
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
									'MySQL snapshots contain a hotcopy of mysql data directory, file that holds binary log position and debian.cnf' +
									'<br>' +
									'When farm starts:<br>' +
									'1. MySQL master dowloads and extracts a snapshot from storage depends on cloud platfrom<br>' +
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
					name: 'mysql.pbw1_hh',
					width: 40
				}, {
					xtype: 'displayfield',
					value: ':',
					margin: {
						left: 3
					}
				}, {
					xtype: 'textfield',
					name: 'mysql.pbw1_mm',
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
					name: 'mysql.pbw2_hh',
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
					name: 'mysql.pbw2_mm',
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
			title: 'Backup settings',
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'checkbox',
					boxLabel: 'Make database backup every',
					name: 'mysql.enable_bcp',
					handler: function (checkbox, checked) {
						if (checked)
							this.next('[name="mysql.bcp_every"]').enable();
						else
							this.next('[name="mysql.bcp_every"]').disable();
					}
				}, {
					xtype: 'textfield',
					name: 'mysql.bcp_every',
					width: 40,
					margin: {
						left: 3
					}
				}, {
					xtype: 'displayfield',
					value: 'minutes',
					margin: {
						left: 3
					}
				}]
			}]
		}, {
			xtype: 'fieldset',
			title: 'Data storage settings',
			items: [{
				xtype: 'displayfield',
				fieldLabel: 'Storage engine',
				name: 'mysql.storage_data_engine',
				value: '',
				labelWidth: 90
			}, {
				xtype: 'textfield',
				fieldLabel: 'EBS size (max. 1000 GB)',
				labelWidth: 140,
				width: 200,
				name: 'mysql.ebs_volume_size'
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				name: 'mysql.ebs.snaps_rotation',
				items: [{
					xtype: 'checkbox',
					hideLabel: true,
					name: 'mysql.ebs.rotate_snaps',
					boxLabel: 'Snapshots are rotated',
					handler: function (checkbox, checked) {
						if (checked)
							this.next('[name="mysql.ebs.rotate"]').enable();
						else
							this.next('[name="mysql.ebs.rotate"]').disable();
					}
				}, {
					xtype: 'textfield',
					hideLabel: true,
					name: 'mysql.ebs.rotate',
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
		}]
	});
});
