Scalr.regPage('Scalr.ui.tools.cloudstack.volumes.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'farmId', 'farmRoleId', 'farmName', 'roleName', 'mysql_master_volume', 'mountStatus', 'serverIndex', 'serverId',
			'volumeId', 'size', 'type', 'storage', 'status', 'attachmentStatus', 'device', 'instanceId', 'autoSnaps', 'autoAttach'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/cloudstack/volumes/xListVolumes/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Cloudstack &raquo; Volumes',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { volumeId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);
			this.down('#cloudLocation').setValue(this.store.proxy.extraParams.cloudLocation);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-tools-cloudstack-volumes-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No volumes found'
		},

		columns: [
			{ header: "Used by", flex: 1, dataIndex: 'id', sortable: false, xtype: 'templatecolumn', tpl:
				'<tpl if="farmId">' +
					'<a href="#/farms/{farmId}/view" title="Farm {farmName}">{farmName}</a>' +
					'<tpl if="roleName">' +
						'&nbsp;&rarr;&nbsp;<a href="#/farms/{farmId}/roles/{farmRoleId}/view" title="Role {roleName}">' +
						'{roleName}</a> #<a href="#/servers/{serverId}/view">{serverIndex}</a>' +
					'</tpl>' +
				'</tpl>' +
				'<tpl if="!farmId"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{ header: "Volume ID", width: 90, dataIndex: 'volumeId', sortable: true },
			{ header: "Size (GB)", width: 80, dataIndex: 'size', sortable: true },
			{ header: "Type", width: 150, dataIndex: 'type', sortable: true},
			{ header: "Storage", width: 120, dataIndex: 'storage', sortable: true },
			{ header: "Status", width: 180, dataIndex: 'status', sortable: true, xtype: 'templatecolumn', tpl:
				'{status}' +
				'<tpl if="attachmentStatus"> / {attachmentStatus}</tpl>' +
				'<tpl if="device"> ({device})</tpl>'
			},
			{ header: "Mount status", width: 100, dataIndex: 'mountStatus', sortable: false, xtype: 'templatecolumn', tpl:
				'<tpl if="mountStatus">{mountStatus}</tpl>' +
				'<tpl if="!mountStatus"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{ header: "Instance ID", width: 90, dataIndex: 'instanceId', sortable: true, xtype: 'templatecolumn', tpl:
				'<tpl if="instanceId">{instanceId}</tpl>'
			},
			{ header: "Auto-snaps", width: 110, dataIndex: 'autoSnaps', sortable: false, align:'center', xtype: 'templatecolumn', tpl:
				'<tpl if="autoSnaps"><img src="/ui/images/icons/true.png" /></tpl>' +
				'<tpl if="!autoSnaps"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{ header: "Auto-attach", width: 130, dataIndex: 'autoAttach', sortable: false, align:'center', xtype: 'templatecolumn', tpl:
				'<tpl if="autoAttach"><img src="/ui/images/icons/true.png" /></tpl>' +
				'<tpl if="!autoAttach"><img src="/ui/images/icons/false.png" /></tpl>'
			}, {
				xtype: 'optionscolumn',
				getOptionVisibility: function (item, record) {
					if (item.itemId == 'option.attach' || item.itemId == 'option.detach' || item.itemId == 'option.attachSep') {
						if (!record.get('mysqMasterVolume')) {
							if (item.itemId == 'option.attachSep')
								return true;
							if (item.itemId == 'option.detach' && record.get('instanceId'))
								return true;
							if (item.itemId == 'option.attach' && !record.get('instanceId'))
								return true;
						}
						return false;
					}
					return true;
				},

				optionsMenu: [{
					itemId: 'option.delete',
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							type: 'delete',
							msg: 'Are you sure want to delete Volume "{volumeId}"?'
						},
						processBox: {
							type: 'delete',
							msg: 'Deleting Volume. Please wait...'
						},
						url: '/tools/cloudtsack/volumes/xRemove/',
						dataHandler: function (record) {
							return { volumeId: Ext.encode([record.get('volumeId')]), cloudLocation: store.proxy.extraParams.cloudLocation };
						},
						success: function () {
							store.load();
						}
					}
				}]
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
						msg: 'Delete selected Volume(s)?',
						type: 'delete'
					},
					processBox: {
						msg: 'Deleting selected Volume(s). Please wait...',
						type: 'delete'
					},
					url: '/tools/cloudtsack/volumes/xRemove/',
					dataHandler: function (records) {
						var data = [];
						for (var i = 0, len = records.length; i < len; i++) {
							data[data.length] = records[i].get('volumeId');
						}
						return { volumeId: Ext.encode(data), cloudLocation: store.proxy.extraParams.cloudLocation };
					}
				}
			}]
		},

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: ['-', {
				text: 'Filter',
				iconCls: 'scalr-ui-btn-icon-filter',
				menu: [{
					xtype: 'fieldcloudlocation',
					itemId: 'cloudLocation',
					store: {
						fields: [ 'id', 'name' ],
						data: moduleParams.locations,
						proxy: 'object'
					},
					gridStore: store,
					cloudLocation: loadParams['cloudLocation'] || ''
				}]
			}, {
				xtype: 'tbfilterinfo'
			}/*, '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create a new EBS volume',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/tools/cloudstack/volumes/create');
				}
			}*/]
		}]
	});
});
