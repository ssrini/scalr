Scalr.regPage('Scalr.ui.environments.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id', 'name', 'dtAdded', 'isSystem','platforms'
		],
		proxy: {
			type: 'scalr.paging',
			url: '/environments/xListEnvironments/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Environments &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			this.store.load();
		},
		store: store,
		stateId: 'grid-environments-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No environments found'
		},

		columns: [
			{ header: 'ID', width: 70, dataIndex: 'id', sortable: true },
			{ header: 'Name', flex: 1, dataIndex: 'name', sortable: true },
			{ header: 'Enabled cloud platforms', flex: 2, dataIndex: 'platforms', sortable: false },
			{ header: 'Date added', width: 180, dataIndex: 'dtAdded', sortable: true },
			{ header: 'System', width: 70, dataIndex: 'isSystem', sortable: false, xtype: 'templatecolumn', align: 'center', tpl:
				'<tpl if="isSystem == 1"><img src="/ui/images/icons/true.png"></tpl>' +
				'<tpl if="isSystem != 1">-</tpl>'
			}, {
				xtype: 'optionscolumn',
				optionsMenu: [{
					text:'Configure',
					iconCls: 'scalr-menu-icon-configure',
					href: '#/environments/{id}/edit'
				}, {
					itemId: 'option.delete',
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							msg: 'Are you sure want to delete environment "{name}"? You <b>WILL LOSE</b> all settings, dns zones, virtualhosts etc. assigned to this environment.',
							type: 'delete'
						},
						processBox: {
							type: 'delete',
							msg: 'Removing environment. Please wait...'
						},
						dataHandler: function (record) {
							this.url = '/environments/' + record.get('id') + '/xRemove';
						},
						success: function (data) {
							Scalr.event.fireEvent('update', 'environments/delete', data.envId);
							if (data.flagReload)
								Scalr.event.fireEvent('reload');
							else
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
			items: ['-', {
				icon: '/ui/images/icons/add_icon_16x16.png', // icons can also be specified inline
				cls: 'x-btn-icon',
				tooltip: 'Create new environment',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/environments/create');
				}
			}]
		}]
	});
});
