Scalr.regPage('Scalr.ui.logs.system', function (loadParams, moduleParams) {
	Ext.applyIf(moduleParams['params'], loadParams);
	var store = Ext.create('store.store', {
		fields: [ 'id','serverid','message','severity','time','source','farmid','servername','farm_name', 's_severity' ],
		proxy: {
			type: 'scalr.paging',
			extraParams: moduleParams['params'],
			url: '/logs/xListLogs/'
		},
		remoteSort: true
	});

	var filterSeverity = function (combo, checked) {
		store.proxy.extraParams['severity[' + combo.severityLevel + ']'] = checked ? 1 : 0;
		store.load();
	};

	var panel = Ext.create('Ext.grid.Panel', {
		title: 'Logs &raquo; System',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			Ext.applyIf(loadParams, { farmId: this.down('#farmId').getValue() });
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (this.store.proxy.extraParams['farmId'] != 0)
				this.headerCt.items.getAt(3).hide();
			else
				this.headerCt.items.getAt(3).show();

			if (this.down('#farmId').getValue() != this.store.proxy.extraParams['farmId'])
				this.store.loadPage(1);
			else
				this.store.load();

			this.down('#farmId').setValue(this.store.proxy.extraParams['farmId']);
		},
		store: store,
		stateId: 'grid-logs-system-view',
		plugins: [{
			ptype: 'gridstore'
		}, {
			ptype: 'rowexpander',
			rowBodyTpl: [
				'<p><b>Caller:</b> <a href="#/servers/{servername}/view">{servername}</a>/{source}</p>',
				'<p><b>Message:</b> {message}</p>'
			]
		}],

		viewConfig: {
			emptyText: 'No logs found',
			getRowClass: function (record, rowIndex, rowParams) {
				return (record.get('severity') > 3) ? 'scalr-ui-grid-row-red x-grid-row-collapsed' : 'x-grid-row-collapsed';
			}
		},

		columns: [
			{ header: '', width: 40, dataIndex: 'severity', sortable: false, resizable: false, align:'center', xtype: 'templatecolumn', tpl:
				'<tpl if="severity == 1"><img src="/ui/images/icons/log/debug.png"></tpl>' +
				'<tpl if="severity == 2"><img src="/ui/images/icons/log/info.png"></tpl>' +
				'<tpl if="severity == 3"><img src="/ui/images/icons/log/warning.png"></tpl>' +
				'<tpl if="severity == 4"><img src="/ui/images/icons/log/error.png"></tpl>' +
				'<tpl if="severity == 5"><img src="/ui/images/icons/log/fatal_error.png"></tpl>'
			},
			{ header: 'Time', width: 156, dataIndex: 'time', sortable: true },
			{ header: 'Farm', width: 120, dataIndex: 'farm_name', itemId: 'farm_name', sortable: false, xtype: 'templatecolumn', tpl:
				'<a href="#/farms/{farmid}/view">{farm_name}</a>'
			},
			{ header: 'Caller', flex: 1, dataIndex: 'source', sortable: false, xtype: 'templatecolumn', tpl:
				'<a href="#/servers/{servername}/view">{servername}</a>/{source}'
			},
			{ header: 'Message', flex: 2, dataIndex: 'message', sortable: false, xtype: 'templatecolumn', tpl:
				'{[values.message.replace(/<br.*?>/g, "")]}' }
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
								panel.headerCt.items.getAt(3).hide();
							else
								panel.headerCt.items.getAt(3).show();
							
							panel.store.proxy.extraParams['farmId'] = this.getValue();
							panel.store.loadPage(1);
						}
					}
				}, {
					text: 'Severity',
					menu: {
						items: [{
							text: 'Fatal error',
							checked: true,
							severityLevel: 5,
							listeners: {
								checkchange: filterSeverity
							}
						}, {
							text: 'Error',
							checked: true,
							severityLevel: 4,
							listeners: {
								checkchange: filterSeverity
							}
						}, {
							text: 'Warning',
							checked: true,
							severityLevel: 3,
							listeners: {
								checkchange: filterSeverity
							}
						}, {
							text: 'Information',
							checked: true,
							severityLevel: 2,
							listeners: {
								checkchange: filterSeverity
							}
						}, {
							text: 'Debug',
							checked: false,
							severityLevel: 1,
							listeners: {
								checkchange: filterSeverity
							}
						}]
					}
				}]
			}, '->', {
				text: 'Download Log',
				iconCls: 'scalr-ui-btn-icon-download',
				handler: function () {
					var params = Scalr.utils.CloneObject(store.proxy.extraParams);
					params['action'] = 'download';
					Scalr.utils.UserLoadFile('/logs/xListLogs?' + Ext.urlEncode(params));
				}
			}]
		}]
	});

	return panel;
});
