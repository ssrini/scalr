Scalr.regPage('Scalr.ui.tools.aws.rds.sg.view', function (loadParams, moduleParams) {
	var store = Ext.create('store.store', {
		fields: [
			'DBSecurityGroupDescription','DBSecurityGroupName'
		],
		proxy: {
			type: 'scalr.paging',
			extraParams: loadParams,
			url: '/tools/aws/rds/sg/xList/'
		},
		remoteSort: true
	});
	var panel = Ext.create('Ext.grid.Panel', {
		title: 'Tools &raquo; Amazon Web Services &raquo; Amazon RDS &raquo; Manage security groups',
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
		plugins: {
			ptype: 'gridstore'
		},
		viewConfig: {
			emptyText: 'No parameter groups found'
		},
		columns: [
			{ flex: 2, text: "Name", dataIndex: 'DBSecurityGroupName', sortable: true },
			{ flex: 2, text: "Description", dataIndex: 'DBSecurityGroupDescription', sortable: true },
			{
				xtype: 'optionscolumn',
				optionsMenu: [{
					text: 'Edit',
					iconCls: 'scalr-menu-icon-edit',
					menuHandler: function(item) {
						Scalr.event.fireEvent('redirect', '#/tools/aws/rds/sg/edit?dbSgName=' + item.record.get('DBSecurityGroupName') + '&cloudLocation=' + store.proxy.extraParams.cloudLocation);
					}
				},{
					text: 'Events log',
					iconCls: 'scalr-menu-icon-logs',
					menuHandler: function(item) {
						Scalr.event.fireEvent('redirect', '#/tools/aws/rds/logs?name=' + item.record.get('DBSecurityGroupName') + '&type=db-security-group&cloudLocation=' + store.proxy.extraParams.cloudLocation);
					}
				},{
					xtype: 'menuseparator'
				},{
					text: 'Delete',
					iconCls: 'scalr-menu-icon-delete',
					menuHandler: function(item) {
						Scalr.Request({
							confirmBox: {
								msg: 'Remove selected security group?',
								type: 'delete'
							},
							processBox: {
								msg: 'Removing selected security group... Please wait, it can take a few minutes.',
								type: 'delete'
							},
							scope: this,
							url: '/tools/aws/rds/sg/xDelete',
							params: {cloudLocation: panel.down('#cloudLocation').value, dbSgName: item.record.get('DBSecurityGroupName')},
							success: function (data, response, options){
								store.remove(item.record);
							}
						});
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
				tooltip: 'Create new security group',
				handler: function() {
					Scalr.Request({
						confirmBox: {
							title: 'Create new security group',
							form: [{
								xtype: 'combo',
								name: 'cloudLocation',
								store: {
									fields: [ 'id', 'name' ],
									data: moduleParams.locations,
									proxy: 'object'
								},
								editable: false,
								fieldLabel: 'Location',
								queryMode: 'local',
								displayField: 'name',
								valueField: 'id',
								value: panel.down('#cloudLocation').value
							},{
								xtype: 'textfield',
								name: 'dbSecurityGroupName',
								fieldLabel: 'Name',
								allowBlank: false
							},{
								xtype: 'textfield',
								name: 'dbSecurityGroupDescription',
								fieldLabel: 'Description',
								allowBlank: false
							}]
						},
						processBox: {
							type: 'save'
						},
						scope: this,
						url: '/tools/aws/rds/sg/xCreate/',
						success: function (data, response, options){
							if (options.params.cloudLocation == panel.down('#cloudLocation').value){
								store.add({'DBSecurityGroupName': options.params.dbSecurityGroupName, 'dbSecurityGroupDescription': options.params.dbSecurityGroupDescription});
							}
							Scalr.event.fireEvent('redirect', '#/tools/aws/rds/sg/edit?dbSgName=' + options.params.dbSecurityGroupName + '&cloudLocation=' + options.params.cloudLocation);
						}
					});
				}
			}]
		}]
	});
	return panel;
});
