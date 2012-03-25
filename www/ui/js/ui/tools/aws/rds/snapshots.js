Scalr.regPage('Scalr.ui.tools.aws.rds.snapshots', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'id','name','storage','idtcreated','avail_zone','engine','status','port','dtcreated' ],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/aws/rds/snapshots/xListSnapshots/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; RDS &raquo; DB snapshots',
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
		stateId: 'grid-tools-aws-rds-snapshots-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No db snapshots found'
		},

		columns: [
			{ header: "Name", flex: 1, dataIndex: 'name', sortable: false },
			{ header: "Storage", width: 100, dataIndex: 'storage', sortable: false },
			{ header: "Created at", width: 150, dataIndex: 'dtcreated', sortable: false },
			{ header: "Instance created at", width: 150, dataIndex: 'idtcreated', sortable: false },
			{ header: "Status", width: 150, dataIndex: 'status', sortable: false },
			{ header: "Port", width: 150, dataIndex: 'port', sortable: false },
			{ header: "Placement", width: 150, dataIndex: 'avail_zone', sortable: false },
			{ header: "Engine", width: 150, dataIndex: 'engine', sortable: false },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Restore DB instance from this snapshot',
					menuHandler: function (item) {
						document.location.href = '#/tools/aws/rds/instances/restore?snapshot=' + item.record.get('name') + '&cloudLocation=' + store.proxy.extraParams.cloudLocation;
					}
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
						msg: 'Delete selected db snapshot(s)?',
						type: 'delete'
					},
					processBox: {
						msg: 'Deleting selected db snapshot(s). Please wait...',
						type: 'delete'
					},
					url: '/tools/aws/rds/snapshots/xDeleteSnapshots/',
					dataHandler: function (records) {
						var data = [];
						for (var i = 0, len = records.length; i < len; i++) {
							data[data.length] = records[i].get('id');
						}

						return { snapshots: Ext.encode(data), cloudLocation: store.proxy.extraParams.cloudLocation };
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
					xtype: 'fieldcloudlocation',
					itemId: 'cloudLocation',
					store: {
						fields: [ 'id', 'name' ],
						data: moduleParams.locations,
						proxy: 'object'
					},
					gridStore: store,
					cloudLocation: loadParams['cloudLocation'] || ''
				}]
			}, {
				xtype: 'tbfilterinfo'
			}, '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Launch new DB instance',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/tools/aws/rds/instances/create');
				}
			}]
		}]
	});
});
