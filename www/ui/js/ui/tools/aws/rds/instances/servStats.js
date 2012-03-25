Scalr.regPage('Scalr.ui.tools.aws.rds.instances.servStats', function (loadParams, moduleParams) {
	console.log(Scalr);
	var today = new Date();
	var store = Ext.create('store.store', {
		fields: [ 'instanceType', 'Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
		proxy: {
			type: 'scalr.paging',
			extraParams: {year: today.getFullYear(), envId: Scalr.InitParams.user.envId},
			url: '/tools/aws/rds/instances/xListServersStats/'
		},
		remoteSort: true
	});
	store.load();
	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; RDS &raquo; DB Instances &raquo; Servers Statistics',
		scalrOptions: {
			'reload': false,
			'maximize': 'all'
		},
		store: store,
		stateId: 'grid-tools-aws-rds-instances-view',
		viewConfig: {
			deferEmptyText: false,
			emptyText: '<center>No statistics found</center>'
		},
		columns: [
			{ text: "Instance Type", flex: 1, dataIndex: 'instanceType', sortable: true},
			{ xtype: 'templatecolumn', text: "January", width: 120, dataIndex: 'Jan', sortable: false,
			 tpl: '<tpl if="Jan"><center>{Jan}</center></tpl><tpl if="!Jan"><center><img src="/ui/images/icons/false.png" /></center></tpl>'},
			{ xtype: 'templatecolumn', text: "February", width: 120, dataIndex: 'Feb', sortable: false,
			 tpl: '<tpl if="Feb"><center>{Feb}</center></tpl><tpl if="!Feb"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "March", width: 120, dataIndex: 'Mar', sortable: false,
			 tpl: '<tpl if="Mar"><center>{Mar}</center></tpl><tpl if="!Mar"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "April", width: 120, dataIndex: 'Apr', sortable: false,
			 tpl: '<tpl if="Apr"><center>{Apr}</center></tpl><tpl if="!Apr"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "May", width: 120, dataIndex: 'May', sortable: false,
			 tpl: '<tpl if="May"><center>{May}</center></tpl><tpl if="!May"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "June", width: 120, dataIndex: 'Jun', sortable: false,
			 tpl: '<tpl if="Jun"><center>{Jun}</center></tpl><tpl if="!Jun"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "July", width: 120, dataIndex: 'Jul', sortable: false,
			 tpl: '<tpl if="Jul"><center>{Jul}</center></tpl><tpl if="!Jul"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "August", width: 120, dataIndex: 'Aug', sortable: false,
			 tpl: '<tpl if="Aug"><center>{Aug}</center></tpl><tpl if="!Aug"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "September", width: 120, dataIndex: 'Sep', sortable: false,
			 tpl: '<tpl if="Sep"><center>{Sep}</center></tpl><tpl if="!Sep"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "October", width: 120, dataIndex: 'Oct', sortable: false,
			 tpl: '<tpl if="Oct"><center>{Oct}</center></tpl><tpl if="!Oct"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "November", width: 120, dataIndex: 'Nov', sortable: false,
			 tpl: '<tpl if="Nov"><center>{Nov}</center></tpl><tpl if="!Nov"><center><img src="/ui/images/icons/false.png" /></center></tpl>' },
			{ xtype: 'templatecolumn', text: "December", width: 120, dataIndex: 'Dec', sortable: false,
			 tpl: '<tpl if="Dec"><center>{Dec}</center></tpl><tpl if="!Dec"><center><img src="/ui/images/icons/false.png" /></center></tpl>' }
		],
		dockedItems: [{
			xtype: 'toolbar',
			dock: 'top',
			items: [{
				xtype: 'combo',
				fieldLabel: 'Year',
				labelWidth: 30,
				store: moduleParams.years,
				valueField: 'name',
				displayField: 'name',
				editable: false,
				value: today.getFullYear().toString(),
				queryMode: 'local',
				itemId: 'years',
				iconCls: 'no-icon',
				listeners: {
					change: function(field, value) {
						store.proxy.extraParams.year = value;
						store.load();
					}
				}
			}, '-', {
				xtype: 'combo',
				hidden: Scalr.InitParams.user.type == 'AccountOwner' ? false : true,
				fieldLabel: 'Environment',
				labelWidth: 70,
				store: {
					fields: ['id', 'name'],
					data: moduleParams.env,
					proxy: 'object'
				},
				valueField: 'id',
				displayField: 'name',
				editable: false,
				value: Scalr.InitParams.user.envId,
				queryMode: 'local',
				itemId: 'envId',
				iconCls: 'no-icon',
				listeners: {
					change: function(field, value) {
						store.proxy.extraParams.envId = value;
						store.load();
					}
				}
			}]
		}]
	});
});