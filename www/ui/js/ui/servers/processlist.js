Scalr.regPage('Scalr.ui.servers.processlist', function (loadParams, moduleParams) {
	return new Ext.Panel({
		title: 'Server "' + moduleParams.name + '" process list',
		scalrOptions: {
			'modal': true,
			'maximize': 'height'
		},
		width: 800,
		tools: [{
			id: 'close',
			handler: function () {
				Scalr.Viewers.EventMessager.fireEvent('close');
			}
		}],
		layout: 'fit',
		items: new Scalr.Viewers.list.ListView({
			store: new Scalr.data.Store({
				reader: new Scalr.data.JsonReader({
					id: 'hrSWRunName',
					fields: [
						'hrSWRunName', 'hrSWRunPath', 'hrSWRunParameters', 'hrSWRunType',
						'hrSWRunStatus', 'hrSWRunPerfCPU', 'hrSWRunPerfMem'
					]
				}),
				data: moduleParams.data
			}),
			emptyText: "No processes found",
			columns: [
				{ header: "Process", width: 50, dataIndex: 'hrSWRunName', sortable: false, hidden: 'no', tpl: "{hrSWRunName} {hrSWRunParameters}" },
				{ header: "RAM Usage", width: 50, dataIndex: 'hrSWRunPerfMem', sortable: false, hidden: 'no' },
				{ header: "Type", width: 60, dataIndex: 'hrSWRunType', sortable: false, hidden: 'no' },
				{ header: "Status", width: 40, dataIndex: 'hrSWRunStatus', sortable: false, hidden: 'no' }
			]
		})
	});
});
