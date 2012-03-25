Scalr.regPage('Scalr.ui.services.chef.runlists.create', function (loadParams, moduleParams) {
	var rolesStore = Ext.create('store.store', {
		fields: ['name', 'chef_type'],
		proxy: {
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'data'
			},
			url: '/services/chef/xListRoles',
			extraParams: {servId: 0, chefEnv: ''}
		},
		remoteSort: true
	});
	
	var recipesStore = Ext.create('store.store', {
		fields: ['name', 'cookbook'],
		proxy: {
			type: 'ajax',
			reader: {
				type: 'json',
				root: 'data'
			},
			url: '/services/chef/xListAllRecipes',
			extraParams: {servId: 0, chefEnv: ''}
		},
		remoteSort: true
	});
	var form = Ext.create('Ext.form.Panel', {
		bodyPadding: 5,
		width: 850,
		bodyCls: 'scalr-ui-frame',
		title: loadParams['runlistId'] ? 'Edit RunList' : 'Create new RunList',
		scalrOptions: {
			'modal': true
		},
		items: [{
			xtype: 'fieldset',
			title: 'General',
			defaults:{
				labelWidth: 150,
				width: 800
			},
			items: [{
				xtype: 'hidden',
				name: 'runlistId',
				value: loadParams['runlistId'] ? loadParams['runlistId']: ''
			},{
				xtype: 'hidden',
				name: 'runListAttrib'
			},{
				xtype: 'textfield',
				readOnly: loadParams['runlistId'] ? true : false,
				fieldLabel: 'Name',
				itemId: 'runListName',
				name: 'runListName',
				allowBlank: false,
				validator: function(value) {
					var reg = /^([a-zA-Z0-9])([\w,-])*([a-zA-Z0-9])$/;
					if (!reg.test(value))
						return 'Runlist name must contain [a-z,A-Z,0-9,_,-] and start/end with [a-z,A-Z,0-9]';
					return true;
				}
			},{
				xtype: 'textfield',
				fieldLabel: 'Description',
				itemId: 'runListDescription',
				name: 'runListDescription'
			},{
				xtype: 'container',
				layout: 'hbox',
				items: [{
					xtype: 'combo',
					labelWidth: 150,
					width: 772,
					fieldLabel: 'Chef Server',
					itemId: 'chefServer',
					name: 'chefServer',
					editable: false,
					valueField: 'id',
					value: moduleParams['runlistParams'] ? moduleParams['runlistParams']['chefServer'] : '',
					displayField: 'url',
					queryMode: 'local',
					store: {
						fields: ['url', 'id'],
						proxy: {
							type: 'ajax',
							reader: {
								type: 'json',
								root: 'data'
							},
							url: '/services/chef/servers/xListServers/'
						}
					},
					listeners: {
						change: function (field, newValue, oldValue) {
							form.down('#chefEnv').enable();
                            form.down('#chefEnv').store.proxy.extraParams.servId = newValue;
							rolesStore.proxy.extraParams.servId = newValue;
							recipesStore.proxy.extraParams.servId = newValue;
							form.down('#chefEnv').store.load(function () {
								var old = form.down('#chefEnv').getValue();
								form.down('#chefEnv').setValue('_default');
								if (old == '_default') {
			                        form.down('#recipesGrid').disable();
			                        form.down('#rolesGrid').enable();
			                        rolesStore.load(function (){
			                        	form.down('#recipesGrid').enable();
			                        	recipesStore.load();
			                        });
								}
							});
						}
					}	
				},{
					xtype: 'button',
					icon: '/ui/images/icons/add_icon_16x16.png',
					cls: 'x-btn-icon',
					tooltip: 'Add new Server',
					margin: {
						left: 3
					},
					listeners: {
						click: function() {
							Scalr.event.fireEvent('redirect','#/services/chef/servers/create');
						}
					}
				}]
			},{
                xtype: 'combo',
                labelWidth: 150,
                width: 772,
                fieldLabel: 'Chef Environment',
                disabled: loadParams['runlistId'] ? false : true,
                itemId: 'chefEnv',
                name: 'chefEnv',
                editable: false,
                displayField: 'name',
                valueField: 'name',
                queryMode: 'local',
                store: {
                    fields: ['name'],
                    proxy: {
                        type: 'ajax',
                        reader: {
                            type: 'json',
                            root: 'data'
                        },
                        extraParams: {servId: 0},
                        url: '/services/chef/servers/xListEnvironments/'
                    }
                },
                listeners: {
                    change: function (field, newValue, oldValue) {
						form.down('#rolesGrid').disable();
						form.down('#recipesGrid').disable();
                        rolesStore.proxy.extraParams.chefEnv = newValue;
                        recipesStore.proxy.extraParams.chefEnv = newValue;
                        form.down('#rolesGrid').enable();
                        rolesStore.load(function () {
                        	form.down('#recipesGrid').enable();
                        	recipesStore.load();
                        });
                    }
                }
            }]
		},{
			xtype: 'container',
			layout: {
				type: 'hbox'
			},
			items: [{
				xtype: 'container',
				defaults: {
					height: 200,
					width: 405
				},
				flex: 1,
				items: [{
					xtype: 'gridpanel',
					title: 'Roles',
					itemId: 'rolesGrid',
					disabled: loadParams['runlistId'] ? false : true,
					plugins: {
						ptype: 'gridstore',
						loadMask: true
					},
					viewConfig: {
						deferEmptyText: false,
						emptyText: 'No roles found',
						plugins: {
							ptype: 'gridviewdragdrop',
							dragGroup: 'runList'
						}
					},
					store: rolesStore,
					columns: [{
						text: 'Name',
						flex: 2,
						dataIndex: 'name'
					}]
				},{
					margin: {
						top: 5
					},
					xtype: 'gridpanel',
					title: 'Recipes',
					itemId: 'recipesGrid',
					disabled: loadParams['runlistId'] ? false : true,
					store: recipesStore,
					plugins: {
						ptype: 'gridstore',
						loadMask: true
					},
					viewConfig: {
						deferEmptyText: false,
						emptyText: 'No recipes found',
						plugins: {
							ptype: 'gridviewdragdrop',
							dragGroup: 'runList'
						}
					},
					columns: [{
						xtype: 'templatecolumn',
						text: 'Name',
						dataIndex: 'name',
						flex: 1,
						tpl: '{cookbook}::{name}'
					}]
				}]
			},{
				xtype: 'container',
				margin: {
					left: 5
				},
				height: 405,
				width: 403,
				flex: 1,
				title: 'RunList',
				items: [{
						xtype: 'checkbox',
						boxLabel: 'Source mode',
						itemId: 'sourceMode',
						listeners: {
							change: function (field, newValue, oldValue) {
								if(newValue){
									form.down('#runlistSource').show();
									form.down('#runList').hide();
									var data = [];
									Ext.each(this.up('panel').down('#runList').store.getRange(), function(item){
										if(item.get('chef_type'))
											data.push('role[' + item.get('name') + ']');
										if(item.get('cookbook'))
											data.push('recipe[' + item.get('cookbook') + '::' + item.get('name') + ']');
										else if(!item.get('cookbook') && !item.get('chef_type'))
											data.push(item.get('name'));
									});
									var runList = Ext.encode(data);
									form.down('#runlistSource').setValue(runList);
								}
								else {
									form.down('#runlistSource').hide();
									form.down('#runList').show();
									form.down('#runList').store.removeAll();
									Ext.each(Ext.decode(form.down('#runlistSource').getValue()), function(item){
										form.down('#runList').store.add({name: item});
									});
								}
							}
						}
					},{
					xtype: 'gridpanel',
					itemId: 'runList',
					width: 414,
					height: '94%',
					hidden: false,
					store:{
						fields: ['name']
					},
					viewConfig: {
						deferEmptyText: false,
						emptyText: "<center><font color='green'>Drag recipes and roles to create Runlist</font></center>",
						plugins: {
							ptype: 'gridviewdragdrop',
							ddGroup: 'runList'
						}
					},
					columns: [{
						xtype: 'templatecolumn',
						tpl: '<tpl if="cookbook">recipe[{cookbook}::</tpl><tpl if="chef_type">role[</tpl>{name}<tpl if="cookbook">]</tpl><tpl if="chef_type">]</tpl>',
						flex: 1,
						text: 'Name',
						dataIndex: 'name'
					}, {
						xtype: 'templatecolumn',
						tpl: '<img class="delete" src="/ui/images/icons/delete_icon_16x16.png">', 
						text: '&nbsp;', 
						width: 35, 
						sortable: false, 
						dataIndex: 'id', 
						align:'center'
					}],
					listeners: {
						itemclick: function (view, record, item, index, e) {
							if (e.getTarget('img.delete'))
								view.store.remove(record);
						}
					}
				},{
					xtype: 'textareafield',
					itemId: 'runlistSource',
					hidden: true,
					width: 414,
					border: false,
					height: '98%'
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
				text: 'Save',
				formBind: true,
				width: 80,
				handler: function() {
					var data = [];
					var runList = '';
					if(form.down('#sourceMode').checked) {
						runList = form.down('#runlistSource').getValue();
					}
					else {
						Ext.each(this.up('panel').down('#runList').store.getRange(), function(item){
							if(item.get('chef_type'))
								data.push('role[' + item.get('name') + ']');
							if(item.get('cookbook'))
								data.push('recipe[' + item.get('cookbook') + '::' + item.get('name') + ']');
							else if(!item.get('cookbook') && !item.get('chef_type'))
								data.push(item.get('name'));
						});
						runList = Ext.encode(data);
					}
					
					Scalr.Request({
						processBox: {
							msg: 'Saving',
							type: 'save'
						},
						scope: this,
						form: form.getForm(),
						url: '/services/chef/runlists/xSaveRunList',
						params: {runList: runList},
						success: function (data) {
							if(loadParams.farmId)
								Scalr.event.fireEvent('update', '/farms/edit', data.runlistParams, loadParams['runlistId'] ? 'update' : 'create');
							else
								Scalr.event.fireEvent('update', '/farms/build', data.runlistParams, loadParams['runlistId'] ? 'update' : 'create');
							Scalr.event.fireEvent('close');
						}
					});
				}
			},{
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
	if (moduleParams['runlistParams']) {
		form.down('#chefServer').store.load(function (records) {
			var isExist = false;
			for (var i = 0 ; i< records.length; i++) {
				if(records[i].get('id') == moduleParams['runlistParams']['chefServer'])
					isExist = true;
			}
			if (!isExist)
				moduleParams['runlistParams']['chefServer'] = 'Server not found';
			form.getForm().setValues(moduleParams['runlistParams']);
		});
		rolesStore.proxy.extraParams.servId = moduleParams['runlistParams']['chefServer'];
		recipesStore.proxy.extraParams.servId = moduleParams['runlistParams']['chefServer'];
        form.down('#chefEnv').store.proxy.extraParams.servId = moduleParams['runlistParams']['chefServer'];
        form.down('#chefEnv').store.load();
		form.down('#runList').store.add(moduleParams['runlistParams']['runlist']);
	} else form.down('#chefServer').store.load();
	return form;
});