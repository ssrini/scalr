Scalr.regPage('Scalr.ui.farms.builder.tabs.placement', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Placement and type',

		isEnabled: function (record) {
			return record.get('platform') == 'ec2';
		},

		getDefaultValues: function (record) {
			return {
				'aws.availability_zone': '',
				'aws.instance_type': record.get('arch') == 'i386' ? 'm1.small' : 'm1.large'
			};
		},

		beforeShowTab: function (record, handler) {
			var cloudLocation = record.get('cloud_location');

			if (this.cacheExist(['availabilityZonesEC2', cloudLocation]))
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
						this.cacheSet(response.data, ['availabilityZonesEC2', cloudLocation]);
						handler();
					},
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			var settings = record.get('settings');

			var tagsString = record.get('tags').join(" ");
			var typeArray = new Array();
			var typeValue = '';

			if (record.get('arch') == 'i386') {
				if (tagsString.indexOf('ec2.ebs') != -1 || settings['aws.instance_type'] == 't1.micro')
					typeArray = ['t1.micro', 'm1.small', 'c1.medium'];
				else
					typeArray = ['m1.small', 'c1.medium'];

				typeValue = (settings['aws.instance_type'] || 'm1.small');
			} else {
				if (tagsString.indexOf('ec2.ebs') != -1 || settings['aws.instance_type'] == 't1.micro') {
					if (tagsString.indexOf('ec2.hvm') != -1) {
						typeArray = ['t1.micro', 'm1.small', 'c1.medium', 'm1.medium', 'm1.large', 'm1.xlarge', 'c1.xlarge', 'm2.xlarge', 'm2.2xlarge', 'm2.4xlarge', 'cc1.4xlarge', 'cc2.8xlarge', 'cg1.4xlarge'];
					} else {
						typeArray = ['t1.micro', 'm1.small', 'c1.medium', 'm1.medium', 'm1.large', 'm1.xlarge', 'c1.xlarge', 'm2.xlarge', 'm2.2xlarge', 'm2.4xlarge'];
					}
				} else
					typeArray = ['m1.large', 'm1.xlarge', 'c1.xlarge', 'm2.xlarge', 'm2.2xlarge', 'm2.4xlarge'];

				typeValue = (settings['aws.instance_type'] || 'm1.large');
			}

			this.down('[name="aws.instance_type"]').store.load({ data: typeArray });
			this.down('[name="aws.instance_type"]').setValue(typeValue);

			var comp = this.down('#aws_availability_zone_loc'), data = this.cacheGet(['availabilityZonesEC2', record.get('cloud_location')]);

			comp.removeAll();
			for (var i = 0; i < data.length; i++)
				comp.add({
					boxLabel: data[i].name,
					name: data[i].id
				});

			var d = [{ id: 'x-scalr-diff', name: 'Distribute equally' }, { id: '', name: 'AWS-chosen' }, { id: 'x-scalr-custom', name: 'Selected by me' }];
			for (var i = 0; i < data.length; i++)
				d.push(data[i]);

			this.down('[name="aws.availability_zone"]').store.load({ data: d });

			var zone = settings['aws.availability_zone'] || '';
			if (zone.match(/x-scalr-custom/)) {
				var loc = zone.replace('x-scalr-custom=', '').split(':');
				this.down('#aws_availability_zone_loc').items.each(function () {
					for (var i = 0; i < loc.length; i++) {
						if (this.name == loc[i])
							this.setValue(true);
					}
				});

				this.down('#aws_availability_zone_loc').show();
				zone = 'x-scalr-custom';
			}

			this.down('[name="aws.availability_zone"]').setValue(zone);

			if (
				record.get('behaviors').match('mysql') &&
				settings['mysql.data_storage_engine'] == 'ebs' &&
				settings['mysql.master_ebs_volume_id'] != '' &&
				settings['mysql.master_ebs_volume_id'] != undefined &&
				record.get('generation') != 2 &&
				this.down('[name="aws.availability_zone"]').getValue() != '' &&
				this.down('[name="aws.availability_zone"]').getValue() != 'x-scalr-diff'
			) {
				this.down('[name="aws.availability_zone"]').disable();
				this.down('#aws_availability_zone_warn').show();
			} else {
				this.down('[name="aws.availability_zone"]').enable();
				this.down('#aws_availability_zone_warn').hide();
			}
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['aws.instance_type'] = this.down('[name="aws.instance_type"]').getValue();

			if (this.down('[name="aws.availability_zone"]').getValue() == 'x-scalr-custom') {
				var loc = [];
				this.down('#aws_availability_zone_loc').items.each(function () {
					if (this.getValue())
						loc[loc.length] = this.name;
				});

				// TODO: replace hack
				if (loc.length == 0)
					Scalr.message.Error('Availability zone for role "' + record.get('name') + '" should be selected');
				else
					Scalr.message.Flush();

				settings['aws.availability_zone'] = 'x-scalr-custom=' + loc.join(':');
			} else
				settings['aws.availability_zone'] = this.down('[name="aws.availability_zone"]').getValue();

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Availability zone',
				items: [{
					xtype: 'combo',
					store: {
						fields: [ 'id', 'name' ],
						proxy: 'object'
					},
					valueField: 'id',
					displayField: 'name',
					editable: false,
					queryMode: 'local',
					name: 'aws.availability_zone',
					width: 200,
					listeners: {
						change: function (field, value) {
							var c = this.next('#aws_availability_zone_loc');
							if (value == 'x-scalr-custom')
								c.show();
							else
								c.hide();
						}
					}
				}, {
					xtype: 'displayfield',
					itemId: 'aws_availability_zone_warn',
					hidden: true,
					margin: {
						left: 3
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						afterrender: function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html: ('If you want to change placement, you need to remove Master EBS volume first on <a href="#/dbmsr/status?farmid=%FARMID%&type=mysql">MySQL status page</a>.').replace('%FARMID%', moduleTabParams.farmId)
							});
						}
					}
				}, {
					itemId: 'aws_availability_zone_loc',
					xtype: 'checkboxgroup',
					flex: 1,
					columns: [ 100, 100, 100, 100, 100, 100, 100 ],
					margin: {
						left: 3
					},
					hidden: true
				}]
			}, {
				xtype: 'combo',
				store: {
					fields: [ 'id', 'name' ],
					proxy: 'object'
				},
				valueField: 'name',
				displayField: 'name',
				fieldLabel: 'Instance type',
				editable: false,
				queryMode: 'local',
				name: 'aws.instance_type',
				width: 200
			}]
		}]
	});
});
