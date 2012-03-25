Scalr.regPage('Scalr.ui.farms.builder.tabs.haproxy', function (moduleTabParams) {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'HAProxy options',
		layout: 'anchor',
		itemId: 'haproxy',

		isEnabled: function (record) {
			return record.get('behaviors').match("haproxy");
		},

		getDefaultValues: function (record) {
			return {
				'haproxy.healthcheck.unhealthyth': 5,
				'haproxy.healthcheck.timeout': 5,
				'haproxy.healthcheck.interval': 30,
				'haproxy.healthcheck.healthyth': 3
			};
		},

		showTab: function (record) {
			var settings = record.get('settings');

			this.down('[name="haproxy.healthcheck.healthyth"]').setValue(settings['haproxy.healthcheck.healthyth']);
			this.down('[name="haproxy.healthcheck.interval"]').setValue(settings['haproxy.healthcheck.interval']);
			this.down('[name="haproxy.healthcheck.target"]').setValue(settings['haproxy.healthcheck.target']);
			this.down('[name="haproxy.healthcheck.timeout"]').setValue(settings['haproxy.healthcheck.timeout']);
			this.down('[name="haproxy.healthcheck.unhealthyth"]').setValue(settings['haproxy.healthcheck.unhealthyth']);
			
			//this.down('[name="haproxy.backend.farm_roleid"]').setValue(settings['haproxy.backend.farm_roleid']);

			var rolesData = [];
			moduleTabParams.farmRolesStore.each(function(r){
				if (!r.get('new') && r != record)
					rolesData.push({id: r.get('farm_role_id'), name: r.get('name')});
			});

			var data = [];
			for (var i in settings) {
				if (i.indexOf('haproxy.listener.') != -1) {
					var lst = settings[i].split('#'), r = moduleTabParams.farmRolesStore.findRecord('farm_role_id', lst[3])
					data[data.length] = {
						protocol: lst[0],
						lb_port: lst[1],
						instance_port: lst[2],
						backend: lst[3],
						backend_name: r ? r.get('name') : ''
					};
				}
			}

			this.down('#listeners').store.load({ data: data });

			if (record.get('new'))
				this.down('#listeners').enable();
			else
				this.down('#listeners').disable();

			this.rolesData = rolesData;
		},

		hideTab: function (record) {
			var settings = record.get('settings');

			settings['haproxy.healthcheck.healthyth'] = this.down('[name="haproxy.healthcheck.healthyth"]').getValue();
			settings['haproxy.healthcheck.interval'] = this.down('[name="haproxy.healthcheck.interval"]').getValue();
			settings['haproxy.healthcheck.target'] = this.down('[name="haproxy.healthcheck.target"]').getValue();
			settings['haproxy.healthcheck.timeout'] = this.down('[name="haproxy.healthcheck.timeout"]').getValue();
			settings['haproxy.healthcheck.unhealthyth'] = this.down('[name="haproxy.healthcheck.unhealthyth"]').getValue();
			//settings['haproxy.backend.farm_roleid'] = this.down('[name="haproxy.backend.farm_roleid"]').getValue();

			for (var i in settings) {
				if (i.indexOf('haproxy.listener.') != -1)
					delete settings[i];
			}

			var i = 0;
			this.down('#listeners').store.each(function (rec) {
				settings['haproxy.listener.' + i++] = [ rec.get('protocol'), rec.get('lb_port'), rec.get('instance_port'), rec.get('backend') ].join("#");
			});

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			title: 'Healthcheck',
			defaults: {
				labelWidth: 130
			},
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Healthy Threshold',
				items: [{
					xtype: 'textfield',
					name: 'haproxy.healthcheck.healthyth',
					width: 40
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
								html: 'The number of consecutive health probe successes required before moving the instance to the Healthy state.<br />The default is 3 and a valid value lies between 2 and 10.'
							});
						}
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Interval',
				items: [{
					xtype: 'textfield',
					name: 'haproxy.healthcheck.interval',
					width: 40
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'seconds'
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
								html:	'The approximate interval (in seconds) between health checks of an individual instance.<br />The default is 30 seconds and a valid interval must be between 5 seconds and 600 seconds.' +
										'Also, the interval value must be greater than the Timeout value'
							});
						}
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Target',
				items: [{
					xtype: 'textfield',
					name: 'haproxy.healthcheck.target',
					width: 200
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
								html: 	'The instance being checked. The protocol is either TCP or HTTP. The range of valid ports is one (1) through 65535.<br />' +
										'Notes: TCP is the default, specified as a TCP: port pair, for example "TCP:5000".' +
										'In this case a healthcheck simply attempts to open a TCP connection to the instance on the specified port.' +
										'Failure to connect within the configured timeout is considered unhealthy.<br />' +
										'For HTTP, the situation is different. HTTP is specified as a "HTTP:port/PathToPing" grouping, for example "HTTP:80/weather/us/wa/seattle". In this case, a HTTP GET request is issued to the instance on the given port and path. Any answer other than "200 OK" within the timeout period is considered unhealthy.<br />' +
										'The total length of the HTTP ping target needs to be 1024 16-bit Unicode characters or less.'
							});
						}
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Timeout',
				items: [{
					xtype: 'textfield',
					name: 'haproxy.healthcheck.timeout',
					width: 40
				}, {
					xtype: 'displayfield',
					margin: {
						left: 3
					},
					value: 'seconds'
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
								html:	'Amount of time (in seconds) during which no response means a failed health probe. <br />The default is 5 seconds and a valid value must be between 2 seconds and 60 seconds.' +
										'Also, the timeout value must be less than the Interval value.'
							});
						}
					}
				}]
			}, {
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Unhealthy Threshold',
				items: [{
					xtype: 'textfield',
					name: 'haproxy.healthcheck.unhealthyth',
					width: 40
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
								html: 'The number of consecutive health probe failures that move the instance to the unhealthy state.<br />The default is 5 and a valid value lies between 2 and 10.'
							});
						}
					}
				}]
			}]
		}, {
			xtype: 'grid',
			anchor: '100%',
			title: 'Listeners',
			itemId: 'listeners',
			deferEmptyText: false,
			store: {
				proxy: 'object',
				fields: [ 'protocol', 'lb_port', 'instance_port', 'backend', 'backend_name' ]
			},
			forceFit: true,
			plugins: {
				ptype: 'gridstore'
			},

			viewConfig: {
				emptyText: 'No listeners defined'
			},

			columns: [
				{ header: 'Protocol', flex: 150, sortable: true, dataIndex: 'protocol' },
				{ header: 'Load balancer port', flex: 280, sortable: true, dataIndex: 'lb_port' },
				{ header: 'Instance port', flex: 180, sortable: true, dataIndex: 'instance_port' },
				{ header: 'Backend role', flex: 180, sortable: true, dataIndex: 'backend_name' },
				{ header: '&nbsp;', width: 20, sortable: false, dataIndex: 'id', align:'center', xtype: 'templatecolumn',
					tpl: '<img class="delete" src="/ui/images/icons/delete_icon_16x16.png">', clickHandler: function (comp, store, record) {
						store.remove(record);
					}
				}
			],

			listeners: {
				itemclick: function (view, record, item, index, e) {
					if (e.getTarget('img.delete'))
						view.store.remove(record);
				}
			},

			dockedItems: [{
				xtype: 'toolbar',
				dock: 'top',
				layout: {
					type: 'hbox',
					align: 'left',
					pack: 'start'
				},
				items: [{
					icon: '/ui/images/icons/add_icon_16x16.png', // icons can also be specified inline
					cls: 'x-btn-icon',
					tooltip: 'Add new listener',
					handler: function () {
						Scalr.Confirm({
							form: [{
								xtype: 'combo',
								name: 'protocol',
								fieldLabel: 'Protocol',
								labelWidth: 150,
								editable: false,
								store: [ 'TCP', 'HTTP' ],
								queryMode: 'local',
								allowBlank: false
							}, {
								xtype: 'textfield',
								name: 'lb_port',
								fieldLabel: 'Load balancer port',
								labelWidth: 150,
								allowBlank: false,
								validator: function (value) {
									if (value < 1024 || value > 65535) {
										if (value != 80 && value != 443)
											return 'Valid LoadBalancer ports are - 80, 443 and 1024 through 65535';
									}
									return true;
								}
							}, {
								xtype: 'textfield',
								name: 'instance_port',
								fieldLabel: 'Instance port',
								labelWidth: 150,
								allowBlank: false,
								validator: function (value) {
									if (value < 1 || value > 65535)
										return 'Valid instance ports are one (1) through 65535';
									else
										return true;
								}
							}, {
								xtype: 'combo',
								store: {
									fields: [ 'id', 'name' ],
									proxy: 'object',
									data: this.up('#haproxy').rolesData
								},
								valueField: 'id',
								displayField: 'name',
								fieldLabel: 'Backend farm role',
								editable: false,
								allowBlank: false,
								labelWidth: 150,
								queryMode: 'local',
								name: 'backend'
							}],
							ok: 'Add',
							title: 'Add new listener',
							formValidate: true,
							closeOnSuccess: true,
							scope: this,
							success: function (formValues, form) {
								var view = this.up('#listeners'), store = view.store;

								if (store.findBy(function (record) {
									if (
										record.get('protocol') == formValues.protocol &&
										record.get('lb_port') == formValues.lb_port &&
										record.get('instance_port') == formValues.instance_port &&
										record.get('backend') == formValues.backend
									) {
										Scalr.message.Error('Such listener already exists');
										return true;
									}
								}) == -1) {
									formValues['backend_name'] = form.down('[name="backend"]').findRecordByValue(formValues['backend']).get('name');
									store.add(formValues);
									return true;
								} else {
									return false;
								}
							}
						});
					}
				}]
			}]
		}]
	});
});
