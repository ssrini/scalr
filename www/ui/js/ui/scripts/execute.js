Scalr.regPage('Scalr.ui.scripts.execute', function (loadParams, moduleParams) {
	var scriptId = moduleParams['scriptId'] || loadParams['scriptId'];

	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		width: 900,
		title: 'Execute script',
		fieldDefaults: {
			anchor: '100%',
			msgTarget: 'side'
		},

		items: [{
			xtype: 'fieldset',
			title: 'Execution target',
			layout: 'column',
			itemId: 'executionTarget',
			items: [{
				xtype: 'combo',
				hideLabel: true,
				name: 'farmId',
				store: {
					fields: [ 'id', 'name' ],
					data: moduleParams['farms'],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				emptyText: 'Select a farm',
				columnWidth: .33,
				editable: false,
				value: moduleParams['farmId'],
				queryMode: 'local',
				listeners: {
					select: function (field) {
						Scalr.Request({
							url: '/scripts/getFarmRoles/',
							params: { farmId: field.getValue() },
							processBox: {
								type: 'load',
								msg: 'Loading farm roles. Please wait ...'
							},
							success: function (data) {
								var field = form.down('[name="farmRoleId"]');
								field.show();
								if (Ext.isObject(data.farmRoles)) {
									field.emptyText = 'Select a role';
									field.reset();
									field.store.load({ data: data.farmRoles });
									field.setValue(0);
									field.enable();
								} else {
									field.store.removeAll();
									field.emptyText = 'No roles';
									field.reset();
									field.disable();
								}
								form.down('[name="serverId"]').hide();
							}
						});
					}
				}
			}, {
				xtype: 'combo',
				hideLabel: true,
				name: 'farmRoleId',
				hiddenName: 'farmRoleId',
				store: {
					fields: [ 'id', 'name', 'platform', 'role_id' ],
					data: moduleParams['farmRoles'],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				emptyText: 'Select a role',
				columnWidth: .33,
				margin: {
					left: 5
				},
				editable: false,
				value: moduleParams['farmRoleId'],
				queryMode: 'local',
				hidden: moduleParams['farmRoleId'] ? false : true,
				listeners: {
					select: function (field) {
						if (! field.getValue()) {
							form.down('[name="serverId"]').hide();
							return;
						}

						Scalr.Request({
							url: '/scripts/getServers/',
							params: { farmRoleId: field.getValue() },
							processBox: {
								type: 'load',
								msg: 'Loading servers. Please wait ...'
							},
							success: function (data) {
								var field = form.down('[name="serverId"]');
								field.show();
								if (Ext.isObject(data.servers)) {
									field.emptyText = 'Select a server';
									field.reset();
									field.store.load({ data: data.servers });
									field.setValue(0);
									field.enable();
								} else {
									field.emptyText = 'No running servers';
									field.reset();
									field.disable();
								}
							}
						});
					}
				}
			}, {
				xtype: 'combo',
				hideLabel: true,
				name: 'serverId',
				store: {
					fields: [ 'id', 'name' ],
					data: moduleParams['servers'],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				emptyText: 'Select a server',
				columnWidth: .33,
				margin: {
					left: 5
				},
				editable: false,
				value: moduleParams['serverId'],
				queryMode: 'local',
				hidden: moduleParams['serverId'] ? false : true
			}]
		}, {
			xtype: 'fieldset',
			title: 'Execution options',
			labelWidth: 100,
			fieldDefaults: {
				width: 150
			},
			items: [{
				xtype: 'combo',
				fieldLabel: 'Script',
				name: 'scriptId',
				store: {
					fields: [ 'id', 'name', 'description', 'issync', 'timeout', 'revisions' ],
					data: moduleParams['scripts'],
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				emptyText: 'Select a script',
				editable: false,
				value: scriptId,
				queryMode: 'local',
				listeners: {
					select: function () {
						var f = form.down('[name="scriptId"]'), r = f.store.findRecord('id', f.getValue()), fR = form.down('[name="scriptVersion"]');

						fR.store.load({ data: r.get('revisions')} );
						fR.store.sort('revision', 'DESC');

						if (!moduleParams['eventName'] || scriptId != f.getValue()) {
							fR.setValue(fR.store.getAt(0).get('revision'));

							form.down('[name="scriptTimeout"]').setValue(r.get('timeout'));
							form.down('[name="scriptIsSync"]').setValue(r.get('issync'));
						}

						fR.fireEvent('select');
					},
					afterrender: function () {
						if (scriptId)
							this.fireEvent('select');
					}
				}
			}, {
				xtype: 'combo',
				store: [ ['1', 'Synchronous'], ['0', 'Asynchronous']],
				editable: false,
				queryMode: 'local',
				name: 'scriptIsSync',
				value: moduleParams['scriptIsSync'],
				fieldLabel: 'Execution mode'
			}, {
				xtype: 'textfield',
				fieldLabel: 'Timeout',
				value: moduleParams['scriptTimeout'],
				name: 'scriptTimeout'
			},{
				xtype: 'combo',
				store: {
					fields: [{ name: 'revision', type: 'int' }, 'fields' ],
					proxy: 'object'
				},
				valueField: 'revision',
				displayField: 'revision',
				editable: false,
				queryMode: 'local',
				name: 'scriptVersion',
				value: moduleParams['scriptVersion'],
				fieldLabel: 'Version',
				listeners: {
					select: function () {
						var f = form.down('[name="scriptVersion"]');
						var r = f.store.findRecord('revision', f.getValue());
						var fields = r.get('fields'), fieldset = form.down('#scriptOptions');

						fieldset.removeAll();
						if (Ext.isObject(fields)) {
							for (var i in fields) {
								fieldset.add({
									xtype: 'textfield',
									fieldLabel: fields[i],
									name: 'scriptOptions[' + i + ']',
									value:moduleParams['scriptOptions'][i],
									//grow: true,
									//growMin: 10,
									//growMax: 100
								});
							}
							fieldset.show();
						} else {
							fieldset.hide();
						}
					}
				}
			}]
		}, {
			xtype: 'fieldset',
			title: 'Script options',
			itemId: 'scriptOptions',
			labelWidth: 100,
			hidden: true,
			fieldDefaults: {
				anchor: '100%'
			}
		}, {
			xtype: 'fieldset',
			title: 'Additional settings',
			labelWidth: 100,
			items: [{
				xtype: 'checkbox',
				hideLabel: true,
				boxLabel: 'Add a shortcut in Options menu for roles. It will allow me to execute this script with the above parameters with a single click.',
				name: 'createMenuLink',
				inputValue: 1,
				checked: loadParams['isShortcut'],
				disabled: loadParams['isShortcut'] || loadParams['eventName']
			}]
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
				text: (loadParams['isShortcut']) ? 'Save' : 'Execute',
				handler: function () {
					Scalr.Request({
						processBox: {
							type: 'action'
						},
						url: '/scripts/xExecute/',
						form: form.getForm(),
						success: function () {
							Scalr.event.fireEvent('close');
						}
					});
				},
				width: 80
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

	return form;
});
