Scalr.regPage('Scalr.ui.farms.builder.tabs.ebs', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'EBS',

		isEnabled: function (record) {
			return record.get('platform') == 'ec2';
		},

		getDefaultValues: function (record) {
			return {
				'aws.use_ebs': 0
			};
		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			if (this.cacheExist(['snapshotsEC2', cloudLocation]))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/platforms/ec2/xGetSnapshots',
					params: {
						cloudLocation: cloudLocation
					},
					scope: this,
					success: function (response) {
						this.cacheSet(response.data, ['snapshotsEC2', cloudLocation]);
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="aws.ebs_snapid"]').reset();
			this.down('[name="aws.ebs_snapid"]').store.load({ data: this.cacheGet(['snapshotsEC2', record.get('cloud_location')]) });

			this.down('[name="aws.ebs_size"]').setValue(settings['aws.ebs_size'] || '5');
			this.down('[name="aws.ebs_snapid"]').setValue(settings['aws.ebs_snapid'] || '');
			this.down('[name="aws.ebs_mountpoint"]').setValue(settings['aws.ebs_mountpoint'] || '/mnt/storage');

			if (settings['aws.use_ebs'] == 1) {
				this.down('[name="aws.use_ebs"]').expand();
			} else {
				this.down('[name="aws.use_ebs"]').collapse();
				this.down('[name="aws.ebs_mountpoint"]').disable();
			}

			if (settings['aws.ebs_mount'] == 1) {
				this.down('[name="aws.ebs_mount"]').setValue(true);
				this.down('[name="aws.ebs_mountpoint"]').enable();
			} else {
				this.down('[name="aws.ebs_mount"]').setValue(false);
				this.down('[name="aws.ebs_mountpoint"]').disable();
			}
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			if (! this.down('[name="aws.use_ebs"]').collapsed) {
				settings['aws.use_ebs'] = 1;
				settings['aws.ebs_size'] = this.down('[name="aws.ebs_size"]').getValue();
				settings['aws.ebs_snapid'] = this.down('[name="aws.ebs_snapid"]').getValue();

				if (this.down('[name="aws.ebs_mount"]').getValue()) {
					settings['aws.ebs_mount'] = 1;
					settings['aws.ebs_mountpoint'] = this.down('[name="aws.ebs_mountpoint"]').getValue();
				} else {
					settings['aws.ebs_mount'] = 0;
					delete settings['aws.ebs_mountpoint'];
				}
			} else {
				settings['aws.use_ebs'] = 0;
				delete settings['aws.ebs_mountpoint'];
				delete settings['aws.ebs_size'];
				delete settings['aws.ebs_snapid'];
				delete settings['aws.ebs_mount'];
				delete settings['aws.ebs_mountpoint'];
			}

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			name: 'aws.use_ebs',
			checkboxToggle: true,
			collapsed: true,
			title: 'Automatically attach EBS volume with the following options:',
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'displayfield',
					value: 'Size'
				}, {
					xtype: 'textfield',
					name: 'aws.ebs_size',
					margin: {
						left: 3
					},
					width: 40
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'GB'
				}]
			}, {
				xtype: 'combo',
				name: 'aws.ebs_snapid',
				fieldLabel: 'Snapshot',
				editable: true,
				forceSelection: false,
				width: 500,
				labelWidth: 60,
				typeAhead: true,
				allowBlank: true,
				selectOnFocus: true,
				valueField: 'snapid',
				displayField: 'snapid',
				queryMode: 'local',
				displayTpl: '<tpl for="."><tpl if="snapid">{snapid} (Created: {createdat}, Size: {size}GB)</tpl></tpl>',
				listConfig: {
					getInnerTpl: function() {
                    	return '<tpl for="."><tpl if="snapid">{snapid} (Created: {createdat}, Size: {size}GB)</tpl></tpl>';
                    }
				},
				store: {
					fields: [ 'snapid', 'createdat', 'size' ],
					proxy: 'object'
				}
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				hideLabel: true,
				items: [{
					xtype: 'checkbox',
					boxLabel: 'Automatically mount device to',
					name: 'aws.ebs_mount',
					handler: function (field, checked) {
						if (checked)
							this.next('[name="aws.ebs_mountpoint"]').enable();
						else
							this.next('[name="aws.ebs_mountpoint"]').disable();
					}
				}, {
					xtype: 'textfield',
					margin: {
						left: 3
					},
					name: 'aws.ebs_mountpoint'
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'mount point.'
				}]
			}]
		}]
	});
});
