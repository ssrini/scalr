Scalr.regPage('Scalr.ui.account.teams.create', function (loadParams, moduleParams) {
	var envStore = Ext.create('store.store', {
		fields: [ 'id', 'name' ],
		proxy: 'object'
	});
	envStore.loadData(moduleParams.envs);
	
	var userStore = Ext.create('store.store', {
		fields: [ 'id', 'email', 'fullname', 'groups', 'permissions' ],
		proxy: 'object'
	});
	userStore.loadData(moduleParams.users);
	
	var userTeamStore = Ext.create('store.store', {
		fields: [ 'id', 'email', 'fullname', 'groups', 'permissions' ],
		proxy: 'object'
	});
	if (moduleParams['team'])
		userTeamStore.loadData(moduleParams.team.users);
	
	var groupStore = Ext.create('store.store', {
		fields: [ 'id', 'name' ],
		proxy: 'object'
	});
	if (moduleParams['team'])
		groupStore.loadData(moduleParams.team.groups);
	
	var panel = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		bodyCls: 'scalr-ui-frame',
		title: moduleParams['team'] ? 'Account &raquo; Teams &raquo; Edit &raquo; ' + moduleParams['team']['name'] : 'Account &raquo; Teams &raquo; Create',
		scalrOptions: {
			maximize: 'all'
		},
		layout: {
			type: 'vbox',
			align: 'stretch'
		},
		items: [{
			xtype: 'fieldset',
			title: 'Params',
			items: [{
				xtype: 'fieldcontainer',
				layout: 'hbox',
				fieldLabel: 'Name',
				labelWidth: 40,
				items: [{
					xtype: 'textfield',
					name: 'teamName',
					value: moduleParams['team'] ? moduleParams['team']['name'] : '',
					readOnly: moduleParams['teamManage'] ? false : true,
					allowBlank: false,
					msgTarget: 'side',
					width: 300
				}/*, {
					xtype: 'button',
					text: 'Create',
					width: 80,
					hidden: !moduleParams['teamCreate'],
					margin: {
						left: 3
					}
				}, {
					xtype: 'button',
					text: 'Change',
					width: 80,
					hidden: moduleParams['teamCreate'],
					margin: {
						left: 3
					}
				}*/]
			}]
		}, {
			xtype: 'panel',
			flex: 1,
			layout: {
				type: 'hbox',
				align: 'stretch'
			},
			border: false,
			bodyCls: 'scalr-ui-frame',
			items: [{
				xtype: 'gridpanel',
				store: userStore,
				plugins: {
					ptype: 'gridstore'
				},
				enableColumnMove: false,
				viewConfig: {
					deferEmptyText: false,
					emptyText: "No users found",
					plugins: {
						ptype: 'gridviewdragdrop',
						dragGroup: 'firstGridDDGroup',
						dropGroup: 'secondGridDDGroup'
					},
					listeners: {
						beforedrop: function (el, data) {
							if (data.records.length) {
								for (var i = 0; i < data.records.length; i++) {
									if (data.records[i].get('permissions') == 'owner')
										return false;
								}
							}
						}
					}
				},
				flex: 2,
				title: 'Available Users',
				itemId: 'users',
				multiSelect: true,
				columns: [{
					text: 'ID',
					dataIndex: 'id',
					width: 80
				}, {
					text: 'Email',
					dataIndex: 'email',
					flex: 1
				}, {
					text: 'Full Name',
					dataIndex: 'fullname',
					flex: 1
				}],
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					items: [{
						icon: '/ui/images/icons/add_icon_16x16.png',
						cls: 'x-btn-icon',
						tooltip: 'Create new user',
						handler: function () {
							Scalr.event.fireEvent('redirect', '#/account/users/create');
						}
					}]
				}]
			}, {
				xtype: 'container',
				bodyCls: 'scalr-ui-frame',
				width: 15,
				border: false,
				html: '<div style="margin-top: -14px; position: absolute; top: 50%; padding-left: 4px; padding-right: 4px;">&raquo;<br>&laquo;</div>'
			}, {
				xtype: 'gridpanel',
				store: userTeamStore,
				plugins: {
					ptype: 'gridstore'
				},
				enableColumnMove: false,
				viewConfig: {
					deferEmptyText: false,
					emptyText: "No users found",
					plugins: {
						ptype: 'gridviewdragdrop',
						dragGroup: 'secondGridDDGroup',
						dropGroup: 'firstGridDDGroup'
					},
					listeners: {
						drop: function (el, data) {
							Ext.each(data.records, function (r) {
								r.set('groups', []);
								r.set('permissions', 'groups');
							});
						},
						itemclick: function (view, record, item, index, e) {
							if (moduleParams['teamManage'] && e.getTarget('input.teamOwner')) {
								if (record.get('permissions') != 'owner') {
									view.store.each(function(record) {
										if (record.get('permissions') == 'owner')
											record.set('permissions', 'group');
									});
									
									record.set('permissions', 'owner');
								}
							} else if (e.getTarget('input.permissionsFull')) {
								record.set('permissions', 'full');
							} else if (e.getTarget('input.permissionsGroups')) {
								record.set('permissions', 'groups');
							} else if (e.getTarget('a.manage')) {
								e.preventDefault();

								var rgroups = {};
								
								Ext.each(record.get('groups'), function (gr) {
									rgroups[gr['id']] = true;
								});
								
								if (! moduleParams['team']['groups'].length) {
									Scalr.message.Error('Please create permission groups first');
									return;
								}
								
								var groups = [];
								groupStore.each(function (rec) {
									groups.push({
										xtype: 'checkbox',
										boxLabel: rec.get('name'),
										inputValue: rec.get('name'),
										name: rec.get('id'),
										checked: rgroups[rec.get('id')] || false
									});
								});
								
								Scalr.Confirm({
									title: 'Please select permission groups',
									form: groups,
									formWidth: 200,
									ok: 'Save',
									success: function (data) {
										var result = [];
										for (var i in data)
											result.push({ id: i, name: data[i] });
										
										record.set('groups', result);
									}
								});
							}
						}
					}
				},
				flex: 3,
				title: 'Users in team',
				itemId: 'teamUsers',
				columns: [{
					text: 'Team lead',
					width: 75,
					disabled: moduleParams['teamManage'] ? false : true,
					xtype: 'templatecolumn',
					tpl: '<div class="<tpl if="permissions == &quot;owner&quot;">x-form-cb-checked</tpl>" style="text-align: center"><input type="button" class="x-form-field x-form-radio teamOwner"></div>'
				}, {
					text: 'ID',
					dataIndex: 'id',
					width: 40
				}, {
					text: 'Email',
					dataIndex: 'email',
					flex: 1
				}, {
					text: 'Fullname',
					dataIndex: 'fullname',
					flex: 1
				}, {
					text: 'Permissions',
					dataIndex: 'permissions',
					flex: 2,
					xtype: 'templatecolumn',
					disabled: moduleParams['team'] ? false : true,
					tpl: moduleParams['team'] ? new Ext.XTemplate(
						'<tpl if="permissions != &quot;owner&quot;">' +
							'<div class="<tpl if="permissions == &quot;full&quot;">x-form-cb-checked</tpl>" style="float: left;"><input type="button" class="x-form-field x-form-radio permissionsFull"> Full</div>' +
							'<tpl if="this.permissionsManage">' +
								'<div class="<tpl if="permissions == &quot;groups&quot;">x-form-cb-checked</tpl>" style="float: left; margin-left: 10px"><input type="button" class="x-form-field x-form-radio permissionsGroups"> Groups</div>' +
								
								'<tpl if="permissions == &quot;groups&quot;">' +
							
									'<tpl if="groups.length">' +
										': <tpl for="groups">' +
											'{name}' +
											'<tpl if="xindex < xcount">, </tpl>' +
										'</tpl> [<a href="#" class="manage">Manage</a>]' +
									'</tpl>' +
									'<tpl if="!groups.length">' +
										': <span style="color: red">No groups assigned</span> [<a href="#" class="manage">Assign</a>]' +
									'</tpl>' +
								'</tpl>' +
							'</tpl>' +
						'</tpl>' +
						'<tpl if="permissions == &quot;owner&quot;">' +
							'<div class="x-form-cb-checked"><input type="button" class="x-form-field x-form-radio"> Full</div>' +
						'</tpl>', { permissionsManage: moduleParams['permissionsManage'] }) : ''
				}]
			}]
		}, {
			xtype: 'panel',
			flex: 1,
			layout: {
				type: 'hbox',
				align: 'stretch'
			},
			border: false,
			height: 200,
			margin: {
				top: 5
			},
			bodyCls: 'scalr-ui-frame',
			items: [{
				xtype: 'gridpanel',
				flex: 2,
				store: envStore,
				plugins: {
					ptype: 'gridstore'
				},
				viewConfig: {
					deferEmptyText: false,
					emptyText: "No environments found"
				},
				disabled: moduleParams['teamManage'] ? false : true,
				title: 'Team has access to the following environments',
				itemId: 'environments',
				multiSelect: true,
				selType: 'checkboxmodel',
				columns: [{
					text: 'ID',
					dataIndex: 'id',
					width: 80
				}, {
					text: 'Name',
					dataIndex: 'name',
					flex: 1
				}],
				listeners: {
					afterrender: function () {
						if (moduleParams['team']) {
							var sm = this.getSelectionModel();
							Ext.each(moduleParams['team']['envs'], function (env) {
								sm.select(envStore.findRecord('id', env['id']), true);
							});
						}
					}
				}
			}, {
				xtype: 'container',
				bodyCls: 'scalr-ui-frame',
				width: 15,
				border: false,
				html: '&nbsp;'
			}, {
				xtype: 'gridpanel',
				flex: 3,
				store: groupStore,
				plugins: {
					ptype: 'gridstore'
				},
				viewConfig: {
					deferEmptyText: false,
					emptyText: "No permission groups found"
				},
				disabled: moduleParams['teamCreate'] || !moduleParams['permissionsManage'],
				title: 'Team has permission groups',
				itemId: 'groups',
				columns: [{
					text: 'ID',
					dataIndex: 'id',
					width: 80
				}, {
					text: 'Name',
					dataIndex: 'name',
					flex: 1
				}, {
					xtype: 'optionscolumn',
					optionsMenu: [{
						text: 'Edit',
						iconCls: 'scalr-menu-icon-edit',
						menuHandler: function (item) {
							Scalr.event.fireEvent('redirect', '#/account/teams/' + moduleParams['team']['id'] +
								'/permissionGroup?groupId=' + item.record.get('id'));
						}
					}, {
						text: 'Remove',
						iconCls: 'scalr-menu-icon-delete',
						request: {
							confirmBox: {
								type: 'delete',
								msg: 'Are you sure want to remove permission group "{name}" ?'
							},
							processBox: {
								type: 'delete'
							},
							dataHandler: function (record) {
								this.url = '/account/teams/' + moduleParams['team']['id'] + '/xRemovePermissionGroup';
								this.removeRecord = record;
								return { groupId: record.get('id') };
							},
							success: function () {
								groupStore.remove(this.removeRecord);
							}
						}
					}]
				}],
				dockedItems: [{
					xtype: 'toolbar',
					dock: 'top',
					items: [{
						icon: '/ui/images/icons/add_icon_16x16.png',
						cls: 'x-btn-icon',
						tooltip: 'Create new permission group',
						handler: function () {
							Scalr.Request({
								confirmBox: {
									title: 'Create new permission group',
									form: {
										xtype: 'textfield',
										fieldLabel: 'Name',
										name: 'name',
										allowBlank: false
									},
									formValidate: true
								},
								processBox: {
									type: 'save'
								},
								url: '/account/teams/' + moduleParams['team']['id'] + '/xCreatePermissionGroup/',
								success: function (data) {
									groupStore.add(data.group);
								}
							});
						}
					}]
				}]
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
				text: moduleParams['team'] ? 'Save' : 'Create',
				handler: function () {
					if (! panel.down('#environments').getSelectionModel().hasSelection()) {
						panel.getForm().isValid();
						Scalr.message.Error('Select at least one environment');
						return;
					} else {
						Scalr.message.Flush();
					}
					
					if (panel.getForm().isValid()) {
						var envs = [], users = [];
						Ext.each(panel.down('#environments').getSelectionModel().getSelection(), function (record) {
							envs.push(record.get('id'));
						});
						
						var isOwner = false;
						userTeamStore.each(function (record) {
							if (record.get('permissions') == 'owner')
								isOwner = true;

							users.push({
								'id': record.get('id'),
								'permissions': record.get('permissions'),
								'groups': record.get('groups')
							});
						});
						
						if (! isOwner) {
							Scalr.message.Error('Select team lead');
							return;
						} else {
							Scalr.message.Flush();
						}
						
						Scalr.Request({
							processBox: {
								type: 'save'
							},
							url: '/account/teams/xSave',
							params: {
								envs: Ext.encode(envs),
								users: Ext.encode(users),
								teamId: moduleParams['team'] ? moduleParams['team']['id'] : 0
							},
							form: panel.getForm(),
							success: function () {
								Scalr.event.fireEvent('redirect', '#/account/teams/view');
							}
						});
					}
				},
				width: 80
			}, {
				xtype: 'button',
				text: 'Cancel',
				margin: {
					left: 5
				},
				width: 80,
				handler: function () {
					Scalr.event.fireEvent('close');
				}
			}]
		}]
	});
	
	Scalr.event.on('update', function (type, user) {
		if (type == '/account/users/create') {
			userStore.add(user);
		}
	});
	
	return panel;
});
