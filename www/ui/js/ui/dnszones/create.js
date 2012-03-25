Scalr.regPage('Scalr.ui.dnszones.create', function (loadParams, moduleParams) {
	var zone = moduleParams['zone'], records = moduleParams['records'] || [];
	var systemRecords = [];

	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		title: (zone['domainId'] || 0) ? 'DNS Zones &raquo; Edit' : 'DNS Zones &raquo; Create',
		items: [{
			xtype: 'fieldset',
			title: 'Domain name',
			layout: 'column',
			items: [{
				xtype: 'combo',
				store: [ [ 'scalr', 'Use domain automatically generated and provided by Scalr'], [ 'own', 'Use own domain name'] ],
				editable: false,
				columnWidth: 0.5,
				name: 'domainType',
				value: zone['domainType'],
				hidden: moduleParams['action'] == 'create' ? false : true,
				listeners: {
					change: function () {
						var field = form.query('textfield[name="domainName"]')[0];
						if (this.getValue() == 'own') {
							field.enable();
							field.setValue('');
						} else {
							field.disable();
							field.setValue(zone['domainName']);
						}
					}
				}
			}, {
				xtype: 'displayfield',
				width: 10,
				hidden: moduleParams['action'] == 'create' ? false : true
			},{

				xtype: 'textfield',
				name: 'domainName',
				disabled: zone['domainType'] == 'scalr' ? true : false,
				value: zone['domainName'],
				hidden: moduleParams['action'] == 'create' ? false : true,
				columnWidth: 0.5
			}, {
				xtype: 'displayfield',
				cls: 'x-form-check-wrap',
				value: zone['domainName'],
				hidden: moduleParams['action'] == 'edit' ? false : true,
				columnWidth: 1
			}]
		}, {
			xtype: 'fieldset',
			title: 'Automatically create A records for',
			items: [{
				xtype: 'fieldcontainer',
				fieldLabel: 'Farm',
				labelWidth: 80,
				layout: 'hbox',
				items: [{
					xtype: 'combo',
					store: {
						fields: [ 'id', 'name' ],
						data: moduleParams['farms'],
						proxy: 'object'
					},
					queryMode: 'local',
					editable: false,
					name: 'domainFarm',
					value: zone['domainFarm'] != '0' ? zone['domainFarm'] : '',
					valueField: 'id',
					displayField: 'name',
					width: 300,
					listeners: {
						change: function (field, value) {
							if (value) {
								Scalr.Request({
									processBox: {
										type: 'load',
										msg: 'Loading farm roles ...'
									},
									url: '/dnszones/xGetFarmRoles/',
									params: { farmId: value },
									success: function (data) {
										form.down('[name="domainFarmRole"]').setValue('');
										form.down('[name="domainFarmRole"]').store.load({ data: data.farmRoles });
									}
								});
							} else {
								form.query('combobox[name="domainFarmRole"]')[0].store.loadData([]);
								form.query('combobox[name="domainFarmRole"]')[0].setValue('');
							}
						}
					}
				}, {
					xtype: 'displayfield',
					padding: {
						left: 5
					},
					value: '<i>Each server in this farm will add int-rolename ext-rolename records. Leave blank if you don\'t need such records.</i>'
				}]
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'Role',
				labelWidth: 80,
				layout: 'hbox',
				items: [{
					xtype: 'combo',
					store: {
						fields: [ 'id', 'name', 'platform', 'role_id' ],
						data: moduleParams['farmRoles'],
						proxy: 'object'
					},
					queryMode: 'local',
					editable: false,
					name: 'domainFarmRole',
					value: zone['domainFarmRole'] != '0' ? zone['domainFarmRole'] : '',
					valueField: 'id',
					displayField: 'name',
					width: 300
				}, {
					xtype: 'displayfield',
					value: '<i>Servers of this role will create root records. Leave blank to add root records manually.</i>',
					padding: {
						left: 5
					}
				}]
			}]
		}, {
			xtype: 'fieldset',
			title: 'SOA settings',
			items: [{
				xtype: 'fieldcontainer',
				fieldLabel: 'SOA Retry',
				labelWidth: 80,
				layout: 'hbox',
				items: [{
					xtype: 'combo',
					store: [['300', '5 minutes'], ['900', '15 minutes'], [ '1800', '30 minutes' ], [ '3600', '1 hour' ], [ '7200', '2 hours' ], [ '14400', '4 hours' ], [ '28800', '8 hours' ], [ '86400', '1 day' ]],
					editable: false,
					name: 'soaRetry',
					width: 150,
					value: zone['soaRetry']
				}, {
					xtype: 'displayfield',
					padding: {
						left: 5
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						'afterrender': function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html: 'Signed 32 bit value in seconds. Defines the time between retries if the slave (secondary) fails to contact the master when refresh (above) has expired. Typical values would be 180 (3 minutes) to 900 (15 minutes) or higher.'
							});
						}
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'SOA refresh',
				labelWidth: 80,
				layout: 'hbox',
				items: [{
					xtype: 'combo',
					store: [[ '3600', '1 hour' ], [ '7200', '2 hours' ], [ '14400', '4 hours' ], [ '28800', '8 hours' ], [ '86400', '1 day' ]],
					editable: false,
					name: 'soaRefresh',
					width: 150,
					value: zone['soaRefresh']
				}, {
					xtype: 'displayfield',
					padding: {
						left: 5
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						'afterrender': function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.down('img.tipHelp'),
								dismissDelay: 0,
								html: 'Signed 32 bit value in seconds. Indicates when the zone data is no longer authoritative. Used by Slave or (Secondary) servers only. BIND9 slaves stop responding to queries for the zone when this time has expired and no contact has been made with the master. Thus every time the refresh values expires the slave will attempt to read the SOA record from the zone master - and request a zone transfer AXFR/IXFR if sn is HIGHER. If contact is made the expiry and refresh values are reset and the cycle starts again. If the slave fails to contact the master it will retry every retry period but continue to supply authoritative data for the zone until the expiry value is reached at which point it will stop answering queries for the domain. RFC 1912 recommends 1209600 to 2419200 seconds (2-4 weeks) to allow for major outages of the zone master.'
							});
						}
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				fieldLabel: 'SOA expire',
				labelWidth: 80,
				layout: 'hbox',
				items: [{
					xtype: 'combo',
					store: [[ '86400', '1 day' ], [ '259200', '3 days' ], [ '432000', '5 days' ], [ '604800', '1 week' ], [ '3024000', '5 weeks' ], [ '6048000', '10 weeks' ] ],
					editable: false,
					name: 'soaExpire',
					width: 150,
					value: zone['soaExpire']
				}, {
					xtype: 'displayfield',
					padding: {
						left: 5
					},
					value: '<img class="tipHelp" src="/ui/images/icons/info_icon_16x16.png" style="cursor: help;">',
					listeners: {
						'afterrender': function () {
							Ext.create('Ext.tip.ToolTip', {
								target: this.el.query('img.tipHelp')[0],
								dismissDelay: 0,
								html: 'Signed 32 bit time value in seconds. Indicates the time when the slave will try to refresh the zone from the master (by reading the master DNS SOA RR). RFC 1912 recommends 1200 to 43200 seconds, low (1200) if the data is volatile or 43200 (12 hours) if it\'s not. If you are using NOTIFY you can set for much higher values, for instance, 1 or more days (> 86400 seconds).'
							});
						}
					}
				}]
			}]
		}, {
			xtype: 'fieldset',
			labelWidth: 100,
			title: 'DNS Records',
			itemId: 'dnsRecords'
		}, {
			xtype: 'fieldset',
			title: 'System DNS Records',
			hidden: true,
			itemId: 'systemDnsRecords',
			collapsible: true,
			collapsed: true,
			autoHeight: true
		}],
		dockedItems: [{
			xtype: 'container',
			dock: 'bottom',
			cls: 'scalr-ui-docked-bottombar',
			layout: {
				type: 'hbox',
				pack: 'center'
			},
			items: [{
				xtype: 'button',
				text: 'Save',
				width: 80,
				handler: function() {
					if (form.getForm().isValid()) {	
						var results = {};					
						form.child('#dnsRecords').items.each(function (item) {
							if (item.isEmpty())
								form.child('#dnsRecords').remove(item);
							else{
								results[item.getName()] = item.getValue();
								item.clearStatus();
							}
						});
						Scalr.Request({
							processBox: {
								type: 'save'
							},
							form: form.getForm(),
							url: '/dnszones/xSave/',
							scope: this,
							params: {
								domainId: zone['domainId'] || 0,
								records: Ext.encode(results)
							},
							success: function () {
								Scalr.event.fireEvent('redirect', '#/dnszones/view', true);
							},
							failure: function() {
								this.up('form').down('#dnsRecords').add({
									xtype: 'dnsfield',
									showAddButton: true
								});
							}
						});
					}
				}
			}, {
				xtype: 'button',
				width: 80,
				margin: {
					left: 5
				},
				text: 'Cancel',
				handler: function() {
					Scalr.event.fireEvent('close');
				}
			}]
		}]
	});

	Ext.each(records, function(item) {
		if (item.issystem == '1') {
			systemRecords.push(item);
		} else
			form.down('#dnsRecords').add({
				showRemoveButton: true,
				xtype: 'dnsfield',
				value: item,
				zone: zone['domainName'],
				readOnly: item.issystem == '1' && moduleParams['allowManageSystemRecords'] != '1'
			});
	});

	form.down('#dnsRecords').add({
		xtype: 'dnsfield',
		zone: zone['domainName'],
		showAddButton: true
	});
	
	if (systemRecords.length)
		form.down('#systemDnsRecords').show();

	form.down('#systemDnsRecords').on('afterlayout', function () {
		Ext.Function.defer(function () {
			this.hide();
			var msg = Scalr.utils.CreateProcessBox({
				type: 'action'
			});

			Ext.Function.defer(function (msg) {
				var table = this.body.createChild({
					tag: 'div',
					style: {
						display: 'table'
					}
				});

				Ext.each(systemRecords, function (item) {
					var column = table.createChild({
						tag: 'div',
						style: {
							display: 'table-row'
						}
					});

					column.createChild({
						tag: 'div',
						style: {
							display: 'table-cell',
							padding: '4px'
						}
					}).update(item['name']);

					column.createChild({
						tag: 'div',
						style: {
							display: 'table-cell',
							padding: '4px',
							'padding-left': '10px'
						}
					}).update(item['ttl']);

					column.createChild({
						tag: 'div',
						style: {
							display: 'table-cell',
							padding: '4px',
							'padding-left': '10px'
						}
					}).update(item['type']);

					column.createChild({
						tag: 'div',
						style: {
							display: 'table-cell',
							padding: '4px',
							'padding-left': '10px'
						}
					}).update(item['value']);
				}, this);
				this.show();
				msg.close();
			}, 50, this, [ msg ]);
		}, 50, form.down('#systemDnsRecords'));
	}, form, {
		single: true
	});

	return form;
});
