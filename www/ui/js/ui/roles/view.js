Scalr.regPage('Scalr.ui.roles.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			{name: 'id', type: 'int'},
			{name: 'client_id', type: 'int'},
			'name', 'tags', 'origin', 'architecture', 'client_name', 'behaviors', 'os', 'platforms','generation','used_servers','status'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/roles/xListRoles/'
		},
		remoteSort: true
	});

	var confirmationRemovalOptions = {
		xtype: 'fieldset',
		title: 'Removal parameters',
		hidden: moduleParams['isScalrAdmin'],
		items: [{
			xtype: 'checkbox',
			boxLabel: 'Remove image from cloud',
			inputValue: 1,
			checked:!moduleParams['isScalrAdmin'],
			name: 'removeFromCloud'
		}]
	};

	return Ext.create('Ext.grid.Panel', {
		title: 'Roles &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { roleId: '', client_id: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-roles-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: "No roles found"
		},

		columns: [
			{ header: "Role name", flex: 2, dataIndex: 'name', sortable: true },
			{ header: "OS", flex: 1, dataIndex: 'os', sortable: true },
			{ header: "Owner", flex: 1, dataIndex: 'client_name', sortable: false},
			{ header: "Behaviors", flex: 1, dataIndex: 'behaviors', sortable: false },
			{ header: "Available on", flex: 1, dataIndex: 'platforms', sortable: false },
			{ header: "Tags", flex: 1, dataIndex: 'tags', sortable: false },
			{ header: "Arch", width: 65, dataIndex: 'architecture', sortable: true },
			{ header: "Status", width: 100, dataIndex: 'status', sortable: false },
			{ header: "Scalr agent", width: 100, dataIndex: 'generation', sortable: false },
			{ header: "Servers", width: 80, dataIndex: 'used_servers', sortable: false },
			{
				xtype: 'optionscolumn',
				optionsMenu: [
					{ itemId: "option.view", iconCls: 'scalr-menu-icon-info', text:'View details', href: "#/roles/{id}/info" },
					{ itemId: "option.edit", iconCls: 'scalr-menu-icon-edit', text:'Edit', href: "#/roles/{id}/edit" }
				],

				getOptionVisibility: function (item, record) {
					if (item.itemId == 'option.view')
						return true;

					if (record.get('origin') == 'CUSTOM') {
						if (item.itemId == 'option.edit') {
							if (! moduleParams.isScalrAdmin)
								return true;
							else
								return false;
						}
						return true;
					}
					else {
						return moduleParams.isScalrAdmin;
					}
				},

				getVisibility: function (record) {
					return (record.get('status').indexOf('Deleting') == -1);
				}
			}
		],

		multiSelect: true,
		selModel: {
			selType: 'selectedmodel',
			selectedMenu: [{
				iconCls: 'scalr-menu-icon-delete',
				text: 'Delete',
				request: {
					confirmBox: {
						msg: 'Remove selected role(s): %s ?',
						type: 'delete',
						form: confirmationRemovalOptions
					},
					processBox: {
						msg: 'Removing selected role(s)... Please wait, it can take a few minutes.',
						type: 'delete'
					},
					url: '/roles/xRemove',
					dataHandler: function (records) {
						var roles = [];
						this.confirmBox.objects = [];
						for (var i = 0, len = records.length; i < len; i++) {
							roles.push(records[i].get('id'));
							this.confirmBox.objects.push(records[i].get('name'));
						}

						return { roles: Ext.encode(roles) };
					}
				}
			}]
		},

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: ['-', {
				text: 'Filter',
				iconCls: 'scalr-ui-btn-icon-filter',
				menu: [{
					xtype: 'tbfilterfield',
					store: store,
					iconCls: 'no-icon'
				}, {
					xtype: 'combo',
					fieldLabel: 'Location',
					labelWidth: 50,
					width: 250,
					matchFieldWidth: false,
					listConfig: {
						width: 'auto',
						minWidth: 200
					},
					editable: false,
					store: {
						fields: [ 'id', 'name' ],
						data: moduleParams.locations,
						proxy: 'object'
					},
					displayField: 'name',
					valueField: 'id',
					value: '',
					queryMode: 'local',
					listeners: {
						change: function() {
							store.proxy.extraParams.cloudLocation = this.getValue();
							store.loadPage(1);
						}
					},
					iconCls: 'no-icon'
				}, {
					xtype: 'combo',
					fieldLabel: 'Owner',
					labelWidth: 50,
					editable: false,
					store: [ [ '', 'All' ], [ 'Shared', 'Scalr' ], [ 'Custom', 'Private' ] ],
					value: '',
					queryMode: 'local',
					listeners: {
						change: function() {
							store.proxy.extraParams.origin = this.getValue();
							store.loadPage(1);
						}
					},
					iconCls: 'no-icon'
				}]
			}, {
				xtype: 'tbfilterinfo'
			}]
		}]
	});
});
