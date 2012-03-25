Scalr.regPage('Scalr.ui.services.configurations.presets.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'id','env_id','client_id','name','role_behavior','dtadded','dtlastmodified' ],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/services/configurations/presets/xListPresets/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Services &raquo; Configurations &raquo; Presets',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { presetId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-services-configurations-presets-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: "No presets found"
		},

		columns:[
			{ header: "ID", width: 50, dataIndex: 'id', sortable:true },
			{ header: "Name", flex: 1, dataIndex: 'name', sortable:true },
			{ header: "Role behavior", flex: 1, dataIndex: 'role_behavior', sortable: true },
			{ header: "Added at", flex: 1, dataIndex: 'dtadded', sortable: false },
			{ header: "Last time modified", flex: 1, dataIndex: 'dtlastmodified', sortable: false },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Edit',
					href: "#/services/configurations/presets/{id}/edit"
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
						msg: 'Remove selected configuration preset(s) ?'
					},
					processBox: {
						type: 'delete',
						msg: 'Removing configuration preset(s). Please wait...'
					},
					url: '/services/configurations/presets/xRemove/',
					dataHandler: function(records) {
						var presets = [];
						for (var i = 0, len = records.length; i < len; i++) {
							presets[presets.length] = records[i].get('id');
						}
						return { presets: Ext.encode(presets) };
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
					store: store,
					iconCls: 'no-icon'
				}]
			}, '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new configuration preset',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/services/configurations/presets/build');
				}
			}]
		}]
	});
});
