Scalr.regPage('Scalr.ui.scripts.shortcuts.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			{name: 'id', type: 'int'},
			'farmid', 'farmname', 'farm_roleid', 'rolename', 'scriptname', 'event_name'
		],
		proxy: {
			type: 'scalr.paging',
			url: '/scripts/shortcuts/xListShortcuts/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Scripts &raquo; Shortcuts &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { scriptId: '', eventName:'' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-scripts-shortcuts-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: "No shortcuts defined"
		},

		columns: [
			{ header: "Target", flex: 1, dataIndex: 'id', sortable: false, xtype: 'templatecolumn', tpl:
				'<a href="#/farms/{farmid}/view">{farmname}</a>' +
				'<tpl if="farm_roleid &gt; 0">&rarr;<a href="#/farms/{farmid}/roles/{farm_roleid}/view">{rolename}</a></tpl>' +
				'&nbsp;&nbsp;&nbsp;'
			},
			{ header: "Script", flex: 2, dataIndex: 'scriptname', sortable: true }, {
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Edit',
					href: "#/scripts/execute?eventName={event_name}&isShortcut=1"
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
						msg: 'Delete selected shortcuts(s)?'
					},
					processBox: {
						type: 'delete',
						msg: 'Removing selected shortcut(s). Please wait...'
					},
					url: '/scripts/shortcuts/xRemove/',
					dataHandler: function(records) {
						var shortcuts = [];
						for (var i = 0, len = records.length; i < len; i++) {
							shortcuts[shortcuts.length] = records[i].get('id');
						}

						return { shortcuts: Ext.encode(shortcuts) };
					}
				}
			}]
		},

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top'
		}]
	});
});
