Scalr.regPage('Scalr.ui.dm.tasks.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			"id", "farm_roleid", "farm_id", "farm_name", "role_name", "server_id", "application_id", "application_name",
			"server_id", "status", "type", "dtdeployed", "dtadded", "remote_path", "server_index"
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/dm/tasks/xListTasks/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Deployments &raquo; Tasks',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			var nL = { deploymentTaskId: ''};
			Ext.applyIf(loadParams, nL);
			Ext.apply(this.store.proxy.extraParams, loadParams);

			if (Scalr.utils.IsEqualValues(nL, this.store.proxy.extraParams))
				this.store.load();
			else
				this.store.loadPage(1);
		},
		store: store,
		stateId: 'grid-dm-tasks-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No tasks found'
		},

		columns: [
			{ header: "ID", width: 130, dataIndex: 'id', sortable: true },
			{ header: "Deployment", flex: 1, dataIndex: 'deployment_name', sortable: false, xtype: 'templatecolumn', tpl:
				'<a href="#/dm/applications/{application_id}/view">{application_name}</a>'
			},
			{ header: "Farm & Role", flex: 1, dataIndex: 'farm_id', sortable: true, xtype: 'templatecolumn', tpl:
				'<tpl if="role_name">'+
					'<a href="#/farms/{farm_id}/view" title="Farm {farm_name}">{farm_name}</a>' +
					'&nbsp;&rarr;&nbsp;<a href="#/farms/{farm_id}/roles/{farm_roleid}/view" title="Role {role_name}">{role_name}</a> ' +
				'</tpl>' +
				'<tpl if="server_index">#<a href="#/servers/{server_id}/view">{server_index}</a></tpl>' +
				'<tpl if="! role_name"><img src="/ui/images/icons/false.png" /></tpl>'
			},
			{ header: "Status", width: 140, dataIndex: 'status', sortable: true, xtype: 'templatecolumn', tpl:
				'<tpl if="status == &quot;failed&quot;">{status} (<a href="#/dm/tasks/{id}/failureDetails">Why?</a>)</tpl>' +
				'<tpl if="status != &quot;failed&quot;">{status}</tpl>'},
			{ header: "Deploying to", width: 200, dataIndex: 'remote_path', sortable: true },
			{ header: "Created", width: 170, dataIndex: 'dtadded', sortable: true },
			{ header: "Deployed", width: 170, dataIndex: 'dtdeployed', sortable: true },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Logs',
					iconCls: 'scalr-menu-icon-logs',
					href: '#/dm/tasks/{id}/logs'
				}, {
					text: 'Re-deploy',
					iconCls: 'scalr-menu-icon-action',
					request: {
						confirmBox: {
							msg: 'Are you sure want to re-deploy task "{id}"?',
							type: 'action'
						},
						processBox: {
							type: 'action',
							msg: 'Re-deploying task. Please wait...'
						},
						url: '/dm/tasks/deploy',
						dataHandler: function (record) {
							return {
								deploymentTaskId: record.get('id')
							};
						},
						success: function(data) {
							store.load();
						}
					}
				}, {
					xtype: 'menuseparator'
				}, {
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							msg: 'Are you sure want to remove demployment task "{id}"?',
							type: 'delete'
						},
						processBox: {
							type: 'delete',
							msg: 'Removing demployment task. Please wait...'
						},
						url: '/dm/tasks/xRemoveTasks',
						dataHandler: function (record) {
							return {
								deploymentTaskId: record.get('id')
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
			dock: 'top'
		}]
	});
});
