Scalr.regPage('Scalr.ui.logs.api', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'id','transaction_id','dtadded','action','ipaddress','request' ],
		proxy: {
			type: 'scalr.paging',
			url: '/logs/xListApiLogs/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Logs &raquo; API',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function () {
			this.store.load();
		},
		store: store,
		stateId: 'grid-logs-api-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No logs found'
		},

		columns: [
			{ header: 'Transaction ID', flex: 1, dataIndex: 'transaction_id', sortable: false },
			{ header: 'Time', flex: 1, dataIndex: 'dtadded', sortable: true },
			{ header: 'Action', flex: 1, dataIndex: 'action', sortable: true },
			{ header: 'IP address', flex: 1, dataIndex: 'ipaddress', sortable: true },
			{
				xtype: 'optionscolumn',
				optionsMenu: [
					{ text:'Details', href: "#/logs/apiLogEntryDetails?transactionId={transaction_id}" }
				]
			}
		],

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: ['-', {
				text: 'Filter',
				iconCls: 'scalr-ui-btn-icon-filter',
				menu: [{
					xtype: 'tbfilterfield',
					store: store,
					iconCls: 'no-icon'
				}]
			}]
		}]
	});
});
