Scalr.regPage('Scalr.ui.tools.aws.iam.serverCertificates.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [ 'name','path','arn','id','upload_date' ],
		proxy: {
			type: 'scalr.paging',
			url: '/tools/aws/iam/serverCertificates/xListCertificates/'
		},
		remoteSort: true
	});

	return Ext.create('Ext.grid.Panel', {
		title: 'Server Certificates &raquo; View',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		scalrReconfigure: function (loadParams) {
			this.store.load();
		},
		store: store,
		stateId: 'grid-tools-aws-iam-serverCertificates-view',
		plugins: {
			ptype: 'gridstore'
		},

		viewConfig: {
			emptyText: 'No server certificates found'
		},

		columns: [
			{ header: "ID", width: 250, dataIndex: 'id', sortable: false },
			{ header: "Name", flex: 1, dataIndex: 'name', sortable: false },
			{ header: "Path", flex: 1, dataIndex: 'path', sortable: false },
			{ header: "Arn", flex: 1, dataIndex: 'arn', sortable: false },
			{ header: "Upload date", width: 200, dataIndex: 'upload_date', sortable: false }
		],

		dockedItems: [{
			xtype: 'scalrpagingtoolbar',
			store: store,
			dock: 'top',
			items: [ '-', {
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Create new certificate',
				handler: function() {
					Scalr.event.fireEvent('redirect', '#/tools/aws/iam/servercertificates/create');
				}
			}]
		}]
	});
});
