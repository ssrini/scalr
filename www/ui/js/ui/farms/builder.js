/*

		if (this.farmId != '0')
			this.panel.setActiveTab('roles');

		// auto select role in farm
		if (this.farmRoleId != '0') {
			this.panel.setActiveTab('roles');
			this.panel.getComponent('roles').getComponent('roles').dataView.select(this.farmRolesStore.find('role_id', this.farmRoleId));
		}
*/

Scalr.regPage('Scalr.ui.farms.builder', function (loadParams, moduleParams) {
	var farmRolesStore = Ext.create('store.store', {
		fields: [
			'id',
			{ name: 'new', type: 'boolean' },
			'role_id',
			'platform',
			'generation',
			'farm_role_id',
			'cloud_location',
			'arch',
			'name',
			'group',
			'behaviors',
			'launch_index',
			'is_bundle_running',
			'settings',
			'scaling',
			'scripting',
			'config_presets',
			'tags'
		],
		proxy: 'object',
		data: moduleParams.farm ? moduleParams.farm.roles : []
	});

	var saveHandler = function (farm) {
		var p = {};
		farm = farm || {};

		panel.down('#farmRoles').getSelectionModel().deselectAll();
		panel.down('#farmRoles').clearFilter();

		farm['farmId'] = moduleParams['farmId'];

		p['name'] = panel.down('#farmName').getValue();
		p['description'] = panel.down('#farmDescription').getValue();
		p['rolesLaunchOrder'] = 0;
		farm['farm'] = Ext.encode(p);

		p = [];
		farmRolesStore.each(function (rec) {
			var settings = rec.get('settings'), sets = {};

			sets = {
				role_id: rec.get('role_id'),
				farm_role_id: rec.get('farm_role_id'),
				launch_index: rec.get('launch_index'),
				platform: rec.get('platform'),
				cloud_location: rec.get('cloud_location'),
				settings: rec.get('settings'),
				scaling: rec.get('scaling'),
				scripting: rec.get('scripting'),
				config_presets: rec.get('config_presets')
			};

			if (Ext.isObject(rec.get('params'))) {
				sets['params'] = rec.get('params');
			}

			p[p.length] = sets;
		});

		farm['roles'] = Ext.encode(p);
		farm['v2'] = 1;
		Scalr.Request({
			processBox: {
				type: 'save'
			},
			url: '/farms/builder/xBuild',
			params: farm,
			success: function (data) {
				Scalr.event.fireEvent('redirect', '#/farms/' + data.farmId + '/view', true);
			}
		});
	}

	var panel = Ext.create('Ext.tab.Panel', {
		scalrOptions: {
			'maximize': 'all'
		},
		title: 'Farms &raquo; ' + (moduleParams.farm ? moduleParams.farm.farm.name : 'Builder'),

		activeTab: 0,
		items: [{
			title: 'Farm',
			itemId: 'farm',
			xtype: 'form',
			bodyPadding: 5,
			bodyCls: 'scalr-ui-frame',
			items: [{
				xtype: 'fieldset',
				title: 'General info',
				items: [{
					xtype: 'textfield',
					itemId: 'farmName',
					fieldLabel: 'Name',
					labelWidth: 70,
					value: moduleParams.farm ? moduleParams.farm.farm.name : '',
					width: 500
				}, {
					xtype: 'textarea',
					itemId: 'farmDescription',
					fieldLabel: 'Description',
					labelWidth: 70,
					value: moduleParams.farm ? moduleParams.farm.farm.description : '',
					anchor: '100%',
					grow: true
				}]
			}, {
				xtype: 'fieldset',
				title: 'Settings',
				itemId: 'settings',
				items: [{
					xtype: 'radiogroup',
					hideLabel: true,
					columns: 1,
					items: [{
						boxLabel: 'Launch roles simultaneously ',
						checked: true,
						inputValue: '0'
					}/*, {
						boxLabel: 'Launch roles one-by-one in the order I set (slower) ',
						name: 'farm_roles_launch_order',
						inputValue: '1'
					}*/]
				}]
			}]
		}, {
			title: 'Roles',
			itemId: 'roles',

			layout: {
				type: 'vbox',
				align: 'stretch'
			},

			items: [{
				xtype: 'farmselroles',
				store: farmRolesStore,
				title: 'Roles',
				itemId: 'farmRoles',
				border: false,
				height: 149,
				maintainFlex: true,
				emptyText: '<div class="scalr-ui-farmselroles-empty-text">No roles were added to farm. <a href="#addrole">Click here to add one</a></div>',
				deferEmptyText: false,
				singleSelect: true,
				itemSelector: 'li',
				selectedItemCls: 'scalr-ui-farmselroles-selected',
				tpl: new Ext.XTemplate(
					'<ul class="scalr-ui-farmselroles">',
						'<tpl for=".">',
							'<li>',
								'<img src="/ui/images/icons/{[this.getLocationIcon(values)]}.png" class="icon" />',
								'<img src="/ui/images/icons/platform/{platform}.png" class="platform" />',
								'<img src="/ui/images/icons/arch/{arch}.png" class="arch" />',
								'<div class="short">',
									'<tpl if="name.length &gt; 12">',
										'<span class="short">{[this.getName(values.name)]}</span>',
										'</div><div class="full">{name}',
									'</tpl>',
									'<tpl if="name.length &lt; 13">',
										'<span class="short">{name}</span>',
									'</tpl>',
								'</div>',
								'<div class="location">{cloud_location}</div>',
								'<a class="delete">&nbsp;</a>',
							'</li>',
						'</tpl>',
					'</ul>', {
					    getLocationIcon: function (values) {
						
						var groups = [ "base", "database", "app", "lb", "cache", "mixed", "utils", "cloudfoundry"];
						var behaviors = [
							"cf_cchm",
							"cf_dea",
							"cf_router",
							"cf_service",
							"mq_rabbitmq", 
							"lb_www", 
							"app_app", 
							"app_tomcat", 
							"utils_mysqlproxy", 
							"cache_memcached", 
							"database_cassandra", 
							"database_mysql", 
							"database_postgresql", 
							"database_redis",
							"database_mongodb"
						];

						//Handle CF all-in-one role
						if (values['behaviors'].match("cf_router") && values['behaviors'].match("cf_cloud_controller") && values['behaviors'].match("cf_health_manager") && values['behaviors'].match("cf_dea"))
							return "behaviors/cloudfoundry_cf_all-in-one";
							
						//Handle CF CCHM role
						if (values['behaviors'].match("cf_cloud_controller") || values['behaviors'].match("cf_health_manager"))
							return "behaviors/cloudfoundry_cf_cchm";

						var b = (values['behaviors'] || '').split(','), key;
						for (var i = 0, len = b.length; i < len; i++) {
							key = values['group'] + '_' + b[i];
							key2 = b[i];
					
							for (var k = 0; k < behaviors.length; k++ ) {
								if (behaviors[k] == key || behaviors[k] == key2)
									return 'behaviors/' + key;
							}
						}

						for (var i = 0; i < groups.length; i++ ) {
							if (groups[i] == values['group'])
								return 'groups/' + groups[i];
						}
					},
					getName: function (n) {
						return n.substr(0, 6) + '...' + n.substr(n.length - 5, 5);
					}
				}),
				listeners: {
					selectionchange: function(c, selections) {
						if (selections[0]) {
							panel.down('#card').layout.setActiveItem('blank');
							panel.down('#edit').setCurrentRole(selections[0]);
							panel.down('#card').layout.setActiveItem('edit');
						} else {
							panel.down('#card').layout.setActiveItem('blank');
						}
					},
					addrole: function () {
						panel.down('#card').layout.setActiveItem('add');
					},
                    viewready: function() {
                        var record = farmRolesStore.findRecord('role_id', loadParams['roleId']);
                        if (record)
                            panel.down('#farmRoles').getSelectionModel().select(record);
                    }
				}
			}, {
				xtype: 'panel',
				layout: 'card',
				itemId: 'card',
				border: false,
				flex: 1,
				activeItem: 'blank',
				items: [{
					xtype: 'panel',
					itemId: 'blank',
					bodyCls: 'scalr-ui-frame',
					border: false
				}, {
					xtype: 'farmroleedit',
					itemId: 'edit',
					border: false
				}, {
					xtype: 'farmroleall',
					itemId: 'add',
					loadParams: loadParams,
					border: false,
					listeners: {
						addrole: function (role) {
							if (
								farmRolesStore.findBy(function(record) {
									if (
										record.get('platform') == role.platform &&
										record.get('role_id') == role.role_id &&
										record.get('cloud_location') == role.cloud_location
									)
										return true;
								}) != -1
							) {
								Scalr.message.Error('Role "' + role['name'] + '" already added');
								return;
							}

							// check before adding
							if (role.behaviors.match('mysql') && !role.behaviors.match('mysqlproxy')) {
								if (
									farmRolesStore.findBy(function(record) {
										if (record.get('behaviors').match('mysql') && !record.get('behaviors').match('mysqlproxy'))
											return true;
									}) != -1
								) {
									Scalr.message.Error('Only one MySQL role can be added to farm');
									return;
								}
							}
							
							if (role.behaviors.match('postgresql')) {
								if (
									farmRolesStore.findBy(function(record) {
										if (record.get('behaviors').match('postgresql'))
											return true;
									}) != -1
								) {
									Scalr.message.Error('Only one PostgreSQL role can be added to farm');
									return;
								}
							}
							
							if (role.behaviors.match('redis')) {
								if (
									farmRolesStore.findBy(function(record) {
										if (record.get('behaviors').match('redis'))
											return true;
									}) != -1
								) {
									Scalr.message.Error('Only one Redis role can be added to farm');
									return;
								}
							}

							role['new'] = true;
							role['settings'] = {};

							var record = farmRolesStore.add(role)[0];
							panel.down('#edit').addRoleDefaultValues(record);
							Scalr.message.Success('Role "' + role['name'] + '" added');
						}
					}
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
				width: 80,
				handler: function() {
					saveHandler();
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

	moduleParams['tabParams']['farmRolesStore'] = farmRolesStore;

	for (var i = 0; i < moduleParams.tabs.length; i++)
		panel.down('#edit').add(Scalr.cache['Scalr.ui.farms.builder.tabs.' + moduleParams.tabs[i]](moduleParams['tabParams']));

	if (moduleParams['farmId'])
		panel.setActiveTab(1);

    return panel;
});
