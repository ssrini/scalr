Scalr.regPage('Scalr.ui.tools.aws.rds.logs', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ "Date", "SourceIdentifier", "Message", "SourceType" ],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/aws/rds/xListLogs/'
		},
		remoteSort: true
	});
	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; RDS &raquo; Event logs',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			this.store.load();
		},
		store: store,
		stateId: 'grid-tools-aws-rds-logs',
		plugins: {
			ptype: 'gridstore'
		},
		viewConfig: {
			deferEmptyText: false,
			emptyText: 'No logs found'
		},
		bodyCls: 'scalr-ui-frame',
		columns: [
			{flex: 3, text: "Time", dataIndex: 'Date', sortable: true },
			{flex: 1, text: "Message", dataIndex: 'Message', sortable: true },
			{flex: 1, text: "Caller", dataIndex: 'SourceIdentifier', sortable: true },
			{flex: 1, text: "Type", dataIndex: 'SourceType', sortable: true }
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
			}, {
				xtype: 'tbfilterinfo'
			}]
		}]
	});
});
