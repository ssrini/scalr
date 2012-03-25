Scalr.regPage('Scalr.ui.farms.roles.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			{name: 'id', type: 'int'}, 'platform', 'location',
			'name', 'min_count', 'max_count', 'min_LA', 'max_LA', 'running_servers', 'non_running_servers' ,'domains',
			'image_id', 'farmid','shortcuts', 'role_id', 'scaling_algos', 'farm_status', 'location'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/farms/roles/xListFarmRoles/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Farms &raquo; ' + moduleParams['farmName'] + ' &raquo; Roles',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { roleId:'', farmRoleId: '', farmId: '', clientId: '', status: '' };

			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-farms-roles-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No roles assigned to selected farm'
		},

		columns: [
			{ header: "Platform", width: 100, dataIndex: 'platform', sortable: true },
			{ header: "Location", width: 100, dataIndex: 'location', sortable: false },
			{ header: "Role name", flex: 1, dataIndex: 'name', sortable: false, xtype: 'templatecolumn', tpl:
				'<a href="#/roles/{role_id}/view">{name}</a>'
			},
			{header: "Image ID", width: 100, dataIndex: 'image_id', sortable: false, xtype: 'templatecolumn', tpl:
				'<a href="#/roles/{role_id}/view">{image_id}</a>'
			},
			{ header: "Min servers", width: 80, dataIndex: 'min_count', sortable: false, align:'center' },
			{ header: "Max servers", width: 80, dataIndex: 'max_count', sortable: false, align:'center' },
			{ header: "Enabled scaling algorithms", flex: 1, dataIndex: 'scaling_algos', sortable: false, align:'center' },
			{ header: "Servers", width: 100, dataIndex: 'servers', sortable: false, xtype: 'templatecolumn', tpl:
				'<span style="color:green;">{running_servers}</span>/<span style="color:gray;">{non_running_servers}</span> [<a href="#/servers/view?farmId={farmid}&farmRoleId={id}">View</a>]'
			},
			{ header: "Domains", width: 100, dataIndex: 'domains', sortable: false, xtype: 'templatecolumn', tpl:
				'{domains} [<a href="#/dnszones/view?farmRoleId={id}">View</a>]'
			}, {
				xtype: 'optionscolumn',
				getOptionVisibility: function (item, record) {
					var data = record.data;

					if (item.itemId == "option.sgEdit")
						return (data.platform == 'euca' || data.platform == 'ec2');

					if (item.itemId == 'option.stat' || 
						item.itemId == 'option.cfg' || 
						item.itemId == 'option.ssh_key' || 
						item.itemId == 'option.info' || 
						item.itemId == 'option.downgrade' ||
						item.itemId == 'option.mainSep2' ||
						item.itemId == 'option.mainSep'
					) {
						return true;
					} else {
						if (data.farm_status == 1)
							return true;
						else
							return false;
					}

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

				optionsMenu: [{
					itemId: 'option.ssh_key',
					text: 'Download SSH private key',
					menuHandler: function (item) {
						Scalr.utils.UserLoadFile('/farms/' + loadParams['farmId'] + '/roles/' + item.record.get('id') + '/xGetRoleSshPrivateKey');
					}
				}, {
					itemId: 'option.cfg',
					iconCls: 'scalr-menu-icon-configure',
					text: 'Configure',
					href: "#/farms/{farmid}/edit?roleId={role_id}"
				}, {
					itemId: 'option.stat',
					iconCls: 'scalr-menu-icon-stats',
					text: 'View statistics',
					href: "#/monitoring/view?role={id}&farmId={farmid}"
				}, {
					itemId: 'option.info',
					iconCls: 'scalr-menu-icon-info',
					text: 'Extended role information',
					href: "#/farms/" + loadParams['farmId'] + "/roles/{id}/extendedInfo"
				}, {
					xtype: 'menuseparator',
					itemId: 'option.mainSep'
				}, {
					itemId: 'option.downgrade',
					iconCls: 'scalr-menu-icon-downgrade',
					text: 'Downgrade role to previous version',
					href: "#/farms/" + loadParams['farmId'] + "/roles/{id}/downgrade"
				}, {
					xtype: 'menuseparator',
					itemId: 'option.mainSep2'
				}, {
					itemId: 'option.exec',
					iconCls: 'scalr-menu-icon-execute',
					text: 'Execute script',
					href: '#/scripts/execute?farmRoleId={id}'
				}, {
					xtype: 'menuseparator',
					itemId: 'option.eSep'
				}, {
					itemId: 'option.sgEdit',
					text: 'Edit security group',
					href: '#/security/groups/edit?farmRoleId={id}&cloudLocation={location}&platform={platform}'
				}, {
					xtype: 'menuseparator',
					itemId: 'option.sgSep'
				}, {
					itemId: 'option.launch',
					iconCls: 'scalr-menu-icon-launch',
					text: 'Launch new instance',
					request: {
						processBox: {
							type: 'launch',
							msg: 'Please wait ...'
						},
						dataHandler: function (record) {
							this.url = '/farms/' + loadParams['farmId'] + '/roles/' + record.get('id') + '/xLaunchNewServer';
						},
						success: function (data) {
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
			}]
		}]
	});
});
