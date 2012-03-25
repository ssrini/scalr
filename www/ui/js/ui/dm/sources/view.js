Scalr.regPage('Scalr.ui.dm.sources.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id', 'url', 'type', 'auth_type'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/dm/sources/xListSources'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Deployments &raquo; Sources',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { sourceId: ''};
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-dm-sources-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No sources found'
		},

		columns: [
			{ header: "ID", width: 80, dataIndex: 'id', sortable: true },
			{ header: "URL", flex: 1, dataIndex: 'url', sortable: true },
			{ header: "Type", width: 120, dataIndex: 'type', sortable: true },
			{ header: "Auth type", width: 120, dataIndex: 'auth_type', sortable: false },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Edit',
					iconCls: 'scalr-menu-icon-edit',
					href: '#/dm/sources/{id}/edit'
				}, {
					xtype: 'menuseparator'
				}, {
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							msg: 'Are you sure want to remove demployment source "{url}"?',
							type: 'delete'
						},
						processBox: {
							type: 'delete',
							msg: 'Removing demployment source. Please wait...'
						},
						url: '/dm/sources/xRemoveSources',
						dataHandler: function (record) {
							return {
								sourceId: record.get('id')
							};
						},
						success: function(data) {
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
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new source',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/dm/sources/create');
				}
			}]
		}]
	});
});
