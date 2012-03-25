Scalr.regPage('Scalr.ui.tools.aws.ec2.elb.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'name','dtcreated','dnsName','farmId','farmRoleId','farmName','roleName'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/aws/ec2/elb/xListElasticLoadBalancers/'
		},
		remoteSort: true
	});
	return Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; Elastic Load Balancer',
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
		stateId: 'grid-tools-aws-ec2-elb-view',
		plugins: {
			ptype: 'gridstore'
		},
		viewConfig: {
			emptyText: 'No Elastic Load Balancer found'
		},
		columns: [
			{ flex: 1, header: "Name", dataIndex: 'name', sortable: true },
			{ flex: 1, header: "Used on", dataIndex: 'farmName', sortable: true, xtype: 'templatecolumn', tpl: new Ext.XTemplate(
				'<tpl if="farmId">' +
					'<a href="#/farms/{farmId}/view" title="Farm {farmName}">{farmName}</a>' +
					'<tpl if="roleName">' +
						'&nbsp;&rarr;&nbsp;<a href="#/farms/{farmId}/roles/{farmRoleId}/view" title="Role {roleName}">' +
						'{roleName}</a> #<a href="#/servers/{serverId}/view">{serverIndex}</a>' +
					'</tpl>' +
				'</tpl>' +
				'<tpl if="!farmId"><img src="/ui/images/icons/false.png" /></tpl>'
			)},
			{ flex: 2, header: "DNS name", dataIndex: 'dnsName', sortable: true },
			{ header: "Created at", width: 150, dataIndex: 'dtcreated', sortable: true },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Details',
					iconCls: 'scalr-menu-icon-info',
					menuHandler:function(item) {			
						Scalr.event.fireEvent('redirect', '#/tools/aws/ec2/elb/' + item.record.get('name') + '/details?cloudLocation=' + store.proxy.extraParams.cloudLocation);
					} 
				},{
					xtype: 'menuseparator'
				},{
					text: 'Remove',
					iconCls: 'scalr-menu-icon-delete',
					request: {
						confirmBox: {
							msg: 'Are you sure want to remove selected Elastic Load Balancer?',
							type: 'delete'
						},
						processBox: {
							type: 'delete',
							msg: 'Removing Elastic Load Balancer. Please wait...'
						},
						url: '/tools/aws/ec2/elb/xDelete/',
						dataHandler: function (record) {
							return {
								elbName: record.get('name'),
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
					xtype: 'tbfilterfield',
					iconCls: 'no-icon',
					store: store
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
			}]
		}]
	});
});
