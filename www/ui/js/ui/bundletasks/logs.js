Scalr.regPage('Scalr.ui.bundletasks.logs', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			{name: 'id', type: 'int'},
			'dtadded','message'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/bundletasks/xListLogs/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Bundle task &raquo; Log',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { bundleTaskId: ''};
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-bundletasks-logs-view',
		plugins: {
			ptype: 'gridstore'
		},
		tools: [{
			id: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],

		viewConfig: {
			emptyText: 'Log is empty for selected bundle task'
		},

		columns: [
			{ header: "Date", width: 165, dataIndex: 'dtadded', sortable: true },
			{ header: "Message", flex: 1, dataIndex: 'message', sortable: true }
		],

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top'
		}]
	});
});
