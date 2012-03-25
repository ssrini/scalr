Scalr.regPage('Scalr.ui.dm.applications.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id', 'name', 'source_id', 'source_url', 'used_on'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/dm/applications/xListApplications/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Deployments &raquo; Applications &raquo; Manage',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { applicationId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-dm-applications-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No applications found'
		},

		columns: [
			{ header: 'ID', width: 80, dataIndex: 'id', sortable: true },
			{ header: 'Name', flex: 1, dataIndex: 'name', sortable: true },
			{ header: 'Source', flex: 1, dataIndex: 'source_url', sortable: true, xtype: 'templatecolumn',
				tpl: '<a href="#/dm/sources/{source_id}/view">{source_url}</a>'
			},
			{ header: 'Status', width: 120, dataIndex: 'status', sortable: false, xtype: 'templatecolumn',
				tpl: '<tpl if="used_on != 0"><span style="color:green;">In use</span></tpl><tpl if="used_on == 0"><span style="color:gray;">Not used</span></tpl>'
			}, {
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Deploy',
					iconCls: 'scalr-menu-icon-launch',
					href: '#/dm/applications/{id}/deploy'
				}, {
					xtype: 'menuseparator'
				}, {
					text: 'Edit',
					iconCls: 'scalr-menu-icon-edit',
					href: '#/dm/applications/{id}/edit'
				}, {
					xtype: 'menuseparator'
				}, {
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							msg: 'Are you sure want to remove demployment "{name}"?',
							type: 'delete'
						},
						processBox: {
							type: 'delete',
							msg: 'Removing demployment. Please wait...'
						},
						url: '/dm/applications/xRemoveApplications',
						dataHandler: function (record) {
							return {
								applicationId: record.get('id')
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
				tooltip: 'Create new application',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/dm/applications/create');
				}
			}]
		}]
	});
});
