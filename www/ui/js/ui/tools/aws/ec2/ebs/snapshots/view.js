Scalr.regPage('Scalr.ui.tools.aws.ec2.ebs.snapshots.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'snapshotId', 'volumeId', 'volumeSize', 'status', 'startTime', 'comment', 'progress', 'owner','volumeSize' ],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/aws/ec2/ebs/snapshots/xListSnapshots/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; EC2 &raquo; EBS &raquo; Snapshots',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { volumeId: '', snapshotId: '' };
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);
			this.down('#cloudLocation').setValue(this.store.proxy.extraParams.cloudLocation);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-tools-aws-ec2-ebs-snapshots-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: "No snapshots found"
		},

		columns: [
			{ header: "Snapshot ID", width: 150, dataIndex: 'snapshotId', sortable: true },
			{ header: "Owner", width: 150, dataIndex: 'owner', sortable: true },
			{ header: "Created on", width: 100, dataIndex: 'volumeId', sortable: true },
			{ header: "Size (GB)", width: 100, dataIndex: 'volumeSize', sortable: true },
			{ header: "Status", width: 120, dataIndex: 'status', sortable: true },
			{ header: "Local start time", width: 150, dataIndex: 'startTime', sortable: true },
			{ header: "Completed", width: 100, dataIndex: 'progress', sortable: false, align:'center', xtype: 'templatecolumn', tpl: '{progress}%' },
			{ header: "Comment", flex: 1, dataIndex: 'comment', sortable: true, xtype: 'templatecolumn', tpl: '<tpl if="comment">{comment}</tpl>' },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					itemId: "option.create", text:'Create new volume based on this snapshot', menuHandler: function(menuItem) {
						Scalr.event.fireEvent('redirect','#/tools/aws/ec2/ebs/volumes/create?' +
							Ext.Object.toQueryString({
								'snapshotId': menuItem.record.get('snapshotId'),
								'size': menuItem.record.get('volumeSize'),
								'cloudLocation': store.proxy.extraParams.cloudLocation
							})
						);
					}
				}, {
					xtype: 'menuseparator',
					itemId: 'option.Sep'
				}, {
					itemId: 'option.delete',
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							type: 'delete',
							msg: 'Are you sure want to delete EBS snapshot "{snapshotId}"?'
						},
						processBox: {
							type: 'delete',
							msg: 'Deleting EBS snapshot. Please wait...'
						},
						url: '/tools/aws/ec2/ebs/snapshots/xRemove/',
						dataHandler: function (record) {
							return { snapshotId: Ext.encode([record.get('snapshotId')]), cloudLocation: store.proxy.extraParams.cloudLocation };
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
						msg: 'Delete selected EBS snapshot(s): %s ?',
						type: 'delete'
					},
					processBox: {
						msg: 'Deleting selected EBS snapshot(s). Please wait...',
						type: 'delete'
					},
					url: '/tools/aws/ec2/ebs/snapshots/xRemove/',
					dataHandler: function (records) {
						var data = [];
						this.confirmBox.objects = [];
						for (var i = 0, len = records.length; i < len; i++) {
							data.push(records[i].get('snapshotId'));
							this.confirmBox.objects.push(records[i].get('snapshotId'));
						}

						return { snapshotId: Ext.encode(data), cloudLocation: store.proxy.extraParams.cloudLocation };
					},
					success: function (data) {
						store.load();
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
					iconCls: 'no-icon'
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
				}, {
					text: 'Show public (Shared) snapshots',
					checked: false,
					checkHandler: function (field, checked) {
						store.proxy.extraParams.showPublicSnapshots = checked ? 1 : 0;
						store.loadPage(1);
					}
				}]
			}, {
				xtype: 'tbfilterinfo'
			}]
		}]
	});
});
