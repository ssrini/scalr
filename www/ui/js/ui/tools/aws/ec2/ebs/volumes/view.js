Scalr.regPage('Scalr.ui.tools.aws.ec2.ebs.volumes.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'farmId', 'farmRoleId', 'farmName', 'roleName', 'mysql_master_volume', 'mountStatus', 'serverIndex', 'serverId',
			'volumeId', 'size', 'snapshotId', 'availZone', 'status', 'attachmentStatus', 'device', 'instanceId', 'autoSnaps', 'autoAttach'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/aws/ec2/ebs/volumes/xListVolumes/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; EC2 &raquo; EBS &raquo; Volumes',
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
		stateId: 'grid-tools-aws-ec2-ebs-volumes-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No volumes found'
		},

		columns: [
			{ header: "Used by", flex: 1, dataIndex: 'id', sortable: true, xtype: 'templatecolumn',
				doSort: function (state) {
					var ds = this.up('tablepanel').store;
						ds.sort([{
							property: 'farmId',
							direction: state
						}, {
							property: 'farmRoleId',
							direction: state
						}, {
							property: 'serverIndex',
							direction: state
						}]);
					}, tpl:
				'<tpl if="farmId">' +
					'<a href="#/farms/{farmId}/view" title="Farm {farmName}">{farmName}</a>' +
					'<tpl if="roleName">' +
						'&nbsp;&rarr;&nbsp;<a href="#/farms/{farmId}/roles/{farmRoleId}/view" title="Role {roleName}">' +
						'{roleName}</a> #<a href="#/servers/{serverId}/view">{serverIndex}</a>' +
					'</tpl>' +
				'</tpl>' +
				'<tpl if="!farmId"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{ header: "Volume ID", width: 120, dataIndex: 'volumeId', sortable: true },
			{ header: "Size (GB)", width: 80, dataIndex: 'size', sortable: true },
			{ header: "Snapshot ID", width: 35, dataIndex: 'snapshotId', sortable: true, hidden: true },
			{ header: "Placement", width: 100, dataIndex: 'availZone', sortable: true },
			{ header: "Status", width: 250, dataIndex: 'status', sortable: true, xtype: 'templatecolumn', tpl:
				'{status}' +
				'<tpl if="attachmentStatus"> / {attachmentStatus}</tpl>' +
				'<tpl if="device"> ({device})</tpl>'
			},
			{ header: "Mount status", width: 100, dataIndex: 'mountStatus', sortable: false, xtype: 'templatecolumn', tpl:
				'<tpl if="mountStatus">{mountStatus}</tpl>' +
				'<tpl if="!mountStatus"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{ header: "Instance ID", width: 110, dataIndex: 'instanceId', sortable: true, xtype: 'templatecolumn', tpl:
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
					text: 'CloudWatch statistics',
					iconCls: 'scalr-menu-icon-stats',
					menuHandler: function (menuItem) {
						document.location.href = '#/tools/aws/ec2/cloudwatch/view?objectId=' + menuItem.record.get('volumeId') + '&object=VolumeId&namespace=AWS/EBS&region=' + store.proxy.extraParams.cloudLocation;
					}
				},{
					itemId: 'option.attach',
					text: 'Attach',
					menuHandler: function(menuItem) {
						document.location.href = "#/tools/aws/ec2/ebs/volumes/" + menuItem.record.get('volumeId') + "/attach?cloudLocation=" + store.proxy.extraParams.cloudLocation;
					}
				}, {
					itemId: 'option.detach',
					text: 'Detach',
					request: {
						confirmBox: {
							type: 'action',
							//TODO: Add form: checkbox: forceDetach
							msg: 'Are you sure want to detach "{volumeId}" volume?'
						},
						processBox: {
							type: 'action',
							msg: 'Detaching EBS volume. Please wait...'
						},
						url: '/tools/aws/ec2/ebs/volumes/xDetach/',
						dataHandler: function (record) {
							return { volumeId: record.get('volumeId'), cloudLocation: store.proxy.extraParams.cloudLocation };
						},
						success: function (data) {
							store.load();
						}
					}
				}, {
					xtype: 'menuseparator',
					itemId: 'option.attachSep'
				}, {
					itemId: 'option.autosnap',
					text: 'Auto-snapshot settings',
					menuHandler: function(menuItem) {
						document.location.href = '#/tools/aws/autoSnapshotSettings?type=ebs&objectId=' + menuItem.record.get('volumeId') + '&cloudLocation=' + store.proxy.extraParams.cloudLocation;
					}
				}, {
					xtype: 'menuseparator',
					itemId: 'option.snapSep'
				}, {
					itemId: 'option.createSnap',
					text: 'Create snapshot',
					request: {
						confirmBox: {
							type: 'action',
							msg: 'Are you sure want to create snapshot for EBS volume "{volumeId}"?'
						},
						processBox: {
							type: 'action',
							msg: 'Creating EBS snapshot. Please wait...'
						},
						url: '/tools/aws/ec2/ebs/snapshots/xCreate/',
						dataHandler: function (record) {
							return { volumeId: record.get('volumeId'), cloudLocation: store.proxy.extraParams.cloudLocation };
						},
						success: function (data) {
							document.location.href = '#/tools/aws/ec2/ebs/snapshots/' + data.data.snapshotId + '/view?cloudLocation=' + store.proxy.extraParams.cloudLocation;
						}
					}
				}, {
					itemId: 'option.viewSnaps',
					text: 'View snapshots',
					menuHandler: function(menuItem) {
						document.location.href = '#/tools/aws/ec2/ebs/snapshots/view?volumeId=' + menuItem.record.get('volumeId') + '&cloudLocation=' + store.proxy.extraParams.cloudLocation;
					}
				}, {
					xtype: 'menuseparator',
					itemId: 'option.vsnapSep'
				}, {
					itemId: 'option.delete',
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							type: 'delete',
							msg: 'Are you sure want to delete EBS volume "{volumeId}"?'
						},
						processBox: {
							type: 'delete',
							msg: 'Deleting EBS volume. Please wait...'
						},
						url: '/tools/aws/ec2/ebs/volumes/xRemove/',
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
						msg: 'Delete selected EBS volume(s): %s ?',
						type: 'delete'
					},
					processBox: {
						msg: 'Deleting selected EBS volume(s). Please wait...',
						type: 'delete'
					},
					url: '/tools/aws/ec2/ebs/volumes/xRemove/',
					dataHandler: function (records) {
						var data = [];
						this.confirmBox.objects = [];
						for (var i = 0, len = records.length; i < len; i++) {
							data.push(records[i].get('volumeId'));
							this.confirmBox.objects.push(records[i].get('volumeId'));
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
					xtype: 'tbfilterfield',
					store: store,
					iconCls: 'no-icon',
					emptyText: 'Filter'
				}, {
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
			}, '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create a new EBS volume',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/tools/aws/ec2/ebs/volumes/create');
				}
			}]
		}]
	});
});
