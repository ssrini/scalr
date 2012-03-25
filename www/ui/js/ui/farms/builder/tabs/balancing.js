Scalr.regPage('Scalr.ui.farms.builder.tabs.balancing', function () {
	return Ext.create('Scalr.ui.FarmsBuilderTab', {
		tabTitle: 'Load balancing options',
		layout: 'anchor',

		isEnabled: function (record) {
			return record.get('platform') == 'ec2' && !record.get('behaviors').match("cf_");
		},

		getDefaultValues: function (record) {
			return {
				'lb.use_elb': 0
			};
		},

		beforeShowTab: function (record, handler) {
			if (this.cacheExist(record.get('cloud_location')))
				handler();
			else
				Scalr.Request({
					processBox: {
						type: 'action'
					},
					url: '/platforms/ec2/xGetAvailZones',
					params: {
						cloudLocation: record.get('cloud_location')
					},
					success: function(data) {
						this.cacheSet(data.data, record.get('cloud_location'));
						handler();
					},
					scope: this,
					failure: function () {
						this.deactivateTab();
					}
				});
		},

		showTab: function (record) {
			var settings = record.get('settings'), availZones = this.cacheGet(record.get('cloud_location'));

			if (settings['lb.use_elb'] == 1) {
				this.down('[name="lb.use_elb"]').expand();
			} else {
				this.down('[name="lb.use_elb"]').collapse();
			}

			this.down('[name="lb.healthcheck.healthythreshold"]').setValue(settings['lb.healthcheck.healthythreshold'] || 3);
			this.down('[name="lb.healthcheck.interval"]').setValue(settings['lb.healthcheck.interval'] || 30);
			this.down('[name="lb.healthcheck.target"]').setValue(settings['lb.healthcheck.target'] || '');
			this.down('[name="lb.healthcheck.timeout"]').setValue(settings['lb.healthcheck.timeout'] || 5);
			this.down('[name="lb.healthcheck.unhealthythreshold"]').setValue(settings['lb.healthcheck.unhealthythreshold'] || 5);

			var avail = this.down('#lb_avail_zone');

			avail.removeAll();
			for (var i = 0; i < availZones.length; i++) {
				var n = 'lb.avail_zone.' + availZones[i].id;
				avail.add({
					xtype: 'checkbox',
					name: n,
					boxLabel: availZones[i].name,
					hideLabel: true,
					checked: (settings[n] || 0) == 1 ? true : false
				});
			}

			var data = [];
			for (i in settings) {
				if (i.indexOf('lb.role.listener.') != -1) {
					var lst = settings[i].split('#');
					data[data.length] = {
						protocol: lst[0],
						lb_port: lst[1],
						instance_port: lst[2],
						ssl_certificate: lst[3]
					};
				}
			}

			this.down('#listeners').store.load({ data: data });

			if (settings['lb.hostname'])
				this.down('#listeners').disable();
			else
				this.down('#listeners').enable();
		},

		hideTab: function (record) {
			var settings = record.get('settings'), avail = this.down('#lb_avail_zone');

			if (! this.down('[name="lb.use_elb"]').collapsed) {
				settings['lb.use_elb'] = 1;

				settings['lb.healthcheck.healthythreshold'] = this.down('[name="lb.healthcheck.healthythreshold"]').getValue();
				settings['lb.healthcheck.interval'] = this.down('[name="lb.healthcheck.interval"]').getValue();
				settings['lb.healthcheck.target'] = this.down('[name="lb.healthcheck.target"]').getValue();
				settings['lb.healthcheck.timeout'] = this.down('[name="lb.healthcheck.timeout"]').getValue();
				settings['lb.healthcheck.unhealthythreshold'] = this.down('[name="lb.healthcheck.unhealthythreshold"]').getValue();

				avail.items.each(function (item) {
					settings[item.name] = item.checked ? 1 : 0;
				});

				for (var i in settings) {
					if (i.indexOf('lb.role.listener.') != -1)
						delete settings[i];
				}

				var i = 0;
				this.down('#listeners').store.each(function (rec) {
					settings['lb.role.listener.' + i++] = [ rec.get('protocol'), rec.get('lb_port'), rec.get('instance_port'), rec.get('ssl_certificate') ].join("#");
				});

			} else {
				settings['lb.use_elb'] = 0;
			}

			record.set('settings', settings);
		},

		items: [{
			xtype: 'fieldset',
			title: 'Use <a target="_blank" href="http://aws.amazon.com/elasticloadbalancing/">Amazon Elastic Load Balancer</a> to balance load between instances of this role',
			name: 'lb.use_elb',
			checkboxToggle: true,
			collapsed: true,
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
						name: 'lb.healthcheck.healthythreshold',
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
						name: 'lb.healthcheck.interval',
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
						name: 'lb.healthcheck.target',
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
						name: 'lb.healthcheck.timeout',
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
									html:	'Amount of time (in seconds) during which no response means a failed health probe. <br />The default is five seconds and a valid value must be between 2 seconds and 60 seconds.' +
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
						name: 'lb.healthcheck.unhealthythreshold',
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
				xtype: 'fieldset',
				title: 'Availability zones',
				itemId: 'lb_avail_zone'
			}, {
				xtype: 'grid',
				anchor: '100%',
				title: 'Listeners',
				itemId: 'listeners',
				deferEmptyText: false,

				store: {
					proxy: 'object',
					fields: [ 'protocol', 'lb_port', 'instance_port' , 'ssl_certificate']
				},
				forceFit: true,
				plugins: {
					ptype: 'gridstore',
				},

				viewConfig: {
					emptyText: 'No listeners defined'
				},

				columns: [
					{ header: 'Protocol', flex: 150, sortable: true, dataIndex: 'protocol' },
					{ header: 'Load balancer port', flex: 280, sortable: true, dataIndex: 'lb_port' },
					{ header: 'Instance port', flex: 180, sortable: true, dataIndex: 'instance_port' },
					{ header: 'SSL certificate', flex: 200, sortable: true, dataIndex: 'ssl_certificate' },
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
									labelWidth: 120,
									editable: false,
									store: [ 'TCP', 'HTTP', 'SSL', 'HTTPS' ],
									queryMode: 'local',
									allowBlank: false,
									listeners: {
										change: function (field, value) {
											if (value == 'SSL' || value == 'HTTPS')
												this.next('[name="ssl_certificate"]').show().enable();
											else
												this.next('[name="ssl_certificate"]').hide().disable();
										}
									}
								}, {
									xtype: 'textfield',
									name: 'lb_port',
									fieldLabel: 'Load balancer port',
									labelWidth: 120,
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
									labelWidth: 120,
									allowBlank: false,
									validator: function (value) {
										if (value < 1 || value > 65535)
											return 'Valid instance ports are one (1) through 65535';
										else
											return true;
									}
								}, {
									xtype: 'combo',
									name: 'ssl_certificate',
									fieldLabel: 'SSL Certificate',
									labelWidth: 120,
									hidden: true,
									disabled: true,
									editable: false,
									allowBlank: false,
									store: {
										fields: [ 'name','path','arn','id','upload_date' ],
										proxy: {
											type: 'ajax',
											reader: {
												type: 'json',
												root: 'data'
											},
											url: '/tools/aws/iam/servercertificates/xListCertificates'
										},
										listeners: {
										load: function () {
											console.log(arguments);
										}}
									},
									valueField: 'arn',
									displayField: 'name'
								}],
								ok: 'Add',
								title: 'Add new listener',
								formValidate: true,
								closeOnSuccess: true,
								scope: this,
								success: function (formValues) {
									var view = this.up('#listeners'), store = view.store;

									if (store.findBy(function (record) {
										if (
											record.get('protocol') == formValues.protocol &&
											record.get('lb_port') == formValues.lb_port &&
											record.get('instance_port') == formValues.instance_port
										) {
											Scalr.message.Error('Such listener already exists');
											return true;
										}
									}) == -1) {
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
		}]
	});
});
