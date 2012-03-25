Scalr.regPage('Scalr.ui.admin.logs.details', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id', 'dtadded', 'message', 'severity', 'transactionid', 'caller'
		],
		proxy: {
			type: 'scalr.paging',
			url: '/admin/logs/xListDetails/',
			extraParams: {trnId: loadParams['trnId'], "severity['FATAL']": '1', "severity['ERROR']": '1', "severity['WARN']": '1', "severity['INFO']": '1'}
		},
		remoteSort: true
	});
	var filterSeverity = function (combo, checked) {
		store.proxy.extraParams["severity['" + combo.severityLevel + "']"] = checked ? 1 : 0;
		store.load();
	};
	return Ext.create('Ext.grid.Panel', {
		title: 'Logs &raquo; '+ loadParams['trnId'] +' &raquo; Details',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-admin-logs-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No logs found',
			getRowClass: function (record, rowIndex, rowParams) {
				return (record.get('severity') == 'ERROR' || record.get('severity') == 'FATAL') ? 'scalr-ui-grid-row-red' : '';
			}
		},

		columns: [
			{ text: "Id", width: 220, dataIndex: 'id', sortable: true },
			{ text: "Severity", flex: 1, dataIndex: 'severity', sortable: true },
			{ text: "Category", flex: 3, dataIndex: 'caller', sortable: false },
			{ text: "Date", flex: 1, dataIndex: 'dtadded', sortable: false},
			{ text: "Message", flex: 2, dataIndex: 'message', sortable: false}
		],
		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items:['-', {
				text: 'Filter',
				iconCls: 'scalr-ui-btn-icon-filter',
				menu: [{
					text: 'Severity',
					menu: {
						items: [{
							text: 'Fatal error',
							checked: true,
							severityLevel: 'FATAL',
							listeners: {
								checkchange: filterSeverity
							}
						}, {
							text: 'Error',
							checked: true,
							severityLevel: 'ERROR',
							listeners: {
								checkchange: filterSeverity
							}
						}, {
							text: 'Warning',
							checked: true,
							severityLevel: 'WARN',
							listeners: {
								checkchange: filterSeverity
							}
						}, {
							text: 'Information',
							checked: true,
							severityLevel: 'INFO',
							listeners: {
								checkchange: filterSeverity
							}
						}]
					}
				},{
					xtype: 'tbfilterfield',
					store: store,
					iconCls: 'no-icon'
				}]
			},]
		}]
	});
});