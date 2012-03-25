Scalr.regPage('Scalr.ui.servers.messages', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'messageid', 'server_id', 'status', 'handle_attempts', 'dtlasthandleattempt','message_type','type','isszr'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/servers/xListMessages/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Servers &raquo; ' + loadParams['serverId'] + ' &raquo; Messages',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { serverId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},

		store: store,
		stateId: 'grid-servers-messages-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No messages found'
		},

		columns:[
			{ header: "Message ID", flex: 1, dataIndex: 'messageid', sortable: true },
			{ header: "Message type", width: 150, dataIndex: 'message_type', xtype: 'templatecolumn', tpl:'{type} / {message_type}', sortable: false },
			{ header: "Server ID", flex: 1, dataIndex: 'server_id', xtype: 'templatecolumn', tpl:'<a href="#/servers/{server_id}/extendedInfo">{server_id}</a>', sortable: true },
			{ header: "Status", width: 100, dataIndex: 'status', sortable: true, xtype: 'templatecolumn', tpl:
				'<tpl if="status == 1"><span style="color:green;">Delivered</span></tpl>'+
				'<tpl if="status == 0"><span style="color:orange;">Delivering...</span></tpl>'+
				'<tpl if="status == 2 || status == 3"><span style="color:red;">Failed</span></tpl>'
			},
			{ header: "Attempts", width: 100, dataIndex: 'handle_attempts', sortable: true },
			{ header: "Last delivery attempt", width: 200, dataIndex: 'dtlasthandleattempt', sortable: true },
			{
				xtype: 'optionscolumn',
				getVisibility: function (record) {
					return (record.get('status') == 2 || record.get('status') == 3);
				},
				optionsMenu: [{
					text: 'Re-send message',
					request: {
						processBox: {
							type: 'action',
							msg: 'Re-sending message. Please wait...'
						},
						dataHandler: function (record) {
							this.url = '/servers/' + record.get('server_id') + '/xResendMessage/';
							return { messageId: record.get('messageid') };
						},
						success: function () {
							store.load();
						}
					}
				}]
			}
		],

		tools: [{
			type: 'close',
			handler: function () {
				Scalr.event.fireEvent('close');
			}
		}],

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top'
		}]
	});
});
