Scalr.regPage('Scalr.ui.logs.scripting', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'id','farmid','event','server_id','dtadded','message','farm_name' ],
		proxy: {
			type: 'scalr.paging',
			extraParams: moduleParams['params'],
			url: '/logs/xListScriptingLogs/'
		},
		remoteSort: true
	});

	var panel = Ext.create('Ext.grid.Panel', {
		title: 'Logs &raquo; Scripting',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			Ext.applyIf(loadParams, { farmId: this.down('#farmId').getValue() });
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (this.store.proxy.extraParams['farmId'] != 0)
				this.headerCt.items.getAt(2).hide();
			else
				this.headerCt.items.getAt(2).show();

			if (this.down('#farmId').getValue() != this.store.proxy.extraParams['farmId'])
				this.store.loadPage(1);
			else
				this.store.load();

			this.down('#farmId').setValue(this.store.proxy.extraParams['farmId']);
		},
		store: store,
		stateId: 'grid-scripting-view',
		plugins: [{
			ptype: 'gridstore'
		}, {
			ptype: 'rowexpander',
			rowBodyTpl: [
				'<p><b>Message:</b> {message}</p>'
			]
		}],

		viewConfig: {
			emptyText: 'No logs found'
		},

		columns: [
			{ header: 'Time', width: 160, dataIndex: 'dtadded', sortable: true },
			{ header: 'Event', width: 250, dataIndex: 'event', sortable: false },
			{ header: 'Farm', width: 120, dataIndex: 'farm_name', sortable: false, xtype: 'templatecolumn', tpl:
				'<a href="#/farms/{farmid}/view">{farm_name}</a>'
			},
			{ header: 'Target', flex: 1, dataIndex: 'server_id', sortable: false, xtype: 'templatecolumn', tpl:
				'<a href="#/servers/{server_id}/view">{server_id}</a>'
			},
			{ header: 'Message', flex: 3, dataIndex: 'message', sortable: false, xtype: 'templatecolumn', tpl:
				'{[values.message.replace(/<br.*?>/g, "")]}'
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
				}, {
					xtype: 'combo',
					fieldLabel: 'Farm',
					labelWidth: 32,
					matchFieldWidth: false,
					listConfig: {
						width: 'auto',
						minWidth: 150
					},
					store: {
						fields: [ 'id', 'name' ],
						data: moduleParams['farms'],
						proxy: 'object'
					},
					editable: false,
					queryMode: 'local',
					itemId: 'farmId',
					value: loadParams['farmId'] || '0',
					valueField: 'id',
					displayField: 'name',
					iconCls: 'no-icon',
					listeners: {
						select: function () {
							if (this.getValue() != 0)
								panel.headerCt.items.getAt(2).hide();
							else
								panel.headerCt.items.getAt(2).show();

							panel.store.proxy.extraParams['farmId'] = this.getValue();
							panel.store.loadPage(1);
						}
					}
				}]
			}]
		}]
	});

	return panel;
});
