Scalr.regPage('Scalr.ui.scaling.metrics.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'id','env_id','client_id','name','file_path','retrieve_method','calc_function' ],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/scaling/metrics/xListMetrics/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Scaling &raquo; Metrics &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { metricId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-scaling-metrics-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: "No presets defined"
		},

		columns: [
			{ header: "ID", width: 40, dataIndex: 'id', sortable: true },
			{ header: "Name", flex: 1, dataIndex: 'name', sortable:true },
			{ header: "File path", flex: 1, dataIndex: 'file_path', sortable: false },
			{ header: "Retrieve method", flex: 1, dataIndex: 'retrieve_method', sortable: false, xtype: 'templatecolumn', tpl:
				'<tpl if="retrieve_method == \'read\'">File-Read</tpl>' +
				'<tpl if="retrieve_method == \'execute\'">File-Execute</tpl>'
			},
			{ header: "Calculation function", flex: 1, dataIndex: 'calc_function', sortable: false, xtype: 'templatecolumn', tpl:
				'<tpl if="calc_function == \'avg\'">Average</tpl>' +
				'<tpl if="calc_function == \'sum\'">Sum</tpl>'
			}, {
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Edit',
					href: "#/scaling/metrics/{id}/edit"
				}],
				getVisibility: function (record) {
					return (record.get('env_id') != 0);
				}
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
						msg: 'Remove selected metric(s)?',
						type: 'delete'
					},
					processBox: {
						msg: 'Removing selected metric(s), Please wait...',
						type: 'delete'
					},
					url: '/scaling/metrics/xRemove/',
					dataHandler: function (records) {
						var metrics = [];
						for (var i = 0, len = records.length; i < len; i++) {
							metrics[metrics.length] = records[i].get('id');
						}

						return { metrics: Ext.encode(metrics) };
					}
				}
			}],
			getVisibility: function (record) {
				return (record.get('env_id') != 0);
			}
		},

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: [ '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new scaling metric',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/scaling/metrics/create');
				}
			}]
		}]
	});
});
