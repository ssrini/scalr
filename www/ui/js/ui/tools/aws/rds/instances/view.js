Scalr.regPage('Scalr.ui.tools.aws.rds.instances.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ "engine", "status", "hostname", "port", "name", "username", "type", "storage", "dtadded", "avail_zone" ],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/aws/rds/instances/xListInstances/'
		},
		remoteSort: true
	});
	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; RDS &raquo; DB Instances',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = {};
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);
			this.down('#cloudLocation').setValue(this.store.proxy.extraParams.cloudLocation);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-tools-aws-rds-instances-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No db instances found'
		},

		columns: [
			{ text: "Name", flex: 1, dataIndex: 'name', sortable: false },
			{ text: "Hostname", width: 150, dataIndex: 'hostname', sortable: false },
			{ text: "Port", width: 130, dataIndex: 'port', sortable: false },
			{ text: "Status", width: 130, dataIndex: 'status', sortable: false },
			{ text: "Username", width: 130, dataIndex: 'username', sortable: false },
			{ text: "Type", width: 130, dataIndex: 'type', sortable: true },
			{ text: "Storage", width: 120, dataIndex: 'storage', sortable: false },
			{ text: "Placement", width: 130, dataIndex: 'avail_zone', sortable: false },
			{ text: "Created at", width: 160, dataIndex: 'dtadded', sortable: true },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Details',
					iconCls: 'scalr-menu-icon-info',
					menuHandler: function (item) {
						document.location.href = '#/tools/aws/rds/instances/' + item.record.get('name') + '/details?cloudLocation=' + store.proxy.extraParams.cloudLocation;
					}
				}, {
					iconCls: 'scalr-menu-icon-edit',
					text: 'Modify',
					menuHandler: function (item) {
						document.location.href = '#/tools/aws/rds/instances/' + item.record.get('name') + '/edit?cloudLocation=' + store.proxy.extraParams.cloudLocation;
					}
				}, {
					xtype: 'menuseparator'
				}, {
					text: 'Create snapshot',
					request: {
						processBox: {
							type: 'action'
						},
						url: '/tools/aws/rds/snapshots/xCreateSnapshot/',
						dataHandler: function (record) {
							return {
								dbinstance: record.get('name'),
								cloudLocation: store.proxy.extraParams.cloudLocation
							}
						},
						success: function () {
							document.location.href = '#/tools/aws/rds/snapshots?dbinstance=' + this.params.dbinstance + '&cloudLocation=' + store.proxy.extraParams.cloudLocation;
						}
					}
				}, {
					text: 'Auto snapshot settings',
					menuHandler: function (item) {
						document.location.href = '#/tools/aws/autoSnapshotSettings?type=rds&objectId=' + item.record.get('name') + '&cloudLocation=' + store.proxy.extraParams.cloudLocation;
					}
				}, {
					text: 'Manage snapshots',
					menuHandler: function (item) {
						document.location.href = '#/tools/aws/rds/snapshots?dbinstance=' + item.record.get('name') + '&cloudLocation=' + store.proxy.extraParams.cloudLocation;
					}
				}, {
					xtype: 'menuseparator'
				}, {
					text: 'CloudWatch statistics',
					iconCls: 'scalr-menu-icon-stats',
					menuHandler: function (item) {
						document.location.href = '#/tools/aws/ec2/cloudwatch/view?objectId=' + item.record.get('name') + '&object=DBInstanceIdentifier&namespace=AWS/RDS&region=' + store.proxy.extraParams.cloudLocation;
					}
				}, {
					xtype: 'menuseparator'
				}, {
					text: 'Events log',
					iconCls: 'scalr-menu-icon-logs',
					menuHandler: function (item) {
						document.location.href = '#/tools/aws/rds/logs?name=' + item.record.get('name') + '&type=db-instance&cloudLocation=' + store.proxy.extraParams.cloudLocation;
					}
				}, {
					xtype: 'menuseparator'
				}, {
					text: 'Reboot',
					iconCls: 'scalr-menu-icon-reboot',
					request: {
						confirmBox: {
							msg: 'Reboot server "{name}"?',
							type: 'reboot'
						},
						processBox: {
							type: 'reboot',
							msg: 'Sending reboot command to the server. Please wait...'
						},
						url: '/tools/aws/rds/instances/xReboot/',
						dataHandler: function (record) {
							return {
								instanceId: record.get('name'),
								cloudLocation: store.proxy.extraParams.cloudLocation
							};
						},
						success: function(data) {
							store.load();
						}
					}
				}, {
					text: 'Terminate',
					iconCls: 'scalr-menu-icon-terminate',
					request: {
						confirmBox: {
							msg: 'Terminate server "{name}"?</br><i> This action will completely remove this server from AWS</i>',
							type: 'delete'
						},
						processBox: {
							type: 'terminate',
							msg: 'Sending terminate command to the server. Please wait...'
						},
						url: '/tools/aws/rds/instances/xTerminate/',
						dataHandler: function (record) {
							return {
								instanceId: record.get('name'),
								cloudLocation: store.proxy.extraParams.cloudLocation
							};
						},
						success: function(data) {
							store.load();
						}
					}
				}]
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
				tooltip: 'Launch new DB instance',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/tools/aws/rds/instances/create');
				}
			}]
		}]
	});
});
