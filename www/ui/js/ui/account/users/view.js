Scalr.regPage('Scalr.ui.account.users.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id', 'status', 'email', 'fullname', 'dtcreated', 'dtlastlogin', 'type', 'comments', 'teams', 'type', 'is2FaEnabled'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/account/users/xListUsers'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Account &raquo; Users &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { teamId : '', userId: '', groupPermissionId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-account-users-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No users found'
		},

		columns: [
			{ text: 'ID', width: 50, dataIndex: 'id', sortable: true },
			{ text: 'Email', flex: 1, dataIndex: 'email', sortable: true },
			{ text: 'Status', Width: 50, dataIndex: 'status', sortable: true, xtype: 'templatecolumn', tpl:
				'<span ' +
				'<tpl if="status == &quot;Active&quot;">style="color: green"</tpl>' +
				'<tpl if="status != &quot;Active&quot;">style="color: red"</tpl>' +
				'>{status}</span>'
			},
			{ text: 'Full name', flex: 1, dataIndex: 'fullname', sortable: true },
			{ text: 'Created date', width: 170, dataIndex: 'dtcreated', sortable: true },
			{ text: 'Last login', width: 170, dataIndex: 'dtlastlogin', sortable: true },
			{ text: 'Type', width: 150, dataIndex: 'type', sortable: true },
			{ text: 'Teams', flex: 1, dataIndex: 'teams', sortable: false, xtype: 'templatecolumn', tpl:
				'<tpl for="teams">' +
				'{name}<tpl if="xindex &lt; xcount">, </tpl>' +
				'</tpl>'
			}, {
				text: '2FA', width: 50, dataIndex: 'is2FaEnabled', align: 'center', sortable: false, hidden: !moduleParams['feature2FA'], xtype: 'templatecolumn', tpl: 
					'<tpl if="is2FaEnabled"><img src="/ui/images/icons/true.png" /></tpl>' +
					'<tpl if="!is2FaEnabled"><img src="/ui/images/icons/false.png" /></tpl>'
			}, {
				xtype: 'optionscolumn',
				getVisibility: function () {
					return moduleParams['userManage'];
				},
				optionsMenu: [{
					text: 'Edit',
					iconCls: 'scalr-menu-icon-edit',
					href: '#/account/users/{id}/edit'
				}, {
					text: 'Remove',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							type: 'delete',
							msg: 'Are you sure want to remove user "{email}" ?'
						},
						processBox: {
							type: 'delete'
						},
						url: '/account/users/xRemove',
						dataHandler: function (record) {
							return { userId: record.get('id') };
						},
						success: function () {
							store.load()
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
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new user',
				hidden: !moduleParams['userManage'],
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/account/users/create');
				}
			}]
		}]
	});
});
