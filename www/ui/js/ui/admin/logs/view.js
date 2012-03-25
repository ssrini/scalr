Scalr.regPage('Scalr.ui.admin.logs.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'dtadded', 'message', 'warn', 'err', 'transactionid'
		],
		proxy: {
			type: 'scalr.paging',
			url: '/admin/logs/xListLogs/',
			extraParams: {toDate: new Date(), farmId: '0', "severity['FATAL']": '1', "severity['ERROR']": '1', "severity['WARN']": '1', "severity['INFO']": '1'}
		},
		remoteSort: true
	});
	var filterSeverity = function (combo, checked) {
		store.proxy.extraParams["severity['" + combo.severityLevel + "']"] = checked ? 1 : 0;
		store.load();
	};
	return Ext.create('Ext.grid.Panel', {
		title: 'Logs &raquo; View',
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
			deferEmptyText: false,
			emptyText: 'No Logs found'
		},

		columns: [
			{ text: "Date", width: 220, dataIndex: 'dtadded', sortable: true },
			{ text: "First log entry", flex:3, dataIndex: 'message', sortable: true },
			{ text: "Warnings", flex: 1, dataIndex: 'warn', sortable: false },
			{ text: "Errors", flex: 1, dataIndex: 'err', sortable: false},
			{ text: "Details", width: 200, dataIndex: 'transactionid', sortable: false, xtype: 'templatecolumn', tpl: "<a href='#/admin/logs/details?trnId={transactionid}'>Show log entry details</a>"}
		],
		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items:['-', {
				text: 'Filter',
				iconCls: 'scalr-ui-btn-icon-filter',
				menu: [{
					xtype: 'tbfilterfield',
					store: store,
					iconCls: 'no-icon'
				},{
					xtype: 'combo',
					fieldLabel: 'Farms',
					labelWidth: 40,
					store: {
						fields: ['id','name'],
						data: moduleParams['farms'],
						proxy: 'object'
					},
					valueField: 'id',
					displayField: 'name',
					editable: false,
					value: '0',
					queryMode: 'local',
					itemId: 'farmId',
					iconCls: 'no-icon',
					listeners: {
						change: function(field, value) {
							store.proxy.extraParams.farmId = value;
							store.load();
						}
					}
				},{
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
				}]
			},'-',{
		        xtype: 'datefield',
		        itemId: 'toDate',
		        value: new Date(),
		        listeners: {
		        	change: function (field, newValue, oldValue, eOpts ) {
		        		store.proxy.extraParams.toDate = newValue;
						store.load();
		        	}
		        }
			}]
		}]
	});
});