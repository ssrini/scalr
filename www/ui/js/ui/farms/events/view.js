Scalr.regPage('Scalr.ui.farms.events.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'id','dtadded', 'type', 'message'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/farms/' + loadParams['farmId'] + '/events/xListEvents'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Farms &raquo; ' + moduleParams['farmName'] + ' &raquo; Events',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { farmId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-farms-events-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: "No events found"
		},

		columns: [
			{ header: "Date", width: 150, dataIndex: 'dtadded', sortable: false },
			{ header: "Event", width: 200, dataIndex: 'type', sortable: false },
			{ header: "Description", flex: 1, dataIndex: 'message', sortable: false }
		],

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: [ '-', {
				xtype: 'tbfilterfield',
				store: store
			}, '-', {
				xtype: 'button',
				text: 'Configure event notifications',
				//iconCls: 'x-btn-download-icon',
				handler: function () {
					document.location.href = '#/farms/events/configure?farmId='+loadParams['farmId'];
				}
			}]
		}]
	});
});
