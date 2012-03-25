Scalr.regPage('Scalr.ui.farms.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			{name: 'id', type: 'int'},
			{name: 'clientid', type: 'int'},
			'name', 'status', 'dtadded', 'running_servers', 'non_running_servers', 'roles', 'zones','client_email',
			'havemysqlrole','shortcuts', 'havepgrole', 'haveredisrole', 'haverabbitmqrole', 'havemongodbrole'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/farms/xListFarms'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Farms &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { farmId: '', clientId: '', status: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-farms-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No farms found'
		},

		columns: [
			{ text: "Farm ID", width: 70, dataIndex: 'id', sortable: true },
			{ text: "Farm Name", flex: 1, dataIndex: 'name', sortable: true },
			{ text: "Added", flex: 1, dataIndex: 'dtadded', sortable: true },
			{ text: "Roles", width: 100, dataIndex: 'roles', sortable: false, xtype: 'templatecolumn',
				tpl: '{roles} [<a href="#/farms/{id}/roles">View</a>]'
			},
			{ text: "Servers", width: 100, dataIndex: 'servers', sortable: false, xtype: 'templatecolumn',
				tpl: '<span style="color:green;">{running_servers}</span>/<span style="color:gray;">{non_running_servers}</span> [<a href="#/servers/view?farmId={id}">View</a>]'
			},
			{ text: "DNS zones", width: 100, dataIndex: 'zones', sortable: false, xtype: 'templatecolumn',
				tpl: '{zones} [<a href="#/dnszones/view?farmId={id}">View</a>]'
			},
			{ text: "Status", width: 100, dataIndex: 'status', sortable: true, xtype: 'templatecolumn', tpl:
				new Ext.XTemplate('<span style="color: {[this.getClass(values.status)]}">{[this.getName(values.status)]}</span>', {
					getClass: function (value) {
						if (value == 1)
							return "green";
						else if (value == 3)
							return "#666633";
						else
							return "red";
					},
					getName: function (value) {
						var titles = {
							1: "Running",
							0: "Terminated",
							2: "Terminating",
							3: "Synchronizing"
						};
						return titles[value] || value;
					}
				})
			}, {
				xtype: 'optionscolumn',
				getOptionVisibility: function (item, record) {
					var data = record.data;

					if (item.itemId == 'option.launchFarm')
						return (data.status == 0);

					if (item.itemId == 'option.terminateFarm')
						return (data.status == 1);

					if (item.itemId == 'option.scSep')
						return (data.shortcuts.length > 0);

					if (item.itemId == 'option.viewMap' ||
							item.itemId == 'option.viewMapSep' ||
							item.itemId == 'option.loadStats' ||
							item.itemId == 'option.mysqlSep' ||
							item.itemId == 'option.mysql' ||
							item.itemId == 'option.postgresql' ||
							item.itemId == 'option.redis' ||
							item.itemId == 'option.rabbitmq' ||
							item.itemId == 'option.mongodb' ||
							item.itemId == 'option.script'
						) {

						if (data.status == 0)
							return false;
						else
						{
							if (item.itemId == 'option.postgresql')
								return data.havepgrole;
							else if (item.itemId == 'option.redis')
								return data.haveredisrole;
							else if (item.itemId == 'option.mysql')
								return data.havemysqlrole;
							else if (item.itemId == 'option.rabbitmq')
								return data.haverabbitmqrole;
							else if (item.itemId == 'option.mongodb')
								return data.havemongodbrole;
							else
								return true;
						}
					}
					else
						return true;
				},

				beforeShowOptions: function (record, menu) {
					menu.items.each(function (item) {
						if (item.isshortcut) {
							menu.remove(item);
						}
					});

					if (record.get('shortcuts').length) {
						menu.add({
							xtype: 'menuseparator',
							isshortcut: true
						});

						Ext.Array.each(record.get('shortcuts'), function (shortcut) {
							if (typeof(shortcut) != 'function') {
								menu.add({
									isshortcut: true,
									text: 'Execute ' + shortcut.name,
									href: '#/scripts/execute?eventName=' + shortcut.event_name
								});
							}
						});
					}
				},

				optionsMenu: [/*{
					itemId: 'option.addToDash',
					text: 'Add to Dashboard',
					iconCls: 'scalr-menu-icon-launch',
					request: {
						url: '/dashboard/xUpdatePanel',
						dataHandler: function (record) {
							var data = Ext.encode({params: {farmId: record.get('id')}, name: 'dashboard.farm', url: '' });
							return {widget: data};
						},
						success: function (data, response, options) {
							Scalr.event.fireEvent('update', '/dashboard/update', data.panel);
						}
					}
				}, {
					xtype: 'menuseparator'
				}, */{
					itemId: 'option.launchFarm',
					text: 'Launch',
					iconCls: 'scalr-menu-icon-launch',
					request: {
						confirmBox: {
							type: 'launch',
							msg: 'Are you sure want to launch farm "{name}" ?'
						},
						processBox: {
							type: 'launch',
							msg: 'Launching farm. Please wait...'
						},
						url: '/farms/xLaunch/',
						dataHandler: function (record) {
							return { farmId: record.get('id') };
						},
						success: function () {
							store.load();
						}
					}
				}, {
					itemId: 'option.terminateFarm',
					iconCls: 'scalr-menu-icon-terminate',
					text: 'Terminate',
					request: {
						processBox: {
							type:'action'
						},
						url: '/farms/xGetTerminationDetails/',
						dataHandler: function (record) {
							return { farmId: record.get('id') };
						},
						success: function (data) {
							var items = [];
							for (var i = 0; i < data.roles.length; i++) {
								var t = {
									xtype: 'checkbox',
									cci: i,
									name: 'sync[]',
									fName: 'sync',
									inputValue: data.roles[i].id,
									boxLabel: '<b>' + data.roles[i].name + '</b> (Last synchronization: ' + data.roles[i].dtLastSync + ')',
									handler: function (checkbox, checked) {
										var c = this.ownerCt.query('[ci="' + checkbox.cci + '"]');
										for (var k = 0; k < c.length; k++) {
											if (checked)
												c[k].show();
											else
												c[k].hide();
										}

										var c = this.ownerCt.query('[fName="sync"]'), flag = false;
										for (var k = 0; k < c.length; k++) {
											if (c[k].checked) {
												flag = true;
												break;
											}
										}

										if (flag)
											this.up('fieldset').ownerCt.down('[name="unTermOnFail"]').show();
										else
											this.up('fieldset').ownerCt.down('[name="unTermOnFail"]').hide();
									}
								};

								if (data.roles[i].isBundleRunning) {
									t.disabled = true;
									items.push([ t, {
										xtype: 'displayfield',
										anchor: '100%',
										margin: {
											left: 20
										},
										value: 'Synchronization for this role already running ...'
									}]);
								} else if (! Ext.isArray(data.roles[i].servers)) {
									t.disabled = true;
									items.push([ t, {
										xtype: 'displayfield',
										anchor: '100%',
										margin: {
											left: 20
										},
										value: 'No running servers found on this role'
									}]);
								} else {
									var s = [];
									for (var j = 0; j < data.roles[i].servers.length; j++) {
										s[s.length] = {
											boxLabel: data.roles[i].servers[j].remoteIp + ' (' + data.roles[i].servers[j].server_id + ')',
											name: 'syncInstances[' + data.roles[i].id + ']',
											checked: j == 0 ? true : false, // select first
											inputValue: data.roles[i].servers[j].server_id
										};
									}

									items.push(t);

									items.push({
										xtype: 'radiogroup',
										hideLabel: true,
										columns: 1,
										hidden: true,
										ci: i,
										anchor: '100%',
										margin: {
											left: 20
										},
										items: s
									});
								}
							}

							Scalr.Request({
								confirmBox: {
									type: 'terminate',
									disabled: data.isMongoDbClusterRunning || false,
									msg: 'Hey mate! Have you made any modifications to your instances since you launched the farm? \'Cause if you did, you might want to save your modifications lest you lose them! Save them by taking a snapshot, which creates a machine image.',
									formWidth: 700,
									form: [{
										xtype: 'displayfield',
										hidden: !data.isMongoDbClusterRunning,
										cls: 'scalr-ui-form-field-warning',
										anchor: '100%',
										value: 'You currently have some Mongo instances in this farm. <br> Terminating it will result in <b>TOTAL DATA LOSS</b> (yeah, we\'re serious).<br/> Please <a href=\'#/services/mongodb/status?farmId='+data.farmId+'\'>shut down the mongo cluster</a>, then wait, then you\'ll be able to terminate the farm.'
									}, {
										xtype: 'displayfield',
										hidden: !data.isMysqlRunning,
										cls: 'scalr-ui-form-field-warning',
										anchor: '100%',
										value: 'The bundle will not include MySQL data. <a href=\'#/dbmsr/status?farmId='+data.farmId+'&type=mysql\'>Click here if you wish to bundle and save MySQL data</a>.'
									}, {
										xtype: 'fieldset',
										title: 'Synchronization settings',
										hidden: items.length ? false : true,
										items: items
									}, {
										xtype: 'fieldset',
										title: 'Termination options',
										items: [{
											xtype: 'checkbox',
											boxLabel: 'Do not terminate a farm if synchronization fail on any role',
											name: 'unTermOnFail',
											hidden: true
										}, {
											xtype: 'checkbox',
											boxLabel: 'Delete DNS zone from nameservers. It will be recreated when the farm is launched.',
											name: 'deleteDNSZones'
										}, {
											xtype: 'checkbox',
											boxLabel: 'Delete cloud objects (EBS, Elastic IPs, etc)',
											name: 'deleteCloudObjects'
										}]
									}]
								},
								processBox: {
									type: 'terminate'
								},
								url: '/farms/xTerminate',
								params: {farmId: data.farmId},
								success: function () {
									store.load();
								}
							});
						}
					}
				}, {
					xtype: 'menuseparator',
					itemId: 'option.controlSep'
				}, {
					itemId: 'option.info',
					iconCls: 'scalr-menu-icon-info',
					text: 'Extended information',
					href: "#/farms/{id}/extendedInfo"
				}, {
					itemId: 'option.usageStats',
					text: 'Usage statistics',
					iconCls: 'scalr-menu-icon-usage',
					href: '#/statistics/serversusage?farmId={id}'
				}, {
					itemId: 'option.loadStats',
					iconCls: 'scalr-menu-icon-stats',
					text: 'Load statistics',
					href: '#/monitoring/view?farmId={id}'
				}, {
					itemId: 'option.events',
					text: 'Events & Notifications',
					href: '#/farms/{id}/events'
				}, {
					xtype: 'menuseparator',
					itemId: 'option.mysqlSep'
				}, {
					itemId: 'option.mysql',
					iconCls: 'scalr-menu-icon-mysql',
					text: 'MySQL status',
					href: "#/dbmsr/status?farmId={id}&type=mysql"
				}, {
					itemId: 'option.postgresql',
					iconCls: 'scalr-menu-icon-postgresql',
					text: 'PostgreSQL status',
					href: "#/dbmsr/status?farmId={id}&type=postgresql"
				}, {
					itemId: 'option.redis',
					iconCls: 'scalr-menu-icon-redis',
					text: 'Redis status',
					href: "#/dbmsr/status?farmId={id}&type=redis"
				}, {
					itemId: 'option.rabbitmq',
					iconCls: 'scalr-menu-icon-rabbitmq',
					text: 'RabbitMQ status',
					href: "#/services/rabbitmq/status?farmId={id}"
				}, {
					itemId: 'option.mongodb',
					iconCls: 'scalr-menu-icon-mongodb',
					text: 'MongoDB status',
					href: "#/services/mongodb/status?farmId={id}"
				}, {
					itemId: 'option.script',
					iconCls: 'scalr-menu-icon-execute',
					text: 'Execute script',
					href: '#/scripts/execute?farmId={id}'
				}, {
					xtype: 'menuseparator',
					itemId: 'option.logsSep'
				}, {
					itemId: 'option.logs',
					iconCls: 'scalr-menu-icon-logs',
					text: 'View log',
					href: "#/logs/system?farmId={id}"
				}, {
					xtype: 'menuseparator',
					itemId: 'option.editSep'
				}, {
					itemId: 'option.edit',
					iconCls: 'scalr-menu-icon-configure',
					text: 'Edit',
					href: '#/farms/{id}/edit'
				}, {
					itemId: 'option.clone',
					iconCls: 'scalr-menu-icon-clone',
					text: 'Clone',
					request: {
						confirmBox: {
							type: 'action',
							msg: 'Are you sure want to clone farm "{name}" ?'
						},
						processBox: {
							type: 'action',
							msg: 'Cloning farm. Please wait...'
						},
						url: '/farms/xClone/',
						dataHandler: function (record) {
							return { farmId: record.get('id') };
						},
						success: function () {
							store.load();
						}
					}
				}, {
					itemId: 'option.delete',
					iconCls: 'scalr-menu-icon-delete',
					text: 'Delete',
					request: {
						confirmBox: {
							type: 'delete',
							msg: 'Are you sure want to remove farm "{name}" ?'
						},
						processBox: {
							type: 'delete',
							msg: 'Removing farm. Please wait...'
						},
						url: '/farms/xRemove/',
						dataHandler: function (record) {
							return { farmId: record.get('id') };
						},
						success: function () {
							store.load();
						}
					}
				}]
			}
		],

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: [ '-', {
				text: 'Filter',
				iconCls: 'scalr-ui-btn-icon-filter',
				menu: [{
					xtype: 'tbfilterfield',
					iconCls: 'no-icon',
					store: store
				}]
			}, '-', {
                icon: '/ui/images/icons/add_icon_16x16.png', // icons can also be specified inline
                cls: 'x-btn-icon',
                tooltip: 'Build new farm',
                handler: function() {
                    Scalr.event.fireEvent('redirect', '#/farms/build');
                }
            }]
		}]
	});
});
