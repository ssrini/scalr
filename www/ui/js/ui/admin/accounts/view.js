Scalr.regPage('Scalr.ui.admin.accounts.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			{name: 'id', type: 'int'}, 
			'name', 'dtadded', 'status', 'servers', 'users', 'envs', 'farms', 'limitEnvs', 'limitFarms', 'limitUsers', 'limitServers', 'ownerEmail'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/admin/accounts/xListAccounts'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Accounts &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { accountId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-admin-accounts-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No accounts found'
		},

		columns: [
			{ header: "ID", width: 40, dataIndex: 'id', sortable: true },
			{ header: "Name", flex:1, dataIndex: 'name', sortable: true },
			{ header: "Owner email", flex: 1, dataIndex: 'ownerEmail', sortable: false },
			{ header: "Added", flex: 1, dataIndex: 'dtadded', sortable: true, xtype: 'templatecolumn',
				tpl: '{[values.dtadded ? values.dtadded : ""]}'
			},
			{ text: "Status", width: 100, dataIndex: 'status', sortable: true, xtype: 'templatecolumn', tpl:
				new Ext.XTemplate('<span style="color: {[this.getClass(values.status)]}">{status}</span>', {
					getClass: function (value) {
						if (value == 'Active')
							return "green";
						else if (value != 'Inactive')
							return "#666633";
						else
							return "red";
					}
				})
			},
			{ header: "Environments", width:  100, align:'center', dataIndex: 'envs', sortable: false, xtype: 'templatecolumn',
				tpl: '{envs}/{limitEnvs}'
			},
			{ header: "Users", width: 100, dataIndex: 'users', align:'center', sortable: false, xtype: 'templatecolumn',
				tpl: '{users}/{limitUsers}'
			},
			{ header: "Servers", width: 100, dataIndex: 'groups', align:'center', sortable: false, xtype: 'templatecolumn',
				tpl: '{servers}/{limitServers}'
			},
			{ header: "Farms", width: 100, dataIndex: 'farms', align:'center', sortable: false, xtype: 'templatecolumn',
				tpl: '{farms}/{limitFarms}'
			},
			{
				xtype: 'optionscolumn',
				getOptionVisibility: function (item, record) {
					var data = record.data;

					return true;
				},

				optionsMenu: [{
					itemId: 'option.edit',
					iconCls: 'scalr-menu-icon-edit',
					text: 'Edit',
					href: "#/admin/accounts/{id}/edit"
				}, {
					itemId: 'option.login',
					iconCls: 'scalr-menu-icon-login',
					text: 'Login as owner',
					href: "/admin/accounts/{id}/loginAsOwner"
				}]
			}
		],
		
		multiSelect: true,
		selModel: {
			selType: 'selectedmodel',
			selectedMenu: [{
				text: 'Delete',
				iconCls: 'scalr-menu-icon-delete',
				request: {
					confirmBox: {
						type: 'delete',
						msg: 'Remove selected accounts(s)?'
					},
					processBox: {
						type: 'delete',
						msg: 'Removing account(s). Please wait...'
					},
					url: '/admin/accounts/xRemove',
					dataHandler: function(records) {
						var accounts = [];
						for (var i = 0, len = records.length; i < len; i++) {
							accounts[accounts.length] = records[i].get('id');
						}
						return { accounts: Ext.encode(accounts) };
					}
				}
			}]
		},

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
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new account',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/admin/accounts/create');
				}
			}]
		}]
	});
});
