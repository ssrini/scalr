Scalr.regPage('Scalr.ui.tools.aws.rds.sg.edit', function (loadParams, moduleParams) {
	rulesStore = Ext.create('Ext.data.JsonStore', {
		fields: ['Type', 'CIDRIP', 'EC2SecurityGroupOwnerId', 'EC2SecurityGroupName', 'Status']
	});
	Ext.each(moduleParams.rules.groupRules, function(item){
		rulesStore.add({Type: 'EC2 Security Group', EC2SecurityGroupOwnerId: item.EC2SecurityGroupOwnerId, EC2SecurityGroupName: item.EC2SecurityGroupName, Status: item.Status});
	});
	Ext.each(moduleParams.rules.ipRules, function(item){
		rulesStore.add({Type: 'CIDR IP', CIDRIP: item.CIDRIP, Status: item.Status});
	});
	return Ext.create('Ext.grid.Panel', {
		bodyCls: 'scalr-ui-frame',
		title: 'Tools &raquo; Amazon Web Services &raquo; Amazon RDS &raquo; Security groups &raquo; ' + loadParams['dbSgName'] + ' &raquo; Edit',
		store: rulesStore,
		width: 600,
		plugins: {
			ptype: 'gridstore'
		},
		viewConfig: {
			deferEmptyText: false,
			emptyText: 'No Rules found'
		},
		columns: [{
			text: "Type", width: 200, dataIndex: 'Type', sortable: true
		},{
			text: "Parameters", width: 220, dataIndex: 'Parameters', sortable: true, xtype: 'templatecolumn',
			tpl: '<tpl if="CIDRIP">{CIDRIP}</tpl><tpl if="EC2SecurityGroupOwnerId">{EC2SecurityGroupOwnerId}/{EC2SecurityGroupName}</tpl>'
		},{
			text: "Status", width: 158, dataIndex: 'Status', sortable: true
		},{
			xtype: 'actioncolumn',
			width: 20,
			items: [{
				icon: '/ui/images/icons/delete_icon_16x16.png',
                tooltip: 'Delete',
                handler: function(grid, rowIndex, colIndex) {
                	rulesStore.remove(rulesStore.getAt(rowIndex));
                }
			}]
		}],
		dockedItems: [{
			xtype: 'container',
			dock: 'bottom',
			cls: 'scalr-ui-docked-bottombar',
			layout: {
				type: 'hbox',
				pack: 'center'
			},
			items: [{
				xtype: 'button',
				text: 'Save',
				width: 80,
				handler: function() {
					var data = [];
					Ext.each (rulesStore.getRange(), function (item) {
						data.push(item.data);
					});
					Scalr.Request({
						processBox: {
							type: 'save'
						},
						url: '/tools/aws/rds/sg/xSave/',
						params: Ext.applyIf(loadParams, {'rules': Ext.encode(data)}),
						success: function (data) {
							Scalr.event.fireEvent('close');
						}
					});
				}
			}, {
				xtype: 'button',
				width: 80,
				margin: {
					left: 5
				},
				text: 'Cancel',
				handler: function() {
					Scalr.event.fireEvent('close');
				}
			}]
		},{
			xtype: 'toolbar',
			dock: 'top',
			layout: {
				type: 'hbox',
				pack: 'start'
			},
			items:[{
				icon: '/ui/images/icons/add_icon_16x16.png',
				cls: 'x-btn-icon',
				tooltip: 'Add new Rule',
				handler: function() {
					Scalr.Confirm({
						title: 'Add new Rule',
						form: [{
							xtype: 'hiddenfield',
        					name: 'Status',
        					value: 'new'
						},{
							xtype: 'combo',
							name: 'Type',
							editable: false,
							fieldLabel: 'Type',
							queryMode: 'local',
							store: [ ['CIDR IP','CIDR IP'], ['EC2 Security Group','EC2 Security Group'] ],
							listeners: {
								change: function (field, value) {
									if (value == 'CIDR IP') {
										this.next('[name="ipRanges"]').show().enable();
										this.next('[name="UserId"]').hide().disable();
										this.next('[name="Group"]').hide().disable();
									}
									else {
										this.next('[name="ipRanges"]').hide().disable();
										this.next('[name="UserId"]').show().enable();
										this.next('[name="Group"]').show().enable();
									}
								}
							}
						},{
							xtype: 'textfield',
							name: 'ipRanges',
							fieldLabel: 'Ip Ranges',
							value: '0.0.0.0/0',
							hidden: true,
							allowBlank: false
						},{
							xtype: 'textfield',
							name: 'UserId',
							fieldLabel: 'User ID',
							hidden: true,
							allowBlank: false,
							validator: function (value) {
								if (value < 100000000000 || value > 999999999999) {
									return 'User ID must be 12 digits length';
								}
								return true;
							}
						},{
							xtype: 'textfield',
							name: 'Group',
							fieldLabel: 'Group',
							hidden: true,
							allowBlank: false
						}],
							formValidate: true,
							ok: 'Add',
							scope: this,
							success: function (formValues) {
								if(formValues.ipRanges)
									rulesStore.insert(rulesStore.data.length,{'Type': formValues.Type, 'CIDRIP': formValues.ipRanges, 'Status': formValues.Status});
								else rulesStore.insert(rulesStore.data.length,{'Type': formValues.Type, 'EC2SecurityGroupOwnerId': formValues.UserId , 'EC2SecurityGroupName': formValues.Group, 'Status': formValues.Status});
							}
						});
					}
				}]
			}]
	});
});
