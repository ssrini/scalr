Scalr.regPage('Scalr.ui.admin.users.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id', 'status', 'email', 'fullname', 'dtcreated', 'dtlastlogin', 'type', 'comments'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/admin/users/xListUsers'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Admin &raquo; Users &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = {};
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-admin-users-view',
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
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Edit',
					iconCls: 'scalr-menu-icon-edit',
					href: '#/admin/users/{id}/edit'
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
						url: '/admin/users/xRemove',
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
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/admin/users/create');
				}
			}]
		}]
	});
});
